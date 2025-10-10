<?php
// Get user ID from URL parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    echo "<script>alert('ID User tidak valid!'); window.location='main_app.php?page=user_management';</script>";
    exit();
}

// Check if user exists
$query_check = mysqli_query($mysqli, "SELECT * FROM users WHERE id = $user_id");
if (mysqli_num_rows($query_check) == 0) {
    echo "<script>alert('User tidak ditemukan!'); window.location='main_app.php?page=user_management';</script>";
    exit();
}

$user_data = mysqli_fetch_array($query_check);

// Prevent deleting current user
if ($user_id == $_SESSION['user_id']) {
    echo "<script>alert('Tidak dapat menghapus user yang sedang login!'); window.location='main_app.php?page=user_management';</script>";
    exit();
}

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Delete user directly (without checking related tables for now)
    $delete_query = mysqli_query($mysqli, "DELETE FROM users WHERE id = $user_id");

    if ($delete_query) {
        echo "<script>alert('User berhasil dihapus!'); window.location='main_app.php?page=user_management';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus user: " . mysqli_error($mysqli) . "'); window.location='main_app.php?page=user_management';</script>";
        exit();
    }
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Hapus User</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=user_management">User Management</a></li>
          <li class="breadcrumb-item active">Hapus User</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-8">
        <div class="card card-danger">
          <div class="card-header">
            <h3 class="card-title">Konfirmasi Hapus User</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <div class="alert alert-warning">
              <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan!</h5>
              Anda akan menghapus user dengan detail berikut. Tindakan ini tidak dapat dibatalkan.
            </div>

            <table class="table table-bordered">
              <tr>
                <th width="200">Username</th>
                <td><?php echo htmlspecialchars($user_data['username']); ?></td>
              </tr>
              <tr>
                <th>Nama Lengkap</th>
                <td><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></td>
              </tr>
              <tr>
                <th>Role</th>
                <td>
                  <span class="badge badge-<?php echo $user_data['role'] == 'Admin' ? 'danger' : ($user_data['role'] == 'Manager' ? 'warning' : 'info') ?>">
                    <?php echo htmlspecialchars($user_data['role']); ?>
                  </span>
                </td>
              </tr>
              <tr>
                <th>Status</th>
                <td>
                  <span class="badge badge-<?php echo $user_data['status'] == 'Aktif' ? 'success' : 'secondary' ?>">
                    <?php echo htmlspecialchars($user_data['status']); ?>
                  </span>
                </td>
              </tr>
              <tr>
                <th>Terakhir Login</th>
                <td><?php echo $user_data['last_login'] ? date('d/m/Y H:i:s', strtotime($user_data['last_login'])) : 'Belum pernah login'; ?></td>
              </tr>
            </table>

            <form method="POST" action="">
              <div class="form-group">
                <div class="custom-control custom-checkbox">
                  <input class="custom-control-input" type="checkbox" id="confirm_delete" required>
                  <label for="confirm_delete" class="custom-control-label">
                    Saya yakin ingin menghapus user ini
                  </label>
                </div>
              </div>

              <div class="card-footer">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus user <?php echo $user_data['nama_lengkap']; ?>?')">
                  <i class="fas fa-trash"></i> Hapus User
                </button>
                <a href="main_app.php?page=user_management" class="btn btn-secondary">Batal</a>
              </div>
            </form>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
    </div>
  </div>
</section>