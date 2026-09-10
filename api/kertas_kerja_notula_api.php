<?php
/**
 * SOKAB - Generate Notula Word Document
 * Converts Kertas Kerja data to Word document (.docx)
 */

session_start();
require_once __DIR__ . '/../includes/check_session.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = $_GET['id'] ?? null;

if (!$id) {
    die('ID required');
}

$db = getDBConnection();

// Get kertas kerja
$stmt = $db->prepare("SELECT * FROM kertas_kerja WHERE id = :id");
$stmt->execute(['id' => $id]);
$kk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kk) {
    die('Kertas Kerja not found');
}

// Get details
$stmt = $db->prepare("
    SELECT * FROM kertas_kerja_detail 
    WHERE kertas_kerja_id = :id 
    ORDER BY row_order ASC
");
$stmt->execute(['id' => $id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate Word document
$filename = 'Notula_Monitoring_Kinerja_' . preg_replace('/[^a-zA-Z0-9]/', '', $kk['satker']) . '_TW' . $kk['triwulan'] . '_' . $kk['tahun'] . '.docx';

// Create temporary directory for DOCX
$temp_dir = sys_get_temp_dir() . '/' . uniqid('notula_');
mkdir($temp_dir);

// Create document structure
createDocxStructure($temp_dir, $kk, $details);

// Create ZIP archive
$temp_file = sys_get_temp_dir() . '/' . uniqid('notula_') . '.docx';
createZipArchive($temp_dir, $temp_file);

// Send to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($temp_file));

readfile($temp_file);

// Cleanup
unlink($temp_file);
deleteDirectory($temp_dir);
exit;

// ============================================================
// Helper Functions
// ============================================================

function createDocxStructure($dir, $kk, $details) {
    // Create folder structure
    mkdir($dir . '/_rels');
    mkdir($dir . '/word');
    mkdir($dir . '/word/_rels');
    mkdir($dir . '/docProps');

    // Create [Content_Types].xml
    $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml"/>
</Types>';
    file_put_contents($dir . '/[Content_Types].xml', $content_types);

    // Create _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/app.xml"/>
</Relationships>';
    file_put_contents($dir . '/_rels/.rels', $rels);

    // Create word/document.xml (main content)
    $document = generateDocumentXml($kk, $details);
    file_put_contents($dir . '/word/document.xml', $document);

    // Create word/_rels/document.xml.rels
    $doc_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>';
    file_put_contents($dir . '/word/_rels/document.xml.rels', $doc_rels);

    // Create docProps/core.xml
    $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
  <dc:title>Notula Monitoring Kinerja</dc:title>
  <dc:creator>SOKAB</dc:creator>
  <dcterms:created>'. date('Y-m-d\TH:i:s\Z') .'</dcterms:created>
</cp:coreProperties>';
    file_put_contents($dir . '/docProps/core.xml', $core);

    // Create docProps/app.xml
    $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <TotalTime>0</TotalTime>
  <Application>SOKAB</Application>
</Properties>';
    file_put_contents($dir . '/docProps/app.xml', $app);
}

function generateDocumentXml($kk, $details) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    <!-- Header -->
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="240" w:after="240"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="32"/>
        </w:rPr>
        <w:t>NOTULA MONITORING KINERJA</w:t>
      </w:r>
    </w:p>

    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:after="240"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:sz w:val="24"/>
        </w:rPr>
        <w:t>' . htmlspecialchars($kk['satker']) . ' - Triwulan ' . $kk['triwulan'] . ' Tahun ' . $kk['tahun'] . '</w:t>
      </w:r>
    </w:p>

    <!-- Info -->
    <w:p>
      <w:pPr><w:spacing w:after="120"/></w:pPr>
      <w:r>
        <w:rPr><w:b/></w:rPr>
        <w:t>Informasi Umum:</w:t>
      </w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="60" w:before="60"/></w:pPr>
      <w:r><w:t>Satuan Kerja: ' . htmlspecialchars($kk['satker']) . '</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="60" w:before="60"/></w:pPr>
      <w:r><w:t>Triwulan: ' . $kk['triwulan'] . '</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="60" w:before="60"/></w:pPr>
      <w:r><w:t>Nilai SAKIP: ' . ($kk['nilai_sakip'] ? $kk['nilai_sakip'] . ' (' . htmlspecialchars($kk['predikat']) . ')' : '-') . '</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="240"/></w:pPr>
    </w:p>

    <!-- Table Title -->
    <w:p>
      <w:pPr><w:spacing w:after="120"/></w:pPr>
      <w:r>
        <w:rPr><w:b/></w:rPr>
        <w:t>Capaian Kinerja Indikator:</w:t>
      </w:r>
    </w:p>';

    // Add table
    $xml .= generateTable($details);

    $xml .= '
    <!-- Footer -->
    <w:p>
      <w:pPr><w:spacing w:before="240"/></w:pPr>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/></w:pPr>
      <w:r>
        <w:rPr><w:i/></w:rPr>
        <w:t>Dokumen ini di-generate oleh SOKAB pada ' . date('d/m/Y H:i:s') . '</w:t>
      </w:r>
    </w:p>

  </w:body>
</w:document>';

    return $xml;
}

function generateTable($details) {
    $xml = '<w:tbl>
    <w:tblPr>
      <w:tblW w:w="9000" w:type="auto"/>
      <w:tblBorders>
        <w:top w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        <w:left w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        <w:bottom w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        <w:right w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        <w:insideH w:val="single" w:sz="12" w:space="0" w:color="000000"/>
        <w:insideV w:val="single" w:sz="12" w:space="0" w:color="000000"/>
      </w:tblBorders>
    </w:tblPr>

    <!-- Header Row -->
    <w:tr>
      <w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>No</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kode IKU</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Nama IKU</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Target</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Realisasi</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Capaian</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kendala</w:t></w:r></w:p></w:tc>
      <w:tc><w:tcPr><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>RTL</w:t></w:r></w:p></w:tc>
    </w:tr>';

    // Data rows
    foreach ($details as $i => $detail) {
        $xml .= '
    <w:tr>
      <w:tc><w:p><w:r><w:t>' . ($i + 1) . '</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . htmlspecialchars($detail['iku_code'] ?? '') . '</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . htmlspecialchars($detail['iku_nama'] ?? '') . '</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . ($detail['target_pk'] ?? '') . ' ' . htmlspecialchars($detail['satuan'] ?? '') . '</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . ($detail['realisasi'] ?? '') . ' ' . htmlspecialchars($detail['satuan'] ?? '') . '</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . ($detail['capaian_tw'] ?? '') . '%</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . htmlspecialchars(substr($detail['kendala'] ?? '', 0, 50)) . '...</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t>' . htmlspecialchars(substr($detail['rtl'] ?? '', 0, 50)) . '...</w:t></w:r></w:p></w:tc>
    </w:tr>';
    }

    $xml .= '
  </w:tbl>';

    return $xml;
}

function createZipArchive($source_dir, $output_file) {
    $zip = new ZipArchive();
    
    if ($zip->open($output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die('Could not create ZIP');
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($source_dir) + 1);
            $zip->addFile($file_path, $relative_path);
        }
    }

    $zip->close();
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
    }
    
    rmdir($dir);
}
?>
