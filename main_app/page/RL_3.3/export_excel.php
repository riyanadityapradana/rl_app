<?php
require '../../assets/vendor/autoload.php';
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

// ======== QUERY RL 3.3 IGD ========
$query = mysqli_query($config, "
    SELECT
        kategori,
        SUM(CASE WHEN stts_daftar = 'Lama' THEN 1 ELSE 0 END) AS rujukan,
        SUM(CASE WHEN stts_daftar = 'Baru' THEN 1 ELSE 0 END) AS non_rujukan,

        SUM(CASE WHEN stts = 'Dirawat' THEN 1 ELSE 0 END) AS dirawat,
        SUM(CASE WHEN stts = 'Dirujuk' THEN 1 ELSE 0 END) AS dirujuk,
        SUM(CASE WHEN stts = 'Pulang' THEN 1 ELSE 0 END) AS pulang,

        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='L' THEN 1 ELSE 0 END) AS mati_L,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='P' THEN 1 ELSE 0 END) AS mati_P,

        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='L' THEN 1 ELSE 0 END) AS doa_L,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='P' THEN 1 ELSE 0 END) AS doa_P,

        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='L' THEN 1 ELSE 0 END) AS luka_L,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='P' THEN 1 ELSE 0 END) AS luka_P,

        SUM(CASE WHEN d.kd_penyakit BETWEEN 'Z00' AND 'Z99' THEN 1 ELSE 0 END) AS false_emergency
    FROM (
        SELECT
            r.no_rawat,
            CASE
                WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' THEN '1. Bedah di Instalasi Gawat Darurat'
                WHEN d.kd_penyakit BETWEEN 'V01' AND 'Y98' THEN '2. Non Bedah'
                WHEN d.kd_penyakit BETWEEN 'O00' AND 'O99' THEN '3. Kebidanan'
                WHEN d.kd_penyakit BETWEEN 'F00' AND 'F99' THEN '4. Psikiatrik'
                WHEN TIMESTAMPDIFF(MONTH, p.tgl_lahir, r.tgl_registrasi) <= 11 THEN '5. Bayi (0-11 bulan)'
                WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) BETWEEN 1 AND 17 THEN '6. Anak (1-17 tahun)'
                WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) >= 60 THEN '7. Geriatri (≥60 tahun)'
                ELSE '8. Lainnya'
            END AS kategori,
            r.stts_daftar,
            r.stts,
            p.jk,
            d.kd_penyakit
        FROM reg_periksa r
        JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
        JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
        JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
        WHERE poli.nm_poli LIKE '%IGD%'
          AND MONTH(r.tgl_registrasi) = $bulan
          AND YEAR(r.tgl_registrasi) = $tahun
    ) AS data_igd
    JOIN reg_periksa r ON data_igd.no_rawat = r.no_rawat
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
    GROUP BY kategori
    ORDER BY kategori
");

// Data untuk sub-kategori bedah dan non-bedah
$sub_query = mysqli_query($config, "
    SELECT
        CASE
            WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' THEN
                CASE
                    WHEN TIMESTAMPDIFF(MONTH, p.tgl_lahir, r.tgl_registrasi) <= 11 THEN '1.1 Kecelakaan lalu lintas darat'
                    WHEN d.kd_penyakit BETWEEN 'V01' AND 'V99' THEN '1.2 Kecelakaan lalu lintas perairan'
                    ELSE '1.3 Kecelakaan lalu lintas udara'
                END
            WHEN d.kd_penyakit BETWEEN 'V01' AND 'Y98' THEN
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) BETWEEN 18 AND 59 THEN '2.1 Kekerasan terhadap Perempuan (≥18 tahun)'
                    WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) BETWEEN 1 AND 17 THEN '2.2 Kekerasan terhadap Anak (<18 tahun)'
                    ELSE '2.3 Kekerasan lainnya'
                END
        END AS sub_kategori,

        SUM(CASE WHEN stts_daftar = 'Lama' THEN 1 ELSE 0 END) AS rujukan,
        SUM(CASE WHEN stts_daftar = 'Baru' THEN 1 ELSE 0 END) AS non_rujukan,

        SUM(CASE WHEN stts = 'Dirawat' THEN 1 ELSE 0 END) AS dirawat,
        SUM(CASE WHEN stts = 'Dirujuk' THEN 1 ELSE 0 END) AS dirujuk,
        SUM(CASE WHEN stts = 'Pulang' THEN 1 ELSE 0 END) AS pulang,

        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='L' THEN 1 ELSE 0 END) AS mati_L,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='P' THEN 1 ELSE 0 END) AS mati_P,

        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='L' THEN 1 ELSE 0 END) AS doa_L,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='P' THEN 1 ELSE 0 END) AS doa_P,

        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='L' THEN 1 ELSE 0 END) AS luka_L,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='P' THEN 1 ELSE 0 END) AS luka_P,

        SUM(CASE WHEN d.kd_penyakit BETWEEN 'Z00' AND 'Z99' THEN 1 ELSE 0 END) AS false_emergency

    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
    JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
    WHERE poli.nm_poli LIKE '%IGD%'
      AND MONTH(r.tgl_registrasi) = $bulan
      AND YEAR(r.tgl_registrasi) = $tahun
      AND (
          (d.kd_penyakit BETWEEN 'S00' AND 'T88') OR
          (d.kd_penyakit BETWEEN 'V01' AND 'Y98')
      )
    GROUP BY sub_kategori
    ORDER BY sub_kategori
