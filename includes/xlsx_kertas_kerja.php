<?php
/**
 * SOKAB — Pengisi template Kertas Kerja (.xlsx) dengan PHP murni
 * File: includes/xlsx_kertas_kerja.php
 *
 * Menggantikan export_kertas_kerja.py untuk hosting yang memblokir shell_exec.
 * Hanya butuh ZipArchive + DOMDocument bawaan PHP.
 *
 * PRINSIP: sel yang berisi formula TIDAK PERNAH ditulis. Setiap sel diperiksa
 * dulu; kalau punya elemen <f>, sel dilewati dan dicatat. Seluruh rumus di
 * kertas kerja tetap utuh dan dihitung ulang saat file dibuka.
 */

const XL_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
const XL_R  = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

/** Nomor kolom (1-based) → huruf: 1→A, 27→AA */
function xlHuruf(int $n): string {
    $s = '';
    while ($n > 0) {
        $sisa = ($n - 1) % 26;
        $s = chr(65 + $sisa) . $s;
        $n = intdiv($n - 1 - $sisa, 26);
    }
    return $s;
}

/** Huruf kolom → nomor */
function xlNomor(string $h): int {
    $n = 0;
    for ($i = 0; $i < strlen($h); $i++) $n = $n * 26 + (ord($h[$i]) - 64);
    return $n;
}

/** Pisahkan referensi sel 'AB12' → ['AB', 12] */
function xlPisah(string $ref): array {
    preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
    return [$m[1], (int)$m[2]];
}

class XlsxKertasKerja
{
    private ZipArchive $zip;
    private DOMDocument $doc;
    private DOMElement $sheetData;
    private string $sheetPath;
    private array $barisCache = [];

    public int   $ditulis  = 0;
    public array $dilewati = [];

