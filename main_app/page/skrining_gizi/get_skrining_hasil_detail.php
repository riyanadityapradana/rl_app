<?php
require_once("../../config/koneksi.php");

$no_rawat = $_GET['no_rawat'] ?? '';

if (empty($no_rawat)) {
    echo '<div class="alert alert-danger">No. Rawat tidak ditemukan</div>';
    exit();
}

$query = mysqli_query($mysqli, "
    SELECT * FROM skrining_gizi WHERE no_rawat = '$no_rawat' ORDER BY created_at DESC LIMIT 1
");

if (mysqli_num_rows($query) == 0) {
    echo '<div class="alert alert-warning">Data skrining tidak ditemukan</div>';
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
                <td><?php echo htmlspecialchars($data['no_rm']); ?></td>
            </tr>
            <tr>
                <th>Nama Pasien</th>
                <td><?php echo htmlspecialchars($data['nama_pasien']); ?></td>
            </tr>
            <tr>
                <th>Usia</th>
                <td><?php echo htmlspecialchars($data['usia']); ?> tahun</td>
            </tr>
            <tr>
                <th>Jenis Kelamin</th>
                <td>
                    <span class="badge badge-<?php echo $data['jenis_kelamin'] == 'L' ? 'primary' : 'info'; ?>">
                        <?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Berat Badan</th>
                <td><?php echo htmlspecialchars($data['berat_badan']); ?> kg</td>
            </tr>
            <tr>
                <th>Tinggi Badan</th>
                <td><?php echo htmlspecialchars($data['tinggi_badan']); ?> cm</td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5>Hasil Skrining</h5>
        <table class="table table-bordered">
            <tr>
                <th width="120">Status Gizi</th>
                <td>
                    <span class="badge badge-<?php
                        echo $data['status'] == 'Normal' ? 'success' : ($data['status'] == 'Malnutrisi' ? 'danger' : 'warning');
                    ?> badge-lg">
                        <?php echo htmlspecialchars($data['status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Skor Total</th>
                <td><strong><?php echo htmlspecialchars($data['skor_total']); ?></strong></td>
            </tr>
            <tr>
                <th>Komponen Skor</th>
                <td>
                    <small>
                        BB: <?php echo $data['skor_bb']; ?> |
                        TB: <?php echo $data['skor_tb']; ?> |
                        LILA: <?php echo $data['skor_lila']; ?> |
                        Asupan: <?php echo $data['skor_asupan']; ?> |
                        Penyakit: <?php echo $data['skor_penyakit']; ?>
                    </small>
                </td>
            </tr>
            <tr>
                <th>Tanggal Skrining</th>
                <td><?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?></td>
            </tr>
            <tr>
                <th>Oleh</th>
                <td><?php echo htmlspecialchars($data['created_by']); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($data['catatan']) || !empty($data['intervensi'])): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <?php if (!empty($data['catatan'])): ?>
        <h5>Catatan</h5>
        <div class="alert alert-info">
            <?php echo nl2br(htmlspecialchars($data['catatan'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($data['intervensi'])): ?>
        <h5>Intervensi</h5>
        <div class="alert alert-success">
            <?php echo nl2br(htmlspecialchars($data['intervensi'])); ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <a href="main_app.php?page=skrining_gizi_edit&id=<?php echo $data['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Skrining
            </a>
            <a href="main_app.php?page=skrining_gizi_delete&id=<?php echo $data['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus data skrining ini?')">
                <i class="fas fa-trash"></i> Hapus
            </a>
            <button type="button" class="btn btn-info" onclick="printSkrining('<?php echo $data['id']; ?>')">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<script>
function printSkrining(id) {
    const printWindow = window.open('page/skrining_gizi/print_skrining.php?id=' + id, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}
</script>