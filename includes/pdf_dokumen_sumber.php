<?php
/**
 * SOKAB — Dokumen sumber dalam format PDF (disusun langsung dengan FPDF, tanpa LibreOffice).
 * Memakai $payload yang sama dengan buatDokumenSumberDocx() dan helper bersama di
 * docx_dokumen_sumber.php (dsAktivitas, dsSusunBukti, dsBacaList, dsParsePoin) sehingga isinya
 * sama dengan versi Word; yang ditulis dua kali hanya tata letaknya.
 * Dua jenis: dokumen sumber biasa dan 'hanya_solusi' (Bukti Solusi dari Kendala).
 */
require_once __DIR__ . '/lib/fpdf/fpdf.php';
require_once __DIR__ . '/docx_dokumen_sumber.php';

class PdfDokumenSumber extends FPDF {
    const M = 15;       // margin (mm)
    const W = 180;      // lebar isi (mm)
    public int $fotoOk = 0;
    public array $fotoGagal = [];
    public float $inset = 0;          // jarak teks dari tepi kiri/kanan (mm) saat di dalam bingkai
    public ?float $bingkaiY = null;   // y awal bingkai di halaman berjalan; null = tak ada bingkai
    private array $tmp = [];

    function __construct() {
        parent::__construct('P', 'mm', 'A4');
        $this->SetMargins(self::M, self::M, self::M);
        $this->SetAutoPageBreak(true, self::M);
        $this->AddPage();
    }

    /** UTF-8 → Windows-1252 (font inti FPDF). Karakter di luar itu jadi '?'. */
    static function t(?string $s): string {
        $s = str_replace(["\u{2022}", "\u{2013}", "\u{2014}", "\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{00A0}"], ['•', '-', '-', "'", "'", '"', '"', ' '], (string)$s);
        $o = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        return $o === false ? mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8') : $o;
    }

    function font(float $size, bool $bold = false): void { $this->SetFont('Arial', $bold ? 'B' : '', $size); }

    /** Jumlah baris bila teks dibungkus pada lebar $w (mm) dengan font aktif. */
    function baris(float $w, string $txt): int {
        $n = 0;
        foreach (explode("\n", self::t($txt)) as $para) {
            $n++; $lebar = 0;
            foreach (preg_split('/\s+/', $para) as $kata) {
                $kw = $this->GetStringWidth($kata . ' ');
                if ($lebar + $kw > $w - 1 && $lebar > 0) { $n++; $lebar = 0; }
                $lebar += $kw;
            }
        }
        return $n;
    }

    /** Paragraf: MultiCell dengan indent kiri (mm) dan jarak bawah (mm). */
    function para(string $txt, float $size = 10, bool $bold = false, string $align = 'L', float $indent = 0, float $after = 2): void {
        $this->font($size, $bold);
        $this->SetX(self::M + $this->inset + $indent);
        $this->MultiCell(self::W - 2 * $this->inset - $indent, $size * 0.5, self::t($txt), 0, $align);
        $this->Ln($after);
    }

    /** Kotak ber-border dengan teks di tengah secara vertikal. */
    function kotak(float $x, float $y, float $w, float $h, string $txt, string $align = 'C', bool $fill = false, bool $bold = false, float $size = 8.5): void {
        $this->font($size, $bold);
        $lh = $size * 0.5;
        if ($fill) $this->SetFillColor(242, 242, 242);
        $this->Rect($x, $y, $w, $h, $fill ? 'DF' : 'D');
        $n = $this->baris($w - 2, $txt);
        $this->SetXY($x + 1, $y + max(0.5, ($h - $n * $lh) / 2));
        $this->MultiCell($w - 2, $lh, self::t($txt), 0, $align);
    }

