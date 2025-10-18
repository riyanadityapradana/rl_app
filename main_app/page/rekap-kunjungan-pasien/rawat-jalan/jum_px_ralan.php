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

// Query untuk mengambil jumlah pasien rawat jalan per hari dalam bulan/tahun yang dipilih
$query_data = "
SELECT
    DATE(r.tgl_registrasi) AS tanggal,
    COUNT(r.no_rawat) AS jumlah_kunjungan,
    DAY(r.tgl_registrasi) AS hari
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE r.status_lanjut = 'Ralan'
AND r.status_bayar = 'Sudah Bayar'
  AND p.nm_pasien NOT LIKE '%TEST%'
  AND p.nm_pasien NOT LIKE '%Tes%'
  AND p.nm_pasien NOT LIKE '%Coba%'
  AND (pl.nm_poli LIKE '%Poli%' OR pl.nm_poli LIKE '%Poliklinik%')
  AND pl.nm_poli NOT LIKE '%Poli Vaksin%'
  AND MONTH(r.tgl_registrasi) = '$bulan'
  AND YEAR(r.tgl_registrasi) = '$tahun'
GROUP BY DATE(r.tgl_registrasi)
ORDER BY tanggal";

// Query untuk mengambil data per poliklinik
$query_poli = "
SELECT
    pl.nm_poli,
    COUNT(DISTINCT r.no_rkm_medis) AS jumlah_pasien_unik,
    COUNT(r.no_rawat) AS jumlah_kunjungan
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE r.status_lanjut = 'Ralan'
AND r.status_bayar = 'Sudah Bayar'
  AND p.nm_pasien NOT LIKE '%TEST%'
  AND p.nm_pasien NOT LIKE '%Tes%'
  AND p.nm_pasien NOT LIKE '%Coba%'
  AND (pl.nm_poli LIKE '%Poli%' OR pl.nm_poli LIKE '%Poliklinik%')
  AND pl.nm_poli NOT LIKE '%Poli Vaksin%'
  AND MONTH(r.tgl_registrasi) = '$bulan'
  AND YEAR(r.tgl_registrasi) = '$tahun'
GROUP BY pl.kd_poli, pl.nm_poli
ORDER BY jumlah_kunjungan DESC";

$result_data = $conn->query($query_data);

// Error handling untuk query
if (!$result_data) {
    die('<div class="alert alert-danger">Query error: ' . $conn->error . '</div>');
}

// Eksekusi query untuk data poliklinik
$result_poli = $conn->query($query_poli);
if (!$result_poli) {
    die('<div class="alert alert-danger">Query poli error: ' . $conn->error . '</div>');
}

// Hitung total kunjungan dari semua poli
$total_pasien_poli = 0;
$data_poli_grafik = [];
$labels_poli_grafik = [];

if ($result_poli->num_rows > 0) {
    while($row_poli = $result_poli->fetch_assoc()) {
        $labels_poli_grafik[] = $row_poli['nm_poli'];
        $data_poli_grafik[] = (int)$row_poli['jumlah_kunjungan'];
        $total_pasien_poli += (int)$row_poli['jumlah_kunjungan'];
    }
}

// Ambil data untuk grafik dan tabel
$data_grafik = [];
$labels_grafik = [];
$total_pasien = 0;
$max_kunjungan = 0;

if ($result_data->num_rows > 0) {
    while($row = $result_data->fetch_assoc()) {
        $tanggal_format = date('d M', strtotime($row['tanggal']));
        $labels_grafik[] = $tanggal_format;
        $jumlah = (int)$row['jumlah_kunjungan'];
        $data_grafik[] = $jumlah;
        $total_pasien += $jumlah;
        if ($jumlah > $max_kunjungan) {
            $max_kunjungan = $jumlah;
        }
    }
}

// Jika tidak ada data, set array kosong
if (empty($labels_grafik)) {
    $labels_grafik = ['Tidak ada data untuk periode ini'];
    $data_grafik = [0];
}

