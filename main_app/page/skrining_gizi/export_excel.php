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
$kategori_umur = isset($_GET['kategori_umur']) ? $_GET['kategori_umur'] : 'balita';

// Fungsi untuk mengkonversi kategori umur ke batas usia maksimal
function get_max_age_from_category($kategori) {
    $batas_umur = [
        'bayi' => 1,           // Bayi: 0 - <1 tahun
        'balita' => 5,         // Balita: 1 - <5 tahun
        'anak' => 12,          // Anak-anak: 5 - <12 tahun
        'remaja' => 18,        // Remaja: 12 - <18 tahun
        'dewasa_muda' => 30,   // Dewasa Muda: 18 - <30 tahun
        'dewasa' => 45,        // Dewasa: 30 - <45 tahun
        'lansia_awal' => 60,   // Lansia Awal: 45 - <60 tahun
        'lansia_akhir' => 200   // Lansia Akhir: 60+ tahun
    ];
    return $batas_umur[$kategori] ?? 5;
}

// Fungsi untuk mendapatkan label kategori umur
function get_kategori_umur_label($kategori) {
    $labels = [
        'bayi' => 'Bayi (0 - <1 tahun)',
        'balita' => 'Balita (1 - <5 tahun)',
        'anak' => 'Anak-anak (5 - <12 tahun)',
        'remaja' => 'Remaja (12 - <18 tahun)',
        'dewasa_muda' => 'Dewasa Muda (18 - <30 tahun)',
        'dewasa' => 'Dewasa (30 - <45 tahun)',
        'lansia_awal' => 'Lansia Awal (45 - <60 tahun)',
        'lansia_akhir' => 'Lansia Akhir (≥60 tahun)'
    ];
    return $labels[$kategori] ?? 'Balita (1 - <5 tahun)';
}

$usia_max = get_max_age_from_category($kategori_umur);
$kategori_label = get_kategori_umur_label($kategori_umur);

// ======== QUERY DATA SKRINING GIZI ========
$query = mysqli_query($config, "
    SELECT
        psad.tanggal,
        psad.no_rawat,
        p.no_rkm_medis,
        p.nm_pasien,
        p.jk AS jenis_kelamin,
        p.tgl_lahir,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) AS umur_tahun,
        MOD(TIMESTAMPDIFF(MONTH, p.tgl_lahir, psad.tanggal), 12) AS umur_bulan,
        psad.keluhan,
        psad.riwayat_penyakit,
        psad.fisik_klinis,
        psad.biokimia,
        psad.riwayat_makan
    FROM pilot_skrining_awal_diet AS psad
    JOIN reg_periksa AS rp ON psad.no_rawat = rp.no_rawat
    JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
    WHERE TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) < $usia_max
      AND YEAR(psad.tanggal) = $tahun
      AND MONTH(psad.tanggal) = $bulan
    ORDER BY psad.tanggal ASC
");

$data = [];
while ($row = mysqli_fetch_array($query)) {
    $data[] = $row;
}

// ======== BUAT FILE EXCEL ========
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data_Skrining_Gizi');

$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Judul
$sheet->mergeCells('A1:M1')->setCellValue('A1', 'LAPORAN DATA SKRINING GIZI');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:M2')->setCellValue('A2', 'Kategori: ' . $kategori_label);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A3:M3')->setCellValue('A3', 'Periode: ' . sprintf('%02d', $bulan) . '-' . $tahun);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header
$headers = [
    'No', 'Tanggal', 'No. RM', 'Nama Pasien', 'Usia', 'Jenis Kelamin',
    'Keluhan', 'Riwayat Penyakit', 'Fisik Klinis', 'Biokimia', 'Riwayat Makan', 'No. Rawat', 'Status Gizi'
];

$sheet->fromArray($headers, NULL, 'A5');

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
$sheet->getStyle('A5:M5')->applyFromArray($headerStyle);

// ======== ISI DATA ========
$rowNum = 6;
$no = 1;

foreach ($data as $row) {
    // Hitung umur dalam tahun dan bulan
    $umur_tahun = $row['umur_tahun'];
    $umur_bulan = $row['umur_bulan'];
    $usia_display = $umur_tahun . ' thn ' . $umur_bulan . ' bln';

    // Status Gizi
    $status_gizi = 'Normal';
    if (!empty($row['keluhan']) || !empty($row['fisik_klinis']) || !empty($row['biokimia'])) {
        $status_gizi = 'Perlu Perhatian';
    }

    $sheet->setCellValue('A'.$rowNum, $no++);
    $sheet->setCellValue('B'.$rowNum, date('d/m/Y', strtotime($row['tanggal'])));
    $sheet->setCellValue('C'.$rowNum, $row['no_rkm_medis']);
    $sheet->setCellValue('D'.$rowNum, $row['nm_pasien']);
    $sheet->setCellValue('E'.$rowNum, $usia_display);
    $sheet->setCellValue('F'.$rowNum, $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan');
    $sheet->setCellValue('G'.$rowNum, $row['keluhan'] ?: '-');
    $sheet->setCellValue('H'.$rowNum, $row['riwayat_penyakit'] ?: '-');
    $sheet->setCellValue('I'.$rowNum, $row['fisik_klinis'] ?: '-');
    $sheet->setCellValue('J'.$rowNum, $row['biokimia'] ?: '-');
    $sheet->setCellValue('K'.$rowNum, $row['riwayat_makan'] ?: '-');
    $sheet->setCellValue('L'.$rowNum, $row['no_rawat']);
    $sheet->setCellValue('M'.$rowNum, $status_gizi);

    $rowNum++;
}

// Border seluruh tabel
$lastRow = $sheet->getHighestRow();
$sheet->getStyle("A5:M$lastRow")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
]);

// Auto size kolom
foreach (range('A','M') as $col) {
    if ($col === 'D') { // Nama pasien lebih lebar
        $sheet->getColumnDimension($col)->setWidth(25);
    } elseif (in_array($col, ['G', 'H', 'I', 'J', 'K'])) { // Kolom teks panjang
        $sheet->getColumnDimension($col)->setWidth(20);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Set tinggi baris untuk header
$sheet->getRowDimension(5)->setRowHeight(30);

// ======== OUTPUT FILE ========
$filename = "Data_Skrining_Gizi_{$kategori_umur}_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>