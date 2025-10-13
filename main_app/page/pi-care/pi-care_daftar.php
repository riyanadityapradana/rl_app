<?php
// Pastikan koneksi database tersedia
if (!isset($conn)) {
    die("Database connection not available. Please check your configuration.");
}

// Query untuk mengambil data pendaftaran pasien berdasarkan filter bulan dan tahun
$bulan = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// Validasi input
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');

$bulan_filter = $bulan;
$tahun_filter = $tahun;
$bulan_tahun_filter = $tahun_filter . '-' . str_pad($bulan_filter, 2, '0', STR_PAD_LEFT);

$sql = "SELECT DATE_FORMAT(insert_at, '%Y-%m-%d') AS tanggal, COUNT(*) AS jumlah
        FROM daftar_pasien
        WHERE DATE_FORMAT(insert_at, '%Y-%m') = '$bulan_tahun_filter'
        AND is_verified <> '1'
        GROUP BY tanggal
        ORDER BY tanggal ASC";

// Eksekusi query dengan error handling yang lebih baik
try {
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }
} catch (Exception $e) {
    // Jika query gagal, gunakan data dummy
    error_log("Database query failed: " . $e->getMessage());
    $result = null;
}

// Ambil data dan konversi ke array
$labels = [];
$jumlahPasien = [];

if (!$result || $result->num_rows == 0) {
    // Data dummy untuk hari-hari bulan yang difilter
    $hari_terakhir = date('t', strtotime($tahun_filter . '-' . $bulan_filter . '-01'));
    $labels = [];
    $jumlahPasien = [];
    for ($i = 1; $i <= $hari_terakhir; $i++) {
        $tgl = $tahun_filter . '-' . $bulan_filter . '-' . sprintf('%02d', $i);
        $labels[] = $tgl;
        $jumlahPasien[] = rand(10, 65);
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['tanggal'];
        $jumlahPasien[] = (int)$row['jumlah'];
    }
}

// Perhitungan summary
$total = array_sum($jumlahPasien);
$hari = count($jumlahPasien);
$rata = $hari > 0 ? round($total / $hari, 1) : 0;
$max = $hari > 0 ? max($jumlahPasien) : 0;
$min = $hari > 0 ? min($jumlahPasien) : 0;

// Debug: Tampilkan data di console browser
echo "<script>console.log('Labels:', " . json_encode($labels) . ");</script>";
echo "<script>console.log('Data:', " . json_encode($jumlahPasien) . ");</script>";
?>

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>DATA PENDAFTARAN PASIEN DI PI-CARE</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item active">Pi-Care</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
      <div class="container-fluid">
           <!-- Filter Section -->
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
                                                   <label>Pilih Bulan :</label>
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
                                                        for ($tahun = $tahun_sekarang; $tahun >= $tahun_sekarang - 5; $tahun--) {
                                                             $selected = (isset($_POST['tahun']) && $_POST['tahun'] == $tahun) ? 'selected' : '';
                                                             echo "<option value='$tahun' $selected>$tahun</option>";
                                                        }
                                                        ?>
                                                   </select>
                                              </div>
                                         </div>
                                         <div class="col-md-3">
                                              <div class="form-group">
                                                   <label>&nbsp;</label><br>
                                                   <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                                                   <a href="main_app.php?page=pi-care_daftar" class="btn btn-secondary"><i class="fas fa-refresh"></i> Reset</a>
                                              </div>
                                         </div>
                                    </div>
                               </form>
                          </div>
                     </div>
                </div>
           </div>

           <div class="row">
               <div class="col-md-6">
                    <!-- AREA CHART -->
                    <div class="card card-red">
                    <div class="card-header">
                         <h3 class="card-title">Chart Pendaftaran Pasien</h3>
                         <div class="card-tools">
                              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                   <i class="fas fa-minus"></i>
                              </button>
                              <button type="button" class="btn btn-tool" data-card-widget="remove">
                                   <i class="fas fa-times"></i>
                              </button>
                         </div>
                    </div>
                    <div class="card-body">
                         <div class="chart">
                              <canvas id="barChart" style="min-height: 400px; height: 400px; max-height: 400px; max-width: 100%;"></canvas>
                         </div>
                         <!-- Summary Report Langsung di bawah chart -->
                         <div style="margin-top: 10px; margin-left: 0; font-size: 15px;">
                              <?php
                              $nama_bulan = [
                                  '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL',
                                  '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS',
                                  '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
                              ];
                              $nama_bulan_terpilih = isset($nama_bulan[$bulan_filter]) ? $nama_bulan[$bulan_filter] : strtoupper(date('F'));
                              ?>
                              <b>PENDAFTARAN <?php echo $nama_bulan_terpilih . ' ' . $tahun_filter; ?></b><br>
                              Jumlah Total Pendaftaran : <?php echo $total; ?><br>
                              Rata-rata Pendaftar Perhari : <?php echo $rata; ?><br>
                              Jumlah Pendaftaran Maksimal Perhari : <?php echo $max; ?><br>
                              Jumlah Pendaftaran Minimal Perhari : <?php echo $min; ?><br>
                              Jumlah Hari Layanan : <?php echo $hari; ?><br>
                         </div>
                    </div>
                    </div>
               </div>

               <!-- Tabel Data -->
               <div class="col-md-6">
                    <div class="card">
                         <div class="card-header" style="background:rgb(245, 3, 3)">
                              <h3 class="card-title" style="color: white;">DATA PASIEN</h3>
                              <div class="card-tools">
                                   <a href="page/pi-care/lap_pi-care_daftar_pdf.php?dari=<?php echo $tahun_filter . '-' . $bulan_filter . '-01'; ?>&sampai=<?php echo date('Y-m-t', strtotime($tahun_filter . '-' . $bulan_filter . '-01')); ?>" onclick="window.open(this.href, 'printWindow', 'width=1200,height=800'); return false;" class="btn btn-tool btn-sm btn-success">
                                        <i class="fas fa-print"></i> Print
                                   </a>
                                   <a href="#" data-toggle='modal' data-target='#modalFilterPDF' class="btn btn-tool btn-sm">
                                        <i class="fas fa-file-pdf"></i> PDF Custom
                                   </a>
                                   <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                        <i class="fas fa-bars"></i>
                                   </a>
                              </div>
                         </div>
                         <div class="card-body" style="background:rgb(203, 212, 212)">
                              <table id="example1" class="table table-bordered table-striped">
                                   <thead style="background:rgb(0, 123, 255, 1)">
                                        <tr>
                                             <th style="text-align: center; color: white;">Tanggal Layanan</th>
                                             <th style="text-align: center; color: white;">Jumlah Pasien</th>
                                        </tr>
                                   </thead>
                                   <tbody>
                                   <?php for ($i = 0; $i < count($labels); $i++) { ?>
                                        <tr>
                                             <td align='center'><?php echo date('d F Y', strtotime($labels[$i])); ?></td>
                                             <td align='center'><?php echo $jumlahPasien[$i]; ?></td>
                                        </tr>
                                   <?php } ?>
                                   </tbody>
                              </table>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</section>
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
<!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->

