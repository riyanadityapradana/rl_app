<?php
require '../assets/vendor/autoload.php';
if (ob_get_length()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


// ======== PARAMETER BULAN & TAHUN ========
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// ======== BUAT TABEL MAPPING JIKA BELUM ADA ========
$config->query("
    CREATE TABLE IF NOT EXISTS mapping_poli_rl35 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nm_poli VARCHAR(100) NOT NULL UNIQUE,
        jenis_kegiatan VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ======== GENERATE MAPPING OTOMATIS DARI POLIKLINIK ========
$poliRes = $config->query("SELECT DISTINCT nm_poli FROM poliklinik");
while ($row = $poliRes->fetch_assoc()) {
    $nm_poli = $row['nm_poli'];
    $cek = $config->prepare("SELECT id FROM mapping_poli_rl35 WHERE nm_poli=?");
    $cek->bind_param("s", $nm_poli);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows == 0) {
        $lower = strtolower($nm_poli);
        if (strpos($lower, 'kandungan') !== false) $jenis = 'Poli Kandungan';
        elseif (strpos($lower, 'anak') !== false) $jenis = 'Poli Anak';
        elseif (strpos($lower, 'bedah') !== false) $jenis = 'Poli Bedah';
        elseif (strpos($lower, 'gigi') !== false) $jenis = 'Poli Gigi';
        elseif (strpos($lower, 'mata') !== false) $jenis = 'Poli Mata';
        elseif (strpos($lower, 'penyakit dalam') !== false) $jenis = 'Poli Penyakit Dalam';
        elseif (strpos($lower, 'syaraf') !== false || strpos($lower, 'neurolog') !== false) $jenis = 'Poli Syaraf';
        elseif (strpos($lower, 'jiwa') !== false || strpos($lower, 'kejiwa') !== false) $jenis = 'Poli Jiwa';
        elseif (strpos($lower, 'kulit') !== false || strpos($lower, 'kelamin') !== false) $jenis = 'Poli Kulit & Kelamin';
        else $jenis = 'Lain-lain';
        $ins = $config->prepare("INSERT INTO mapping_poli_rl35 (nm_poli, jenis_kegiatan) VALUES (?, ?)");
        $ins->bind_param("ss", $nm_poli, $jenis);
        $ins->execute();
    }
    $cek->close();
}

// ======== QUERY RL 3.5 ========

$sql = "
SELECT 
    CASE 
        WHEN pl.nm_poli LIKE '%Kandungan%' THEN 'Poli Kandungan'
        WHEN pl.nm_poli LIKE '%Anak%' THEN 'Poli Anak'
        WHEN pl.nm_poli LIKE '%Bedah%' THEN 'Poli Bedah'
        WHEN pl.nm_poli LIKE '%Gigi%' THEN 'Poli Gigi'
        WHEN pl.nm_poli LIKE '%Mata%' THEN 'Poli Mata'
        WHEN pl.nm_poli LIKE '%Jantung%' THEN 'Poli Jantung'
        WHEN pl.nm_poli LIKE '%Kejiwaan%' THEN 'Poli Kejiwaan'
        WHEN pl.nm_poli LIKE '%P.Dalam%' THEN 'Poli Penyakit Dalam'
        WHEN pl.nm_poli LIKE '%Syaraf%' OR pl.nm_poli LIKE '%Neurolog%' THEN 'Poli Syaraf'
        WHEN pl.nm_poli LIKE '%Jiwa%' OR pl.nm_poli LIKE '%Kejwa%' THEN 'Poli Jiwa'
        WHEN pl.nm_poli LIKE '%Kulit%' OR pl.nm_poli LIKE '%Kelamin%' THEN 'Poli Kulit & Kelamin'
        ELSE pl.nm_poli
    END AS 'Jenis Kegiatan',
    SUM(CASE WHEN ps.jk='L' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Dalam Kab/Kota Laki-laki',
    SUM(CASE WHEN ps.jk='P' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Dalam Kab/Kota Perempuan',
    SUM(CASE WHEN ps.jk='L' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Luar Kab/Kota Laki-laki',
    SUM(CASE WHEN ps.jk='P' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Luar Kab/Kota Perempuan',
    COUNT(r.no_rawat) AS 'Total Kunjungan'
FROM reg_periksa r
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
LEFT JOIN kabupaten k ON ps.kd_kab = k.kd_kab
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien IS NOT NULL
  AND ps.nm_pasien <> ''
  AND pl.nm_poli NOT REGEXP 'TEST|PONEK|OBGYN|Vaksin|Gizi|HEMODIALISA|Laboratorium|Rawat Inap|IGD|UGD'
GROUP BY 
    CASE 
        WHEN pl.nm_poli LIKE '%Kandungan%' THEN 'Poli Kandungan'
		WHEN pl.nm_poli LIKE '%Anak%' THEN 'Poli Anak'
		WHEN pl.nm_poli LIKE '%Bedah%' THEN 'Poli Bedah'
		WHEN pl.nm_poli LIKE '%Gigi%' THEN 'Poli Gigi'
		WHEN pl.nm_poli LIKE '%Mata%' THEN 'Poli Mata'
		WHEN pl.nm_poli LIKE '%Jantung%' THEN 'Poli Jantung'
		WHEN pl.nm_poli LIKE '%Kejiwaan%' THEN 'Poli Kejiwaan'
		WHEN pl.nm_poli LIKE '%P.Dalam%' THEN 'Poli Penyakit Dalam'
		WHEN pl.nm_poli LIKE '%Syaraf%' OR pl.nm_poli LIKE '%Neurolog%' THEN 'Poli Syaraf'
		WHEN pl.nm_poli LIKE '%Jiwa%' OR pl.nm_poli LIKE '%Kejwa%' THEN 'Poli Jiwa'
		WHEN pl.nm_poli LIKE '%Kulit%' OR pl.nm_poli LIKE '%Kelamin%' THEN 'Poli Kulit & Kelamin'
        ELSE pl.nm_poli
    END

UNION ALL

SELECT 
    'TOTAL' AS 'Jenis Kegiatan',
    SUM(CASE WHEN ps.jk='L' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END),
    SUM(CASE WHEN ps.jk='P' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END),
    SUM(CASE WHEN ps.jk='L' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END),
    SUM(CASE WHEN ps.jk='P' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END),
    COUNT(r.no_rawat)
FROM reg_periksa r
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
LEFT JOIN kabupaten k ON ps.kd_kab = k.kd_kab
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien IS NOT NULL
  AND ps.nm_pasien <> ''
  AND pl.nm_poli NOT REGEXP 'TEST|PONEK|OBGYN|Vaksin|Gizi|HEMODIALISA|Laboratorium|Rawat Inap|IGD|UGD'

UNION ALL

SELECT 
    'Rata-Rata Hari Poliklinik Buka' AS 'Jenis Kegiatan',
    NULL, NULL, NULL, NULL,
    ROUND(COUNT(DISTINCT CONCAT(j.kd_poli, j.hari_kerja)) / COUNT(DISTINCT j.kd_poli), 2)
FROM jadwal j

UNION ALL

SELECT 
    'Rata-Rata Kunjungan per Hari' AS 'Jenis Kegiatan',
    NULL, NULL, NULL, NULL,
    ROUND(
        COUNT(r.no_rawat) /
        (SELECT ROUND(COUNT(DISTINCT CONCAT(j.kd_poli, j.hari_kerja)) / COUNT(DISTINCT j.kd_poli), 2) FROM jadwal j)
    , 2)
FROM reg_periksa r
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien IS NOT NULL
  AND ps.nm_pasien <> ''
  AND pl.nm_poli NOT REGEXP 'TEST|PONEK|OBGYN|Vaksin|Gizi|HEMODIALISA|Laboratorium|Rawat Inap|IGD|UGD'
";

$stmt = $config->prepare($sql);
$stmt->bind_param("iiiiii", $bulan, $tahun, $bulan, $tahun, $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ======== BUAT FILE EXCEL ========
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('RL3.5_' . $bulan . '_' . $tahun);

$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Judul
$sheet->mergeCells('A1:G1')->setCellValue('A1', 'RL 3.5 REKAPITULASI KUNJUNGAN POLIKLINIK');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->mergeCells('A2:G2')->setCellValue('A2', 'Periode: ' . sprintf('%02d', $bulan) . '-' . $tahun);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header
$headers = ['No','Jenis Kegiatan','Dalam Kab/Kota Laki-laki','Dalam Kab/Kota Perempuan','Luar Kab/Kota Laki-laki','Luar Kab/Kota Perempuan','Total Kunjungan'];
$sheet->fromArray($headers, NULL, 'A4');

// Style header
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D9D9D9']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$sheet->getStyle('A4:G4')->applyFromArray($headerStyle);

// ======== ISI DATA ========

$rowNum = 5;
foreach ($data as $idx => $row) {
    // Penomoran khusus
    if ($row['Jenis Kegiatan'] === 'TOTAL') {
        $no = 99;
    } elseif ($row['Jenis Kegiatan'] === 'Rata-Rata Hari Poliklinik Buka') {
        $no = 66;
    } elseif ($row['Jenis Kegiatan'] === 'Rata-Rata Kunjungan per Hari') {
        $no = 77;
    } else {
        $no = $idx + 1;
    }

    // Format baris khusus rata-rata
    if ($row['Jenis Kegiatan'] === 'Rata-Rata Hari Poliklinik Buka' || $row['Jenis Kegiatan'] === 'Rata-Rata Kunjungan per Hari') {
        $sheet->setCellValue('A'.$rowNum, $no);
        // Gabung kolom B-F
        $sheet->mergeCells("B{$rowNum}:F{$rowNum}");
        $sheet->setCellValue('B'.$rowNum, $row['Jenis Kegiatan']);
        $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('G'.$rowNum, $row['Total Kunjungan'] ?? 0);
        $sheet->getStyle("A{$rowNum}:G{$rowNum}")->getFont()->setBold(true);
    } else {
        $sheet->setCellValue('A'.$rowNum, $no);
        $sheet->setCellValue('B'.$rowNum, $row['Jenis Kegiatan']);
        $sheet->setCellValue('C'.$rowNum, $row['Dalam Kab/Kota Laki-laki'] ?? 0);
        $sheet->setCellValue('D'.$rowNum, $row['Dalam Kab/Kota Perempuan'] ?? 0);
        $sheet->setCellValue('E'.$rowNum, $row['Luar Kab/Kota Laki-laki'] ?? 0);
        $sheet->setCellValue('F'.$rowNum, $row['Luar Kab/Kota Perempuan'] ?? 0);
        $sheet->setCellValue('G'.$rowNum, $row['Total Kunjungan'] ?? 0);
        if ($row['Jenis Kegiatan'] === 'TOTAL') {
            $sheet->getStyle("A{$rowNum}:G{$rowNum}")->getFont()->setBold(true);
        }
    }
    $rowNum++;
    // Baris kosong setelah TOTAL
    if ($row['Jenis Kegiatan'] === 'TOTAL') {
        $sheet->setCellValue('A'.$rowNum, '');
        $sheet->setCellValue('B'.$rowNum, '');
        $sheet->setCellValue('C'.$rowNum, '');
        $sheet->setCellValue('D'.$rowNum, '');
        $sheet->setCellValue('E'.$rowNum, '');
        $sheet->setCellValue('F'.$rowNum, '');
        $sheet->setCellValue('G'.$rowNum, '');
        $rowNum++;
    }
}

// Border seluruh tabel
$lastRow = $sheet->getHighestRow();
$sheet->getStyle("A4:G$lastRow")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
]);

foreach (range('A','G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// ======== OUTPUT FILE ========
$filename = "RL3.5_Kunjungan_Poliklinik_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