    public function __construct(string $output, string $namaSheet)
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP "zip" tidak aktif.');
        }
        $this->zip = new ZipArchive();
        if ($this->zip->open($output) !== true) {
            throw new RuntimeException('Gagal membuka .xlsx sebagai arsip ZIP.');
        }
        $this->sheetPath = $this->cariSheet($namaSheet);

        $this->doc = new DOMDocument();
        $this->doc->preserveWhiteSpace = false;
        if (!$this->doc->loadXML($this->zip->getFromName($this->sheetPath), LIBXML_PARSEHUGE)) {
            throw new RuntimeException('Isi ' . $this->sheetPath . ' tidak bisa dibaca.');
        }
        $sd = $this->doc->getElementsByTagNameNS(XL_NS, 'sheetData')->item(0);
        if (!$sd) throw new RuntimeException('sheetData tidak ditemukan di ' . $this->sheetPath);
        $this->sheetData = $sd;

        foreach ($this->sheetData->getElementsByTagNameNS(XL_NS, 'row') as $row) {
            $this->barisCache[(int)$row->getAttribute('r')] = $row;
        }
    }

    /** Cari file worksheet berdasarkan nama tab, lewat workbook.xml + rels. */
    private function cariSheet(string $nama): string
    {
        $wb = new DOMDocument();
        $wb->loadXML($this->zip->getFromName('xl/workbook.xml'));

        $rid = null;
        $tersedia = [];
        foreach ($wb->getElementsByTagNameNS(XL_NS, 'sheet') as $s) {
            $tersedia[] = $s->getAttribute('name');
            if (strcasecmp($s->getAttribute('name'), $nama) === 0) {
                $rid = $s->getAttributeNS(XL_R, 'id');
            }
        }
        if (!$rid) {
            throw new RuntimeException("Sheet '$nama' tidak ada. Tersedia: " . implode(', ', $tersedia));
        }

        $rels = new DOMDocument();
        $rels->loadXML($this->zip->getFromName('xl/_rels/workbook.xml.rels'));
        foreach ($rels->getElementsByTagName('Relationship') as $r) {
            if ($r->getAttribute('Id') === $rid) {
                $t = $r->getAttribute('Target');
                return strpos($t, '/') === 0 ? ltrim($t, '/') : 'xl/' . ltrim($t, './');
            }
        }
        throw new RuntimeException('Relasi worksheet tidak ditemukan untuk ' . $nama);
    }

    private function ambilBaris(int $nomor): DOMElement
    {
        if (isset($this->barisCache[$nomor])) return $this->barisCache[$nomor];

        $row = $this->doc->createElementNS(XL_NS, 'row');
        $row->setAttribute('r', (string)$nomor);

        $sesudah = null;
        foreach ($this->sheetData->getElementsByTagNameNS(XL_NS, 'row') as $r) {
            if ((int)$r->getAttribute('r') > $nomor) { $sesudah = $r; break; }
        }
        $sesudah ? $this->sheetData->insertBefore($row, $sesudah) : $this->sheetData->appendChild($row);

        return $this->barisCache[$nomor] = $row;
    }

    private function ambilSel(DOMElement $row, string $ref, int $kolom): DOMElement
    {
        foreach ($row->getElementsByTagNameNS(XL_NS, 'c') as $c) {
            if ($c->getAttribute('r') === $ref) return $c;
        }
        $sel = $this->doc->createElementNS(XL_NS, 'c');
        $sel->setAttribute('r', $ref);

        $sesudah = null;
        foreach ($row->getElementsByTagNameNS(XL_NS, 'c') as $c) {
            [$h, ] = xlPisah($c->getAttribute('r'));
            if (xlNomor($h) > $kolom) { $sesudah = $c; break; }
        }
        $sesudah ? $row->insertBefore($sel, $sesudah) : $row->appendChild($sel);
        return $sel;
    }

    /** Baca teks sebuah sel (untuk mendeteksi baris IKU dan label X/Y). */
    public function baca(int $baris, int $kolom, array $sharedStrings): string
    {
        if (!isset($this->barisCache[$baris])) return '';
        $ref = xlHuruf($kolom) . $baris;
        foreach ($this->barisCache[$baris]->getElementsByTagNameNS(XL_NS, 'c') as $c) {
            if ($c->getAttribute('r') !== $ref) continue;
            $tipe = $c->getAttribute('t');
            if ($tipe === 'inlineStr') {
                $t = $c->getElementsByTagNameNS(XL_NS, 't')->item(0);
                return $t ? $t->nodeValue : '';
            }
            $v = $c->getElementsByTagNameNS(XL_NS, 'v')->item(0);
            if (!$v) return '';
            if ($tipe === 's') return $sharedStrings[(int)$v->nodeValue] ?? '';
            return $v->nodeValue;
        }
        return '';
    }

    /**
     * Tulis nilai ke sel — TAPI hanya kalau sel itu bukan formula.
     * Inilah pengaman utamanya: bukan sekadar mengandalkan daftar kolom aman.
     */
    public function tulis(int $baris, int $kolom, $nilai, string $label = ''): bool
    {
        if ($nilai === null || $nilai === '') return false;

        $ref = xlHuruf($kolom) . $baris;
        $row = $this->ambilBaris($baris);
        $sel = $this->ambilSel($row, $ref, $kolom);

        if ($sel->getElementsByTagNameNS(XL_NS, 'f')->length > 0) {
            $this->dilewati[] = ['sel' => $ref, 'label' => $label, 'alasan' => 'berisi formula'];
            return false;
        }

        while ($sel->firstChild) $sel->removeChild($sel->firstChild);

        if (is_numeric($nilai)) {
            $sel->removeAttribute('t');
            $v = $this->doc->createElementNS(XL_NS, 'v', (string)(0 + $nilai));
            $sel->appendChild($v);
        } else {
            // inlineStr: tidak perlu menyentuh sharedStrings sama sekali
            $sel->setAttribute('t', 'inlineStr');
            $is = $this->doc->createElementNS(XL_NS, 'is');
            $t  = $this->doc->createElementNS(XL_NS, 't');
            $t->setAttribute('xml:space', 'preserve');
            $t->appendChild($this->doc->createTextNode((string)$nilai));
            $is->appendChild($t);
            $sel->appendChild($is);
        }
        $this->ditulis++;
        return true;
    }

    /** Paksa Excel menghitung ulang seluruh rumus saat file dibuka. */
    private function paksaHitungUlang(): void
    {
        $wb = new DOMDocument();
        $wb->loadXML($this->zip->getFromName('xl/workbook.xml'));
        $root = $wb->documentElement;

        $calc = $wb->getElementsByTagNameNS(XL_NS, 'calcPr')->item(0);
        if (!$calc) {
            $calc = $wb->createElementNS(XL_NS, 'calcPr');
            $root->appendChild($calc);
        }
        $calc->setAttribute('calcId', '0');
        $calc->setAttribute('fullCalcOnLoad', '1');
        $this->zip->addFromString('xl/workbook.xml', $wb->saveXML());

        // Buang cache hasil hitungan lama supaya tidak ada angka basi
        if ($this->zip->locateName('xl/calcChain.xml') !== false) {
            $this->zip->deleteName('xl/calcChain.xml');
        }
    }

    public function simpan(): void
    {
        $this->zip->addFromString($this->sheetPath, $this->doc->saveXML());
        $this->paksaHitungUlang();
        $this->zip->close();
    }
}

