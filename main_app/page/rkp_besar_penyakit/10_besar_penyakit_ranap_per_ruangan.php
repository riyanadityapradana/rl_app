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

// Query untuk mengambil 10 besar penyakit per ruangan dalam bulan/tahun yang dipilih
$query_ruangan = "
SELECT
    b.nm_bangsal AS ruangan,
    p.nm_penyakit AS nama_penyakit,
    COUNT(*) AS jumlah_kasus
FROM diagnosa_pasien d
JOIN reg_periksa r ON d.no_rawat = r.no_rawat
JOIN kamar_inap ki ON r.no_rawat = ki.no_rawat
JOIN kamar k ON ki.kd_kamar = k.kd_kamar
JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
JOIN penyakit p ON d.kd_penyakit = p.kd_penyakit
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
WHERE r.status_lanjut = 'Ranap'
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien NOT LIKE '%Tes%'
  AND ps.nm_pasien NOT LIKE '%Coba%'
  AND MONTH(r.tgl_registrasi) = '$bulan'
  AND YEAR(r.tgl_registrasi) = '$tahun'
GROUP BY b.nm_bangsal, p.kd_penyakit, p.nm_penyakit
ORDER BY b.nm_bangsal, jumlah_kasus DESC";

$result_ruangan = $conn->query($query_ruangan);

// Error handling untuk query
if (!$result_ruangan) {
    die('<div class="alert alert-danger">Query error: ' . $conn->error . '</div>');
}

// Ambil data untuk grafik dan tabel - dikelompokkan per ruangan
$data_grafik = [];
$labels_grafik = [];
$total_kasus = 0;
$ruangan_data = [];
$max_kasus = 0;

if ($result_ruangan->num_rows > 0) {
    $current_ruangan = '';
    $ruangan_penyakit = [];

    while($row = $result_ruangan->fetch_assoc()) {
        if ($current_ruangan != $row['ruangan']) {
            // Simpan data ruangan sebelumnya jika ada
            if ($current_ruangan != '' && !empty($ruangan_penyakit)) {
                $ruangan_data[$current_ruangan] = $ruangan_penyakit;
            }

            // Reset untuk ruangan baru
            $current_ruangan = $row['ruangan'];
            $ruangan_penyakit = [];
        }

        $ruangan_penyakit[] = [
            'nama_penyakit' => $row['nama_penyakit'],
            'jumlah_kasus' => (int)$row['jumlah_kasus']
        ];

        $total_kasus += (int)$row['jumlah_kasus'];
        if ((int)$row['jumlah_kasus'] > $max_kasus) {
            $max_kasus = (int)$row['jumlah_kasus'];
        }
    }

    // Simpan data ruangan terakhir
    if ($current_ruangan != '' && !empty($ruangan_penyakit)) {
        $ruangan_data[$current_ruangan] = $ruangan_penyakit;
    }

    // Siapkan data untuk grafik (ambil 5 ruangan dengan kasus terbanyak)
    $top_ruangan = [];
    foreach ($ruangan_data as $ruangan => $penyakit_list) {
        $total_ruangan = array_sum(array_column($penyakit_list, 'jumlah_kasus'));
        $top_ruangan[$ruangan] = $total_ruangan;
    }

    // Urutkan ruangan berdasarkan total kasus
    arsort($top_ruangan);

    // Ambil 5 ruangan teratas untuk grafik
    $count = 0;
    foreach ($top_ruangan as $ruangan => $total) {
        if ($count >= 5) break;
        $labels_grafik[] = $ruangan;
        $data_grafik[] = $total;
        $count++;
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
                <h1>10 BESAR PENYAKIT RAWAT INAP PER RUANGAN</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
                    <li class="breadcrumb-item active">10 Besar Penyakit Rawat Inap Per Ruangan</li>
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
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">3 Besar Penyakit Per Ruangan</h4>
                                        <div class="card-tools">
                                            <span class="badge badge-info">Total Kasus: <?php echo number_format($total_kasus); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body table-responsive p-0" style="max-height: 500px; overflow-y: auto;">
                                        <?php if ($total_kasus > 0): ?>
                                        <table class="table table-striped table-bordered">
                                            <thead class="sticky-top" style="background-color: #dc3545; color: white;">
                                                <tr>
                                                    <th width="25%" class="text-center">Ruangan</th>
                                                    <th width="50%" class="text-center">Nama Penyakit</th>
                                                    <th width="15%" class="text-center">Jumlah Kasus</th>
                                                    <th width="10%" class="text-center">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ($ruangan_data as $ruangan => $penyakit_list):
                                                    $total_ruangan = array_sum(array_column($penyakit_list, 'jumlah_kasus'));
                                                    $displayed = 0;
                                                    foreach ($penyakit_list as $penyakit):
                                                        if ($displayed >= 3) break; // Maksimal 3 penyakit per ruangan
                                                        $persentase = $total_kasus > 0 ? round(($penyakit['jumlah_kasus'] / $total_kasus) * 100, 1) : 0;
                                                ?>
                                                <tr>
                                                    <?php if ($displayed === 0): ?>
                                                        <td class="text-center" rowspan="<?php echo min(3, count($penyakit_list)); ?>"><?php echo htmlspecialchars($ruangan); ?></td>
                                                    <?php endif; ?>
                                                    <td><?php echo htmlspecialchars($penyakit['nama_penyakit']); ?></td>
                                                    <td class="text-center"><strong><?php echo number_format($penyakit['jumlah_kasus']); ?></strong></td>
                                                    <td class="text-center"><?php echo $persentase; ?>%</td>
                                                </tr>
                                                <?php
                                                        $displayed++;
                                                    endforeach;
                                                endforeach;
                                                ?>
                                            </tbody>
                                        </table>
                                        <?php else: ?>
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>Tidak ada data penyakit rawat inap per ruangan untuk periode <?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?>.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Kanan - Grafik -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Grafik 5 Ruangan dengan Kasus Terbanyak</h4>
                                        <div class="card-tools">
                                            <small class="text-muted"><?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chartRuanganRanap" width="400" height="300"></canvas>
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
    const ctxRuangan = document.getElementById('chartRuanganRanap').getContext('2d');

    // Chart Bar untuk data ruangan
    new Chart(ctxRuangan, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_grafik); ?>,
            datasets: [{
                label: 'Total Kasus',
                data: <?php echo json_encode($data_grafik); ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.6)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 2,
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
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' kasus';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
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
</body>
</html>