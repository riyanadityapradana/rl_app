<?php
// Koneksi ke database sik9 (hardcode, sesuaikan jika perlu)
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);
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

// Ambil daftar poliklinik aktif
$poliklinik = [];
$res = $conn->query("SELECT kd_poli, nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli");
while ($row = $res->fetch_assoc()) {
    $poliklinik[$row['kd_poli']] = $row['nm_poli'];
}

// Mapping kode penjamin
$penjamin = [
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
    // Tambahkan mapping lain sesuai kebutuhan
];

// Siapkan array rekap
$rekap = [];
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    foreach ($minggu as $i => $range) {
        foreach ($penjamin as $kd_pj => $label) {
            $rekap[$nama_poli][$i][$kd_pj] = 0;
        }
        $rekap[$nama_poli][$i]['JUMLAH'] = 0;
    }
}

// Query data per minggu, per poli utama (akumulasi sub-poli), per penjamin
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    foreach ($minggu as $i => $range) {
        $start = $range[0];
        $end   = $range[1];
        if (count($list_kd_poli) === 0) continue;
        $sql = "SELECT kd_pj, COUNT(*) as jml FROM reg_periksa WHERE tgl_registrasi BETWEEN '$start' AND '$end' AND kd_poli IN ('" . implode("','", $list_kd_poli) . "') AND kd_pj IN ('A09','BPJ','A92') AND no_rkm_medis NOT IN (SELECT no_rkm_medis FROM pasien WHERE LOWER(nm_pasien) LIKE '%test%') GROUP BY kd_pj";
        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $rekap[$nama_poli][$i][$row['kd_pj']] = (int)$row['jml'];
            $rekap[$nama_poli][$i]['JUMLAH'] += (int)$row['jml'];
        }
    }
}

// Hitung total per jenis bayar dan total per minggu
$total_per_jenis = [];
$total_per_minggu = [];
foreach ($minggu as $i => $range) {
    foreach ($penjamin as $kd_pj => $label) {
        $total_per_jenis[$i][$kd_pj] = 0;
    }
    $total_per_minggu[$i] = 0;
    foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
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
<div class="top-bar">
    <h2>REKAP KUNJUNGAN PASIEN HARIAN RAWAT JALAN</h2>
    <button class="btn-grafik" onclick="showModal()">Lihat Grafik Bulanan</button>
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
// Data grafik bulanan dari PHP
<?php
// Ambil data bulan sekarang saja
$now = new DateTime();
$start = $now->format('Y-m-01');
$end = $now->format('Y-m-t');
$label_bulan = [date('M Y', strtotime($start))];
// Rekap total per poli utama untuk bulan ini
$data_bulanan = [];
foreach ($mapping_poli as $nama_poli => $list_kd_poli) {
    if (count($list_kd_poli) === 0) { $data_bulanan[$nama_poli] = [0]; continue; }
    $sql = "SELECT COUNT(*) as jml FROM reg_periksa WHERE tgl_registrasi BETWEEN '$start' AND '$end' AND kd_poli IN ('" . implode("','", $list_kd_poli) . "') AND kd_pj IN ('A09','BPJ','A92') AND no_rkm_medis NOT IN (SELECT no_rkm_medis FROM pasien WHERE LOWER(nm_pasien) LIKE '%test%')";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $data_bulanan[$nama_poli] = [(int)$row['jml']];
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