/** Baca sharedStrings sekali, untuk keperluan membaca teks sel. */
function xlSharedStrings(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    if ($xml === false) return [];

    $doc = new DOMDocument();
    $doc->loadXML($xml, LIBXML_PARSEHUGE);
    $out = [];
    foreach ($doc->getElementsByTagNameNS(XL_NS, 'si') as $si) {
        $teks = '';
        foreach ($si->getElementsByTagNameNS(XL_NS, 't') as $t) $teks .= $t->nodeValue;
        $out[] = $teks;
    }
    return $out;
}

// ============================================================
// FUNGSI UTAMA
// ============================================================
const KK_SHEET      = 'LK_Kabkot';
const KK_KODE       = 4;    // D
const KK_JENIS      = 8;    // H  "IKU"
const KK_LABEL      = 5;    // E  label X / Y
const KK_REALISASI  = [17, 18, 19, 20];   // Q–T
const KK_KENDALA    = 29;   // AC
const KK_SOLUSI     = 30;
const KK_RTL        = 31;
const KK_PIC        = 32;
const KK_BATAS      = 33;
const KK_LINK       = 34;
const KK_LINK_TL    = 35;

function isiKertasKerjaXlsx(array $data, string $template, string $output): array
{
    if (!file_exists($template)) {
        throw new RuntimeException('Template tidak ditemukan: ' . $template);
    }
    if (!copy($template, $output)) {
        throw new RuntimeException('Gagal menyalin template ke ' . $output);
    }

    $ss = xlSharedStrings($output);
    $xl = new XlsxKertasKerja($output, KK_SHEET);

    // Header
    $xl->tulis(3, 5, $data['satker'] ?? '', 'Satuan Kerja');
    if (($data['nilai_sakip'] ?? '') !== '') {
        $xl->tulis(4, 5, $data['nilai_sakip'], 'Nilai SAKIP');
    }
    // E5 (predikat) sengaja tidak disentuh: isinya formula turunan E4.

    // Petakan kode IKU → nomor baris
    $petaBaris = [];
    for ($r = 1; $r <= 400; $r++) {
        if (strcasecmp(trim($xl->baca($r, KK_JENIS, $ss)), 'IKU') === 0) {
            $kode = trim($xl->baca($r, KK_KODE, $ss));
            if ($kode !== '') $petaBaris[$kode] = $r;
        }
    }

    $terisi = [];
    $takKetemu = [];

    foreach ($data['iku_list'] ?? [] as $iku) {
        $kode = trim((string)($iku['kode'] ?? ''));
        if (!isset($petaBaris[$kode])) { $takKetemu[] = $kode; continue; }
        $baris = $petaBaris[$kode];

        // Baris X/Y dideteksi dari labelnya, bukan diasumsikan posisinya
        $lx = strtoupper(trim($xl->baca($baris + 1, KK_LABEL, $ss)));
        $ly = strtoupper(trim($xl->baca($baris + 2, KK_LABEL, $ss)));
        $punyaXY = (strpos($lx, 'X') === 0 && strpos($ly, 'Y') === 0);

        if (($iku['jenis_satuan'] ?? '%') === '%' && $punyaXY) {
            for ($i = 1; $i <= 4; $i++) {
                $xl->tulis($baris + 1, KK_REALISASI[$i - 1], $iku["x_tw$i"] ?? null, "$kode X TW$i");
                $xl->tulis($baris + 2, KK_REALISASI[$i - 1], $iku["y_tw$i"] ?? null, "$kode Y TW$i");
            }
        } else {
            for ($i = 1; $i <= 4; $i++) {
                $xl->tulis($baris, KK_REALISASI[$i - 1], $iku["realisasi_tw$i"] ?? null, "$kode realisasi TW$i");
            }
        }

        foreach ([[KK_KENDALA, 'kendala'], [KK_SOLUSI, 'solusi'], [KK_RTL, 'rtl'],
                  [KK_PIC, 'pic'], [KK_BATAS, 'batas'], [KK_LINK, 'link_bukti'],
                  [KK_LINK_TL, 'link_tl']] as [$kol, $kunci]) {
            $xl->tulis($baris, $kol, $iku[$kunci] ?? null, "$kode $kunci");
        }

        $terisi[] = $kode;
    }

    $xl->simpan();

    return [
        'sel_ditulis'    => $xl->ditulis,
        'sel_dilewati'   => count($xl->dilewati),
        'dilewati'       => array_slice($xl->dilewati, 0, 20),
        'iku_terisi'     => $terisi,
        'iku_tak_ketemu' => $takKetemu,
    ];
}

