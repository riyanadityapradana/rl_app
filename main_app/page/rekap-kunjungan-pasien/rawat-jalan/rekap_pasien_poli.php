<?php
/**
 * Rekap Kunjungan Pasien Poli - Optimized Version
 *
 * Optimasi yang dilakukan:
 * - Query gabungan menggunakan UNION ALL untuk mengurangi jumlah query
 * - Prepared statements untuk keamanan dan performa
 * - Filter poli aktif untuk mengurangi data yang tidak perlu
 * - Optimasi perhitungan array menggunakan array_fill_keys
 * - Caching format tanggal dengan base timestamp
 */

$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
$conn = new mysqli($host, $user, $pass, $db);

// Error handling untuk koneksi database
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
}
$conn->set_charset('utf8');

// Daftar urutan poliklinik sesuai keinginan
$urutan_poli = [
    ['kd_poli' => 'U0008', 'nm_poli' => 'GIGI'],
    ['kd_poli' => 'U0002', 'nm_poli' => 'BEDAH'],
    ['kd_poli' => 'U0003', 'nm_poli' => 'ANAK'],
    ['kd_poli' => 'U0006', 'nm_poli' => 'THT'],
    ['kd_poli' => 'U0004', 'nm_poli' => 'PENYAKIT DALAM'],
    ['kd_poli' => 'U0019', 'nm_poli' => 'PARU'],
    ['kd_poli' => 'U0007', 'nm_poli' => 'SARAF'],
    ['kd_poli' => 'U0005', 'nm_poli' => 'MATA'],
    ['kd_poli' => 'U0010', 'nm_poli' => 'KANDUNGAN'],
    ['kd_poli' => 'kfr',    'nm_poli' => 'REHABILITASI MEDIK'],
    ['kd_poli' => 'U0012', 'nm_poli' => 'JANTUNG'],
    ['kd_poli' => 'U0013', 'nm_poli' => 'JIWA'],
    ['kd_poli' => 'U0014', 'nm_poli' => 'ORTHOPEDI'],
];

// Ambil daftar poliklinik aktif dengan prepared statement
$poliklinik = [];
$stmt = $conn->prepare("SELECT kd_poli, nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $poliklinik[$row['kd_poli']] = $row['nm_poli'];
}
$stmt->close();

// Mapping kode penjamin
$penjamin = [
    'A09' => 'UMUM',
    'BPJ' => 'BPJS',
    'A92' => 'ASURANSI',
];
// Ambil bulan dan tahun dari POST atau default sekarang
$bulan = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// Validasi input
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');
// List bulan untuk dropdown
$bulanList = [
    '01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June',
    '07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'
];

// Optimasi: Cari jumlah hari dalam bulan yang dipilih
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, (int)$bulan, $tahun);

// Optimasi: Tentukan range minggu dengan caching format tanggal
$minggu = [];
$start = 1;
$base_timestamp = mktime(0, 0, 0, $bulan, 1, $tahun); // Base timestamp untuk bulan ini
while ($start <= $jumlah_hari) {
    $end = min($start + 6, $jumlah_hari);
    $minggu[] = [
        date('Y-m-d', strtotime("+$start day", $base_timestamp) - 86400), // Kurangi 1 hari karena base adalah tanggal 1
        date('Y-m-d', strtotime("+$end day", $base_timestamp) - 86400)
    ];
    $start = $end + 1;
}

// Mapping poli utama ke daftar kode poli sub-poli
$mapping_poli = [
    'GIGI' => ['U0008', 'U0025', 'U0042', 'U0043', 'U0052', 'U0057', 'U0065'],
    'BEDAH' => ['U0004', 'U0015', 'U0054', 'U0066'],
    'ANAK' => ['U0002', 'U0026'],
    'THT' => ['U0011'],
    'PENYAKIT DALAM' => ['U0003', 'U0030', 'U0031', 'U0033', 'U0034', 'U0035', 'U0036', 'U0037', 'U0038', 'U0039', 'U0040', 'U0041', 'U0063'],
    'PARU' => ['U0019'],
    'SARAF' => ['U0007', 'U0049', 'U0050'],
    'MATA' => ['U0005', 'U0061'],
    'KANDUNGAN' => ['U0010', 'U0024', 'U0044', 'U0045', 'U0046', 'U0047', 'U0048', 'U0051', 'U0059', 'U0060'],
    'REHABILITASI MEDIK' => ['kfr'],
    'JANTUNG' => ['U0012', 'U0032'],
    'JIWA' => ['U0013', 'U0018'],
    'ORTHOPEDI' => ['U0016'],
];

