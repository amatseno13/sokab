<?php
/**
 * SOKAB — Pengisi template Notula (.docx) dengan PHP murni
 * File: includes/docx_notula.php
 *
 * Menggantikan generate_notula.py untuk hosting yang memblokir shell_exec.
 * Tidak butuh Composer, tidak butuh Python — hanya ZipArchive + DOMDocument
 * yang sudah menyatu di PHP.
 *
 * Cara kerja: .docx sebenarnya arsip ZIP. Isi utamanya ada di word/document.xml.
 * Berkas itu dibongkar, diubah lewat DOM, lalu dikemas ulang. Bagian lain
 * (gaya, header, gambar) disalin apa adanya sehingga format resmi tetap utuh.
 *
 * Pemakaian:
 *   require_once __DIR__ . '/docx_notula.php';
 *   $hasil = isiNotulaDocx($data, $templatePath, $outputPath);
 */

const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

// ============================================================
// UTILITAS DOM
// ============================================================

/** Ambil seluruh teks sebuah elemen (gabungan semua <w:t> di dalamnya). */
function dxTeks(DOMElement $el): string {
    $out = '';
    foreach ($el->getElementsByTagNameNS(W_NS, 't') as $t) $out .= $t->nodeValue;
    return $out;
}

/** Daftar anak langsung dengan nama tertentu (bukan keturunan jauh). */
function dxAnak(DOMElement $el, string $nama): array {
    $hasil = [];
    foreach ($el->childNodes as $n) {
        if ($n instanceof DOMElement && $n->localName === $nama && $n->namespaceURI === W_NS) {
            $hasil[] = $n;
        }
    }
    return $hasil;
}

/** Sel unik sebuah baris. Iterasi <w:tc> langsung sudah bebas duplikat merge. */
function dxSel(DOMElement $tr): array { return dxAnak($tr, 'tc'); }

/** Paragraf langsung di dalam sebuah sel. */
function dxParagraf(DOMElement $tc): array { return dxAnak($tc, 'p'); }

/** Buat elemen <w:...> baru. */
function dxBuat(DOMDocument $doc, string $nama): DOMElement {
    return $doc->createElementNS(W_NS, 'w:' . $nama);
}

/**
 * Paragraf berisi satu run teks, Arial.
 * $link = true → rata kiri (untuk tautan), selain itu justify.
 */
function dxParaNilai(DOMDocument $doc, string $teks, bool $link = false): DOMElement {
    $p   = dxBuat($doc, 'p');
    $pPr = dxBuat($doc, 'pPr');
    $jc  = dxBuat($doc, 'jc');
    $jc->setAttributeNS(W_NS, 'w:val', $link ? 'left' : 'both');
    $pPr->appendChild($jc);
    $p->appendChild($pPr);

    $r    = dxBuat($doc, 'r');
    $rPr  = dxBuat($doc, 'rPr');
    $font = dxBuat($doc, 'rFonts');
    $font->setAttributeNS(W_NS, 'w:ascii', 'Arial');
    $font->setAttributeNS(W_NS, 'w:hAnsi', 'Arial');
    $rPr->appendChild($font);
    $r->appendChild($rPr);

    $t = dxBuat($doc, 't');
    $t->setAttribute('xml:space', 'preserve');
    $t->appendChild($doc->createTextNode($link ? rtrim($teks) . ' ' : $teks));
    $r->appendChild($t);
    $p->appendChild($r);
    return $p;
}

/** Sisipkan node tepat setelah node acuan. */
function dxSisipSetelah(DOMElement $acuan, DOMElement $baru): void {
    if ($acuan->nextSibling) {
        $acuan->parentNode->insertBefore($baru, $acuan->nextSibling);
    } else {
        $acuan->parentNode->appendChild($baru);
    }
}

/**
 * Ganti teks dalam satu paragraf.
 * Word sering memecah satu kalimat menjadi beberapa <w:r>, sehingga pencarian
 * per-run bisa meleset. Kalau itu terjadi, seluruh teks paragraf disatukan ke
 * run pertama.
 */