// ============================================================
// PEMBACA KERTAS KERJA (untuk mode Upload Excel)
// ============================================================

/**
 * Baca isi kertas kerja Excel yang di-upload user.
 * Mengembalikan struktur yang sama dengan mode database, supaya sisa alurnya
 * tidak perlu tahu datanya berasal dari mana.
 */
function bacaKertasKerjaXlsx(string $path, string $sheet = KK_SHEET, ?string $triwulan = null): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi PHP "zip" tidak aktif.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('File tidak bisa dibuka. Pastikan formatnya .xlsx, bukan .xls lama.');
    }

    // --- daftar sheet ---
    $wbXml = $zip->getFromName('xl/workbook.xml');
    if ($wbXml === false) { $zip->close(); throw new RuntimeException('Bukan workbook Excel yang valid.'); }
    $wb = new DOMDocument(); $wb->loadXML($wbXml);

    $tersedia = [];
    $ridPeta  = [];
    foreach ($wb->getElementsByTagNameNS(XL_NS, 'sheet') as $s) {
        $nm = $s->getAttribute('name');
        $tersedia[] = $nm;
        $ridPeta[$nm] = $s->getAttributeNS(XL_R, 'id');
    }

    // Sheet yang diminta; kalau tak ada, cari yang diawali "LK"; kalau tetap tak ada, ambil pertama
    $pakai = null;
    foreach ($tersedia as $nm) if (strcasecmp($nm, $sheet) === 0) { $pakai = $nm; break; }
    if (!$pakai) foreach ($tersedia as $nm) if (stripos($nm, 'LK') === 0) { $pakai = $nm; break; }
    if (!$pakai) $pakai = $tersedia[0] ?? null;
    if (!$pakai) { $zip->close(); throw new RuntimeException('Workbook tidak punya sheet apa pun.'); }

    $rels = new DOMDocument();
    $rels->loadXML($zip->getFromName('xl/_rels/workbook.xml.rels'));
    $target = null;
    foreach ($rels->getElementsByTagName('Relationship') as $r) {
        if ($r->getAttribute('Id') === $ridPeta[$pakai]) {
            $t = $r->getAttribute('Target');
            $target = strpos($t, '/') === 0 ? ltrim($t, '/') : 'xl/' . ltrim($t, './');
        }
    }
    if (!$target) { $zip->close(); throw new RuntimeException('Worksheet tidak ditemukan di arsip.'); }

    // --- shared strings ---
    $ss = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $sd = new DOMDocument(); $sd->loadXML($ssXml, LIBXML_PARSEHUGE);
        foreach ($sd->getElementsByTagNameNS(XL_NS, 'si') as $si) {
            $teks = '';
            foreach ($si->getElementsByTagNameNS(XL_NS, 't') as $t) $teks .= $t->nodeValue;
            $ss[] = $teks;
        }
    }

    // --- isi sheet ke matriks [baris][kolom] ---
    $sheetDoc = new DOMDocument();
    $sheetDoc->loadXML($zip->getFromName($target), LIBXML_PARSEHUGE);
    $zip->close();

    $sel = [];
    foreach ($sheetDoc->getElementsByTagNameNS(XL_NS, 'c') as $c) {
        $ref = $c->getAttribute('r');
        if ($ref === '') continue;
        [$h, $b] = xlPisah($ref);
        $k = xlNomor($h);

        $tipe = $c->getAttribute('t');
        $nilai = '';
        if ($tipe === 'inlineStr') {
            $t = $c->getElementsByTagNameNS(XL_NS, 't')->item(0);
            $nilai = $t ? $t->nodeValue : '';
        } else {
            $v = $c->getElementsByTagNameNS(XL_NS, 'v')->item(0);
            if ($v) {
                $nilai = ($tipe === 's') ? ($ss[(int)$v->nodeValue] ?? '') : $v->nodeValue;
            }
        }
        $sel[$b][$k] = $nilai;
    }

    $ambil = function (int $b, int $k) use ($sel): string {
        return isset($sel[$b][$k]) ? trim((string)$sel[$b][$k]) : '';
    };
    $angka = function (string $v): string {
        if ($v === '') return '';
        if (!is_numeric($v)) return $v;
        $f = (float)$v;
        return ($f == (int)$f) ? (string)(int)$f : rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
    };

    // --- triwulan menentukan kolom mana yang dibaca ---
    $urut = ['I', 'II', 'III', 'IV'];
    $tw = ($triwulan && in_array(strtoupper($triwulan), $urut, true)) ? strtoupper($triwulan) : 'I';
    $off = array_search($tw, $urut, true);

    // Blok 4 kolom per triwulan:
    //   M–P alokasi (13) | Q–T realisasi (17) | U–X capaian TW (21) | Y–AB capaian PK (25)
    $kAlokasi = 13 + $off;
    $kReal    = 17 + $off;
    $kCapTw   = 21 + $off;
    $kCapPk   = 25 + $off;

    $ikuList = [];
    $tujuan = $sasaranKode = $sasaranNama = '';
    $maks = $sel ? max(array_keys($sel)) : 0;

    for ($b = 10; $b <= min($maks, 400); $b++) {
        $a = $ambil($b, 1);
        if ($a !== '' && preg_match('/^T\d\s*:/', $a)) { $tujuan = $a; continue; }

        $kolB = $ambil($b, 2);
        $kolC = $ambil($b, 3);
        $kolH = $ambil($b, 8);
        if ($kolB !== '' && $kolC !== '' && $kolH === '') {
            $sasaranKode = $kolB; $sasaranNama = $kolC; continue;
        }

        $kode = $ambil($b, 4);
        if ($kode !== '' && strcasecmp($kolH, 'IKU') === 0) {
            $ikuList[] = [
                'kode'         => $kode,
                'nama'         => $ambil($b, 5),
                'sasaran_kode' => $sasaranKode,
                'sasaran_nama' => $sasaranNama,
                'tujuan'       => $tujuan,
                'target_pk'    => $angka($ambil($b, 11)),
                'satuan'       => $ambil($b, 12),
                'alokasi_tw'   => $angka($ambil($b, $kAlokasi)),
                'real_tw'      => $angka($ambil($b, $kReal)),
                'capaian_tw'   => $angka($ambil($b, $kCapTw)),
                'capaian_pk'   => $angka($ambil($b, $kCapPk)),
                'kendala'      => $ambil($b, 29),
                'solusi'       => $ambil($b, 30),
                'rtl'          => $ambil($b, 31),
                'pic'          => $ambil($b, 32),
                'batas'        => $ambil($b, 33),
                'link_bukti'   => $ambil($b, 34),
                'link_tl_sblm' => $ambil($b, 35),
                'ro_narasi'    => $ambil($b, 40),
            ];
        }
    }

    $peringatan = [];
    if (!$ikuList) {
        $peringatan[] = "Tidak ada baris IKU yang terbaca dari sheet '$pakai'. "
                      . "Pastikan kolom D berisi kode IKU dan kolom H berisi teks 'IKU'.";
    }

    return [
        'satker'          => $ambil(3, 5) ?: 'BPS Kabupaten/Kota',
        'nilai_sakip'     => $angka($ambil(4, 5)),
        'predikat'        => $ambil(5, 5),
        'triwulan'        => $tw,
        'tahun'           => (int)date('Y'),
        'sheet'           => $pakai,
        'sheets_tersedia' => $tersedia,
        'peringatan'      => $peringatan,
        'iku_list'        => $ikuList,
    ];
}