");

// Mengambil data hasil query
$data = [];
while ($row = mysqli_fetch_array($query)) {
    $data[] = $row;
}

// Mengambil data sub-kategori
$sub_data = [];
while ($row = mysqli_fetch_array($sub_query)) {
    $sub_data[] = $row;
}

// Hitung total
$total_query = mysqli_query($config, "
    SELECT
        SUM(CASE WHEN stts_daftar = 'Lama' THEN 1 ELSE 0 END) AS total_rujukan,
        SUM(CASE WHEN stts_daftar = 'Baru' THEN 1 ELSE 0 END) AS total_non_rujukan,
        SUM(CASE WHEN stts = 'Dirawat' THEN 1 ELSE 0 END) AS total_dirawat,
        SUM(CASE WHEN stts = 'Dirujuk' THEN 1 ELSE 0 END) AS total_dirujuk,
        SUM(CASE WHEN stts = 'Pulang' THEN 1 ELSE 0 END) AS total_pulang,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='L' THEN 1 ELSE 0 END) AS total_mati_L,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='P' THEN 1 ELSE 0 END) AS total_mati_P,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='L' THEN 1 ELSE 0 END) AS total_doa_L,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='P' THEN 1 ELSE 0 END) AS total_doa_P,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='L' THEN 1 ELSE 0 END) AS total_luka_L,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='P' THEN 1 ELSE 0 END) AS total_luka_P,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'Z00' AND 'Z99' THEN 1 ELSE 0 END) AS total_false_emergency
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
    JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
    WHERE poli.nm_poli LIKE '%IGD%'
      AND MONTH(r.tgl_registrasi) = $bulan
      AND YEAR(r.tgl_registrasi) = $tahun
");

$total_data = mysqli_fetch_array($total_query);

// ======== BUAT FILE EXCEL ========
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('RL3.3_IGD_' . $bulan . '_' . $tahun);