// Query untuk total kunjungan dalam periode bulan/tahun yang dipilih
$query_total = "
SELECT
    COUNT(r.no_rawat) AS jumlah_kunjungan
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE r.status_lanjut = 'Ralan'
AND r.status_bayar = 'Sudah Bayar'
AND (pl.nm_poli LIKE '%Poli%' OR pl.nm_poli LIKE '%Poliklinik%')
  AND pl.nm_poli NOT LIKE '%Poli Vaksin%'
  AND p.nm_pasien NOT LIKE '%TEST%'
  AND p.nm_pasien NOT LIKE '%Tes%'
  AND p.nm_pasien NOT LIKE '%Coba%'
  AND MONTH(r.tgl_registrasi) = '$bulan'
  AND YEAR(r.tgl_registrasi) = '$tahun'";

$result_total = $conn->query($query_total);
$total_kunjungan = 0;
if ($result_total && $row_total = $result_total->fetch_assoc()) {
    $total_kunjungan = (int)$row_total['jumlah_kunjungan'];
}

// Hitung rata-rata kunjungan per hari
$rata_rata = count($data_grafik) > 0 ? round($total_pasien / count($data_grafik), 1) : 0;

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
                <h1 style="font-size: 22px; color: red;">JUMLAH (HARIAN) PASIEN RAWAT JALAN UNTUK SEMUA POLI</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
                    <li class="breadcrumb-item"><a href="main_app.php?page=rekap_pasien_poli">Rekap Pasien Poli</a></li>
                    <li class="breadcrumb-item active">Jumlah Pasien Rawat Jalan</li>
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
                                        <h4 class="card-title">Data Pasien Rawat Jalan (Harian)</h4>
                                        <div class="card-tools">
                                            <span class="badge badge-info">Total Kunjungan: <?php echo number_format($total_kunjungan); ?></span>
                                            <span class="badge badge-success">Total Kunjungan Harian: <?php echo number_format($total_pasien); ?></span>
                                            <span class="badge badge-warning">Rata-rata: <?php echo number_format($rata_rata, 1); ?>/hari</span>
                                        </div>
                                    </div>
                                    <div class="card-body table-responsive p-0" style="max-height: 400px; overflow-y: auto;">
                                        <?php if (count($data_grafik) > 0 && $data_grafik[0] > 0): ?>
                                        <table class="table table-striped table-bordered">
                                            <thead class="sticky-top" style="background-color: #007bff; color: white;">
                                                <tr>
                                                    <th width="15%" class="text-center">Tanggal</th>
                                                    <th width="15%" class="text-center">Hari</th>
                                                    <th width="20%" class="text-center">Jumlah Kunjungan</th>
                                                    <th width="15%" class="text-center">Persentase</th>
                                                    <th width="15%" class="text-center">Status</th>
                                                    <th width="20%" class="text-center">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($data_grafik) > 0 && $data_grafik[0] > 0): ?>
                                                    <?php
                                                    $result_data->data_seek(0); // Reset pointer hasil query
                                                    $no = 1;
                                                    while($row = $result_data->fetch_assoc()):
                                                        $tanggal = $row['tanggal'];
                                                        $hari = date('l', strtotime($tanggal));
                                                        $jumlah = (int)$row['jumlah_kunjungan'];
                                                        $persentase = $max_kunjungan > 0 ? round(($jumlah / $max_kunjungan) * 100, 1) : 0;

                                                        // Tentukan status berdasarkan jumlah kunjungan
                                                        if ($jumlah >= $max_kunjungan * 0.8) {
                                                            $status = '<span class="badge badge-success">Tinggi</span>';
                                                            $keterangan = 'Puncak kunjungan';
                                                        } elseif ($jumlah >= $max_kunjungan * 0.5) {
                                                            $status = '<span class="badge badge-warning">Sedang</span>';
                                                            $keterangan = 'Normal';
                                                        } else {
                                                            $status = '<span class="badge badge-danger">Rendah</span>';
                                                            $keterangan = 'Di bawah normal';
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo date('d/m', strtotime($tanggal)); ?></td>
                                                        <td class="text-center"><?php echo substr($hari, 0, 3); ?></td>
                                                        <td class="text-center"><strong><?php echo number_format($jumlah); ?></strong></td>
                                                        <td class="text-center"><?php echo $persentase; ?>%</td>
                                                        <td class="text-center"><?php echo $status; ?></td>
                                                        <td class="text-center"><small><?php echo $keterangan; ?></small></td>
                                                    </tr>
                                                    <?php
                                                        $no++;
                                                    endwhile;
                                                    ?>
                                                <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">
                                                        Tidak ada data untuk periode <?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?>.
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <?php else: ?>
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>Tidak ada data pasien rawat jalan untuk periode <?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?>.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Kanan - Grafik -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Grafik Jumlah Pasien Rawat Jalan (Harian)</h4>
                                        <div class="card-tools">
                                            <small class="text-muted"><?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chartPasienRalan" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Baris untuk Tabel dan Grafik Poliklinik -->
                        <div class="row mt-4">
                            <!-- Panel Kiri - Tabel Poliklinik -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Data Kunjungan per Poliklinik Berdasarkan Bulan <?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?></h4>
                                        <div class="card-tools">
                                            <span class="badge badge-primary">Total Poli: <?php echo count($labels_poli_grafik); ?></span>
                                            <span class="badge badge-info">Total Kunjungan: <?php echo number_format($total_pasien_poli); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body table-responsive p-0" style="max-height: 300px; overflow-y: auto;">
                                        <?php if (count($data_poli_grafik) > 0): ?>
                                        <table class="table table-striped table-bordered">
                                            <thead class="sticky-top" style="background-color: #28a745; color: white;">
                                                <tr>
                                                    <th width="10%" class="text-center">No</th>
                                                    <th width="50%" class="text-center">Nama Poliklinik</th>
                                                    <th width="20%" class="text-center">Jumlah Kunjungan</th>
                                                    <th width="20%" class="text-center">Persentase</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $result_poli->data_seek(0); // Reset pointer hasil query
                                                $no = 1;
                                                while($row_poli = $result_poli->fetch_assoc()):
                                                    $nm_poli = $row_poli['nm_poli'];
                                                    $jml_kunjungan = (int)$row_poli['jumlah_kunjungan'];
                                                    $persentase = $total_pasien_poli > 0 ? round(($jml_kunjungan / $total_pasien_poli) * 100, 1) : 0;
                                                ?>
                                                <tr>
                                                    <td class="text-center"><?php echo $no; ?></td>
                                                    <td><?php echo htmlspecialchars($nm_poli); ?></td>
                                                    <td class="text-center"><strong><?php echo number_format($jml_kunjungan); ?></strong></td>
                                                    <td class="text-center">
                                                        <span class="badge <?php echo $persentase >= 20 ? 'badge-success' : ($persentase >= 10 ? 'badge-warning' : 'badge-secondary'); ?>">
                                                            <?php echo $persentase; ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php
                                                    $no++;
                                                endwhile;
                                                ?>
                                            </tbody>
                                        </table>
                                        <?php else: ?>
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>Tidak ada data poliklinik untuk periode ini.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Kanan - Grafik Poliklinik -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Grafik Distribusi Kunjungan per Poliklinik</h4>
                                        <div class="card-tools">
                                            <small class="text-muted"><?php echo date('F Y', strtotime("$tahun-$bulan-01")); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chartPoli" width="400" height="300"></canvas>
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
    const ctxRalan = document.getElementById('chartPasienRalan').getContext('2d');

    // Chart Line untuk data harian
    new Chart(ctxRalan, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_grafik); ?>,
            datasets: [{
                label: 'Jumlah Kunjungan per Hari',
                data: <?php echo json_encode($data_grafik); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 3,
                pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' kunjungan';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)'
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
            },
            interaction: {
                intersect: false,
                mode: 'nearest'
            }
        }
    });

    // Chart untuk data poliklinik (Bar Chart horizontal)
    const ctxPoli = document.getElementById('chartPoli').getContext('2d');

    new Chart(ctxPoli, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_poli_grafik); ?>,
            datasets: [{
                label: 'Jumlah Kunjungan per Poli',
                data: <?php echo json_encode($data_poli_grafik); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.x.toLocaleString() + ' kunjungan';
                        }
                    }
                }
            },
            scales: {
                x: {
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
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'nearest'
            }
        }
    });
});
</script>