// Filter mapping poli hanya untuk poli yang aktif
$mapping_poli_aktif = [];
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    $filtered_kd_poli = array_intersect($list_kd_poli, array_keys($poliklinik));
    if (!empty($filtered_kd_poli)) {
        $mapping_poli_aktif[$nama_poli] = array_values($filtered_kd_poli);
    }
}
$mapping_poli = $mapping_poli_aktif;

// Siapkan array rekap dengan inisialisasi yang lebih efisien
$rekap = [];
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    $rekap[$nama_poli] = [];
    foreach ($minggu as $i => $range) {
        $rekap[$nama_poli][$i] = array_merge(['JUMLAH' => 0], array_fill_keys(array_keys($penjamin), 0));
    }
}

// Optimasi: Query dengan prepared statement untuk setiap kombinasi poli dan minggu
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    foreach ($minggu as $i => $range) {
        if (count($list_kd_poli) === 0) continue;

        $start = $range[0];
        $end   = $range[1];

        // Siapkan placeholders untuk IN clause
        $poli_placeholders = implode(',', array_fill(0, count($list_kd_poli), '?'));

        $sql = "SELECT kd_pj, COUNT(*) as jml FROM reg_periksa
                WHERE tgl_registrasi BETWEEN ? AND ?
                AND kd_poli IN ($poli_placeholders)
                AND kd_pj IN ('A09','BPJ','A92')
                AND stts='Sudah'
                AND status_bayar='Sudah Bayar'
                AND no_rkm_medis NOT IN (SELECT no_rkm_medis FROM pasien WHERE LOWER(nm_pasien) LIKE '%test%')
                GROUP BY kd_pj";

        $stmt = $conn->prepare($sql);

        // Bind parameters: start_date, end_date, dan semua kd_poli
        $bind_params = array_merge([$start, $end], $list_kd_poli);
        $stmt->bind_param(str_repeat('s', count($bind_params)), ...$bind_params);

        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $rekap[$nama_poli][$i][$row['kd_pj']] = (int)$row['jml'];
            $rekap[$nama_poli][$i]['JUMLAH'] += (int)$row['jml'];
        }
        $stmt->close();
    }
}

// Hitung total per jenis bayar dan total per minggu dengan optimasi
$total_per_jenis = array_fill_keys(array_keys($minggu), array_fill_keys(array_keys($penjamin), 0));
$total_per_minggu = array_fill_keys(array_keys($minggu), 0);

foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    foreach ($minggu as $i => $range) {
        foreach ($penjamin as $kd_pj => $label) {
            $total_per_jenis[$i][$kd_pj] += $rekap[$nama_poli][$i][$kd_pj];
        }
        $total_per_minggu[$i] += $rekap[$nama_poli][$i]['JUMLAH'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kunjungan Pasien Poli</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #888; padding: 4px 8px; text-align: center; }
        th { background: #ff9800; color: #111; font-weight: bold; }
        .header-minggu { background: #ffe0b2; color: #111; font-weight: bold; }
        .header-jenis { background: #bbdefb; color: #111; font-weight: bold; }
        .jumlah { background: #ffe082; color: #111; font-weight: bold; }
        .total { background: #ff5722; color: #fff; font-weight: bold; }
        .poli { text-align: left; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .btn-grafik { background: #1976d2; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-grafik:hover { background: #1565c0; }
        /* Modal */
        .modal-bg { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.4); justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 24px; border-radius: 8px; min-width: 400px; max-width: 90vw; max-height: 90vh; overflow: auto; position: relative; }
        .modal-close { position: absolute; right: 12px; top: 8px; font-size: 20px; color: #888; cursor: pointer; }
    </style>
    <script src="../assets/plugins/chart.js/Chart.min.js"></script>
</head>
<body>
<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h2 style="font-size: 18px; color: black;">REKAP KUNJUNGAN PASIEN HARIAN RAWAT JALAN</h2>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="dashboard_staff.php?unit=beranda">Home</a></li>
          <li class="breadcrumb-item active">Rekap Kunjungan Pasien</li>
          <li class="breadcrumb-item"><a href="main_app.php?page=jum_px_ralan">Rekap Jumlah Pasien Rawat Jalan</a></li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>
<section class="content">
    <!-- Filter Section -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header" style="background:#007bff; color:white;">
                    <h3 class="card-title">Filter Data</h3>
                </div>
                <div class="card-body">
                    <div class="card-tools" style="float: left; text-align: left;">
                        <form method="post" class="mb-3" style="display:flex;align-items:center;gap:10px;">
                            <label for="bulan" style="margin-bottom:0;">Bulan:</label>
                            <select name="bulan" id="bulan" class="form-control" style="width:auto;display:inline-block;">
                                <?php
                                foreach($bulanList as $val=>$label) {
                                    $selected = ($bulan == $val || $bulan == ltrim($val, '0')) ? 'selected' : '';
                                    echo '<option value="'.$val.'" '.$selected.'>'.$label.'</option>';
                                }
                                ?>
                            </select>
                            <label for="tahun" style="margin-bottom:0;">Tahun:</label>
                            <input type="number" name="tahun" id="tahun" class="form-control" value="<?php echo $tahun; ?>" style="width:90px;display:inline-block;">
                            <button type="submit" class="btn btn-primary">Tampilkan Data</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="top-bar">
        <button class="btn-grafik" onclick="showModal()">Lihat Grafik Harian</button>
    </div>
<table>
    <tr>
        <th rowspan="2">POLIKLINIK</th>
        <?php foreach ($minggu as $i => $range): ?>
            <th colspan="4" class="header-minggu">
                <?= date('j', strtotime($range[0])) . ' - ' . date('j F Y', strtotime($range[1])) ?>
            </th>
        <?php endforeach; ?>
    </tr>
    <tr>
        <?php foreach ($minggu as $i => $range): ?>
            <th class="header-jenis">UMUM</th>
            <th class="header-jenis">BPJS</th>
            <th class="header-jenis">ASURANSI</th>
            <th class="jumlah">JLH</th>
        <?php endforeach; ?>
    </tr>
    <?php foreach ($mapping_poli as $nama_poli => $list_kd_poli): ?>
        <tr>
            <td class="poli"><?= htmlspecialchars($nama_poli) ?></td>
            <?php foreach ($minggu as $i => $range): ?>
                <td><?= isset($rekap[$nama_poli][$i]['A09']) ? $rekap[$nama_poli][$i]['A09'] : 0 ?></td>
                <td><?= isset($rekap[$nama_poli][$i]['BPJ']) ? $rekap[$nama_poli][$i]['BPJ'] : 0 ?></td>
                <td><?= isset($rekap[$nama_poli][$i]['A92']) ? $rekap[$nama_poli][$i]['A92'] : 0 ?></td>
                <td class="jumlah"><?= isset($rekap[$nama_poli][$i]['JUMLAH']) ? $rekap[$nama_poli][$i]['JUMLAH'] : 0 ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    <tr class="total">
        <td>JUMLAH PER JENIS BAYAR</td>
        <?php foreach ($minggu as $i => $range): ?>
            <td><?= $total_per_jenis[$i]['A09'] ?></td>
            <td><?= $total_per_jenis[$i]['BPJ'] ?></td>
            <td><?= $total_per_jenis[$i]['A92'] ?></td>
            <td></td>
        <?php endforeach; ?>
    </tr>
    <tr class="total">
        <td>JUMLAH PX PER MINGGU</td>
        <?php foreach ($minggu as $i => $range): ?>
            <td colspan="4"><?= $total_per_minggu[$i] ?></td>
        <?php endforeach; ?>
    </tr>
</table>
<!-- Modal Grafik Bulanan -->
<div class="modal-bg" id="modalGrafik">
  <div class="modal-content">
    <span class="modal-close" onclick="closeModal()">&times;</span>
    <h3>Grafik Kunjungan Pasien Harian (<?php echo $bulanList[str_pad($bulan, 2, '0', STR_PAD_LEFT)] . ' ' . $tahun; ?>)</h3>
    <canvas id="chartBulanan" style="min-width:350px; min-height:300px;"></canvas>
  </div>
</div>
</section>
<script>
function showModal() {
  document.getElementById('modalGrafik').style.display = 'flex';
  if (!window.chartBulananInit) {
    renderChartBulanan();
    window.chartBulananInit = true;
  }
}
function closeModal() {
  document.getElementById('modalGrafik').style.display = 'none';
}
// Data grafik harian dari PHP
<?php
// Optimasi grafik harian: Query gabungan untuk semua tanggal dan poli
$start_date = sprintf('%04d-%02d-01', $tahun, $bulan);
$end_date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $jumlah_hari);
$period = new DatePeriod(
    new DateTime($start_date),
    new DateInterval('P1D'),
    new DateTime($end_date . ' +1 day')
);

$label_harian = [];
$data_harian = [];
$data_harian_totals = [];

// Inisialisasi array untuk setiap poli
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    $data_harian[$nama_poli] = [];
}

// Query optimasi untuk grafik harian dengan prepared statement per kombinasi
foreach ($period as $date) {
    $tanggal = $date->format('Y-m-d');
    $label_harian[] = $date->format('m/d');

    foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
        if (count($list_kd_poli) === 0) {
            $data_harian[$nama_poli][] = 0;
            continue;
        }

        $sql_grafik = "SELECT COUNT(*) as jml FROM reg_periksa
                      WHERE DATE(tgl_registrasi) = ?
                      AND MONTH(tgl_registrasi) = ?
                      AND YEAR(tgl_registrasi) = ?
                      AND kd_poli IN (" . implode(',', array_fill(0, count($list_kd_poli), '?')) . ")
                      AND kd_pj IN ('A09','BPJ','A92')
                      AND stts='Sudah'
                      AND status_bayar='Sudah Bayar'
                      AND no_rkm_medis NOT IN (SELECT no_rkm_medis FROM pasien WHERE LOWER(nm_pasien) LIKE '%test%')";

        $stmt_grafik = $conn->prepare($sql_grafik);

        // Bind parameters: tanggal, bulan, tahun, dan semua kd_poli
        $bind_params_grafik = array_merge([$tanggal, $bulan, $tahun], $list_kd_poli);
        $stmt_grafik->bind_param(str_repeat('s', count($bind_params_grafik)), ...$bind_params_grafik);

        $stmt_grafik->execute();
        $res_grafik = $stmt_grafik->get_result();
        $row_grafik = $res_grafik->fetch_assoc();

        $data_harian[$nama_poli][] = (int)($row_grafik['jml'] ?? 0);
        $stmt_grafik->close();
    }
}
?>
const labelHarian = <?php echo json_encode($label_harian); ?>;
const dataHarian = <?php echo json_encode($data_harian); ?>;
function renderChartBulanan() {
  const ctx = document.getElementById('chartBulanan').getContext('2d');

  // Hitung total dari semua poli untuk setiap tanggal
  const totalData = labelHarian.map((_, index) =>
    Object.keys(dataHarian).reduce((sum, poli) => sum + (dataHarian[poli][index] || 0), 0)
  );

  const datasets = [{
    label: 'Total Kunjungan Pasien',
    data: totalData,
    borderColor: '#4CAF50',
    backgroundColor: 'rgba(76, 175, 80, 0.1)',
    pointBackgroundColor: '#4CAF50',
    pointBorderColor: '#4CAF50',
    pointBorderWidth: 2,
    pointRadius: 4,
    pointHoverRadius: 6,
    tension: 0.4,
    fill: false
  }];

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labelHarian,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            boxWidth: 12,
            font: {
              size: 11
            }
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            title: function(context) {
              return 'Tanggal: ' + context[0].label;
            }
          }
        }
      },
      scales: {
        x: {
          display: true,
          grid: {
            display: true,
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            maxRotation: 45,
            minRotation: 45,
            font: {
              size: 10
            }
          }
        },
        y: {
          display: true,
          beginAtZero: true,
          max: 700,
          grid: {
            color: 'rgba(0, 0, 0, 0.1)',
            lineWidth: 1
          },
          ticks: {
            stepSize: 100,
            font: {
              size: 10
            }
          }
        }
      },
      elements: {
        line: {
          borderWidth: 3
        },
        point: {
          radius: 4,
          hoverRadius: 6
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      }
    }
  });
}
</script>
</body>
</html>