    private function jpegSementara(string $path, string $mime, int $wPx, int $hPx): ?string {
        // PNG (alpha) dan foto sangat besar dijadikan JPEG ≤1600px lewat GD: aman untuk FPDF dan PDF tidak membengkak
        if (!function_exists('imagecreatetruecolor')) return $mime === 'image/jpeg' ? $path : null;
        if ($mime === 'image/jpeg' && max($wPx, $hPx) <= 1600) return $path;
        $src = $mime === 'image/png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
        if (!$src) return $mime === 'image/jpeg' ? $path : null;
        $skala = min(1, 1600 / max($wPx, $hPx));
        $w = max(1, (int)round($wPx * $skala)); $h = max(1, (int)round($hPx * $skala));
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $wPx, $hPx);
        $f = tempnam(sys_get_temp_dir(), 'sokabpdf') . '.jpg';
        imagejpeg($dst, $f, 85);
        imagedestroy($src); imagedestroy($dst);
        return $this->tmp[] = $f;
    }

    /** Pasang daftar foto DUA per baris (hemat halaman, cocok untuk screenshot WA), maks 85×110 mm per foto, beserta keterangannya. */
    function foto(array $list): void {
        $siap = [];
        foreach ($list as $f) {
            $path = $f['path'] ?? '';
            if (!$path || !is_file($path)) { $this->fotoGagal[] = $path ?: '(kosong)'; continue; }
            $u = @getimagesize($path);
            if (!$u || $u[0] <= 0 || $u[1] <= 0) { $this->fotoGagal[] = $path . ' (bukan gambar valid)'; continue; }
            if (!in_array($u['mime'] ?? '', ['image/jpeg', 'image/png'], true)) { $this->fotoGagal[] = $path . ' (tipe tidak didukung)'; continue; }
            $berkas = $this->jpegSementara($path, $u['mime'], $u[0], $u[1]);
            if (!$berkas) { $this->fotoGagal[] = $path . ' (gagal diproses)'; continue; }
            $w = 85; $h = $w * $u[1] / $u[0];
            if ($h > 110) { $h = 110; $w = $h * $u[0] / $u[1]; }
            $siap[] = ['berkas' => $berkas, 'w' => $w, 'h' => $h, 'ket' => trim((string)($f['keterangan'] ?? '')), 'path' => $path];
        }
        foreach (array_chunk($siap, 2) as $pasang) {
            $tinggi = max(array_column($pasang, 'h'));
            if ($this->GetY() + $tinggi > $this->PageBreakTrigger) $this->AddPage();
            $y = $this->GetY();
            $lajur = count($pasang) === 1 ? [105] : [self::M + 45, self::M + 135];   // pusat tiap lajur (mm)
            foreach ($pasang as $i => $g) {
                try {
                    $this->Image($g['berkas'], $lajur[$i] - $g['w'] / 2, $y, $g['w'], $g['h'], 'JPG');
                    $this->fotoOk++;
                } catch (Throwable $e) { $this->fotoGagal[] = $g['path'] . ' (' . $e->getMessage() . ')'; }
            }
            $this->SetY($y + $tinggi + 2);
            foreach ($pasang as $g) if ($g['ket'] !== '') {   // keterangan ditulis berurutan di bawah baris foto
                $this->SetTextColor(100, 116, 139); $this->para($g['ket'], 8.5, false, 'C', 0, 1); $this->SetTextColor(0);
            }
            $this->Ln(3);
        }
    }

    /** Bingkai terbuka: sisi kiri/kanan digambar per halaman (Footer), sisi bawah saat ditutup. */
    function bukaBingkai(float $y): void { $this->bingkaiY = $y; $this->inset = 2; }
    function tutupBingkai(): void {
        if ($this->bingkaiY === null) return;
        $y = $this->GetY();
        $this->Line(self::M, $this->bingkaiY, self::M, $y); $this->Line(self::M + self::W, $this->bingkaiY, self::M + self::W, $y);
        $this->Line(self::M, $y, self::M + self::W, $y);
        $this->bingkaiY = null; $this->inset = 0;
    }
    function Footer(): void {
        if ($this->bingkaiY === null) return;
        $y = $this->GetY();
        $this->Line(self::M, $this->bingkaiY, self::M, $y); $this->Line(self::M + self::W, $this->bingkaiY, self::M + self::W, $y);
        $this->Line(self::M, $y, self::M + self::W, $y);   // garis bawah di setiap halaman
    }
    function Header(): void {   // halaman lanjutan: bingkai dibuka lagi dengan garis atas
        if ($this->bingkaiY === null) return;
        $this->bingkaiY = self::M;
        $this->Line(self::M, self::M, self::M + self::W, self::M);
    }

    function batasHalaman(): float { return $this->PageBreakTrigger; }

    function bersihkan(): void { foreach ($this->tmp as $f) @unlink($f); }
}