function dxGantiTeksParagraf(DOMElement $p, string $cari, string $ganti): bool {
    $runs = [];
    foreach ($p->getElementsByTagNameNS(W_NS, 't') as $t) $runs[] = $t;
    if (!$runs) return false;

    $gabung = '';
    foreach ($runs as $t) $gabung .= $t->nodeValue;
    if (strpos($gabung, $cari) === false) return false;

    // Coba ganti di dalam satu run dulu supaya format aslinya terjaga
    foreach ($runs as $t) {
        if (strpos($t->nodeValue, $cari) !== false) {
            $t->nodeValue = str_replace($cari, $ganti, $t->nodeValue);
            return true;
        }
    }

    // Teks terpecah antar run: satukan ke run pertama
    $runs[0]->nodeValue = str_replace($cari, $ganti, $gabung);
    for ($i = 1; $i < count($runs); $i++) $runs[$i]->nodeValue = '';
    return true;
}

/**
 * Perbaiki run yang isinya persis nama satker apa adanya (mis. "BPS Kota Bima"
 * yang di-hardcode langsung di judul template, bukan lewat placeholder xxx/yyy
 * yang biasa diganti dxGantiSemua) — dipaksa KAPITAL semua dan warna hitam,
 * bukan warna merah bawaan dari siapa pun yang terakhir mengetik di template.
 */
function dxPerbaikiJudulSatker(DOMDocument $doc, string $satker): void {
    foreach ($doc->getElementsByTagNameNS(W_NS, 'r') as $run) {
        $tNodes = $run->getElementsByTagNameNS(W_NS, 't');
        if ($tNodes->length !== 1) continue;
        $t = $tNodes->item(0);
        if (strcasecmp(trim($t->nodeValue), $satker) !== 0) continue;

        $t->nodeValue = mb_strtoupper($satker, 'UTF-8');

        $rPr = null;
        foreach ($run->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'rPr' && $child->namespaceURI === W_NS) {
                $rPr = $child;
                break;
            }
        }
        if (!$rPr) continue;

        foreach (dxAnak($rPr, 'color') as $lama) $rPr->removeChild($lama);
        $warna = dxBuat($doc, 'color');
        $warna->setAttributeNS(W_NS, 'w:val', '000000');
        $rPr->appendChild($warna);
    }
}

/** Terapkan sekumpulan penggantian ke seluruh paragraf dokumen. */
function dxGantiSemua(DOMDocument $doc, array $peta): void {
    foreach ($doc->getElementsByTagNameNS(W_NS, 'p') as $p) {
        foreach ($peta as $cari => $ganti) {
            dxGantiTeksParagraf($p, $cari, (string)$ganti);
        }
    }
}

/** Isi sebuah sel dengan teks polos (isi lama dibuang). */
function dxIsiSel(DOMDocument $doc, DOMElement $tc, string $teks): void {
    foreach (dxParagraf($tc) as $i => $p) {
        if ($i === 0) {
            foreach (iterator_to_array($p->getElementsByTagNameNS(W_NS, 'r')) as $r) {
                $r->parentNode->removeChild($r);
            }
        } else {
            $p->parentNode->removeChild($p);
        }
    }
    $ps = dxParagraf($tc);
    if (!$ps) {
        $p = dxBuat($doc, 'p');
        $tc->appendChild($p);
        $ps = [$p];
    }
    $isi = dxParaNilai($doc, $teks);
    foreach (dxAnak($isi, 'r') as $r) $ps[0]->appendChild($r->cloneNode(true));
}

/** Sisipkan nilai tepat setelah paragraf yang memuat label tertentu. */
function dxSisipSetelahLabel(DOMDocument $doc, DOMElement $tc, string $label,
                             string $nilai, bool $link = false): bool {
    if ($nilai === '') return false;
    foreach (dxParagraf($tc) as $p) {
        if (stripos(dxTeks($p), $label) !== false) {
            dxSisipSetelah($p, dxParaNilai($doc, $nilai, $link));
            return true;
        }
    }
    return false;
}

