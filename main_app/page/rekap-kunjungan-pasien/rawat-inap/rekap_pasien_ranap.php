<?php
// Koneksi ke database (hardcode, sesuaikan jika perlu)
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Daftar kamar sesuai urutan
$daftar_kamar = [
    'BERLIAN', 'SAFIR', 'RUBY A', 'RUBY B',
    'ZAMRUD A', 'ZAMRUD B', 'ZAMRUD C',
    'ISOLASI',
    'KECUBUNG A', 'KECUBUNG B1', 'KECUBUNG B2', 'KECUBUNG B3', 'KECUBUNG B4',
    'YAKUT A', 'YAKUT B', 'YAKUT C'
];
$mapping_kamar = [
    'YAKUT C' => 'YAKUT C',
    'YAKUT A' => 'YAKUT A',
    'YAKUT B' => 'YAKUT B',
    'ZAMRUD'  => 'ZAMRUD',
    'KECUBUNG' => 'KECUBUNG',
    'RUBY'    => 'RUBY',
    'SAFIR'   => 'SAFIR',
    'BERLIAN' => 'BERLIAN',
    // dst...
];
// Mapping jenis bayar
$jenis_bayar = [
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

// Cari jumlah hari dalam bulan yang dipilih
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, (int)$bulan, $tahun);

// Tentukan range minggu (bisa diubah manual atau otomatis)
$minggu = [];
$start = 1;
while ($start <= $jumlah_hari) {
    $end = min($start + 6, $jumlah_hari);
    $minggu[] = [
        date('Y-m-d', mktime(0, 0, 0, $bulan, $start, $tahun)),
        date('Y-m-d', mktime(0, 0, 0, $bulan, $end, $tahun))
    ];
    $start = $end + 1;
}

// Siapkan array rekap
$rekap = [];
foreach ($mapping_kamar as $group => $sub_kamar_list) {
    foreach ($minggu as $i => $range) {
        foreach ($jenis_bayar as $kd_pj => $label) {
            $rekap[$group][$i][$kd_pj] = 0;
        }
        $rekap[$group][$i]['JUMLAH'] = 0;
    }
}

// Query data per minggu, per kamar, per jenis bayar
foreach ($mapping_kamar as $group => $prefix) {
    foreach ($minggu as $i => $range) {
        $start = $range[0];
        $end = $range[1];
        $sql = "SELECT rp.kd_pj, COUNT(*) as jml
                FROM kamar_inap ki
                JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                WHERE k.kd_kamar LIKE '$prefix%'
                AND rp.kd_pj IN ('A09','BPJ','A92')
                AND rp.stts='Sudah'
                AND rp.status_bayar='Sudah Bayar'
                AND MONTH(ki.tgl_masuk) = " . (int)$bulan . " AND YEAR(ki.tgl_masuk) = " . (int)$tahun . "
                GROUP BY rp.kd_pj";
        $res = $conn->query($sql);
        foreach ($jenis_bayar as $kd_pj => $label) {
            $rekap[$group][$i][$kd_pj] = 0;
        }
        $rekap[$group][$i]['JUMLAH'] = 0;
        while ($row = $res->fetch_assoc()) {
            $rekap[$group][$i][$row['kd_pj']] = (int)$row['jml'];
            $rekap[$group][$i]['JUMLAH'] += (int)$row['jml'];
        }
    }
}


