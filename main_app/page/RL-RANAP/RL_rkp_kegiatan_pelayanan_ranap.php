<?php
require '../assets/vendor/autoload.php';

// Ambil input bulan dan tahun dari POST, default bulan dan tahun sekarang
$bulan = isset($_POST['bulan']) ? intval($_POST['bulan']) : intval(date('m'));
$tahun = isset($_POST['tahun']) ? intval($_POST['tahun']) : intval(date('Y'));

// Validasi input
if ($bulan < 1 || $bulan > 12) $bulan = intval(date('m'));
if ($tahun < 2000 || $tahun > 2100) $tahun = intval(date('Y'));

// Fungsi untuk menjalankan query utama (TIDAK DIUBAH, SESUAI PERMINTAAN)
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

    // Query utama yang sudah lengkap
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
        COALESCE(rhp.Kelas_1, 0) AS 'Kelas 1',
        COALESCE(rhp.Kelas_2, 0) AS 'Kelas 2',
        COALESCE(rhp.Kelas_3, 0) AS 'Kelas 3',
        COALESCE(rhp.Kelas_Khusus, 0) AS 'Kelas Khusus',
        COALESCE(att.jumlah, 0) AS 'Jumlah alokasi tempat tidur awal bulan'
    FROM
        (SELECT DISTINCT CASE WHEN kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN kd_bangsal ELSE 'Umum' END AS jenis_pelayanan FROM bangsal) AS mk
    LEFT JOIN
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(ki.no_rawat) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk < @START_DATE AND ki.tgl_masuk >= @PREV_MONTH_START_DATE AND (ki.tgl_keluar >= @START_DATE OR ki.tgl_keluar IS NULL) GROUP BY jenis_pelayanan) AS pab ON mk.jenis_pelayanan = pab.jenis_pelayanan
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
        (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, COUNT(ki.no_rawat) AS jumlah FROM kamar_inap ki INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk <= @END_DATE AND ki.tgl_masuk >= @START_DATE AND (ki.tgl_keluar > @END_DATE OR ki.tgl_keluar IS NULL) GROUP BY jenis_pelayanan) AS p_akhir ON mk.jenis_pelayanan = p_akhir.jenis_pelayanan
    LEFT JOIN
        (SELECT T.jenis_pelayanan, SUM(CASE WHEN T.kelas_perawatan = 'Kelas VVIP' THEN T.days_in_period ELSE 0 END) AS 'VVIP', SUM(CASE WHEN T.kelas_perawatan = 'Kelas VIP' THEN T.days_in_period ELSE 0 END) AS 'VIP', SUM(CASE WHEN T.kelas_perawatan = 'Kelas 1' THEN T.days_in_period ELSE 0 END) AS 'Kelas_1', SUM(CASE WHEN T.kelas_perawatan = 'Kelas 2' THEN T.days_in_period ELSE 0 END) AS 'Kelas_2', SUM(CASE WHEN T.kelas_perawatan = 'Kelas 3' THEN T.days_in_period ELSE 0 END) AS 'Kelas_3', SUM(CASE WHEN T.kelas_perawatan = 'Kelas Khusus' THEN T.days_in_period ELSE 0 END) AS 'Kelas_Khusus', SUM(T.days_in_period) AS 'JUMLAH_HARI_PERAWATAN' FROM (SELECT CASE WHEN b.kd_bangsal IN ('ICU','KN','PERIN','ISO') THEN b.kd_bangsal ELSE 'Umum' END AS jenis_pelayanan, CASE k.kelas WHEN 'Kelas VVIP' THEN 'Kelas VVIP' WHEN 'Kelas VIP' THEN 'Kelas VIP' WHEN 'Kelas 1' THEN 'Kelas 1' WHEN 'Kelas 2' THEN 'Kelas 2' WHEN 'Kelas 3' THEN 'Kelas 3' ELSE 'Kelas Khusus' END AS kelas_perawatan, DATEDIFF(LEAST(IF(ki.tgl_keluar IS NULL, @END_DATE, ki.tgl_keluar), @END_DATE), GREATEST(ki.tgl_masuk, @START_DATE)) + 1 AS days_in_period FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal WHERE ki.tgl_masuk <= @END_DATE AND ki.tgl_masuk >= @START_DATE AND (ki.tgl_keluar >= @START_DATE OR ki.tgl_keluar IS NULL)) AS T GROUP BY T.jenis_pelayanan) AS rhp ON mk.jenis_pelayanan = rhp.jenis_pelayanan
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
?>
<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>REKAP KEGIATAN PELAYANAN RANAP</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="dashboard_staff.php?unit=beranda">Home</a></li>
          <li class="breadcrumb-item active">Rekap Kegiatan Pelayanan Ranap</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
		<div class="card">
		    <div class="card-header">
		    	<div class="card-tools" style="float: left; text-align: left;">
                    <form method="post" class="mb-3" style="display:flex;align-items:center;gap:10px;">
                        <label for="bulan" style="margin-bottom:0;">Bulan:</label>
                        <select name="bulan" id="bulan" class="form-control" style="width:auto;display:inline-block;">
                            <?php
                            $bulanList = [
                                '01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June',
                                '07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'
                            ];
                            $bulanNow = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
                            foreach($bulanList as $val=>$label) {
                                echo '<option value="'.$val.'"'.($bulanNow==$val?' selected':'').'>'.$label.'</option>';
                            }
                            ?>
                        </select>
                        <label for="tahun" style="margin-bottom:0;">Tahun:</label>
                        <input type="number" name="tahun" id="tahun" class="form-control" value="<?php echo isset($_POST['tahun']) ? $_POST['tahun'] : date('Y'); ?>" style="width:90px;display:inline-block;">
                        <button type="submit" class="btn btn-primary">Tampilkan Data</button>
                    </form>
              	</div>
			    <div class="card-tools" style="float: right; text-align: right;">
                    <a href="main_app.php?page=export_excel&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-success">Export Excel</a>
                    <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
                            <i class="fas fa-bars"></i>
                    </a>
              	</div>
			</div>
		    <!-- /.card-header -->
            <div class="card-body">
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table class="table table-bordered table-striped text-center align-middle" style="width: 100%; table-layout: auto;">
                        <thead style="background:#81a1c1;color:#fff;">
                            <tr>
                                <?php
                                if (count($data) > 0) {
                                    $columns = array_keys($data[0]);
                                    $totalColumns = count($columns);
                                    $colIndex = 0;
                                    foreach ($columns as $col) {
                                        $stickyClass = ($colIndex < 2) ? 'sticky-column' : '';
                                        $colIndex++;
                                        echo "<th class=\"$stickyClass\" style=\"padding: 8px;\">" . htmlspecialchars($col) . "</th>";
                                    }
                                } else {
                                    echo "<th style=\"padding: 8px;\">Tidak ada data untuk ditampilkan</th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <style>
                                /* Sticky columns styling */
                                .sticky-column {
                                    position: sticky !important;
                                    background: #81a1c1 !important;
                                    z-index: 10 !important;
                                    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                                }

                                /* First column (No) */
                                .sticky-column:nth-child(1) {
                                    left: 0 !important;
                                }

                                /* Second column (Jenis Pelayanan) */
                                .sticky-column:nth-child(2) {
                                    left: 60px !important; /* Adjust based on first column width */
                                    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                                }

                                /* Table body sticky cells */
                                .table tbody tr td:nth-child(1) {
                                    position: sticky;
                                    left: 0;
                                    background: white;
                                    z-index: 5;
                                    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                                }

                                .table tbody tr td:nth-child(2) {
                                    position: sticky;
                                    left: 60px; /* Adjust based on first column width */
                                    background: white;
                                    z-index: 5;
                                    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                                }

                                /* Ensure proper spacing for all cells */
                                .table td {
                                    white-space: nowrap;
                                    padding: 8px;
                                    min-width: 80px;
                                }

                                .table th {
                                    padding: 8px;
                                }
                            </style>
                            <?php
                                // =====================================================================
                                // PERUBAHAN DI SINI: Array untuk menerjemahkan kode ke nama lengkap
                                // =====================================================================
                                $namaPelayananMap = [
                                    'ICU'   => 'ICU',
                                    'KN'    => 'NICU',
                                    'PERIN' => 'Perinatologi',
                                    'ISO'   => 'Isolasi',
                                    'Umum'  => 'Umum'
                                ];

                                // Untuk mapping balik dari nama lengkap ke kode
                                $kodePelayananMap = array_flip($namaPelayananMap);

                                if (empty($data)) {
                                    $columnCount = 22; // Sesuaikan jumlah kolom
                                    echo "<tr><td colspan='$columnCount' class='text-center'>Tidak ada data yang ditemukan untuk periode yang dipilih.</td></tr>";
                                } else {
                                    foreach ($data as $row) {
                                        echo "<tr>";
                                        $cellIndex = 0;
                                        foreach ($row as $colName => $value) {
                                            $cellIndex++;
                                            $stickyClass = ($cellIndex <= 2) ? 'sticky-column' : '';

                                            // Logika untuk menampilkan NAMA LENGKAP Jenis Pelayanan
                                            if ($colName == 'Jenis Pelayanan') {
                                                // Simpan kode asli untuk baris ini
                                                $kodePelayanan = $value;
                                                // Cari nama lengkap di map, jika tidak ada, tampilkan kode aslinya
                                                $displayName = $namaPelayananMap[$value] ?? $value;
                                                echo "<td class=\"$stickyClass\">" . htmlspecialchars($displayName) . "</td>";
                                            
                                            // Logika untuk sel yang bisa diklik (angka > 0)
                                            } else if ($colName != 'No' && is_numeric($value) && intval($value) > 0) {
                                                $metricMap = [
                                                    'Pasien Awal Bulan' => 'pasien_awal',
                                                    'Pasien Masuk' => 'pasien_masuk',
                                                    'Pasien Pindahan' => 'pasien_pindahan',
                                                    'Pasien Dipindahkan' => 'pasien_dipindahkan',
                                                    'Pasien Keluar Hidup' => 'pasien_keluar_hidup',
                                                    'Pasien Laki-Laki Keluar Mati <48 jam' => 'laki_mati_under_48',
                                                    'Pasien Laki-Laki Keluar Mati >=48 jam' => 'laki_mati_over_48',
                                                    'Pasien Perempuan Keluar Mati <48 jam' => 'perempuan_mati_under_48',
                                                    'Pasien Perempuan Keluar Mati >=48 jam' => 'perempuan_mati_over_48',
                                                    'Jumlah Lama Dirawat' => 'jumlah_lama_dirawat',
                                                    'Pasien Akhir Bulan' => 'pasien_akhir',
                                                    'Jumlah Hari Perawatan' => 'jumlah_hari_perawatan',
                                                    'VVIP' => 'vvip',
                                                    //'VIP' => 'vip',
                                                    //'Kelas 1' => 'kelas_1',
                                                    //'Kelas 2' => 'kelas_2',
                                                    //'Kelas 3' => 'kelas_3',
                                                    //'Kelas Khusus' => 'kelas_khusus',
                                                    'Jumlah alokasi tempat tidur awal bulan' => 'alokasi_tempat_tidur',
                                                ];
                                                $metric = $metricMap[$colName] ?? '';
                                                // Gunakan kode asli (bukan nama lengkap) untuk data-service
                                                $service = isset($kodePelayanan) ? $kodePelayanan : ($row['Jenis Pelayanan'] ?? '');
                                                // Khusus kolom Jumlah Hari Perawatan, pastikan metric dan data-service benar
                                                if ($colName == 'Jumlah Hari Perawatan' && $metric) {
                                                    echo "<td class='clickable-cell' data-metric='jumlah_hari_perawatan' data-service='$service'>" . htmlspecialchars($value) . "</td>";
                                                } else if ($metric) {
                                                    echo "<td class=\"$stickyClass clickable-cell\" data-metric='$metric' data-service='$service'>" . htmlspecialchars($value) . "</td>";
                                                } else {
                                                    echo "<td class=\"$stickyClass\">" . htmlspecialchars($value) . "</td>";
                                                }

                                            // Logika untuk sel lainnya (teks, angka 0, dll)
                                            } else {
                                                echo "<td class=\"$stickyClass\">" . htmlspecialchars($value) . "</td>";
                                            }
                                        }
                                        echo "</tr>";
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                    </div>
            </div>
		</div>
		    <!-- /.card-body -->
	</div>
		<!-- /.card -->
	</div>
  </div>
</div>
</section>
<!-- /.content -->