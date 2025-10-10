<?php
require_once("../../../config/koneksi.php");

$no_rawat = $_GET['no_rawat'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');

if (empty($no_rawat)) {
    echo '<div class="alert alert-danger">No. Rawat tidak ditemukan</div>';
    exit();
}

$query = mysqli_query($config, "
    SELECT
        psad.tanggal,
        psad.no_rawat,
        p.no_rkm_medis,
        p.nm_pasien,
        p.jk AS jenis_kelamin,
        p.tgl_lahir,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) AS umur_tahun,
        MOD(TIMESTAMPDIFF(MONTH, p.tgl_lahir, psad.tanggal), 12) AS umur_bulan,
        psad.keluhan,
        psad.riwayat_penyakit,
        psad.fisik_klinis,
        psad.biokimia,
        psad.riwayat_makan
    FROM pilot_skrining_awal_diet AS psad
    JOIN reg_periksa AS rp ON psad.no_rawat = rp.no_rawat
    JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
    WHERE psad.no_rawat = '$no_rawat'
      AND YEAR(psad.tanggal) = $tahun
      AND MONTH(psad.tanggal) = $bulan
    LIMIT 1
");

if (mysqli_num_rows($query) == 0) {
    echo '<div class="alert alert-warning">Data skrining tidak ditemukan untuk periode yang dipilih</div>';
    exit();
}

$data = mysqli_fetch_array($query);
?>

<div class="row">
    <div class="col-md-6">
        <h5>Informasi Pasien</h5>
        <table class="table table-bordered">
            <tr>
                <th width="120">No. RM</th>
                <td><?php echo htmlspecialchars($data['no_rkm_medis']); ?></td>
            </tr>
            <tr>
                <th>Nama Pasien</th>
                <td><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
            </tr>
            <tr>
                <th>Tanggal Lahir</th>
                <td><?php echo date('d/m/Y', strtotime($data['tgl_lahir'])); ?></td>
            </tr>
            <tr>
                <th>Usia Saat Skrining</th>
                <td><?php echo $data['umur_tahun']; ?> tahun <?php echo $data['umur_bulan']; ?> bulan</td>
            </tr>
            <tr>
                <th>Jenis Kelamin</th>
                <td>
                    <span class="badge badge-<?php echo $data['jenis_kelamin'] == 'L' ? 'primary' : 'info'; ?>">
                        <?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5>Hasil Skrining</h5>
        <table class="table table-bordered">
            <tr>
                <th width="120">Tanggal Skrining</th>
                <td><?php echo date('d/m/Y', strtotime($data['tanggal'])); ?></td>
            </tr>
            <tr>
                <th>Keluhan</th>
                <td><?php echo !empty($data['keluhan']) ? htmlspecialchars($data['keluhan']) : '<em class="text-muted">Tidak ada keluhan</em>'; ?></td>
            </tr>
            <tr>
                <th>Riwayat Penyakit</th>
                <td><?php echo !empty($data['riwayat_penyakit']) ? htmlspecialchars($data['riwayat_penyakit']) : '<em class="text-muted">Tidak ada</em>'; ?></td>
            </tr>
            <tr>
                <th>Pemeriksaan Fisik</th>
                <td><?php echo !empty($data['fisik_klinis']) ? htmlspecialchars($data['fisik_klinis']) : '<em class="text-muted">Normal</em>'; ?></td>
            </tr>
            <tr>
                <th>Biokimia</th>
                <td><?php echo !empty($data['biokimia']) ? htmlspecialchars($data['biokimia']) : '<em class="text-muted">Normal</em>'; ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($data['riwayat_makan'])): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <h5>Riwayat Makan</h5>
        <div class="alert alert-info">
            <?php echo nl2br(htmlspecialchars($data['riwayat_makan'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="printDetail('<?php echo $data['no_rawat']; ?>')">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<script>
function printDetail(no_rawat) {
    const printWindow = window.open('page/skrining_gizi/print_detail.php?no_rawat=' + no_rawat, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}
</script>