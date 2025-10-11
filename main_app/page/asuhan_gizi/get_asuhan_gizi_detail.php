<?php
// File: get_asuhan_gizi_detail.php
// Detail modal untuk data asuhan gizi anak
require_once("../../../config/koneksi.php");
if (isset($_GET['no_rawat'])) {
    $no_rawat = mysqli_real_escape_string($config, $_GET['no_rawat']);
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
    $bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');

    // Query detail asuhan gizi dengan prepared statement untuk keamanan
    $stmt = mysqli_prepare($config, "
        SELECT
            ag.tanggal,
            ag.no_rawat,
            p.no_rkm_medis,
            p.nm_pasien,
            p.jk AS jenis_kelamin,
            p.tgl_lahir,
            TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) AS umur_tahun,
            MOD(TIMESTAMPDIFF(MONTH, p.tgl_lahir, ag.tanggal), 12) AS umur_bulan,
            p.alamat,
            ag.antropometri_bb,
            ag.antropometri_tb,
            ag.antropometri_imt,
            ag.antropometri_lla,
            ag.fisik_klinis,
            ag.biokimia,
            ag.diagnosis,
            ag.intervensi_gizi,
            ag.monitoring_evaluasi,
            ag.pola_makan,
            d.nm_dokter,
            pol.nm_poli
        FROM asuhan_gizi AS ag
        JOIN reg_periksa AS rp ON ag.no_rawat = rp.no_rawat
        JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
        LEFT JOIN dokter AS d ON rp.kd_dokter = d.kd_dokter
        LEFT JOIN poliklinik AS pol ON rp.kd_poli = pol.kd_poli
        WHERE ag.no_rawat = ?
          AND YEAR(ag.tanggal) = ?
          AND MONTH(ag.tanggal) = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, 'sii', $no_rawat, $tahun, $bulan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($data = mysqli_fetch_array($result)) {
        mysqli_stmt_close($stmt);

        // Hitung umur dalam tahun dan bulan
        $umur_tahun = (int)$data['umur_tahun'];
        $umur_bulan = (int)$data['umur_bulan'];
        $usia_display = $umur_tahun . ' tahun ' . $umur_bulan . ' bulan';

        // Status Gizi berdasarkan IMT dengan konversi yang aman
        $imt_raw = $data['antropometri_imt'];
        $imt_clean = $imt_raw ? str_replace(',', '.', trim($imt_raw)) : '0';
        $imt = floatval($imt_clean);

        $status_gizi = 'Normal';
        $badge_class = 'success';

        if ($imt > 0) {
            if ($imt < 18.5) {
                $status_gizi = 'Underweight';
                $badge_class = 'warning';
            } elseif ($imt >= 18.5 && $imt < 25) {
                $status_gizi = 'Normal';
                $badge_class = 'success';
            } elseif ($imt >= 25 && $imt < 30) {
                $status_gizi = 'Overweight';
                $badge_class = 'danger';
            } else {
                $status_gizi = 'Obesitas';
                $badge_class = 'danger';
            }
        }

        // Fungsi untuk format angka dengan aman
        function safe_format_angka($nilai) {
            if (!$nilai || trim($nilai) === '') return false;
            $clean = str_replace(',', '.', trim($nilai));
            return number_format(floatval($clean), 1);
        }
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-user-md"></i> Detail Asuhan Gizi
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Informasi Pasien -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-user"></i> Informasi Pasien
                                </h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td width="120"><strong>No. RM</strong></td>
                                        <td>: <?php echo htmlspecialchars($data['no_rkm_medis']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nama</strong></td>
                                        <td>: <?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jenis Kelamin</strong></td>
                                        <td>: <?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Lahir</strong></td>
                                        <td>: <?php echo date('d/m/Y', strtotime($data['tgl_lahir'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Usia Saat Ini</strong></td>
                                        <td>: <?php echo htmlspecialchars($usia_display); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-stethoscope"></i> Informasi Kunjungan
                                </h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td width="120"><strong>Tanggal</strong></td>
                                        <td>: <?php echo date('d/m/Y', strtotime($data['tanggal'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>No. Rawat</strong></td>
                                        <td>: <?php echo htmlspecialchars($data['no_rawat']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dokter</strong></td>
                                        <td>: <?php echo htmlspecialchars($data['nm_dokter'] ?: 'Tidak tersedia'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Poliklinik</strong></td>
                                        <td>: <?php echo htmlspecialchars($data['nm_poli'] ?: 'Tidak tersedia'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Data Antropometri -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="text-primary">
                                    <i class="fas fa-chart-line"></i> Data Antropometri
                                </h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <span class="info-box-text">Berat Badan</span>
                                                <span class="info-box-number">
                                                    <?php echo safe_format_angka($data['antropometri_bb']) ? safe_format_angka($data['antropometri_bb']) . ' kg' : 'Tidak ada data'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <span class="info-box-text">Tinggi Badan</span>
                                                <span class="info-box-number">
                                                    <?php echo safe_format_angka($data['antropometri_tb']) ? safe_format_angka($data['antropometri_tb']) . ' cm' : 'Tidak ada data'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <span class="info-box-text">IMT</span>
                                                <span class="info-box-number">
                                                    <?php echo safe_format_angka($data['antropometri_imt']) ? safe_format_angka($data['antropometri_imt']) : 'Tidak ada data'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <span class="info-box-text">LLA</span>
                                                <span class="info-box-number">
                                                    <?php echo safe_format_angka($data['antropometri_lla']) ? safe_format_angka($data['antropometri_lla']) . ' cm' : 'Tidak ada data'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="info-box bg-<?php echo $badge_class; ?> bg-gradient">
                                            <div class="info-box-content text-white">
                                                <span class="info-box-text">Status Gizi Berdasarkan IMT</span>
                                                <span class="info-box-number"><?php echo htmlspecialchars($status_gizi); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Klinis -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-notes-medical"></i> Fisik Klinis
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <?php echo $data['fisik_klinis'] ? nl2br(htmlspecialchars($data['fisik_klinis'])) : 'Tidak ada data'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-flask"></i> Biokimia
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <?php echo $data['biokimia'] ? nl2br(htmlspecialchars($data['biokimia'])) : 'Tidak ada data'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Diagnosis dan Intervensi -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-diagnoses"></i> Diagnosis Gizi
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <?php echo $data['diagnosis'] ? nl2br(htmlspecialchars($data['diagnosis'])) : 'Tidak ada data'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-hand-holding-heart"></i> Intervensi Gizi
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <?php echo $data['intervensi_gizi'] ? nl2br(htmlspecialchars($data['intervensi_gizi'])) : 'Tidak ada data'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Monitoring dan Pola Makan -->
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-chart-bar"></i> Monitoring & Evaluasi
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <?php echo $data['monitoring_evaluasi'] ? nl2br(htmlspecialchars($data['monitoring_evaluasi'])) : 'Tidak ada data'; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary">
                                    <i class="fas fa-utensils"></i> Pola Makan
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <?php echo $data['pola_makan'] ? nl2br(htmlspecialchars($data['pola_makan'])) : 'Tidak ada data'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        mysqli_stmt_close($stmt);
        echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
    }
} else {
    echo '<div class="alert alert-danger">No. Rawat tidak valid.</div>';
}
?>