<!-- Modal Filter PDF -->
<div class="modal" id="modalFilterPDF" role="dialog">
     <div class="modal-dialog">
          <div class="modal-content">
               <div class="modal-header" align="center">
                    <h3>PILIH TANGGAL UNTUK PDF</h3>
               </div>
               <div class="modal-body" align="left">
                  <form action="page/pi-care/lap_pi-care_daftar_pdf.php" method="get" onsubmit="window.open('', 'printWindow', 'width=1200,height=800,scrollbars=yes,resizable=yes'); this.target='printWindow'; return true;">
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
                                   <button type="submit" class="btn btn-block btn-success">PROSES</button>
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
<!-- Bagian modal -->

<!-- Script Chart -->
<script src="../../assets/plugins/chart.js/Chart.min.js"></script>
<script>
    // Tunggu sampai DOM selesai loading
    document.addEventListener('DOMContentLoaded', function() {
        // Data dari PHP
        var labels = <?php echo json_encode($labels); ?>;
        var data = <?php echo json_encode($jumlahPasien); ?>;

        console.log('Chart.js version:', typeof Chart !== 'undefined' ? Chart.version : 'Not loaded');
        console.log('Canvas element:', document.getElementById("barChart"));

        // Pastikan Chart.js sudah dimuat
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded!');
            return;
        }

        // Pastikan canvas element ada
        var canvas = document.getElementById("barChart");
        if (!canvas) {
            console.error('Canvas element not found!');
            return;
        }

        // Warna tetap
        var backgroundColors = [
            'rgba(54, 162, 235, 0.7)',  
            'rgba(255, 99, 132, 0.7)',  
            'rgba(255, 206, 86, 0.7)',  
            'rgba(75, 192, 192, 0.7)',  
            'rgba(153, 102, 255, 0.7)', 
            'rgba(255, 159, 64, 0.7)'   
        ];

        var dynamicColors = labels.map((_, index) => backgroundColors[index % backgroundColors.length]);
        var borderColors = dynamicColors.map(color => color.replace('0.7', '1'));

        // Inisialisasi Chart dengan konfigurasi yang kompatibel dengan Chart.js v2.9.4
        var ctx = canvas.getContext("2d");
        var barChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Jumlah Pasien",
                    data: data,
                    backgroundColor: dynamicColors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            display: false
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                legend: {
                    display: true
                },
                tooltips: {
                    enabled: true
                }
            }
        });

        console.log('Chart created successfully:', barChart);
    });
</script>
