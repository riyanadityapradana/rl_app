<?php
// Bersihkan semua output buffer dan nonaktifkan error display untuk Excel
ob_clean();
error_reporting(0);
ini_set('display_errors', 0);

require '../assets/vendor/autoload.php';

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

// Set user-defined variables untuk kategori umur yang fleksibel
if (!mysqli_query($config, "SET @tahun_laporan = $tahun")) {
    die('Error setting tahun_laporan: ' . mysqli_error($config));
}
if (!mysqli_query($config, "SET @bulan_laporan = $bulan")) {
    die('Error setting bulan_laporan: ' . mysqli_error($config));
}
if (!mysqli_query($config, "SET @kategori_uji = '$kategori_umur'")) {
    die('Error setting kategori_uji: ' . mysqli_error($config));
}

// Menentukan Usia Minimal menggunakan CASE
if (!mysqli_query($config, "
    SET @usia_min = CASE @kategori_uji
        WHEN 'bayi' THEN 0
        WHEN 'balita' THEN 1
        WHEN 'anak' THEN 5
        WHEN 'remaja' THEN 12
        WHEN 'dewasa_muda' THEN 18
        WHEN 'dewasa' THEN 30
        WHEN 'lansia_awal' THEN 45
        WHEN 'lansia_akhir' THEN 60
        ELSE 1
    END
")) {
    die('Error setting usia_min: ' . mysqli_error($config));
}

// Menentukan Usia Maksimal menggunakan CASE
if (!mysqli_query($config, "
    SET @usia_max = CASE @kategori_uji
        WHEN 'bayi' THEN 1
        WHEN 'balita' THEN 5
        WHEN 'anak' THEN 12
        WHEN 'remaja' THEN 18
        WHEN 'dewasa_muda' THEN 30
        WHEN 'dewasa' THEN 45
        WHEN 'lansia_awal' THEN 60
        WHEN 'lansia_akhir' THEN 200
        ELSE 5
    END
")) {
    die('Error setting usia_max: ' . mysqli_error($config));
}

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
        psad.riwayat_makan,
        CONCAT('Hasil tes untuk Kategori: ''', @kategori_uji, ''' (usia >=', @usia_min, ' & <', @usia_max, ' tahun)') AS kategori_info
    FROM pilot_skrining_awal_diet AS psad
    JOIN reg_periksa AS rp ON psad.no_rawat = rp.no_rawat
    JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
    WHERE (TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) >= @usia_min
      AND TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) < @usia_max)
      AND YEAR(psad.tanggal) = @tahun_laporan
      AND MONTH(psad.tanggal) = @bulan_laporan
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
$sheet->mergeCells('A1:N1')->setCellValue('A1', 'LAPORAN DATA SKRINING GIZI');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:N2')->setCellValue('A2', 'Kategori: ' . $kategori_label);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A3:N3')->setCellValue('A3', 'Periode: ' . sprintf('%02d', $bulan) . '-' . $tahun);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header
$headers = [
    'No', 'Tanggal', 'No. RM', 'Nama Pasien', 'Usia', 'Kategori', 'Jenis Kelamin',
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
// Style header
$sheet->getStyle('A5:N5')->applyFromArray($headerStyle);

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
    $sheet->setCellValue('F'.$rowNum, $row['kategori_info']);
    $sheet->setCellValue('G'.$rowNum, $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan');
    $sheet->setCellValue('H'.$rowNum, $row['keluhan'] ?: '-');
    $sheet->setCellValue('I'.$rowNum, $row['riwayat_penyakit'] ?: '-');
    $sheet->setCellValue('J'.$rowNum, $row['fisik_klinis'] ?: '-');
    $sheet->setCellValue('K'.$rowNum, $row['biokimia'] ?: '-');
    $sheet->setCellValue('L'.$rowNum, $row['riwayat_makan'] ?: '-');
    $sheet->setCellValue('M'.$rowNum, $row['no_rawat']);
    $sheet->setCellValue('N'.$rowNum, $status_gizi);

    $rowNum++;
}

// Border seluruh tabel
$lastRow = $sheet->getHighestRow();
$sheet->getStyle("A5:N$lastRow")->applyFromArray([
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
    if ($col === 'D') { // Nama pasien lebih lebar
        $sheet->getColumnDimension($col)->setWidth(25);
    } elseif (in_array($col, ['F', 'H', 'I', 'J', 'K', 'L'])) { // Kolom teks panjang (termasuk kategori_info)
        $sheet->getColumnDimension($col)->setWidth(20);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Set tinggi baris untuk header
$sheet->getRowDimension(5)->setRowHeight(30);

// Bersihkan buffer sekali lagi sebelum output
ob_end_clean();

// Set headers untuk file Excel
$filename = "Data_Skrining_Gizi_{$kategori_umur}_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Output file Excel langsung tanpa menyimpan ke memory
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>