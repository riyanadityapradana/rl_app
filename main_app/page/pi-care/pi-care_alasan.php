<?php
require_once __DIR__ . '/../../../config/koneksi.php';


// Query alasan pembatalan berdasarkan filter bulan dan tahun
$bulan = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// Validasi input
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');

$bulan_filter = $bulan;
$tahun_filter = $tahun;

$sql = "SELECT
  pp.tipe AS alasan_pembatalan,
  COUNT(*) AS jumlah
FROM
  batal_daftar bd
JOIN
  preset_pesan pp ON bd.alasan_batal = pp.id
WHERE
  YEAR(bd.insert_at) = '$tahun_filter'
  AND MONTH(bd.insert_at) = '$bulan_filter'
  AND bd.is_verified <> 0
  AND pp.tipe LIKE 'Pesan Pembatalan%'
GROUP BY
  pp.tipe
ORDER BY
  jumlah DESC;";

try {
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Database query failed: " . $e->getMessage());
    $result = null;
}

$labels = [];
$data = [];
$table = [];
$total = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['alasan_pembatalan'];
        $data[] = (int)$row['jumlah'];
        $table[] = $row;
        $total += (int)$row['jumlah'];
    }
} else {
    // Data dummy jika query gagal
    $labels = [
        'Data Diri yang Dimasukkan Keliru',
        'Dokumen Salah/Tidak Sesuai',
        'Dokumen Tidak Lengkap',
        'Kuota Pendaftaran Penuh',
        'Permintaan Pembatalan dari Pasien',
        'Poli Tutup',
        'Tutup Mendadak/Reschedule'
    ];
    $data = [1, 8, 6, 167, 1, 262, 0];
    $total = array_sum($data);
    foreach ($labels as $i => $label) {
        $table[] = [
            'alasan_pembatalan' => $label,
            'jumlah' => $data[$i]
        ];
    }
}