function buatDokumenSumberPdf(array $dAsal, ?array &$info = null): string {
    $p = new PdfDokumenSumber();
    $roTerisi = 0;
    // Dokumen gabungan: $dAsal['gabungan'] = daftar payload (satu per IKU), tiap IKU mulai di halaman baru
    foreach ($dAsal['gabungan'] ?? [$dAsal] as $nomorDok => $d) {
    if ($nomorDok > 0) $p->AddPage();
    $tl = !empty($d['mode_tl']);

    if (!empty($d['hanya_solusi'])) {
        $p->para('Bukti Dokumen Sumber Solusi Dari Kendala', 14, true, 'C', 0, 1);
        $p->para('Triwulan ' . ($d['triwulan'] ?? '') . ' ' . ($d['tahun'] ?? ''), 14, true, 'C', 0, 3);
        $p->para(trim(($d['kode'] ?? '') . ' ' . ($d['nama'] ?? '')), 12, true, 'C', 0, 5);

        $kosong = [['judul' => false, 'teks' => '-', 'no' => null, 'idx' => null]];
        $kendala = dsParsePoin($d['kendala'] ?? '') ?: $kosong;
        $solusi  = dsParsePoin($d['solusi'] ?? '') ?: $kosong;

        $p->para('Kendala :', 11, true, 'L', 0, 1);
        foreach ($kendala as $it) {
            if ($it['judul']) $p->para($it['teks'], 11, true, 'L', 0, 1);
            else $p->para(($it['no'] ? $it['no'] . '. ' : '') . $it['teks'], 11, false, 'J', 0, 1);
        }
        $p->Ln(3);

        $p->para('Solusi :', 11, true, 'L', 0, 1);
        foreach ($solusi as $it) {
            if ($it['judul']) { $p->para($it['teks'], 11, true, 'L', 0, 1); continue; }
            $p->para(($it['no'] ? $it['no'] . '. ' : '') . $it['teks'], 11, false, 'J', 0, 2);
            if ($it['idx']) $p->foto($d['solusi_foto'][$it['idx']] ?? []);
        }
    } elseif ($tl) {
        // Tindak Lanjut TW sebelumnya: format "Laporan Pelaksanaan Tindak Lanjut Monitoring Kinerja"
        if (empty($d['tanpa_judul'])) {   // dokumen gabungan: judul cukup di halaman pertama
            $p->para("LAPORAN PELAKSANAAN TINDAK LANJUT\nMONITORING KINERJA TRIWULAN " . ($d['triwulan'] ?? '') . ' TAHUN ' . ($d['tahun'] ?? '') . "\nBPS KOTA BIMA", 12, true, 'C', 0, 6);
        }
        $x0 = PdfDokumenSumber::M;
        $y = $p->GetY();
        $p->font(10);
        $h1 = max(7, $p->baris(180 - 36 - 2, (string)($d['sasaran_nama'] ?? '-')) * 5 + 2);
        $p->kotak($x0, $y, 36, $h1, 'Sasaran', 'L', true, true, 10);
        $p->kotak($x0 + 36, $y, 144, $h1, (string)($d['sasaran_nama'] ?? '-'), 'L', true, true, 10);
        $y += $h1;
        $h2 = max(12, $p->baris(144 - 2, (string)($d['nama'] ?? '')) * 5 + 2);
        $p->kotak($x0, $y, 36, $h2, "Indikator Kinerja\n" . ($d['kode'] ?? ''), 'L', false, true, 10);
        $p->kotak($x0 + 36, $y, 144, $h2, (string)($d['nama'] ?? ''), 'L', false, false, 10);
        $y += $h2;
        $progres = ($d['progres'] ?? '') !== '' ? $d['progres'] . ' Persen' : '-';
        // Bingkai membentang dari Aksi Tindak Lanjut sampai bukti paling akhir
        if ($y + 20 > $p->batasHalaman()) { $p->AddPage(); $y = $p->GetY(); $p->Line($x0, $y, $x0 + 180, $y); }
        $p->SetY($y + 2);
        $p->bukaBingkai($y);
        $p->para('Aksi Tindak Lanjut :', 10, true, 'L', 0, 1);
        foreach (dsBacaList2((string)($d['rtl'] ?? '')) ?: ['-'] as $t) $p->para($t, 10, false, 'J', 0, 1);
        $p->Ln(2);
        $p->para('PIC Tindak Lanjut: ' . ($d['pic'] ?: '-'), 10, false, 'L', 0, 1);
        $p->para('Batas Waktu Tindak Lanjut: ' . ($d['batas'] ?: '-'), 10, false, 'L', 0, 1);
        $p->para('Progres Pelaksanaan: ' . $progres, 10, false, 'L', 0, 3);

        $p->para('Bukti Tindak Lanjut :', 10, true, 'L', 0, 1);
        $p->para('Tindak lanjut tersebut telah dilaksanakan pada Triwulan ' . ($d['tw_laksana'] ?? '') . ' ' . ($d['tahun_laksana'] ?? '') . ' yang akan dirincikan sebagai berikut :', 10, false, 'J', 0, 3);
        foreach (dsSusunBukti($d['ro_list'] ?? [], true) as [$teks, $foto, $judul, $indent]) {
            if (!$judul) $roTerisi++;
            $p->para($teks, $judul ? 11 : 10, $judul, 'L', $indent * 10, 2);
            $p->foto($foto);
        }
        $p->tutupBingkai();
    } else {
        // ── Judul ──
        if (empty($d['tanpa_judul'])) {   // dokumen gabungan: judul cukup di halaman pertama
            $p->para('Bukti Dokumen Sumber TW ' . ($d['triwulan'] ?? '') . ' ' . ($d['tahun'] ?? ''), 13, true, 'C', 0, 0.5);
            $p->para((string)($d['satker'] ?? ''), 13, true, 'C', 0, 5);
        }

        // ── Tabel indikator (lebar mm; sama proporsinya dengan versi Word) ──
        $k = 180 / 16.8;
        $w = array_map(fn($c) => $c * $k, [1.3, 4.8, 2.0, 1.8, 1.8, 2.7, 2.4]);
        $x0 = PdfDokumenSumber::M;
        $y = $p->GetY();

        $p->kotak($x0, $y, 180, 7, 'Sasaran : ' . ($d['sasaran_nama'] ?? ''), 'L', true, false, 9);
        $y += 7;
        $x = [$x0]; foreach ($w as $i => $lebar) $x[$i + 1] = $x[$i] + $lebar;
        $p->kotak($x[0], $y, $w[0], 24, 'No.', 'C', true, true);
        $p->kotak($x[1], $y, $w[1], 24, 'Indikator Kinerja', 'C', true, true);
        $p->kotak($x[2], $y, $w[2], 24, "Target PK\n" . ($d['tahun'] ?? ''), 'C', true, true);
        $p->kotak($x[3], $y, $w[3] + $w[4] + $w[5], 6, 'Triwulan ' . ($d['triwulan'] ?? ''), 'C', true, true);
        $p->kotak($x[6], $y, $w[6], 24, "Capaian Terhadap\nTarget PK", 'C', true, true);
        $p->kotak($x[3], $y + 6, $w[3], 18, 'Target', 'C', true, true);
        $p->kotak($x[4], $y + 6, $w[4], 18, 'Realisasi', 'C', true, true);
        $p->kotak($x[5], $y + 6, $w[5], 18, "Capaian Terhadap\nTarget Triwulanan", 'C', true, true);
        $y += 24;

        $nilai = [$d['kode'] ?? '', $d['nama'] ?? '', trim(($d['target_pk'] ?? '') . ' ' . ($d['satuan'] ?? '')),
                  $d['alokasi_tw'] ?? '', $d['real_tw'] ?? '', $d['capaian_tw'] ?? '', $d['capaian_pk'] ?? ''];
        $p->font(9);
        $maks = 1;
        foreach ($nilai as $i => $v) $maks = max($maks, $p->baris($w[$i] - 2, $v !== '' ? (string)$v : '-'));
        $h = $maks * 4.5 + 2;
        foreach ($nilai as $i => $v) $p->kotak($x[$i], $y, $w[$i], $h, $v !== '' ? (string)$v : '-', $i < 2 ? 'L' : 'C', false, false, 9);
        $y += $h;
        $p->kotak($x0, $y, 180, 7, 'Analisis Capaian Kinerja', 'C', true, true, 9.5);
        $p->SetY($y + 7 + 4);

        // ── Isi ──
        $roList = $d['ro_list'] ?? [];
        $p->para('Aktivitas yang dilakukan', 10, true, 'L', 0, 1);
        $p->para(dsAktivitas($roList), 10, false, 'J', 0, 4);

        foreach (['Kendala' => 'kendala', 'Solusi' => 'solusi'] as $label => $kunci) {
            $items = dsParsePoin($d[$kunci] ?? '');
            if (!$items) continue;
            $p->para($label . ' :', 10, true, 'L', 0, 1);
            foreach ($items as $it) {
                if ($it['judul']) $p->para($it['teks'], 10, true, 'L', 0, 1);
                else $p->para($it['no'] . '. ' . $it['teks'], 10, false, 'J', 0, 1);
            }
            $p->Ln(2);
        }

        // RTL & PIC
        $kiri  = "Rencana Tindak Lanjut (RTL) :\n" . ($d['rtl'] ?: '-');
        $kanan = "PIC Tindak Lanjut :\n" . ($d['pic'] ?: '-') . "\nBatas Waktu Tindak Lanjut :\n" . ($d['batas'] ?: '-');
        $p->font(9.5);
        $h = max($p->baris(116 - 2, $kiri), $p->baris(64 - 2, $kanan)) * 4.75 + 2;
        if ($p->GetY() + $h > $p->batasHalaman()) $p->AddPage();
        $y = $p->GetY();
        $p->kotak($x0, $y, 116, $h, $kiri, 'L', false, false, 9.5);
        $p->kotak($x0 + 116, $y, 64, $h, $kanan, 'L', false, false, 9.5);
        $p->SetY($y + $h + 4);

        // ── Bukti Dokumen Sumber ──
        $p->para('Bukti Dokumen Sumber', 10.5, true, 'L', 0, 1);
        $p->para('Adapun Bukti Dokumen Sumber adalah sebagai berikut:', 10, false, 'L', 0, 3);
        foreach (dsSusunBukti($roList) as [$teks, $foto, $utama, $indentCm]) {
            if ($utama) $roTerisi++;
            $p->para($teks, 10, $utama, 'L', $indentCm * 10, 2);
            $p->foto($foto);
        }
    }

    }   // akhir foreach dokumen

    $info = ['ro_terisi' => $roTerisi, 'foto_terpasang' => $p->fotoOk, 'foto_gagal' => $p->fotoGagal];
    $out = $p->Output('S');
    $p->bersihkan();
    return $out;
}
