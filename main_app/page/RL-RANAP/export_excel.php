
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

// Ambil dan validasi input
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('m'));
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

if ($bulan < 1 || $bulan > 12) $bulan = intval(date('m'));
if ($tahun < 2000 || $tahun > 2100) $tahun = intval(date('Y'));

function getMainReport($config, $tahun, $bulan) {
    // Menyiapkan variabel tanggal untuk query
    $start_date = "$tahun-$bulan-01";
    $prev_month_start_date = ($bulan == 1) ? ($tahun - 1) . "-12-01" : "$tahun-" . str_pad($bulan - 1, 2, '0', STR_PAD_LEFT) . "-01";

    // Set user variables di MySQL
    $config->query("SET @TAHUN = $tahun;");
    $config->query("SET @BULAN = $bulan;");
    $config->query("SET @START_DATE = DATE(CONCAT(@TAHUN, '-', @BULAN, '-01'));");
    $config->query("SET @END_DATE = LAST_DAY(@START_DATE);");
    $config->query("SET @PREV_MONTH_START_DATE = CASE WHEN @BULAN = 1 THEN DATE(CONCAT(@TAHUN - 1, '-12-01')) ELSE DATE(CONCAT(@TAHUN, '-', @BULAN - 1, '-01')) END;");
    $config->query("SET @row_num = 0;");

    // Query utama yang sudah lengkap (dicopy dari index.php)
    // PERUBAHAN KECIL: Alias untuk Kelas 1, 2, 3 diubah menjadi I, II, III
    $sql = "
    SELECT
        (@row_num := @row_num + 1) AS 'No',
        mk.jenis_pelayanan AS 'Jenis Pelayanan',
        COALESCE(pab.jumlah, 0) AS 'Pasien Awal Bulan',
        COALESCE(pm.jumlah, 0) AS 'Pasien Masuk',
        COALESCE(ppm.jumlah, 0) AS 'Pasien Pindahan',
        COALESCE(ppk.jumlah, 0) AS 'Pasien Dipindahkan',
        COALESCE(pkh.jumlah, 0) AS 'Pasien Keluar Hidup',
        COALESCE(p_mati.laki_mati_under_48, 0) AS 'Pasien Laki-Laki Keluar Mati <48 jam',
        COALESCE(p_mati.laki_mati_over_48, 0) AS 'Pasien Laki-Laki Keluar Mati >=48 jam',
        COALESCE(p_mati.perempuan_mati_under_48, 0) AS 'Pasien Perempuan Keluar Mati <48 jam',
        COALESCE(p_mati.perempuan_mati_over_48, 0) AS 'Pasien Perempuan Keluar Mati >=48 jam',
        COALESCE(jld.jumlah, 0) AS 'Jumlah Lama Dirawat',
        COALESCE(p_akhir.jumlah, 0) AS 'Pasien Akhir Bulan',
        COALESCE(rhp.JUMLAH_HARI_PERAWATAN, 0) AS 'Jumlah Hari Perawatan',
        COALESCE(rhp.VVIP, 0) AS 'VVIP',
        COALESCE(rhp.VIP, 0) AS 'VIP',
        COALESCE(rhp.Kelas_1, 0) AS 'I',
        COALESCE(rhp.Kelas_2, 0) AS 'II',
        COALESCE(rhp.Kelas_3, 0) AS 'III',
        COALESCE(rhp.Kelas_Khusus, 0) AS 'Kelas Khusus',
        COALESCE(att.jumlah, 0) AS 'Jumlah alokasi tempat tidur awal bulan'
    FROM
        (SELECT DISTINCT CASE WHEN kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN kd_bangsal ELSE 'Umum' END AS jenis_pelayanan FROM bangsal) AS mk
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(ki.no_rawat) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk < @START_DATE AND ki.tgl_masuk >= @PREV_MONTH_START_DATE AND (ki.tgl_keluar >= @START_DATE OR ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00') GROUP BY jenis_pelayanan) AS pab ON mk.jenis_pelayanan = pab.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(ki.no_rawat) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk BETWEEN @START_DATE AND @END_DATE AND ki.stts_pulang <> 'Pindah Kamar' GROUP BY jenis_pelayanan) AS pm ON mk.jenis_pelayanan = pm.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN bangsal_baru.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN bangsal_baru.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(baru.no_rawat) AS jumlah FROM kamar_inap AS lama INNER JOIN kamar_inap AS baru ON lama.no_rawat = baru.no_rawat AND lama.tgl_keluar = baru.tgl_masuk AND lama.jam_keluar = baru.jam_masuk INNER JOIN kamar AS kamar_lama ON lama.kd_kamar = kamar_lama.kd_kamar INNER JOIN bangsal AS bangsal_lama ON kamar_lama.kd_bangsal = bangsal_lama.kd_bangsal INNER JOIN kamar AS kamar_baru ON baru.kd_kamar = kamar_baru.kd_kamar INNER JOIN bangsal AS bangsal_baru ON kamar_baru.kd_bangsal = bangsal_baru.kd_bangsal WHERE lama.stts_pulang = 'Pindah Kamar' AND baru.tgl_masuk BETWEEN @START_DATE AND @END_DATE AND (CASE WHEN bangsal_lama.kd_bangsal IN ('ICU', 'KN', 'PERIN', 'ISO') THEN 'Khusus' ELSE 'Umum' END) <> (CASE WHEN bangsal_baru.kd_bangsal IN ('ICU', 'KN', 'PERIN', 'ISO') THEN 'Khusus' ELSE 'Umum' END) GROUP BY jenis_pelayanan) AS ppm ON mk.jenis_pelayanan = ppm.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN bangsal_lama.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN bangsal_lama.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(lama.no_rawat) AS jumlah FROM kamar_inap AS lama INNER JOIN kamar_inap AS baru ON lama.no_rawat = baru.no_rawat AND lama.tgl_keluar = baru.tgl_masuk AND lama.jam_keluar = baru.jam_masuk INNER JOIN kamar AS kamar_lama ON lama.kd_kamar = kamar_lama.kd_kamar INNER JOIN bangsal AS bangsal_lama ON kamar_lama.kd_bangsal = bangsal_lama.kd_bangsal INNER JOIN kamar AS kamar_baru ON baru.kd_kamar = kamar_baru.kd_kamar INNER JOIN bangsal AS bangsal_baru ON kamar_baru.kd_bangsal = bangsal_baru.kd_bangsal WHERE lama.stts_pulang = 'Pindah Kamar' AND lama.tgl_keluar BETWEEN @START_DATE AND @END_DATE AND (CASE WHEN bangsal_lama.kd_bangsal IN ('ICU', 'KN', 'PERIN', 'ISO') THEN 'Khusus' ELSE 'Umum' END) <> (CASE WHEN bangsal_baru.kd_bangsal IN ('ICU', 'KN', 'PERIN', 'ISO') THEN 'Khusus' ELSE 'Umum' END) GROUP BY jenis_pelayanan) AS ppk ON mk.jenis_pelayanan = ppk.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(ki.no_rawat) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_keluar BETWEEN @START_DATE AND @END_DATE AND ki.stts_pulang NOT IN ('Meninggal', 'Pindah Kamar') GROUP BY jenis_pelayanan) AS pkh ON mk.jenis_pelayanan = pkh.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, SUM(CASE WHEN p.jk = 'L' AND TIMESTAMPDIFF(HOUR, CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar)) < 48 THEN 1 ELSE 0 END) AS laki_mati_under_48, SUM(CASE WHEN p.jk = 'L' AND TIMESTAMPDIFF(HOUR, CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar)) >= 48 THEN 1 ELSE 0 END) AS laki_mati_over_48, SUM(CASE WHEN p.jk = 'P' AND TIMESTAMPDIFF(HOUR, CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar)) < 48 THEN 1 ELSE 0 END) AS perempuan_mati_under_48, SUM(CASE WHEN p.jk = 'P' AND TIMESTAMPDIFF(HOUR, CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk), CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar)) >= 48 THEN 1 ELSE 0 END) AS perempuan_mati_over_48 FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis WHERE ki.tgl_keluar BETWEEN @START_DATE AND @END_DATE AND ki.stts_pulang = 'Meninggal' GROUP BY jenis_pelayanan) AS p_mati ON mk.jenis_pelayanan = p_mati.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, SUM(ki.lama) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_keluar BETWEEN @START_DATE AND @END_DATE GROUP BY jenis_pelayanan) AS jld ON mk.jenis_pelayanan = jld.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(ki.no_rawat) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk <= @END_DATE AND (ki.tgl_keluar > @END_DATE OR ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00') GROUP BY jenis_pelayanan) AS p_akhir ON mk.jenis_pelayanan = p_akhir.jenis_pelayanan
    LEFT JOIN
        (SELECT T.jenis_pelayanan, SUM(T.days_in_period) AS 'JUMLAH_HARI_PERAWATAN', SUM(CASE WHEN T.kelas_perawatan = 'Kelas VVIP' THEN T.days_in_period ELSE 0 END) AS 'VVIP', SUM(CASE WHEN T.kelas_perawatan = 'Kelas VIP' THEN T.days_in_period ELSE 0 END) AS 'VIP', SUM(CASE WHEN T.kelas_perawatan = 'Kelas 1' THEN T.days_in_period ELSE 0 END) AS 'Kelas_1', SUM(CASE WHEN T.kelas_perawatan = 'Kelas 2' THEN T.days_in_period ELSE 0 END) AS 'Kelas_2', SUM(CASE WHEN T.kelas_perawatan = 'Kelas 3' THEN T.days_in_period ELSE 0 END) AS 'Kelas_3', SUM(CASE WHEN T.kelas_perawatan = 'Kelas Khusus' THEN T.days_in_period ELSE 0 END) AS 'Kelas_Khusus' FROM (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, CASE k.kelas WHEN 'Kelas VVIP' THEN 'Kelas VVIP' WHEN 'Kelas VIP' THEN 'Kelas VIP' WHEN 'Kelas 1' THEN 'Kelas 1' WHEN 'Kelas 2' THEN 'Kelas 2' WHEN 'Kelas 3' THEN 'Kelas 3' ELSE 'Kelas Khusus' END AS kelas_perawatan, DATEDIFF(LEAST(IF(ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00', @END_DATE, ki.tgl_keluar), @END_DATE), GREATEST(ki.tgl_masuk, @START_DATE)) + 1 AS days_in_period FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk <= @END_DATE AND (ki.tgl_keluar >= @START_DATE OR ki.tgl_keluar IS NULL OR ki.tgl_keluar = '0000-00-00')) AS T GROUP BY T.jenis_pelayanan) AS rhp ON mk.jenis_pelayanan = rhp.jenis_pelayanan
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(k.kd_kamar) AS jumlah FROM kamar k INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE k.statusdata = '1' GROUP BY jenis_pelayanan) AS att ON mk.jenis_pelayanan = att.jenis_pelayanan
    ORDER BY mk.jenis_pelayanan;
    ";

    $result = $config->query($sql);
    if (!$result) {
        die("Query error: " . $config->error);
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$data = getMainReport($config, $tahun, $bulan);

$namaPelayananMap = ['ICU' => 'ICU', 'KN' => 'NICU', 'PERIN' => 'Perinatologi', 'ISO' => 'Isolasi', 'Umum' => 'Umum'];
$processedData = [];
foreach ($data as $row) {
    $kodePelayanan = $row['Jenis Pelayanan'];
    $row['Jenis Pelayanan'] = $namaPelayananMap[$kodePelayanan] ?? $kodePelayanan;
    $processedData[] = array_values($row); // Ambil nilainya saja untuk penulisan data
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$namaBulan = date('F', mktime(0, 0, 0, $bulan, 10));
$sheet->setTitle("RL 3.2 - $namaBulan $tahun");

// === PAGE SETUP A4 LANDSCAPE ===
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageMargins()->setTop(0.75)->setRight(0.25)->setLeft(0.25)->setBottom(0.75);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

// Judul Laporan
$sheet->mergeCells('A1:U1')->setCellValue('A1', 'RL 3.2 REKAPITULASI KEGIATAN PELAYANAN RAWAT INAP');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:U2')->setCellValue('A2', "PERIODE: " . strtoupper($namaBulan) . " $tahun");
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// === PEMBUATAN HEADER MANUAL ===
$startRow = 4;
// Baris 1 header
$sheet->mergeCells('A'.$startRow.':A'.($startRow+2))->setCellValue('A'.$startRow, 'No');
$sheet->mergeCells('B'.$startRow.':B'.($startRow+2))->setCellValue('B'.$startRow, 'Jenis Pelayanan');
$sheet->mergeCells('C'.$startRow.':C'.($startRow+2))->setCellValue('C'.$startRow, 'Pasien Awal Bulan');
$sheet->mergeCells('D'.$startRow.':D'.($startRow+2))->setCellValue('D'.$startRow, 'Pasien Masuk');
$sheet->mergeCells('E'.$startRow.':E'.($startRow+2))->setCellValue('E'.$startRow, 'Pasien Pindahan');
$sheet->mergeCells('F'.$startRow.':F'.($startRow+2))->setCellValue('F'.$startRow, 'Pasien Dipindahkan');
$sheet->mergeCells('G'.$startRow.':G'.($startRow+2))->setCellValue('G'.$startRow, 'Pasien Keluar Hidup');
$sheet->mergeCells('H'.$startRow.':K'.$startRow)->setCellValue('H'.$startRow, 'Pasien Keluar Mati');
$sheet->mergeCells('L'.$startRow.':L'.($startRow+2))->setCellValue('L'.$startRow, 'Jumlah Lama Dirawat');
$sheet->mergeCells('M'.$startRow.':M'.($startRow+2))->setCellValue('M'.$startRow, 'Pasien Akhir Bulan');
$sheet->mergeCells('N'.$startRow.':N'.($startRow+2))->setCellValue('N'.$startRow, 'Jumlah Hari Perawatan');
$sheet->mergeCells('O'.$startRow.':T'.$startRow)->setCellValue('O'.$startRow, 'Rincian Hari Perawatan per Kelas');
$sheet->mergeCells('U'.$startRow.':U'.($startRow+2))->setCellValue('U'.$startRow, "Jumlah alokasi tempat tidur awal bulan");

// Baris 2 header
$sheet->mergeCells('H'.($startRow+1).':I'.($startRow+1))->setCellValue('H'.($startRow+1), 'Pasien Laki-Laki');
$sheet->mergeCells('J'.($startRow+1).':K'.($startRow+1))->setCellValue('J'.($startRow+1), 'Pasien Perempuan');
$sheet->mergeCells('O'.($startRow+1).':O'.($startRow+2))->setCellValue('O'.($startRow+1), 'VVIP');
$sheet->mergeCells('P'.($startRow+1).':P'.($startRow+2))->setCellValue('P'.($startRow+1), 'VIP');
$sheet->mergeCells('Q'.($startRow+1).':Q'.($startRow+2))->setCellValue('Q'.($startRow+1), 'I');
$sheet->mergeCells('R'.($startRow+1).':R'.($startRow+2))->setCellValue('R'.($startRow+1), 'II');
$sheet->mergeCells('S'.($startRow+1).':S'.($startRow+2))->setCellValue('S'.($startRow+1), 'III');
$sheet->mergeCells('T'.($startRow+1).':T'.($startRow+2))->setCellValue('T'.($startRow+1), 'Kelas Khusus');

// Baris 3 header
$sheet->setCellValue('H'.($startRow+2), '<48 jam');
$sheet->setCellValue('I'.($startRow+2), '>=48 jam');
$sheet->setCellValue('J'.($startRow+2), '<48 jam');
$sheet->setCellValue('K'.($startRow+2), '>=48 jam');

// Penulisan Data
if (count($processedData) > 0) {
    $sheet->fromArray($processedData, NULL, 'A'.($startRow + 3));
} else {
    $sheet->mergeCells('A'.($startRow+3).':U'.($startRow+3));
    $sheet->setCellValue('A'.($startRow+3), 'Tidak ada data untuk periode yang dipilih.');
}

// === STYLING ===
$headerRange = 'A'.$startRow.':U'.($startRow+2);
$lastRow = $sheet->getHighestRow();
$dataRange = 'A'.$startRow.':U'.$lastRow;

// Style Header
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
]);

// Style Border untuk seluruh tabel
$sheet->getStyle($dataRange)->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]
    ]
]);

// Style alignment untuk sel data
$sheet->getStyle('A'.($startRow+3).':U'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A'.($startRow+3).':U'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Atur lebar kolom (opsional, bisa di-nonaktifkan jika autoSize lebih disukai)
foreach (range('A', 'U') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}
$sheet->getColumnDimension('B')->setWidth(20); // Lebar khusus untuk Jenis Pelayanan
$sheet->getColumnDimension('U')->setWidth(20); // Lebar khusus untuk Alokasi TT

// Header HTTP untuk download file
$filename = "RL3.2_Pelayanan_Rawat_Inap_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;