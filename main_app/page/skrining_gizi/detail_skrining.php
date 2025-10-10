<?php
$skrining_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($skrining_id <= 0) {
    echo "<script>alert('ID skrining tidak valid!'); window.location='main_app.php?page=skrining_gizi_data';</script>";
    exit();
}

// Ambil data skrining
$query = mysqli_query($config, "SELECT * FROM skrining_gizi WHERE id = $skrining_id");
if (mysqli_num_rows($query) == 0) {
    echo "<script>alert('Data skrining tidak ditemukan!'); window.location='main_app.php?page=skrining_gizi_data';</script>";
    exit();
}

$data = mysqli_fetch_array($query);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Detail Skrining Gizi</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=skrining_gizi_dashboard">Dashboard Gizi</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=skrining_gizi_data">Data Skrining</a></li>
          <li class="breadcrumb-item active">Detail Skrining</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Detail Skrining Gizi #<?php echo $data['id']; ?></h3>
            <div class="card-tools">
              <a href="main_app.php?page=skrining_gizi_edit&id=<?php echo $data['id']; ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="main_app.php?page=skrining_gizi_data" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <h4>Informasi Pasien</h4>
                <table class="table table-bordered">
                  <tr>
                    <th width="150">No. RM</th>
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
                <h4>Hasil Skrining</h4>
                <table class="table table-bordered">
                  <tr>
                    <th width="150">Status Gizi</th>
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

            <?php if (!empty($data['catatan'])): ?>
            <div class="row mt-3">
              <div class="col-md-12">
                <h4>Catatan</h4>
                <div class="alert alert-info">
                  <?php echo nl2br(htmlspecialchars($data['catatan'])); ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <div class="row">
              <div class="col-md-12">
                <div class="card-footer">
                  <a href="main_app.php?page=skrining_gizi_edit&id=<?php echo $data['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Data
                  </a>
                  <a href="main_app.php?page=skrining_gizi_data" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Data
                  </a>
                  <a href="main_app.php?page=skrining_gizi_delete&id=<?php echo $data['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus data skrining ini?')">
                    <i class="fas fa-trash"></i> Hapus
                  </a>
                </div>
              </div>
            </div>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
    </div>
  </div>
</section>