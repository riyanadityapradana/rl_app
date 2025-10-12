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

// Ambil bulan dan tahun sekarang
$bulan = date('n'); // 1-12
$tahun = date('Y'); // 4 digit
// Cari jumlah hari dalam bulan ini
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

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
                AND ki.tgl_masuk BETWEEN '$start' AND '$end'
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
        <h2 style="font-size: 18px; color: black;">REKAP KUNJUNGAN PASIEN HARIAN RAWAT JALAN</h2>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item active">Rekap Kunjungan Pasien</li>
          <li class="breadcrumb-item"><a href="main_app.php?page=jum_px_ranap">Jumlah Pasien Ranap</a></li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>
<div class="top-bar">
    <h2>REKAP KUNJUNGAN PASIEN RAWAT INAP</h2>
    <button class="btn-grafik" onclick="showModal()">Lihat Grafik Bulanan</button>
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
    <h3>Grafik Kunjungan Pasien per Bulan</h3>
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
// Ambil data bulan sekarang saja
$now = new DateTime();
$start = $now->format('Y-m-01');
$end = $now->format('Y-m-t');
$label_bulan = [date('M Y', strtotime($start))];
// Rekap total per kamar untuk bulan ini
$data_bulanan = [];
foreach ($mapping_kamar as $group => $prefix) {
    $sql = "SELECT COUNT(*) as jml
            FROM kamar_inap ki
            JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            WHERE b.nm_bangsal LIKE '$prefix%'
            AND rp.kd_pj IN ('A09','BPJ','A92')
            AND ki.tgl_masuk BETWEEN '$start' AND '$end'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $data_bulanan[$group] = [(int)$row['jml']];
}
?>
const labelBulan = <?php echo json_encode($label_bulan); ?>;
const dataBulanan = <?php echo json_encode($data_bulanan); ?>;
function renderChartBulanan() {
  const ctx = document.getElementById('chartBulanan').getContext('2d');
  const datasets = Object.keys(dataBulanan).map((nama, idx) => ({
    label: nama,
    data: dataBulanan[nama],
    backgroundColor: `hsl(${idx*30},70%,60%)`,
    borderColor: `hsl(${idx*30},70%,40%)`,
    borderWidth: 1
  }));
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labelBulan,
      datasets: datasets
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { x: { stacked: true }, y: { beginAtZero: true, stacked: true } }
    }
  });
}
</script>
</body>
</html>