// Hitung total per jenis bayar dan total per minggu
$total_per_jenis = [];
$total_per_minggu = [];
foreach ($minggu as $i => $range) {
    foreach ($jenis_bayar as $kd_pj => $label) {
        $total_per_jenis[$i][$kd_pj] = 0;
    }
    $total_per_minggu[$i] = 0;
    foreach (array_keys($mapping_kamar) as $group) {
        foreach ($jenis_bayar as $kd_pj => $label) {
            $total_per_jenis[$i][$kd_pj] += $rekap[$group][$i][$kd_pj];
        }
        $total_per_minggu[$i] += $rekap[$group][$i]['JUMLAH'];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kunjungan Pasien Rawat Inap</title>
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
        <h2 style="font-size: 18px; color: black;">REKAP KUNJUNGAN PASIEN HARIAN RAWAT INAP</h2>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="dashboard_staff.php?unit=beranda">Home</a></li>
          <li class="breadcrumb-item active">Rekap Kunjungan Pasien</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>
<!-- Filter Section -->
<section class="content">
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
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th rowspan="2">KAMAR RAWAT</th>
            <?php foreach ($minggu as $i => $range): ?>
                <th colspan="4"><?= date('j', strtotime($range[0])) . ' - ' . date('j F Y', strtotime($range[1])) ?></th>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($minggu as $i => $range): ?>
                <th>UMUM</th>
                <th>BPJS</th>
                <th>ASURANSI</th>
                <th>JML</th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
            <?php foreach ($mapping_kamar as $group => $sub_kamar_list): ?>
                <tr>
                    <td><?= htmlspecialchars($group) ?></td>
                    <?php foreach ($minggu as $i => $range): ?>
                        <td><?= $rekap[$group][$i]['A09'] ?></td>
                        <td><?= $rekap[$group][$i]['BPJ'] ?? 0 ?></td>
                        <td><?= $rekap[$group][$i]['A92'] ?></td>
                        <td><strong><?= $rekap[$group][$i]['JUMLAH'] ?></strong></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: orange; font-weight: bold;">
            <td>JUMLAH PER JENIS BAYAR</td>
            <?php foreach ($minggu as $i => $range): ?>
                <td align="center"><?= $total_per_jenis[$i]['A09'] ?></td>
                <td align="center"><?= $total_per_jenis[$i]['BPJ'] ?? 0 ?></td>
                <td align="center"><?= $total_per_jenis[$i]['A92'] ?></td>
                <td align="center"><?= $total_per_minggu[$i] ?></td>
            <?php endforeach; ?>
        </tr>
        <tr style="background-color: orangered; font-weight: bold;">
            <td>JUMLAH PX PER MINGGU</td>
            <?php foreach ($minggu as $i => $range): ?>
                <td colspan="4" align="center"><?= $total_per_minggu[$i] ?></td>
            <?php endforeach; ?>
        </tr>
    </tfoot>
</table>

<!-- Modal Grafik Bulanan -->
<div class="modal-bg" id="modalGrafik">
  <div class="modal-content">
    <span class="modal-close" onclick="closeModal()">&times;</span>
    <h3>Grafik Kunjungan Pasien Rawat Inap Harian (<?php echo $bulanList[str_pad($bulan, 2, '0', STR_PAD_LEFT)] . ' ' . $tahun; ?>)</h3>
    <canvas id="chartBulanan" style="min-width:350px; min-height:300px;"></canvas>
  </div>
</div>

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
<?php
// Ambil data harian untuk bulan dan tahun yang dipilih
$start_date = sprintf('%04d-%02d-01', $tahun, $bulan);
$end_date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $jumlah_hari);
$period = new DatePeriod(
    new DateTime($start_date),
    new DateInterval('P1D'),
    new DateTime($end_date . ' +1 day')
);

$label_harian = [];
$data_harian = [];

// Inisialisasi array untuk setiap kamar
foreach ($mapping_kamar as $group => $prefix) {
    $data_harian[$group] = [];
}

// Ambil data untuk setiap tanggal
foreach ($period as $date) {
    $tanggal = $date->format('Y-m-d');
    $label_harian[] = $date->format('m/d');

    foreach ($mapping_kamar as $group => $prefix) {
        $sql = "SELECT COUNT(*) as jml
                FROM kamar_inap ki
                JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                WHERE k.kd_kamar LIKE '$prefix%'
                AND rp.kd_pj IN ('A09','BPJ','A92')
                AND rp.stts='Sudah'
                AND rp.status_bayar='Sudah Bayar'
                AND DATE(ki.tgl_masuk) = '$tanggal'";
        $res = $conn->query($sql);
        $row = $res->fetch_assoc();
        $data_harian[$group][] = (int)$row['jml'];
    }
}
?>
const labelHarian = <?php echo json_encode($label_harian); ?>;
const dataHarian = <?php echo json_encode($data_harian); ?>;
function renderChartBulanan() {
  const ctx = document.getElementById('chartBulanan').getContext('2d');

  // Hitung total dari semua kamar untuk setiap tanggal
  const totalData = labelHarian.map((_, index) =>
    Object.keys(dataHarian).reduce((sum, kamar) => sum + (dataHarian[kamar][index] || 0), 0)
  );

  const datasets = [{
    label: 'Total Kunjungan Rawat Inap',
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
          max: 50,
          grid: {
            color: 'rgba(0, 0, 0, 0.1)',
            lineWidth: 1
          },
          ticks: {
            stepSize: 5,
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