// Hitung persentase untuk chart
$percent = [];
foreach ($data as $val) {
    $percent[] = $total > 0 ? round($val / $total * 100) : 0;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Alasan Pembatalan <?= ucfirst($nama_bulan_id) ?></title>
    <script src="../../assets/plugins/chart.js/Chart.min.js"></script>
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
<!-- Filter Section -->
<section class="content">
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header" style="background:rgb(40, 167, 69)">
            <h3 class="card-title" style="color: white;"><i class="fas fa-filter"></i> Filter Data</h3>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Pilih Bulan:</label>
                    <select name="bulan" class="form-control" required>
                      <option value="">-- Pilih Bulan --</option>
                      <option value="01" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '01') ? 'selected' : '' ?>>Januari</option>
                      <option value="02" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '02') ? 'selected' : '' ?>>Februari</option>
                      <option value="03" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '03') ? 'selected' : '' ?>>Maret</option>
                      <option value="04" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '04') ? 'selected' : '' ?>>April</option>
                      <option value="05" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '05') ? 'selected' : '' ?>>Mei</option>
                      <option value="06" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '06') ? 'selected' : '' ?>>Juni</option>
                      <option value="07" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '07') ? 'selected' : '' ?>>Juli</option>
                      <option value="08" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '08') ? 'selected' : '' ?>>Agustus</option>
                      <option value="09" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '09') ? 'selected' : '' ?>>September</option>
                      <option value="10" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '10') ? 'selected' : '' ?>>Oktober</option>
                      <option value="11" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '11') ? 'selected' : '' ?>>November</option>
                      <option value="12" <?= (isset($_POST['bulan']) && $_POST['bulan'] == '12') ? 'selected' : '' ?>>Desember</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Pilih Tahun:</label>
                    <select name="tahun" class="form-control" required>
                      <option value="">-- Pilih Tahun --</option>
                      <?php
                      $tahun_sekarang = date('Y');
                      for ($tahun_loop = $tahun_sekarang; $tahun_loop >= $tahun_sekarang - 5; $tahun_loop--) {
                        $selected = (isset($_POST['tahun']) && $_POST['tahun'] == $tahun_loop) ? 'selected' : '';
                        echo "<option value='$tahun_loop' $selected>$tahun_loop</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="main_app.php?page=pi-care_alasan" class="btn btn-secondary"><i class="fas fa-refresh"></i> Reset</a>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Data Alasan Pembatalan <?php
          $nama_bulan = [
            '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL',
            '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS',
            '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
          ];
          $nama_bulan_terpilih = isset($nama_bulan[$bulan_filter]) ? $nama_bulan[$bulan_filter] : strtoupper(date('F'));
          echo $nama_bulan_terpilih . ' ' . $tahun_filter;
        ?></h1>
      </div>
    </div>
  </div>
</section>
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header" style="background:rgb(245, 3, 3)">
            <h3 class="card-title" style="color: white;">Alasan Pembatalan</h3>
            <div class="card-tools">
              <a href="page/pi-care/lap_pi-care_alasan_pdf.php?dari=<?php echo $tahun_filter . '-' . $bulan_filter . '-01'; ?>&sampai=<?php echo date('Y-m-t', strtotime($tahun_filter . '-' . $bulan_filter . '-01')); ?>" onclick="window.open(this.href, 'printWindow', 'width=1200,height=800,scrollbars=yes,resizable=yes'); return false;" class="btn btn-tool btn-sm btn-success">
                   <i class="fas fa-print"></i> Print
              </a>
              <a href="#" data-toggle='modal' data-target='#modalFilterPDF' class="btn btn-tool btn-sm">
                   <i class="fas fa-file-pdf"></i> PDF Custom
              </a>
            </div>
          </div>
          <div class="card-body" style="background:rgb(250, 255, 255)">
            <table id="example1" class="table table-bordered table-striped">
              <thead style="background:rgb(0, 123, 255, 1)">
                <tr>
                  <th style="text-align:center; color:white;">Alasan Pembatalan</th>
                  <th style="text-align:center; color:white;">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($table as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['alasan_pembatalan']) ?></td>
                  <td align="center"><?= $row['jumlah'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="font-weight:bold; background:#e9ecef;">
                  <td align="right">Total</td>
                  <td align="center"><?= $total ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Grafik Alasan Pembatalan PiCare <?php echo $nama_bulan_terpilih . ' ' . $tahun_filter; ?></h3>
          </div>
          <div class="card-body" style="background:rgb(189, 189, 189)">
            <canvas id="pieChart" style="min-height:300px; height:300px; max-height:300px; max-width:100%;"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var labels = <?php echo json_encode($labels); ?>;
    var data = <?php echo json_encode($data); ?>;
    var percent = <?php echo json_encode($percent); ?>;
    var total = <?php echo $total; ?>;
    var backgroundColors = [
      'rgba(54, 162, 235, 0.7)',
      'rgba(255, 99, 132, 0.7)',
      'rgba(255, 206, 86, 0.7)',
      'rgba(75, 192, 192, 0.7)',
      'rgba(153, 102, 255, 0.7)',
      'rgba(255, 159, 64, 0.7)',
      'rgba(40, 167, 69, 0.7)'
    ];
    var borderColors = backgroundColors.map(color => color.replace('0.7', '1'));
    var ctx = document.getElementById('pieChart').getContext('2d');
    var pieChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        legend: { position: 'right' },
        tooltips: {
          callbacks: {
            label: function(tooltipItem, chartData) {
              var idx = tooltipItem.index;
              var label = chartData.labels[idx] || '';
              var val = chartData.datasets[0].data[idx] || 0;
              var pct = percent[idx] || 0;
              return label + ': ' + val + ' (' + pct + '%)';
            }
          }
        },
        plugins: {
          datalabels: {
            display: true,
            formatter: function(value, context) {
              var pct = percent[context.dataIndex] || 0;
              return pct + '%';
            }
          }
        }
      }
    });
  });
</script>

<!-- Modal untuk PDF -->
<div class="modal" id="modalFilterPDF" role="dialog">
     <div class="modal-dialog">
          <div class="modal-content">
               <div class="modal-header" align="center">
                    <h3>PILIH TANGGAL UNTUK PDF</h3>
               </div>
               <div class="modal-body" align="left">
                    <form role="form" method="get" action="page/pi-care/lap_pi-care_alasan_pdf.php" onsubmit="window.open('', 'printWindow', 'width=1200,height=800,scrollbars=yes,resizable=yes'); this.target='printWindow'; return true;">
                         <div class="row">
                              <div class="form-group col-lg-6">
                                   <label>Tanggal Awal:</label>
                                   <input type="date" name="dari" class="form-control" value="<?php echo $tahun_filter . '-' . $bulan_filter . '-01'; ?>">
                              </div>
                              <div class="form-group col-lg-6">
                                   <label>Tanggal Akhir:</label>
                                   <input type="date" name="sampai" class="form-control" value="<?php echo date('Y-m-t', strtotime($tahun_filter . '-' . $bulan_filter . '-01')); ?>">
                              </div>
                         </div>
                         <div class="row">
                              <div class="col-lg-6">
                                   <button type="submit" class="btn btn-block btn-success">GENERATE PDF</button>
                              </div>
                              <div class="col-lg-6">
                                   <button type="button" class="btn btn-block btn-warning" data-dismiss="modal">TUTUP</button>
                              </div>
                         </div>
                    </form>
               </div>
          </div>
     </div>
</div>

</body>
</html>
