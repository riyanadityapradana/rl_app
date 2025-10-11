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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    // Validation
    $errors = [];

    if (empty($username)) {
        $errors[] = "Username harus diisi!";
    }

    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap harus diisi!";
    }

    if (empty($role)) {
        $errors[] = "Role harus dipilih!";
    }

    if (empty($status)) {
        $errors[] = "Status harus dipilih!";
    }

    // Check if username already exists (except for current user)
    if ($username != $user_data['username']) {
        $check_username = mysqli_query($mysqli, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username sudah digunakan!";
        }
    }

    // If no errors, proceed with update
    if (empty($errors)) {
        if (!empty($password)) {
            // Update with new password (plain text as requested)
            $update_query = mysqli_query($mysqli, "UPDATE users SET username='$username', password='$password', nama_lengkap='$nama_lengkap', role='$role', status='$status' WHERE id = $user_id");
        } else {
            // Update without changing password
            $update_query = mysqli_query($mysqli, "UPDATE users SET username='$username', nama_lengkap='$nama_lengkap', role='$role', status='$status' WHERE id = $user_id");
        }

        if ($update_query) {
            echo "<script>alert('User berhasil diperbarui!'); window.location='main_app.php?page=user_management';</script>";
            exit();
        } else {
            $errors[] = "Gagal memperbarui user: " . mysqli_error($mysqli);
        }
    }
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Edit User</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=user_management">User Management</a></li>
          <li class="breadcrumb-item active">Edit User</li>
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
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Edit User</h3>
          </div>
          <!-- /.card-header -->
          <!-- form start -->
          <form method="POST" action="">
            <div class="card-body">
              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                  <ul>
                    <?php foreach ($errors as $error): ?>
                      <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
              </div>

              <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                       value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required>
              </div>

              <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                <small class="form-text text-muted">Password akan disimpan sebagai plain text</small>
              </div>

              <div class="form-group">
                <label for="role">Role</label>
                <select class="form-control" id="role" name="role" required>
                  <option value="">-- Pilih Role --</option>
                  <option value="Admin" <?php echo $user_data['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                  <option value="Pemasaran" <?php echo $user_data['role'] == 'Pemasaran' ? 'selected' : ''; ?>>Pemasaran</option>
                  <option value="Kepegawaian" <?php echo $user_data['role'] == 'Kepegawaian' ? 'selected' : ''; ?>>Kepegawaian</option>
                  <option value="Perawat" <?php echo $user_data['role'] == 'Perawat' ? 'selected' : ''; ?>>Perawat</option>
                  <option value="Gizi" <?php echo $user_data['role'] == 'Gizi' ? 'selected' : ''; ?>>Gizi</option>
                  <option value="RM" <?php echo $user_data['role'] == 'RM' ? 'selected' : ''; ?>>RM</option>
                  <option value="User" <?php echo $user_data['role'] == 'User' ? 'selected' : ''; ?>>User</option>
                </select>
              </div>

              <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status" required>
                  <option value="Aktif" <?php echo $user_data['status'] == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                  <option value="Nonaktif" <?php echo $user_data['status'] == 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
              </div>

              <div class="form-group">
                <label>Informasi Login Terakhir</label>
                <p class="form-control-plaintext">
                  <?php echo $user_data['last_login'] ? date('d/m/Y H:i:s', strtotime($user_data['last_login'])) : 'Belum pernah login'; ?>
                </p>
              </div>
            </div>
            <!-- /.card-body -->

            <div class="card-footer">
              <button type="submit" class="btn btn-primary">Update User</button>
              <a href="main_app.php?page=user_management" class="btn btn-secondary">Batal</a>
            </div>
          </form>
        </div>
        <!-- /.card -->
      </div>
    </div>
  </div>
</section>