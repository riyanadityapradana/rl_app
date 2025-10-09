<?php
require '../assets/vendor/autoload.php';
if (ob_get_length()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Ambil input bulan dan tahun dari GET, default bulan dan tahun sekarang
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');

// Query rekap pengunjung
$sql = "
SELECT 
    'Pengunjung Baru' AS jenis_pengunjung,
    COUNT(DISTINCT r.no_rkm_medis) AS jumlah
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND MONTH(p.tgl_daftar) = ?
  AND YEAR(p.tgl_daftar) = ?
  AND p.nm_pasien NOT LIKE '%TEST%'
  AND p.nm_pasien IS NOT NULL
  AND p.nm_pasien <> ''
UNION ALL
SELECT 
    'Pengunjung Lama' AS jenis_pengunjung,
    COUNT(DISTINCT r.no_rkm_medis) AS jumlah
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND p.tgl_daftar < DATE_FORMAT(CONCAT(?, '-', ?, '-01'), '%Y-%m-01')
  AND p.nm_pasien NOT LIKE '%TEST%'
  AND p.nm_pasien IS NOT NULL
  AND p.nm_pasien <> ''
UNION ALL
SELECT 
    'TOTAL',
    COUNT(DISTINCT r.no_rkm_medis) AS jumlah
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND p.nm_pasien NOT LIKE '%TEST%'
  AND p.nm_pasien IS NOT NULL
  AND p.nm_pasien <> '';
";

$stmt = $config->prepare($sql);
$stmt->bind_param(
    'iiiiiiiiii',
    $bulan, $tahun, $bulan, $tahun, // Pengunjung Baru
    $bulan, $tahun, $tahun, $bulan, // Pengunjung Lama
    $bulan, $tahun // TOTAL
);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('RL3.4_' . $bulan . '_' . $tahun);
$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Judul
$sheet->mergeCells('A1:C1')->setCellValue('A1', 'RL 3.4 REKAPITULASI PENGUNJUNG');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->mergeCells('A2:C2')->setCellValue('A2', 'Periode: ' . sprintf('%02d', $bulan) . '-' . $tahun);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header
$headers = ['No','Jenis Pengunjung','Jumlah'];
$sheet->fromArray($headers, NULL, 'A4');
$sheet->getStyle('A4:C4')->applyFromArray([
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
]);

// Isi Data
$rowNum = 5;
foreach ($data as $idx => $row) {
    $no = ($row['jenis_pengunjung'] === 'TOTAL') ? 99 : $idx + 1;
    $sheet->setCellValue('A'.$rowNum, $no);
    $sheet->setCellValue('B'.$rowNum, $row['jenis_pengunjung']);
    $sheet->setCellValue('C'.$rowNum, $row['jumlah']);
    if ($row['jenis_pengunjung'] === 'TOTAL') {
        $sheet->getStyle("A{$rowNum}:C{$rowNum}")->getFont()->setBold(true);
    }
    $rowNum++;
}

// Border seluruh tabel
$lastRow = $sheet->getHighestRow();
$sheet->getStyle("A4:C$lastRow")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
]);
foreach (range('A','C') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

// Output file
$filename = "RL3.4_Rekap_Pengunjung_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
