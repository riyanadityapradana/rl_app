<?php
// Koneksi database (pastikan path sesuai)
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Ambil filter dari GET
$status_lanjut = isset($_GET['status_lanjut']) ? $_GET['status_lanjut'] : '';
$kd_pj = isset($_GET['kd_pj']) ? $_GET['kd_pj'] : '';
$kategori_usia = isset($_GET['kategori_usia']) ? $_GET['kategori_usia'] : '';
$bulan_ini = date('Y-m');
// Query dasar
$sql = "SELECT r.no_reg, r.no_rawat, r.tgl_registrasi, r.status_lanjut, r.kd_pj, p.nm_pasien, p.tgl_lahir, j.png_jawab
        FROM reg_periksa r
        JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
        JOIN penjab j ON r.kd_pj = j.kd_pj
        WHERE r.status_lanjut = 'Ralan'";

// Filter jenis bayar
if ($kd_pj != '') {
    $sql .= " AND r.kd_pj = '$kd_pj'";
}

// Filter kategori usia
if ($kategori_usia != '') {
    if ($kategori_usia == 'anak') {
        $sql .= " AND (TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) BETWEEN 0 AND 12)";
    } elseif ($kategori_usia == 'remaja') {
        $sql .= " AND (TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) BETWEEN 13 AND 17)";
    } elseif ($kategori_usia == 'dewasa') {
        $sql .= " AND (TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) BETWEEN 18 AND 59)";
    } elseif ($kategori_usia == 'lansia') {
        $sql .= " AND (TIMESTAMPDIFF(YEAR, p.tgl_lahir, CURDATE()) >= 60)";
    }
}

// Tambahkan filter bulan berjalan
$sql .= "AND DATE_FORMAT(r.tgl_registrasi, '%Y-%m') = '$bulan_ini'";

$sql .= "ORDER BY r.tgl_registrasi DESC";
$result = $conn->query($sql);

// Untuk dropdown jenis bayar
$penjab = [
    ['kd_pj' => 'A09', 'png_jawab' => 'Umum'],
    ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS'],
    ['kd_pj' => 'A92', 'png_jawab' => 'Asuransi'],
];
?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Filter Card -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Filter Data Pasien Rawat Inap Berdasarkan Usia</h3>
                    </div>
                    <div class="card-body">
                        <form method="get" class="form-inline">
                            <!-- Dropdown Jenis Bayar -->
                            <div class="form-group mr-2">
                                <label for="kd_pj" class="mr-2">Jenis Bayar</label>
                                <select name="kd_pj" id="kd_pj" class="form-control">
                                    <option value="">Semua</option>
                                    <?php
                                    $penjab = [
                                        ['kd_pj' => 'A09', 'png_jawab' => 'Umum'],
                                        ['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS'],
                                        ['kd_pj' => 'A92', 'png_jawab' => 'Asuransi'],
                                    ];
                                    foreach($penjab as $row): ?>
                                        <option value="<?= htmlspecialchars($row['kd_pj']) ?>" <?= $kd_pj == $row['kd_pj'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['png_jawab']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mr-2">
                                <label for="kategori_usia" class="mr-2">Kategori Usia</label>
                                <select name="kategori_usia" id="kategori_usia" class="form-control">
                                    <option value="">Semua</option>
                                    <option value="anak" <?= $kategori_usia == 'anak' ? 'selected' : '' ?>>Anak-Anak (0-12)</option>
                                    <option value="remaja" <?= $kategori_usia == 'remaja' ? 'selected' : '' ?>>Remaja (13-17)</option>
                                    <option value="dewasa" <?= $kategori_usia == 'dewasa' ? 'selected' : '' ?>>Dewasa (18-59)</option>
                                    <option value="lansia" <?= $kategori_usia == 'lansia' ? 'selected' : '' ?>>Lanjut Usia (60+)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                        </form>
                    </div>
                </div>
                <!-- Data Table Card -->
                <div class="card">
                    <div class="card-header" style="background:rgb(0, 123, 255)">
                        <h3 class="card-title" style="color: white;">Data Pasien Rawat Inap</h3>
                    </div>
                    <div class="card-body" style="background:rgb(203, 212, 212)">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead style="background:rgb(0, 123, 255, 1)">
                                <tr>
                                    <th style="text-align: center; color: white;">No. Reg</th>
                                    <th style="text-align: center; color: white;">No. Rawat</th>
                                    <th style="text-align: center; color: white;">Nama Pasien</th>
                                    <th style="text-align: center; color: white;">Tanggal Lahir</th>
                                    <th style="text-align: center; color: white;">Usia</th>
                                    <th style="text-align: center; color: white;">Jenis Bayar</th>
                                    <th style="text-align: center; color: white;">Tanggal Registrasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td align="center"><?= htmlspecialchars($row['no_reg']) ?></td>
                                            <td align="center"><?= htmlspecialchars($row['no_rawat']) ?></td>
                                            <td><?= htmlspecialchars($row['nm_pasien']) ?></td>
                                            <td align="center"><?= htmlspecialchars($row['tgl_lahir']) ?></td>
                                            <td align="center">
                                                <?php
                                                    $usia = date_diff(date_create($row['tgl_lahir']), date_create('today'))->y;
                                                    echo $usia . " th";
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['png_jawab']) ?></td>
                                            <td align="center"><?= htmlspecialchars($row['tgl_registrasi']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" align="center">Data tidak ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>