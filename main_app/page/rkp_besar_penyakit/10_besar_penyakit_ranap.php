<?php
// Koneksi ke database sik9
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('<div class="alert alert-danger">Koneksi gagal: ' . $conn->connect_error . '</div>');
}
$conn->set_charset('utf8');

// Ambil input bulan dan tahun dari POST, default bulan dan tahun sekarang
$bulan = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// Validasi input
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');

// Query untuk mengambil 10 besar penyakit rawat inap berdasarkan filter
$query_ranap = "
SELECT
    d.kd_penyakit,
    p.nm_penyakit,
    COUNT(*) AS jumlah_kasus
FROM diagnosa_pasien d
JOIN reg_periksa r ON d.no_rawat = r.no_rawat
JOIN pasien p2 ON r.no_rkm_medis = p2.no_rkm_medis
JOIN penyakit p ON d.kd_penyakit = p.kd_penyakit
WHERE r.status_lanjut = 'Ranap'
  AND p2.nm_pasien NOT LIKE '%TEST%'
  AND p2.nm_pasien NOT LIKE '%Tes%'
  AND p2.nm_pasien NOT LIKE '%Coba%'
  AND MONTH(r.tgl_registrasi) = '$bulan'
  AND YEAR(r.tgl_registrasi) = '$tahun'
GROUP BY d.kd_penyakit, p.nm_penyakit
ORDER BY jumlah_kasus DESC
LIMIT 10";

$result_ranap = $conn->query($query_ranap);

// Error handling untuk query
if (!$result_ranap) {
    die('<div class="alert alert-danger">Query error: ' . $conn->error . '</div>');
}

// Ambil data untuk grafik dan tabel
$data_grafik = [];
$labels_grafik = [];
$total_kasus = 0;

if ($result_ranap->num_rows > 0) {
    while($row = $result_ranap->fetch_assoc()) {
        $labels_grafik[] = strlen($row['nm_penyakit']) > 25 ?
                          substr($row['nm_penyakit'], 0, 25) . '...' :
                          $row['nm_penyakit'];
        $data_grafik[] = (int)$row['jumlah_kasus'];
        $total_kasus += (int)$row['jumlah_kasus'];
    }
}

// Jika tidak ada data, set array kosong
if (empty($labels_grafik)) {
    $labels_grafik = ['Tidak ada data untuk periode ini'];
    $data_grafik = [0];
}

// List bulan untuk dropdown
$bulanList = [
    '01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June',
    '07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'
];
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>10 BESAR PENYAKIT RAWAT INAP</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
                    <li class="breadcrumb-item active">10 Besar Penyakit Rawat Inap</li>
                </ol>
            </div>
        </div>
    </div>
</section>

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
                                    foreach($bulanList as $val=>$label) {
                                        $selected = ($bulan == $val || $bulan == ltrim($val, '0')) ? 'selected' : '';
                                        echo '<option value="'.$val.'" '.$selected.'>'.$label.'</option>';
                                    }
                                    ?>
                                </select>
                                <label for="tahun" style="margin-bottom:0;">Tahun:</label>
                                <input type="number" name="tahun" id="tahun" class="form-control" value="<?php echo $tahun; ?>" style="width:90px;display:inline-block;" min="2000" max="2100">
                                <button type="submit" class="btn btn-primary">Tampilkan Data</button>
                            </form>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <!-- Panel Kiri - Tabel -->
                            <div class="col-md-6">
                                <div style="overflow-x: auto; white-space: nowrap;">
                                    <table class="table table-bordered table-striped text-center align-middle" style="width: 100%; table-layout: auto;">
                                        <thead style="background:#81a1c1;color:#fff;">
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Nama Penyakit</th>
                                                <th>Jumlah</th>
                                                <th>%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (empty($data_grafik) || $total_kasus == 0) {
                                                echo '<tr><td colspan="5" class="text-center">Tidak ada data untuk periode yang dipilih.</td></tr>';
                                            } else {
                                                $result_ranap->data_seek(0); // Reset pointer hasil query
                                                $no = 1;
                                                while($row = $result_ranap->fetch_assoc()):
                                                    $persentase = $total_kasus > 0 ? round(($row['jumlah_kasus'] / $total_kasus) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['kd_penyakit']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nm_penyakit']); ?></td>
                                                <td><?php echo number_format($row['jumlah_kasus']); ?></td>
                                                <td><?php echo $persentase; ?>%</td>
                                            </tr>
                                            <?php endwhile; } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Panel Kanan - Grafik -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Grafik 10 Besar Penyakit Rawat Inap</h4>
                                        <div class="card-tools">
                                            <small class="text-muted"><?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chartPenyakitRanap" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart dengan data dari PHP
    const ctxRanap = document.getElementById('chartPenyakitRanap').getContext('2d');
    new Chart(ctxRanap, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_grafik); ?>,
            datasets: [{
                label: 'Jumlah Kasus',
                data: <?php echo json_encode($data_grafik); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>