$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Judul
$sheet->mergeCells('A1:M1')->setCellValue('A1', 'RL 3.3 REKAPITULASI KEGIATAN PELAYANAN RAWAT DARURAT');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:M2')->setCellValue('A2', 'Formulir Kunjungan Rawat Darurat - Periode: ' . sprintf('%02d', $bulan) . '-' . $tahun);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header
$headers = [
    'No.', 'Jenis Pelayanan', 'Rujukan', 'Non Rujukan', 'Dirawat', 'Dirujuk', 'Pulang',
    'Mati di IGD L', 'Mati di IGD P', 'DOA L', 'DOA P', 'Luka-luka L', 'Luka-luka P', 'False Emergency'
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
    // Parse kategori untuk mendapatkan nomor dan nama
    preg_match('/(\d+)\.\s*(.+)/', $row['kategori'], $matches);
    $nomor = $matches[1] ?? '';
    $nama_kategori = $matches[2] ?? $row['kategori'];

    $sheet->setCellValue('A'.$rowNum, $nomor);
    $sheet->setCellValue('B'.$rowNum, $nama_kategori);
    $sheet->setCellValue('C'.$rowNum, $row['rujukan']);
    $sheet->setCellValue('D'.$rowNum, $row['non_rujukan']);
    $sheet->setCellValue('E'.$rowNum, $row['dirawat']);
    $sheet->setCellValue('F'.$rowNum, $row['dirujuk']);
    $sheet->setCellValue('G'.$rowNum, $row['pulang']);
    $sheet->setCellValue('H'.$rowNum, $row['mati_L']);
    $sheet->setCellValue('I'.$rowNum, $row['mati_P']);
    $sheet->setCellValue('J'.$rowNum, $row['doa_L']);
    $sheet->setCellValue('K'.$rowNum, $row['doa_P']);
    $sheet->setCellValue('L'.$rowNum, $row['luka_L']);
    $sheet->setCellValue('M'.$rowNum, $row['luka_P']);
    $sheet->setCellValue('N'.$rowNum, $row['false_emergency']);

    $rowNum++;

    // Tambahkan sub-kategori untuk Bedah dan Non Bedah
    if (in_array($nomor, ['1', '2'])) {
        foreach ($sub_data as $sub_row) {
            if (strpos($sub_row['sub_kategori'], $nomor . '.') === 0) {
                preg_match('/' . $nomor . '\.(\d+)\s*(.+)/', $sub_row['sub_kategori'], $sub_matches);
                $sub_nomor = $sub_matches[1] ?? '';
                $sub_nama = $sub_matches[2] ?? $sub_row['sub_kategori'];

                $sheet->setCellValue('A'.$rowNum, $nomor . '.' . $sub_nomor);
                $sheet->setCellValue('B'.$rowNum, $sub_nama);
                $sheet->setCellValue('C'.$rowNum, $sub_row['rujukan']);
                $sheet->setCellValue('D'.$rowNum, $sub_row['non_rujukan']);
                $sheet->setCellValue('E'.$rowNum, $sub_row['dirawat']);
                $sheet->setCellValue('F'.$rowNum, $sub_row['dirujuk']);
                $sheet->setCellValue('G'.$rowNum, $sub_row['pulang']);
                $sheet->setCellValue('H'.$rowNum, $sub_row['mati_L']);
                $sheet->setCellValue('I'.$rowNum, $sub_row['mati_P']);
                $sheet->setCellValue('J'.$rowNum, $sub_row['doa_L']);
                $sheet->setCellValue('K'.$rowNum, $sub_row['doa_P']);
                $sheet->setCellValue('L'.$rowNum, $sub_row['luka_L']);
                $sheet->setCellValue('M'.$rowNum, $sub_row['luka_P']);
                $sheet->setCellValue('N'.$rowNum, $sub_row['false_emergency']);

                $rowNum++;
            }
        }
    }
}

// Tambahkan baris TOTAL
$sheet->setCellValue('A'.$rowNum, '99');
$sheet->setCellValue('B'.$rowNum, 'TOTAL');
$sheet->setCellValue('C'.$rowNum, $total_data['total_rujukan']);
$sheet->setCellValue('D'.$rowNum, $total_data['total_non_rujukan']);
$sheet->setCellValue('E'.$rowNum, $total_data['total_dirawat']);
$sheet->setCellValue('F'.$rowNum, $total_data['total_dirujuk']);
$sheet->setCellValue('G'.$rowNum, $total_data['total_pulang']);
$sheet->setCellValue('H'.$rowNum, $total_data['total_mati_L']);
$sheet->setCellValue('I'.$rowNum, $total_data['total_mati_P']);
$sheet->setCellValue('J'.$rowNum, $total_data['total_doa_L']);
$sheet->setCellValue('K'.$rowNum, $total_data['total_doa_P']);
$sheet->setCellValue('L'.$rowNum, $total_data['total_luka_L']);
$sheet->setCellValue('M'.$rowNum, $total_data['total_luka_P']);
$sheet->setCellValue('N'.$rowNum, $total_data['total_false_emergency']);

// Style untuk baris TOTAL
$sheet->getStyle('A'.$rowNum.':N'.$rowNum)->getFont()->setBold(true);
$sheet->getStyle('A'.$rowNum.':N'.$rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DARKGRAY');

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
    if ($col === 'B') { // Kolom jenis pelayanan lebih lebar
        $sheet->getColumnDimension($col)->setWidth(30);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Set tinggi baris untuk header
$sheet->getRowDimension(4)->setRowHeight(40);
$sheet->getRowDimension(5)->setRowHeight(20);

// ======== OUTPUT FILE ========
$filename = "RL3.3_Rekapitulasi_IGD_{$tahun}_{$bulan}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>