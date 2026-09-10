<?php
/**
 * SOKAB — Generate Dokumen Sumber (Bukti Dukung) per IKU, PHP murni
 * File: includes/docx_dokumen_sumber.php
 *
 * Sama filosofinya dengan docx_notula.php: tidak butuh Python/shell_exec,
 * karena hosting produksi (Hostinger) mematikan shell_exec — itu sebabnya
 * generate_notula.py sebelumnya juga ditulis ulang jadi docx_notula.php.
 *
 * Beda dengan docx_notula.php: dokumen ini TIDAK memakai template berisi
 * (tidak ada tabel/placeholder yang sudah dicetak), karena jumlah RO dan
 * foto per IKU berbeda-beda. Jadi seluruh isi dibangun dari nol lewat
 * potongan XML mentah, ditempel ke docx kosong (tools/notula/template/
 * blank_dokumen_sumber.docx) yang cuma berisi setup halaman (A4, margin).
 *
 * Pemakaian:
 *   require_once __DIR__ . '/docx_dokumen_sumber.php';
 *   $info = buatDokumenSumberDocx($data, $templateKosong, $outputPath);
 */

const DS_W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

/** Escape teks untuk isi XML (bukan HTML — beda entity). */
function dsEsc(?string $s): string {
    return htmlspecialchars((string)($s ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function dsTwips(float $cm): int { return (int)round($cm * 566.929133858); }
function dsEmu(float $cm): int { return (int)round($cm * 360000); }

/**
 * Satu paragraf, bisa berisi beberapa run (misal label bold + nilai biasa).
 * $runs: list of ['text'=>string, 'bold'=>bool, 'size'=>pt, 'color'=>hex6, 'br_before'=>bool]
 * $opts: ['align'=>left|center|right|both, 'space_after'=>pt, 'space_before'=>pt]
 */
function dsParagraf(array $runs, array $opts = []): string {
    $align = $opts['align'] ?? null;
    $pPr = '';
    if ($align || isset($opts['space_after']) || isset($opts['space_before'])) {
        $pPr .= '<w:pPr>';
        if (isset($opts['space_after']) || isset($opts['space_before'])) {
            $pPr .= '<w:spacing';
            if (isset($opts['space_before'])) $pPr .= ' w:before="' . (int)($opts['space_before'] * 20) . '"';
            if (isset($opts['space_after']))  $pPr .= ' w:after="'  . (int)($opts['space_after']  * 20) . '"';
            $pPr .= '/>';
        }
        if ($align) $pPr .= '<w:jc w:val="' . dsEsc($align) . '"/>';
        $pPr .= '</w:pPr>';
    }

    $runsXml = '';
    if (!$runs) {
        $runsXml = '<w:r><w:t xml:space="preserve"></w:t></w:r>';
    }
    foreach ($runs as $r) {
        $rPr = '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>';
        if (!empty($r['bold'])) $rPr .= '<w:b/>';
        if (!empty($r['color'])) $rPr .= '<w:color w:val="' . dsEsc($r['color']) . '"/>';
        $sz = $r['size'] ?? 10;
        $rPr .= '<w:sz w:val="' . (int)round($sz * 2) . '"/><w:szCs w:val="' . (int)round($sz * 2) . '"/>';
        $rPr .= '</w:rPr>';

        $br = !empty($r['br_before']) ? '<w:br/>' : '';
        $runsXml .= '<w:r>' . $rPr . $br . '<w:t xml:space="preserve">' . dsEsc($r['text'] ?? '') . '</w:t></w:r>';
    }

    return '<w:p xmlns:w="' . DS_W_NS . '">' . $pPr . $runsXml . '</w:p>';
}

/** Paragraf satu run saja — pintasan paling umum. */
function dsP(string $text, array $opts = []): string {
    return dsParagraf([[
        'text'  => $text,
        'bold'  => $opts['bold']  ?? false,
        'size'  => $opts['size']  ?? 10,
        'color' => $opts['color'] ?? null,
    ]], $opts);
}

/** Satu sel tabel. $innerParas = list of paragraf XML (string) yang sudah jadi. */
function dsSel(array $innerParas, array $opts = []): string {
    $tcPr = '<w:tcPr>';
    if (isset($opts['width_cm'])) $tcPr .= '<w:tcW w:w="' . dsTwips($opts['width_cm']) . '" w:type="dxa"/>';
    if (isset($opts['gridspan'])) $tcPr .= '<w:gridSpan w:val="' . (int)$opts['gridspan'] . '"/>';
    if (isset($opts['vmerge']))   $tcPr .= '<w:vMerge w:val="' . dsEsc($opts['vmerge']) . '"/>';
    if (isset($opts['shade']))    $tcPr .= '<w:shd w:val="clear" w:color="auto" w:fill="' . dsEsc($opts['shade']) . '"/>';
    $tcPr .= '<w:vAlign w:val="center"/></w:tcPr>';

    $isi = $innerParas ? implode('', $innerParas) : dsP('');
    return '<w:tc xmlns:w="' . DS_W_NS . '">' . $tcPr . $isi . '</w:tc>';
}

/** Satu baris tabel dari list sel (string XML <w:tc>...). */
function dsBaris(array $selList): string {
    return '<w:tr xmlns:w="' . DS_W_NS . '">' . implode('', $selList) . '</w:tr>';
}

/** Tabel penuh dari list baris + lebar kolom (cm). */
function dsTabel(array $barisList, array $lebarKolomCm): string {
    $grid = '';
    foreach ($lebarKolomCm as $w) $grid .= '<w:gridCol w:w="' . dsTwips($w) . '"/>';

    $border = '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';

    return '<w:tbl xmlns:w="' . DS_W_NS . '">'
         . '<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>' . $border . '</w:tblBorders>'
         . '<w:tblLayout w:type="fixed"/></w:tblPr>'
         . '<w:tblGrid>' . $grid . '</w:tblGrid>'
         . implode('', $barisList)
         . '</w:tbl>';
}

/**
 * Paragraf berisi satu gambar inline (didahului relasi rId yang sudah ditambahkan
 * ke document.xml.rels oleh pemanggil). $cx/$cy dalam EMU.
 */
function dsGambar(string $relId, int $cx, int $cy, int $docPrId): string {
    return '<w:p xmlns:w="' . DS_W_NS . '"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:drawing '
         . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
         . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
         . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
         . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
         . '<wp:docPr id="' . $docPrId . '" name="Picture ' . $docPrId . '"/>'
         . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
         . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
         . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
         . '<pic:nvPicPr><pic:cNvPr id="' . $docPrId . '" name="Picture ' . $docPrId . '"/><pic:cNvPicPr/></pic:nvPicPr>'
         . '<pic:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="'
         . dsEsc($relId) . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
         . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
         . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
         . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
}

/** Sisipkan potongan XML (satu elemen blok) tepat sebelum node acuan. */
function dsSisip(DOMDocument $doc, DOMNode $acuan, string $xml): void {
    $frag = $doc->createDocumentFragment();
    $frag->appendXML($xml);
    $acuan->parentNode->insertBefore($frag, $acuan);
}

/** Pecah teks kendala/solusi jadi list bernomor (satu baris = satu poin). */
/**
 * Buang nomor yang sudah ditulis sendiri di depan baris (kolega sering menomori
 * manual di Excel: "1. ...", "2) ...", "(3) ..."). Tanpa ini nomor kita tambahkan
 * lagi di atasnya sehingga dobel, misal "2. 1. Pada hari pertama...".
 */
function dsBuangNomor(string $baris): string {
    return preg_replace('/^\(?\d{1,2}\)?[.\)\-:]\s*/', '', $baris);
}

function dsBacaList(?string $teks): array {
    if (!$teks) return [];
    $baris = preg_split('/\r\n|\r|\n/', $teks);
    $baris = array_map('trim', $baris);
    $baris = array_filter($baris, fn($b) => $b !== '');
    $baris = array_map('dsBuangNomor', $baris);
    return array_values($baris);
}

/**
 * Bangun dokumen Bukti Dokumen Sumber dari nol, pakai template kosong (A4 + margin saja).
 * $data harus punya struktur sama dengan generate_dokumen_sumber.py.
 */
function buatDokumenSumberDocx(array $data, string $templateKosong, string $output): array {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Ekstensi PHP "zip" tidak aktif.');
    if (!file_exists($templateKosong)) throw new RuntimeException('Template kosong tidak ditemukan: ' . $templateKosong);

    if (!copy($templateKosong, $output)) throw new RuntimeException('Gagal menyalin template ke ' . $output);

    $zip = new ZipArchive();
    if ($zip->open($output) !== true) { @unlink($output); throw new RuntimeException('Gagal membuka .docx sebagai ZIP.'); }

    $xml = $zip->getFromName('word/document.xml');
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = true;
    $doc->loadXML($xml, LIBXML_PARSEHUGE);

    $body = $doc->getElementsByTagNameNS(DS_W_NS, 'body')->item(0);
    $sectPr = $doc->getElementsByTagNameNS(DS_W_NS, 'sectPr')->item(0);
    if (!$body || !$sectPr) throw new RuntimeException('Struktur template kosong tidak sesuai (body/sectPr tidak ada).');

    // Margin dipadatkan (1,5cm tiap sisi) supaya tabel dapat ruang lebih lebar —
    // tanpa ini tabelnya terlihat kecil/terjepit di tengah halaman yang lebar.
    $pgMar = $doc->getElementsByTagNameNS(DS_W_NS, 'pgMar')->item(0);
    if ($pgMar) {
        $pgMar->setAttribute('w:top', (string)dsTwips(1.5));
        $pgMar->setAttribute('w:right', (string)dsTwips(1.5));
        $pgMar->setAttribute('w:bottom', (string)dsTwips(1.5));
        $pgMar->setAttribute('w:left', (string)dsTwips(1.5));
    }

    // Relationship id berikutnya yang aman dipakai
    $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
    $relsDoc = new DOMDocument();
    $relsDoc->preserveWhiteSpace = true;
    $relsDoc->loadXML($relsXml);
    $relsRoot = $relsDoc->documentElement;
    $maxRid = 0;
    foreach ($relsRoot->childNodes as $rel) {
        if ($rel instanceof DOMElement) {
            $id = (int)preg_replace('/\D/', '', $rel->getAttribute('Id'));
            if ($id > $maxRid) $maxRid = $id;
        }
    }
    $ridBerikut = $maxRid + 1;
    $imgIndex = 1;

    $satker = (string)($data['satker'] ?? 'BPS Kabupaten/Kota');
    $tw     = (string)($data['triwulan'] ?? 'I');
    $tahun  = (string)($data['tahun'] ?? date('Y'));

    // ── Judul ──
    dsSisip($doc, $sectPr, dsP("Bukti Dokumen Sumber TW $tw $tahun", ['bold' => true, 'size' => 13, 'align' => 'center', 'space_after' => 2]));
    dsSisip($doc, $sectPr, dsP($satker, ['bold' => true, 'size' => 13, 'align' => 'center', 'space_after' => 12]));

    // ── Tabel indikator ──
    // Header dua baris: No/Indikator/Target PK/Capaian Terhadap Target PK menyatu
    // vertikal (vMerge) di kedua baris header, sedangkan "Triwulan X" jadi satu label
    // yang membawahi 3 sub-kolom (Target/Realisasi/Capaian Terhadap Target Triwulanan) —
    // menyamai struktur dokumen resmi (contoh yang diberikan), bukan tabel rata 1 baris.
    // Lebar total sengaja mendekati lebar halaman terpakai (margin 1,5cm x2 dari 21cm =
    // 18cm) supaya tabel tidak terlihat kecil/terjepit di tengah halaman.
    $lebar = [1.3, 4.8, 2.0, 1.8, 1.8, 2.7, 2.4];
    $lebarTotal = array_sum($lebar);
    $lebarTw    = $lebar[3] + $lebar[4] + $lebar[5];   // gabungan sub-kolom Triwulan

    $baris0 = dsBaris([dsSel([dsParagraf([
        ['text' => 'Sasaran : ', 'bold' => true, 'size' => 9],
        ['text' => (string)($data['sasaran_nama'] ?? ''), 'size' => 9],
    ])], ['gridspan' => 7, 'width_cm' => $lebarTotal, 'shade' => 'F2F2F2'])]);

    // Baris header atas — kolom yang tunggal (No/Indikator/Target PK/Capaian PK)
    // mulai vMerge "restart"; muncul di baris ini, disambung kosong di baris bawah.
    $baris1 = dsBaris([
        dsSel([dsP('No.', ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[0], 'shade' => 'F2F2F2', 'vmerge' => 'restart']),
        dsSel([dsP('Indikator Kinerja', ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[1], 'shade' => 'F2F2F2', 'vmerge' => 'restart']),
        dsSel([dsP("Target PK\n$tahun", ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[2], 'shade' => 'F2F2F2', 'vmerge' => 'restart']),
        dsSel([dsP("Triwulan $tw", ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebarTw, 'gridspan' => 3, 'shade' => 'F2F2F2']),
        dsSel([dsP("Capaian Terhadap\nTarget PK", ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[6], 'shade' => 'F2F2F2', 'vmerge' => 'restart']),
    ]);

    // Baris header bawah — sub-kolom Target/Realisasi/Capaian Terhadap Target Triwulanan.
    $baris1b = dsBaris([
        dsSel([], ['width_cm' => $lebar[0], 'shade' => 'F2F2F2', 'vmerge' => 'continue']),
        dsSel([], ['width_cm' => $lebar[1], 'shade' => 'F2F2F2', 'vmerge' => 'continue']),
        dsSel([], ['width_cm' => $lebar[2], 'shade' => 'F2F2F2', 'vmerge' => 'continue']),
        dsSel([dsP('Target', ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[3], 'shade' => 'F2F2F2']),
        dsSel([dsP('Realisasi', ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[4], 'shade' => 'F2F2F2']),
        dsSel([dsP("Capaian Terhadap\nTarget Triwulanan", ['bold' => true, 'size' => 8.5, 'align' => 'center'])],
              ['width_cm' => $lebar[5], 'shade' => 'F2F2F2']),
        dsSel([], ['width_cm' => $lebar[6], 'shade' => 'F2F2F2', 'vmerge' => 'continue']),
    ]);

    $targetPk = trim(($data['target_pk'] ?? '') . ' ' . ($data['satuan'] ?? ''));
    $nilai = [$data['kode'] ?? '', $data['nama'] ?? '', $targetPk, $data['alokasi_tw'] ?? '',
              $data['real_tw'] ?? '', $data['capaian_tw'] ?? '', $data['capaian_pk'] ?? ''];
    $selData = [];
    foreach ($nilai as $i => $v) {
        $align = $i < 2 ? 'left' : 'center';
        $selData[] = dsSel([dsP($v !== '' ? (string)$v : '-', ['size' => 9, 'align' => $align])], ['width_cm' => $lebar[$i]]);
    }
    $baris2 = dsBaris($selData);

    $baris3 = dsBaris([dsSel([dsP('Analisis Capaian Kinerja', ['bold' => true, 'size' => 9.5, 'align' => 'center'])],
                              ['gridspan' => 7, 'width_cm' => $lebarTotal, 'shade' => 'F2F2F2'])]);

    dsSisip($doc, $sectPr, dsTabel([$baris0, $baris1, $baris1b, $baris2, $baris3], $lebar));
    dsSisip($doc, $sectPr, dsP('', ['space_after' => 4]));

    // ── Aktivitas yang dilakukan ──
    $roList = $data['ro_list'] ?? [];
    dsSisip($doc, $sectPr, dsP('Aktivitas yang dilakukan', ['bold' => true, 'space_after' => 2]));
    $aktivitas = [];
    foreach ($roList as $ro) {
        $narasi = trim((string)($ro['narasi'] ?? ''));
        if ($narasi === '') continue;
        $aktivitas[] = trim(($ro['nama_ro'] ?? '') . ': ' . $narasi, ': ');
    }
    dsSisip($doc, $sectPr, dsP($aktivitas ? implode(' ', $aktivitas) : '-', ['align' => 'both', 'space_after' => 10]));

    // ── Kendala ──
    $kendalaList = dsBacaList($data['kendala'] ?? '');
    if ($kendalaList) {
        dsSisip($doc, $sectPr, dsP('Kendala :', ['bold' => true, 'space_after' => 2]));
        foreach ($kendalaList as $i => $k) {
            dsSisip($doc, $sectPr, dsP(($i + 1) . '. ' . $k, ['align' => 'both', 'space_after' => 2]));
        }
        dsSisip($doc, $sectPr, dsP('', ['space_after' => 4]));
    }

    // ── Solusi ──
    $solusiList = dsBacaList($data['solusi'] ?? '');
    if ($solusiList) {
        dsSisip($doc, $sectPr, dsP('Solusi :', ['bold' => true, 'space_after' => 2]));
        foreach ($solusiList as $i => $s) {
            dsSisip($doc, $sectPr, dsP(($i + 1) . '. ' . $s, ['align' => 'both', 'space_after' => 2]));
        }
        dsSisip($doc, $sectPr, dsP('', ['space_after' => 4]));
    }

    // ── RTL & PIC ──
    $selRtl = dsSel([dsParagraf([
        ['text' => "Rencana Tindak Lanjut (RTL) :", 'bold' => true, 'size' => 9.5],
        ['text' => (string)($data['rtl'] ?: '-'), 'size' => 9.5, 'br_before' => true],
    ])], ['width_cm' => 11.0]);

    $selPic = dsSel([dsParagraf([
        ['text' => 'PIC Tindak Lanjut :', 'bold' => true, 'size' => 9.5],
        ['text' => (string)($data['pic'] ?: '-'), 'size' => 9.5, 'br_before' => true],
    ]), dsParagraf([
        ['text' => 'Batas Waktu Tindak Lanjut :', 'bold' => true, 'size' => 9.5],
        ['text' => (string)($data['batas'] ?: '-'), 'size' => 9.5, 'br_before' => true],
    ])], ['width_cm' => 5.8]);

    dsSisip($doc, $sectPr, dsTabel([dsBaris([$selRtl, $selPic])], [11.0, 5.8]));
    dsSisip($doc, $sectPr, dsP('', ['space_after' => 4]));

    // ── Bukti Dokumen Sumber ──
    dsSisip($doc, $sectPr, dsP('Bukti Dokumen Sumber', ['bold' => true, 'size' => 10.5, 'space_after' => 4]));
    dsSisip($doc, $sectPr, dsP('Adapun Bukti Dokumen Sumber adalah sebagai berikut:', ['space_after' => 8]));

    $roTerisi = 0; $fotoTerpasang = 0; $fotoGagal = [];
    $newRels = ''; // Relationship XML tambahan, disatukan di akhir

    foreach ($roList as $ro) {
        // Label tiap bukti diambil dari narasi realisasi (bukan nama Rincian Output
        // di katalog) — narasi lebih menjelaskan apa yang sungguh dikerjakan.
        // Nama RO cuma dipakai kalau narasinya kosong, supaya foto tidak hilang begitu saja.
        $narasiRo = trim((string)($ro['narasi'] ?? ''));
        $namaRo   = trim((string)($ro['nama_ro'] ?? ''));
        $label    = $narasiRo !== '' ? $narasiRo : $namaRo;
        if ($label === '') continue;
        $roTerisi++;
        dsSisip($doc, $sectPr, dsP('• ' . $label, ['bold' => true, 'space_after' => 6]));

        foreach (($ro['foto'] ?? []) as $foto) {
            $path = $foto['path'] ?? '';
            if (!$path || !is_file($path)) { $fotoGagal[] = $path ?: '(kosong)'; continue; }

            $ukuran = @getimagesize($path);
            if (!$ukuran) { $fotoGagal[] = $path . ' (bukan gambar valid)'; continue; }
            [$wPx, $hPx] = $ukuran;
            if ($wPx <= 0 || $hPx <= 0) { $fotoGagal[] = $path . ' (dimensi tidak valid)'; continue; }

            $mime = $ukuran['mime'] ?? '';
            $extZip = $mime === 'image/jpeg' ? 'jpeg' : ($mime === 'image/png' ? 'png' : null);
            if (!$extZip) { $fotoGagal[] = $path . " (tipe $mime tidak didukung)"; continue; }

            $rid = 'rId' . $ridBerikut++;
            $namaMedia = "image{$imgIndex}.{$extZip}";
            $zip->addFromString("word/media/$namaMedia", file_get_contents($path));

            $newRels .= '<Relationship Id="' . $rid . '" '
                      . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                      . 'Target="media/' . $namaMedia . '"/>';

            // Batasi lebar MAUPUN tinggi — foto HP kebanyakan potret (tinggi > lebar).
            // Kalau cuma lebar dibatasi (11cm), foto potret jadi setinggi >14cm dan
            // memenuhi lebih dari separuh halaman. Dipilih batas mana yang lebih ketat,
            // supaya hasilnya selalu proporsional dan tidak pernah melebihi kotak 11×8cm.
            $maxW = dsEmu(11.0);
            $maxH = dsEmu(8.0);
            $cx = $maxW;
            $cy = (int)round($maxW * $hPx / $wPx);
            if ($cy > $maxH) {
                $cy = $maxH;
                $cx = (int)round($maxH * $wPx / $hPx);
            }

            dsSisip($doc, $sectPr, dsGambar($rid, $cx, $cy, $imgIndex));
            $imgIndex++;
            $fotoTerpasang++;

            $ket = trim((string)($foto['keterangan'] ?? ''));
            if ($ket !== '') {
                dsSisip($doc, $sectPr, dsP($ket, ['align' => 'center', 'color' => '64748B', 'size' => 8.5, 'space_after' => 10]));
            } else {
                dsSisip($doc, $sectPr, dsP('', ['space_after' => 6]));
            }
        }
    }

    // Tulis relationship baru ke document.xml.rels
    if ($newRels !== '') {
        $frag = $relsDoc->createDocumentFragment();
        $frag->appendXML($newRels);
        $relsRoot->appendChild($frag);
    }

    $zip->addFromString('word/document.xml', $doc->saveXML());
    $zip->addFromString('word/_rels/document.xml.rels', $relsDoc->saveXML());
    $zip->close();

    return ['ro_terisi' => $roTerisi, 'foto_terpasang' => $fotoTerpasang, 'foto_gagal' => $fotoGagal];
}