/** Arial + spasi 1.15 + jarak bawah 6pt untuk seluruh dokumen. */
function dxSeragamkanFont(DOMDocument $doc): void {
    foreach ($doc->getElementsByTagNameNS(W_NS, 'r') as $r) {
        $rPr = dxAnak($r, 'rPr')[0] ?? null;
        if (!$rPr) {
            $rPr = dxBuat($doc, 'rPr');
            $r->insertBefore($rPr, $r->firstChild);
        }
        $font = dxAnak($rPr, 'rFonts')[0] ?? null;
        if (!$font) {
            $font = dxBuat($doc, 'rFonts');
            $rPr->insertBefore($font, $rPr->firstChild);
        }
        foreach (['w:ascii', 'w:hAnsi', 'w:cs'] as $a) {
            $font->setAttributeNS(W_NS, $a, 'Arial');
        }
    }
    foreach ($doc->getElementsByTagNameNS(W_NS, 'p') as $p) {
        $pPr = dxAnak($p, 'pPr')[0] ?? null;
        if (!$pPr) {
            $pPr = dxBuat($doc, 'pPr');
            $p->insertBefore($pPr, $p->firstChild);
        }
        $sp = dxAnak($pPr, 'spacing')[0] ?? null;
        if (!$sp) { $sp = dxBuat($doc, 'spacing'); $pPr->appendChild($sp); }
        $sp->setAttributeNS(W_NS, 'w:line', '276');       // 1.15 baris
        $sp->setAttributeNS(W_NS, 'w:lineRule', 'auto');
        $sp->setAttributeNS(W_NS, 'w:after', '120');      // 6pt
    }
}

// ============================================================
// PEMBERSIH NILAI (sama dengan versi Python)
// ============================================================
function dxBersihkan(?string $teks): string {
    if ($teks === null) return '';
    $teks = trim($teks);
    if ($teks === '') return '';
    $baris = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $teks)), 'strlen'));
    if (!$baris) return $teks;
    $hasil = $baris[0];

    // Buang pengulangan kalimat yang sama persis di belakangnya
    $kata = explode(' ', $hasil);
    $n = intdiv(count($kata), 2);
    for ($len = 1; $len <= $n; $len++) {
        $potong = implode(' ', array_slice($kata, 0, $len));
        $sisa = trim(substr($hasil, strlen($potong)));
        if ($sisa !== '' && strpos($sisa, $potong) === 0) return $potong;
    }
    return $hasil;
}

// ============================================================
// DOKUMENTASI — foto ditambahkan di halaman terakhir
// ============================================================

/** Sisipkan potongan XML (satu elemen blok) tepat sebelum node acuan. */
function dxSisipXml(DOMDocument $doc, DOMNode $acuan, string $xml): void {
    $frag = $doc->createDocumentFragment();
    $frag->appendXML($xml);
    $acuan->parentNode->insertBefore($frag, $acuan);
}

