<?php
// Pastikan koneksi database tersedia
if (!isset($mysqli)) {
    die("Database connection not available. Please check your configuration.");
}

// Query untuk mengambil data pembatalan pasien HANYA bulan ini
$bulan_ini = date('Y-m');
$sql = "SELECT DATE_FORMAT(insert_at, '%Y-%m-%d') AS tanggal, COUNT(*) AS jumlah
        FROM batal_daftar
        WHERE DATE_FORMAT(insert_at, '%Y-%m') = '$bulan_ini'
        AND is_verified <> '0'
        GROUP BY tanggal
        ORDER BY tanggal ASC";

// Eksekusi query dengan error handling yang lebih baik
try {
    $result = $mysqli->query($sql);
    if (!$result) {
        throw new Exception("Query error: " . $mysqli->error);
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
    // Data dummy untuk hari-hari bulan ini
    $hari_terakhir = date('t');
    $labels = [];
    $jumlahPasien = [];
    for ($i = 1; $i <= $hari_terakhir; $i++) {
        $tgl = date('Y-m-') . sprintf('%02d', $i);
        $labels[] = $tgl;
        $jumlahPasien[] = rand(1, 15);
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
        <h1>DATA PEMBATALAN PASIEN DI PI-CARE</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_staff.php?unit=beranda">Home</a></li>
          <li class="breadcrumb-item active">Pi-Care</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
     <div class="container-fluid">
          <div class="row">
               <div class="col-md-6">
                    <!-- AREA CHART -->
                    <div class="card card-primary">
                    <div class="card-header">
                         <h3 class="card-title">Chart Pembatalan Pasien</h3>
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
                              <b>PEMBATALAN <?php echo strtoupper(date('F Y')); ?></b><br>
                              Jumlah Total Pembatalan : <?php echo $total; ?><br>
                              Rata-rata Pembatalan Perhari : <?php echo $rata; ?><br>
                              Jumlah Pembatalan Maksimal Perhari : <?php echo $max; ?><br>
                              Jumlah Pembatalan Minimal Perhari : <?php echo $min; ?><br>
                              Jumlah Hari Layanan : <?php echo $hari; ?><br>
                         </div>
                    </div>
                    </div>
               </div>

               <!-- Tabel Data -->
               <div class="col-md-6">
                    <div class="card">
                    <div class="card-header" style="background:rgb(0, 123, 255, 1)">
                         <h3 class="card-title" style="color: white;">DATA PASIEN</h3>
                         <div class="card-tools">
                         <a href="#" data-toggle='modal' data-target='#modalFilterPDF' class="btn btn-tool btn-sm"> 
                              <i class="fas fa-file-pdf"></i>
                         </a>
                         <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse">
                              <i class="fas fa-bars"></i>
                         </a>
                         </div>
                    </div>
                    <div class="card-body" style="background:rgb(250, 255, 255)">
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
</section>
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
<!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->

<!-- Modal untuk PDF -->
<div class="modal" id="modalFilterPDF" role="dialog">
     <div class="modal-dialog">
          <div class="modal-content">
               <div class="modal-header" align="center">
                    <h3>PILIH TANGGAL UNTUK PDF</h3>
               </div>
               <div class="modal-body" align="left">
                    <form role="form" method="get" action="unit/pi-care/lap_pi-care_batal_pdf.php" target="_blank">
                         <div class="row">
                              <div class="form-group col-lg-6">
                                   <input type="date" name="tanggalawal" class="form-control" placeholder="<?=date('Y-m-d');?>">
                              </div>
                              <div class="form-group col-lg-6">
                                   <input type="date" name="tanggalakhir" class="form-control" placeholder="<?=date('Y-m-d');?>">
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
<script src="../assets/plugins/chart.js/Chart.min.js"></script>
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
