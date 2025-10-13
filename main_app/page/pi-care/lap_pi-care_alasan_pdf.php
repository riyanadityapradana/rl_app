<?php
// Koneksi database
require_once '../../../config/koneksi.php';

// Ambil parameter tanggal dari GET
$dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

// Query alasan pembatalan sesuai rentang tanggal
$sql = "SELECT 
  pp.tipe AS alasan_pembatalan,
  COUNT(*) AS jumlah
FROM 
  batal_daftar bd
JOIN 
  preset_pesan pp ON bd.alasan_batal = pp.id
WHERE 
  DATE(bd.insert_at) BETWEEN '$dari' AND '$sampai'
  AND bd.is_verified <> 0
  AND pp.tipe LIKE 'Pesan Pembatalan%'
GROUP BY 
  pp.tipe
ORDER BY 
  jumlah DESC";

$result = $conn->query($sql);

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
    // Data dummy jika tidak ada data
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
    <meta charset="UTF-8">
    <title>Laporan Alasan Pembatalan PI-Care</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: white;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #333; 
            padding-bottom: 20px; 
        }
        .header h1 { 
            color: #333; 
            margin: 0; 
            font-size: 18px; 
        }
        .header p { 
            color: #666; 
            margin: 5px 0; 
        }
        .content-wrapper {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        .chart-section {
            flex: 0.5;
            background: rgb(172, 172, 172);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid rgb(171, 170, 170);
            color: white;
        }
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: white;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .table-section {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .table-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: black;
            background: #dc3545;
            color: black;
            padding: 10px;
            border-radius: 5px;
            margin: -20px -20px 15px -20px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: center; 
        }
        th { 
            background-color: #007bff; 
            color: white; 
            font-weight: bold; 
        }
        tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        .summary { 
            background-color: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
        }
        .summary h3 { 
            margin-top: 0; 
            color: #333; 
        }
        .footer { 
            margin-top: 40px; 
            text-align: center; 
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <script>
        // Auto print saat halaman dimuat
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000); // Delay 1 detik agar halaman dan chart selesai dimuat
        };
    </script>
    
    <div class="header">
        <h1>LAPORAN ALASAN PEMBATALAN PASIEN PI-CARE</h1>
        <p>Rumah Sakit Pelita Insani Martapura</p>
        <p>Periode: <?php echo date('d F Y', strtotime($dari)); ?> - <?php echo date('d F Y', strtotime($sampai)); ?></p>
        <p>Tanggal Cetak: <?php echo date('d F Y H:i'); ?></p>
    </div>

    <div class="content-wrapper">
        <!-- Chart Section -->
        <div class="chart-section">
            <div class="chart-title">Chart Alasan Pembatalan</div>
            <div class="chart-container">
                <canvas id="doughnutChart"></canvas>
            </div>
            <div style="margin-top: 10px; margin-left: 0; font-size: 15px; background: white; padding: 15px; border-radius: 5px; color: black;">
                <b style="color: black;">ALASAN PEMBATALAN <?php echo strtoupper(date('F Y', strtotime($dari))); ?></b><br>
                Jumlah Total Pembatalan : <?php echo number_format($total); ?><br>
                Jumlah Jenis Alasan : <?php echo count($labels); ?><br>
                                 Alasan Terbanyak : <?php echo isset($labels[0]) ? $labels[0] : 'Tidak ada data'; ?><br>
                 Alasan Terendah : <?php echo !empty($labels) ? end($labels) : 'Tidak ada data'; ?><br>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="table-title">DATA ALASAN PEMBATALAN</div>
            <table>
                <thead style="background: rgb(0, 123, 255, 1);">
                    <tr>
                        <th style="text-align: center; color: white;">Alasan Pembatalan</th>
                        <th style="text-align: center; color: white;">Jumlah</th>
                        <th style="text-align: center; color: white;">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table as $i => $row) { ?>
                        <tr>
                            <td align='left'><?php echo htmlspecialchars($row['alasan_pembatalan']); ?></td>
                            <td align='center'><?php echo number_format($row['jumlah']); ?></td>
                            <td align='center'><?php echo $percent[$i]; ?>%</td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #007bff; color: white; font-weight: bold;">
                        <td colspan="1" style="text-align: center;">TOTAL</td>
                        <td style="text-align: center;"><?php echo number_format($total); ?></td>
                        <td style="text-align: center;">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="../../../assets/plugins/chart.js/Chart.min.js"></script>
    <script>
        // Data dari PHP
        var labels = <?php echo json_encode($labels); ?>;
        var data = <?php echo json_encode($data); ?>;
        var percent = <?php echo json_encode($percent); ?>;

        // Warna untuk chart
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

        // Inisialisasi Chart dengan konfigurasi yang kompatibel
        var ctx = document.getElementById('doughnutChart').getContext('2d');
        var doughnutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'right',
                    labels: {
                        fontColor: 'white',
                        fontSize: 12
                    }
                },
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
                }
            }
        });
    </script>
</body>
</html> 