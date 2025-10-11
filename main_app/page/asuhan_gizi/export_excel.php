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

// ======== PARAMETER FILTER ========
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// ======== QUERY DATA ASUHAN GIZI ANAK 0-5 TAHUN ========
$query = mysqli_query($config, "
    SELECT
        ag.tanggal,
        ag.no_rawat,
        p.no_rkm_medis,
        p.nm_pasien,
        p.jk AS jenis_kelamin,
        p.tgl_lahir,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) AS umur_tahun,
        MOD(TIMESTAMPDIFF(MONTH, p.tgl_lahir, ag.tanggal), 12) AS umur_bulan,

        -- Kategori umur (anak, remaja, dewasa, lansia)
        CASE
            WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) < 12 THEN 'Anak'
            WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) BETWEEN 12 AND 17 THEN 'Remaja'
            WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) BETWEEN 18 AND 59 THEN 'Dewasa'
            ELSE 'Lansia'
        END AS kategori_umur,

        -- Kolom penting dari asuhan gizi
        ag.antropometri_bb,
        ag.antropometri_tb,
        ag.antropometri_imt,
        ag.antropometri_lla,
        ag.fisik_klinis,
        ag.biokimia,
        ag.diagnosis,
        ag.intervensi_gizi,
        ag.monitoring_evaluasi,
        ag.pola_makan,
        d.nm_dokter,
        pol.nm_poli
    FROM asuhan_gizi AS ag
    JOIN reg_periksa AS rp ON ag.no_rawat = rp.no_rawat
    JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN dokter AS d ON rp.kd_dokter = d.kd_dokter
    LEFT JOIN poliklinik AS pol ON rp.kd_poli = pol.kd_poli
    WHERE
        -- Filter anak usia ≤ 5 tahun
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) <= 5
        -- Filter berdasarkan bulan dan tahun yang diatur
        AND YEAR(ag.tanggal) = $tahun
        AND MONTH(ag.tanggal) = $bulan
    ORDER BY ag.tanggal ASC
");

$data = [];
while ($row = mysqli_fetch_array($query)) {
    $data[] = $row;
}

// ======== BUAT FILE EXCEL ========
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data_Asuhan_Gizi_Anak');

$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Judul
$sheet->mergeCells('A1:N1')->setCellValue('A1', 'LAPORAN DATA ASUHAN GIZI ANAK (0-5 TAHUN)');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:N2')->setCellValue('A2', 'Periode: ' . sprintf('%02d', $bulan) . '-' . $tahun);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header
$headers = [
    'No', 'Tanggal', 'No. RM', 'Nama Pasien', 'Jenis Kelamin', 'Tanggal Lahir', 'Usia',
    'Kategori', 'Berat Badan (kg)', 'Tinggi Badan (cm)', 'IMT', 'LLA (cm)',
    'Dokter', 'Poliklinik'
];

$sheet->fromArray($headers, NULL, 'A4');

// Style header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4CAF50']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$sheet->getStyle('A4:N4')->applyFromArray($headerStyle);

// ======== ISI DATA ========
$rowNum = 5;
$no = 1;

foreach ($data as $row) {
    // Hitung umur dalam tahun dan bulan
    $umur_tahun = $row['umur_tahun'];
    $umur_bulan = $row['umur_bulan'];
    $usia_display = $umur_tahun . ' thn ' . $umur_bulan . ' bln';

    $sheet->setCellValue('A'.$rowNum, $no++);
    $sheet->setCellValue('B'.$rowNum, date('d/m/Y', strtotime($row['tanggal'])));
    $sheet->setCellValue('C'.$rowNum, $row['no_rkm_medis']);
    $sheet->setCellValue('D'.$rowNum, $row['nm_pasien']);
    $sheet->setCellValue('E'.$rowNum, $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan');
    $sheet->setCellValue('F'.$rowNum, date('d/m/Y', strtotime($row['tgl_lahir'])));
    $sheet->setCellValue('G'.$rowNum, $usia_display);
    $sheet->setCellValue('H'.$rowNum, $row['kategori_umur']);
    $sheet->setCellValue('I'.$rowNum, $row['antropometri_bb'] ? str_replace(',', '.', $row['antropometri_bb']) : '-');
    $sheet->setCellValue('J'.$rowNum, $row['antropometri_tb'] ? str_replace(',', '.', $row['antropometri_tb']) : '-');
    $sheet->setCellValue('K'.$rowNum, $row['antropometri_imt'] ? str_replace(',', '.', $row['antropometri_imt']) : '-');
    $sheet->setCellValue('L'.$rowNum, $row['antropometri_lla'] ? str_replace(',', '.', $row['antropometri_lla']) : '-');
    $sheet->setCellValue('M'.$rowNum, $row['nm_dokter'] ?: '-');
    $sheet->setCellValue('N'.$rowNum, $row['nm_poli'] ?: '-');

    $rowNum++;
}

// Border seluruh tabel
$lastRow = $sheet->getHighestRow();
$sheet->getStyle("A4:N$lastRow")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
]);

// Auto size kolom
foreach (range('A','N') as $col) {
    if (in_array($col, ['D', 'M', 'N'])) { // Nama pasien, dokter, poli lebih lebar
        $sheet->getColumnDimension($col)->setWidth(20);
    } elseif (in_array($col, ['F', 'G', 'H', 'I', 'J', 'K', 'L'])) { // Kolom tanggal dan angka
        $sheet->getColumnDimension($col)->setWidth(15);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Set tinggi baris untuk header
$sheet->getRowDimension(4)->setRowHeight(30);

// ======== OUTPUT FILE ========
$filename = "Data_Asuhan_Gizi_Anak_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>