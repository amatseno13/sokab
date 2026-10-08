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
        $this->SetX(self::M + $indent);
        $this->MultiCell(self::W - $indent, $size * 0.5, self::t($txt), 0, $align);
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

    /** Pasang daftar foto (maks 11×8 cm, di tengah) beserta keterangannya. */
    function foto(array $list): void {
        foreach ($list as $f) {
            $path = $f['path'] ?? '';
            if (!$path || !is_file($path)) { $this->fotoGagal[] = $path ?: '(kosong)'; continue; }
            $u = @getimagesize($path);
            if (!$u || $u[0] <= 0 || $u[1] <= 0) { $this->fotoGagal[] = $path . ' (bukan gambar valid)'; continue; }
            if (!in_array($u['mime'] ?? '', ['image/jpeg', 'image/png'], true)) { $this->fotoGagal[] = $path . ' (tipe tidak didukung)'; continue; }
            $berkas = $this->jpegSementara($path, $u['mime'], $u[0], $u[1]);
            if (!$berkas) { $this->fotoGagal[] = $path . ' (gagal diproses)'; continue; }

            $w = 110; $h = $w * $u[1] / $u[0];
            if ($h > 80) { $h = 80; $w = $h * $u[0] / $u[1]; }
            if ($this->GetY() + $h > $this->PageBreakTrigger) $this->AddPage();
            try {
                $this->Image($berkas, (210 - $w) / 2, $this->GetY(), $w, $h, 'JPG');
            } catch (Throwable $e) { $this->fotoGagal[] = $path . ' (' . $e->getMessage() . ')'; continue; }
            $this->SetY($this->GetY() + $h + 2);
            $this->fotoOk++;

            $ket = trim((string)($f['keterangan'] ?? ''));
            if ($ket !== '') { $this->SetTextColor(100, 116, 139); $this->para($ket, 8.5, false, 'C', 0, 3); $this->SetTextColor(0); }
            else $this->Ln(3);
        }
    }

    function batasHalaman(): float { return $this->PageBreakTrigger; }

    function bersihkan(): void { foreach ($this->tmp as $f) @unlink($f); }
}

function buatDokumenSumberPdf(array $d, ?array &$info = null): string {
    $p = new PdfDokumenSumber();
    $roTerisi = 0;

    if (!empty($d['hanya_solusi'])) {
        $p->para('Bukti Dokumen Sumber Solusi Dari Kendala', 14, true, 'C', 0, 1);
        $p->para('Triwulan ' . ($d['triwulan'] ?? '') . ' ' . ($d['tahun'] ?? ''), 14, true, 'C', 0, 3);
        $p->para(trim(($d['kode'] ?? '') . ' ' . ($d['nama'] ?? '')), 12, true, 'C', 0, 5);

        $kendala = dsParsePoin($d['kendala'] ?? '');
        $solusi  = dsParsePoin($d['solusi'] ?? '');

        $p->para('Masalah :', 11, true, 'L', 0, 1);
        foreach ($kendala['judul'] as $j) $p->para($j, 11, true, 'L', 0, 1);
        foreach ($kendala['poin'] ?: ['-'] as $i => $k) $p->para(($kendala['poin'] ? ($i + 1) . '. ' : '') . $k, 11, false, 'J', 0, 1);
        $p->Ln(3);

        $p->para('Solusi :', 11, true, 'L', 0, 1);
        foreach ($solusi['judul'] as $j) $p->para($j, 11, true, 'L', 0, 1);
        foreach ($solusi['poin'] ?: ['-'] as $i => $s) {
            $p->para(($solusi['poin'] ? ($i + 1) . '. ' : '') . $s, 11, false, 'J', 0, 2);
            $p->foto($d['solusi_foto'][$i + 1] ?? []);
        }
    } else {
        // ── Judul ──
        $p->para('Bukti Dokumen Sumber TW ' . ($d['triwulan'] ?? '') . ' ' . ($d['tahun'] ?? ''), 13, true, 'C', 0, 0.5);
        $p->para((string)($d['satker'] ?? ''), 13, true, 'C', 0, 5);

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
            $list = dsBacaList($d[$kunci] ?? '');
            if (!$list) continue;
            $p->para($label . ' :', 10, true, 'L', 0, 1);
            foreach ($list as $i => $t) $p->para(($i + 1) . '. ' . $t, 10, false, 'J', 0, 1);
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

    $info = ['ro_terisi' => $roTerisi, 'foto_terpasang' => $p->fotoOk, 'foto_gagal' => $p->fotoGagal];
    $out = $p->Output('S');
    $p->bersihkan();
    return $out;
}