/** Paragraf teks sederhana, dengan opsi bold/align/spasi — dipakai khusus bagian dokumentasi. */
function dxParaTeks(string $teks, array $opts = []): string {
    $esc = htmlspecialchars($teks, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $pPr = '<w:pPr>';
    if (!empty($opts['page_break_before'])) $pPr .= '<w:pageBreakBefore/>';
    if (isset($opts['align'])) $pPr .= '<w:jc w:val="' . htmlspecialchars($opts['align'], ENT_XML1) . '"/>';
    if (isset($opts['space_after'])) $pPr .= '<w:spacing w:after="' . (int)($opts['space_after'] * 20) . '"/>';
    $pPr .= '</w:pPr>';
    $b = !empty($opts['bold']) ? '<w:b/>' : '';
    $sz = isset($opts['size']) ? (int)round($opts['size'] * 2) : 22;
    return '<w:p xmlns:w="' . W_NS . '">' . $pPr
         . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>' . $b
         . '<w:sz w:val="' . $sz . '"/><w:szCs w:val="' . $sz . '"/></w:rPr>'
         . '<w:t xml:space="preserve">' . $esc . '</w:t></w:r></w:p>';
}

/** Paragraf berisi satu gambar inline, dicenter. $cx/$cy dalam EMU. */
function dxParaGambar(string $relId, int $cx, int $cy, int $docPrId): string {
    return '<w:p xmlns:w="' . W_NS . '"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:drawing '
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
         . htmlspecialchars($relId, ENT_XML1) . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
         . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
         . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
         . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
}

/**
 * Tambahkan bagian "Dokumentasi" di halaman baru paling akhir, isinya galeri foto.
 * $dokumentasi = list of ['path' => absolute path, 'keterangan' => string|null]
 * Dipanggil SEBELUM $zip->close() — perlu $zip untuk menaruh file media & relasi baru.
 */
function dxTambahDokumentasi(DOMDocument $doc, ZipArchive $zip, array $dokumentasi): array {
    if (!$dokumentasi) return ['foto_terpasang' => 0, 'foto_gagal' => []];

    $body = $doc->getElementsByTagNameNS(W_NS, 'body')->item(0);
    $sectPrList = $doc->getElementsByTagNameNS(W_NS, 'sectPr');
    $sectPr = $sectPrList->length ? $sectPrList->item($sectPrList->length - 1) : null;
    if (!$body) return ['foto_terpasang' => 0, 'foto_gagal' => ['body tidak ditemukan']];
    $acuan = $sectPr ?: null; // null → tambahkan di akhir body (appendChild dipakai lewat body)

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

    // Nomor gambar berikutnya yang aman (hindari bentrok nama file dgn media yang sudah ada)
    $imgIndex = 1;
    for ($i = $zip->numFiles - 1; $i >= 0; $i--) {
        $nm = $zip->getNameIndex($i);
        if (preg_match('#^word/media/image(\d+)\.#', $nm, $m)) {
            $imgIndex = max($imgIndex, (int)$m[1] + 1);
        }
    }

    $sisip = function (string $xml) use ($doc, $body, $acuan): void {
        if ($acuan) { dxSisipXml($doc, $acuan, $xml); }
        else { $frag = $doc->createDocumentFragment(); $frag->appendXML($xml); $body->appendChild($frag); }
    };

    $sisip(dxParaTeks('Dokumentasi', ['bold' => true, 'size' => 14, 'align' => 'center',
                                       'space_after' => 10, 'page_break_before' => true]));

    $fotoTerpasang = 0; $fotoGagal = []; $newRels = '';

    foreach ($dokumentasi as $foto) {
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

        // Kotak maksimal 13x9cm — foto potret & lanskap sama-sama proporsional, tak dominasi halaman
        $maxW = (int)round(13.0 * 360000);
        $maxH = (int)round(9.0 * 360000);
        $cx = $maxW; $cy = (int)round($maxW * $hPx / $wPx);
        if ($cy > $maxH) { $cy = $maxH; $cx = (int)round($maxH * $wPx / $hPx); }

        $sisip(dxParaGambar($rid, $cx, $cy, $imgIndex));
        $imgIndex++;
        $fotoTerpasang++;

        $ket = trim((string)($foto['keterangan'] ?? ''));
        $sisip(dxParaTeks($ket !== '' ? $ket : ' ', ['align' => 'center', 'size' => 9, 'space_after' => 12]));
    }

    if ($newRels !== '') {
        $frag = $relsDoc->createDocumentFragment();
        $frag->appendXML($newRels);
        $relsRoot->appendChild($frag);
        $zip->addFromString('word/_rels/document.xml.rels', $relsDoc->saveXML());
    }

    return ['foto_terpasang' => $fotoTerpasang, 'foto_gagal' => $fotoGagal];
}

// ============================================================
// PEMERIKSAAN TEMPLATE
// ============================================================
function dxPeriksaTemplate(DOMDocument $doc): ?string {
    $awal = '';
    $n = 0;
    foreach ($doc->getElementsByTagNameNS(W_NS, 'p') as $p) {
        $awal .= ' ' . dxTeks($p);
        if (++$n >= 12) break;
    }
    if (stripos($awal, 'TRIWULAN XX') === false) {
        return "File template sepertinya bukan dokumen kosong — penanda 'TRIWULAN XX' "
             . "tidak ditemukan. Sepertinya yang dipakai adalah notula hasil generate, "
             . "bukan template asli. Ganti tools/notula/template/word_FRA.docx dengan "
             . "template resmi yang masih kosong.";
    }
    return null;
}

// ============================================================
// FUNGSI UTAMA
// ============================================================
/**
 * @param array  $data     satker, nilai_sakip, predikat, triwulan, tahun, agenda, iku_list
 * @param string $template path word_FRA.docx
 * @param string $output   path hasil
 * @return array           ringkasan hasil
 * @throws RuntimeException
 */
function isiNotulaDocx(array $data, string $template, string $output): array {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi PHP "zip" tidak aktif. Aktifkan lewat hPanel → PHP Configuration.');
    }
    if (!file_exists($template)) {
        throw new RuntimeException('Template tidak ditemukan: ' . $template);
    }

    // Salin template lalu ubah salinannya — template asli tidak pernah tersentuh
    if (!copy($template, $output)) {
        throw new RuntimeException('Gagal menyalin template ke ' . $output);
    }

    $zip = new ZipArchive();
    if ($zip->open($output) !== true) {
        @unlink($output);
        throw new RuntimeException('Gagal membuka .docx sebagai arsip ZIP.');
    }

    $xml = $zip->getFromName('word/document.xml');
    if ($xml === false) {
        $zip->close(); @unlink($output);
        throw new RuntimeException('word/document.xml tidak ada di dalam template.');
    }

    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = true;
    $doc->formatOutput = false;
    if (!$doc->loadXML($xml, LIBXML_PARSEHUGE)) {
        $zip->close(); @unlink($output);
        throw new RuntimeException('Isi document.xml tidak bisa dibaca.');
    }

    if ($masalah = dxPeriksaTemplate($doc)) {
        $zip->close(); @unlink($output);
        throw new RuntimeException($masalah);
    }

    $satker = (string)($data['satker'] ?? 'BPS Kabupaten/Kota');
    $tw     = (string)($data['triwulan'] ?? 'I');
    $tahun  = (string)($data['tahun'] ?? date('Y'));
    $agenda = $data['agenda'] ?? [];
    $ikuList = $data['iku_list'] ?? [];

    $twKata = ['I' => 'pertama', 'II' => 'kedua', 'III' => 'ketiga', 'IV' => 'keempat'];
    $kata = $twKata[$tw] ?? $tw;

    // ---- 1. Penggantian teks global ----
    dxGantiSemua($doc, [
        'MONITORING KINERJA TRIWULAN XX TAHUN 2026' => "MONITORING KINERJA TRIWULAN $tw TAHUN $tahun",
        'BPS PROVINSI xxxxxx/ BPS KABUPATEN/KOTA yyy'  => mb_strtoupper($satker, 'UTF-8'),
        'BPS PROVINSI xxxxxx / BPS KABUPATEN/KOTA yyy' => mb_strtoupper($satker, 'UTF-8'),
        'Monitoring Kinerja Triwulan XX Tahun 2026' => "Monitoring Kinerja Triwulan $tw Tahun $tahun",
        'BPS Provinsi xxx / BPS Kabupaten/kota yyy' => $satker,
        'Capaian Kinerja IKU triwulan xx tahun 2026 pada BPS Provinsi xxx / BPS Kabupaten/kota yyy sebesar … persen.'
            => "Capaian Kinerja IKU triwulan $kata tahun $tahun pada $satker.",
        'Capaian Kinerja IKU triwulan xx tahun 2026 pada BPS Provinsi xxx / BPS Kabupaten/kota yyy sebesar ... persen.'
            => "Capaian Kinerja IKU triwulan $kata tahun $tahun pada $satker.",
        'Capaian Kinerja Triwulan XX Tahun 2026' => "Capaian Kinerja Triwulan $tw Tahun $tahun",
        'Triwulan XX Tahun 2026' => "Triwulan $tw Tahun $tahun",
        'Triwulan XX'            => "Triwulan $tw",
        'triwulan xx tahun 2026' => "triwulan $kata tahun $tahun",
    ]);

    dxPerbaikiJudulSatker($doc, $satker);

    $tabel = iterator_to_array($doc->getElementsByTagNameNS(W_NS, 'tbl'));

    // ---- 2. Tabel agenda (tabel pertama) ----
    if ($tabel) {
        $peta = [
            'hari/tanggal'   => $agenda['hari_tanggal'] ?? '',
            'waktu'          => $agenda['waktu'] ?? '',
            'tempat'         => $agenda['tempat'] ?? '',
            'pimpinan rapat' => $agenda['pimpinan'] ?? '',
        ];
        foreach (dxAnak($tabel[0], 'tr') as $tr) {
            $sel = dxSel($tr);
            if (count($sel) < 2) continue;
            $label = strtolower(trim(dxTeks($sel[0])));
            foreach ($peta as $k => $v) {
                if ($label === $k && $v !== '') dxIsiSel($doc, $sel[1], (string)$v);
            }
        }
    }

    // ---- 3. Blok tanda tangan (tabel terakhir) ----
    if ($tabel) {
        $akhir   = $tabel[count($tabel) - 1];
        $kepala  = (string)($agenda['kepala_satker'] ?? '');
        $notulis = (string)($agenda['notulis'] ?? '');
        $ttd     = (string)($agenda['tempat_tanggal_ttd'] ?? '');

        foreach (dxAnak($akhir, 'tr') as $tr) {
            foreach (dxSel($tr) as $tc) {
                $teks = dxTeks($tc);
                if (strpos($teks, 'Kepala Satker') !== false && $kepala !== '') {
                    foreach (dxParagraf($tc) as $p) {
                        if (trim(dxTeks($p)) === 'xxx') { dxGantiTeksParagraf($p, 'xxx', $kepala); break; }
                    }
                }
                if (strpos($teks, 'Notulis') !== false) {
                    if ($ttd !== '') {
                        foreach (dxParagraf($tc) as $p) {
                            if (strpos(dxTeks($p), 'Tempat, Tanggal') !== false) {
                                dxGantiTeksParagraf($p, 'Tempat, Tanggal', $ttd); break;
                            }
                        }
                    }
                    if ($notulis !== '') {
                        foreach (dxParagraf($tc) as $p) {
                            if (trim(dxTeks($p)) === 'xxx') { dxGantiTeksParagraf($p, 'xxx', $notulis); break; }
                        }
                    }
                }
            }
        }
    }

    // ---- 4. Isi tiap IKU ----
    $terpakai = [];
    $terisi = 0;
    $tanpaTabel = [];

    foreach ($ikuList as $iku) {
        $kode    = (string)($iku['kode'] ?? '');
        $penanda = str_replace('.', '', $kode);

        $target = null;
        $dipakai = $penanda;
        foreach ([$penanda, $kode] as $bentuk) {
            foreach ($tabel as $idx => $t) {
                if (isset($terpakai[$idx])) continue;
                if (strpos(dxTeks($t), $bentuk) !== false) {
                    $target = $t; $dipakai = $bentuk; $terpakai[$idx] = true;
                    break 2;
                }
            }
        }
        if (!$target) { $tanpaTabel[] = $kode; continue; }
        $terisi++;

        $nilai = function ($k) use ($iku) { return (string)($iku[$k] ?? ''); };

        foreach (dxAnak($target, 'tr') as $tr) {
            $sel = dxSel($tr);
            if (!$sel) continue;
            $barisTeks = dxTeks($tr);

            // Baris data utama
            if (strpos(dxTeks($sel[0]), $dipakai) !== false) {
                foreach ($sel[0]->getElementsByTagNameNS(W_NS, 't') as $t) {
                    if (strpos($t->nodeValue, $dipakai) !== false) {
                        $t->nodeValue = str_replace($dipakai, $kode, $t->nodeValue);
                    }
                }
                $targetPk = trim($nilai('target_pk') . ' ' . $nilai('satuan'));
                $kolom = [2 => $targetPk, 3 => $nilai('alokasi_tw'), 4 => $nilai('real_tw'),
                          5 => $nilai('capaian_tw'), 6 => $nilai('capaian_pk')];
                foreach ($kolom as $pos => $isi) {
                    if (isset($sel[$pos]) && trim(dxTeks($sel[$pos])) === '' && $isi !== '') {
                        dxIsiSel($doc, $sel[$pos], $isi);
                    }
                }
            }

            // Realisasi RO + Kendala + Solusi
            if (strpos($barisTeks, 'Kendala') !== false && strpos($barisTeks, 'Solusi') !== false) {
                foreach ($sel as $tc) {
                    $t = dxTeks($tc);
                    if (strpos($t, 'Kendala') === false || strpos($t, 'Solusi') === false) continue;
                    if ($nilai('ro_narasi') !== '') {
                        dxSisipSetelahLabel($doc, $tc, 'Realisasi Volume RO', $nilai('ro_narasi'));
                    }
                    foreach (dxParagraf($tc) as $p) {
                        $isi = trim(dxTeks($p));
                        if (strpos($isi, 'Kendala') === 0 && $nilai('kendala') !== '') {
                            dxSisipSetelah($p, dxParaNilai($doc, dxBersihkan($nilai('kendala'))));
                        } elseif (strpos($isi, 'Solusi') === 0 && $nilai('solusi') !== '') {
                            dxSisipSetelah($p, dxParaNilai($doc, dxBersihkan($nilai('solusi'))));
                        }
                    }
                }
            }

            // RTL, PIC, batas waktu
            if (strpos($barisTeks, 'Rencana Tindak Lanjut') !== false && strpos($barisTeks, 'PIC') !== false) {
                foreach ($sel as $tc) {
                    $t = dxTeks($tc);
                    if (strpos($t, 'Rencana Tindak Lanjut') !== false && strpos($t, 'PIC') === false) {
                        dxSisipSetelahLabel($doc, $tc, 'Rencana Tindak Lanjut', dxBersihkan($nilai('rtl')));
                    }
                    if (strpos($t, 'PIC Tindak Lanjut') !== false) {
                        dxSisipSetelahLabel($doc, $tc, 'Batas Waktu Tindak Lanjut', dxBersihkan($nilai('batas')));
                        dxSisipSetelahLabel($doc, $tc, 'PIC Tindak Lanjut', dxBersihkan($nilai('pic')));
                    }
                }
            }

            // Tautan bukti dukung realisasi
            if (strpos($barisTeks, 'Tautan Bukti Dukung Realisasi IKU') !== false
                && strpos($barisTeks, 'Rencana') === false) {
                foreach ($sel as $tc) {
                    if (strpos(dxTeks($tc), 'Tautan Bukti Dukung Realisasi IKU') !== false) {
                        dxSisipSetelahLabel($doc, $tc, 'Tautan Bukti Dukung Realisasi IKU',
                                            dxBersihkan($nilai('link_bukti')), true);
                    }
                }
            }

            // Tautan tindak lanjut triwulan sebelumnya
            if (strpos($barisTeks, 'Tautan Bukti Dukung Rencana Tindak Lanjut Triwulan Sebelumnya') !== false) {
                foreach ($sel as $tc) {
                    if (strpos(dxTeks($tc), 'Tautan Bukti Dukung Rencana Tindak Lanjut') !== false) {
                        dxSisipSetelahLabel($doc, $tc, 'Tautan Bukti Dukung Rencana Tindak Lanjut',
                                            dxBersihkan($nilai('link_tl_sblm')), true);
                    }
                }
            }
        }
    }

    // ---- 5. Format & simpan ----
    dxSeragamkanFont($doc);

    // ---- 6. Dokumentasi (opsional) — ditambahkan di halaman baru paling akhir ----
    $infoDok = dxTambahDokumentasi($doc, $zip, $data['dokumentasi'] ?? []);

    $zip->addFromString('word/document.xml', $doc->saveXML());
    $zip->close();

    return [
        'iku_total'       => count($ikuList),
        'iku_terisi'      => $terisi,
        'iku_tanpa_tabel' => $tanpaTabel,
        'dok_terpasang'   => $infoDok['foto_terpasang'],
        'dok_gagal'       => $infoDok['foto_gagal'],
    ];
}
