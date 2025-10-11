<?php
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

    if (empty($password)) {
        $errors[] = "Password harus diisi!";
    }

    // Check if username already exists
    if (!empty($username)) {
        $check_username = mysqli_query($mysqli, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username sudah digunakan!";
        }
    }

    // If no errors, proceed with creation
    if (empty($errors)) {
        $insert_query = mysqli_query($mysqli, "INSERT INTO users (username, password, nama_lengkap, role, status) VALUES ('$username', '$password', '$nama_lengkap', '$role', '$status')");

        if ($insert_query) {
            echo "<script>alert('User berhasil ditambahkan!'); window.location='main_app.php?page=user_management';</script>";
            exit();
        } else {
            $errors[] = "Gagal menambahkan user: " . mysqli_error($mysqli);
        }
    }
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Tambah User Baru</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=user_management">User Management</a></li>
          <li class="breadcrumb-item active">Tambah User</li>
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
            <h3 class="card-title">Form Tambah User</h3>
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
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
              </div>

              <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                       value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>" required>
              </div>

              <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <small class="form-text text-muted">Password akan disimpan sebagai plain text</small>
              </div>

              <div class="form-group">
                <label for="role">Role</label>
                <select class="form-control" id="role" name="role" required>
                  <option value="">-- Pilih Role --</option>
                  <option value="Admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                  <option value="Pemasaran" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Pemasaran') ? 'selected' : ''; ?>>Pemasaran</option>
                  <option value="Kepegawaian" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Kepegawaian') ? 'selected' : ''; ?>>Kepegawaian</option>
                  <option value="Perawat" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Perawat') ? 'selected' : ''; ?>>Perawat</option>
                  <option value="Gizi" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Gizi') ? 'selected' : ''; ?>>Gizi</option>
                  <option value="RM" <?php echo (isset($_POST['role']) && $_POST['role'] == 'RM') ? 'selected' : ''; ?>>RM</option>
                  <option value="User" <?php echo (isset($_POST['role']) && $_POST['role'] == 'User') ? 'selected' : ''; ?>>User</option>
                </select>
              </div>

              <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status" required>
                  <option value="Aktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                  <option value="Nonaktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
              </div>
            </div>
            <!-- /.card-body -->

            <div class="card-footer">
              <button type="submit" class="btn btn-primary">Tambah User</button>
              <a href="main_app.php?page=user_management" class="btn btn-secondary">Batal</a>
            </div>
          </form>
        </div>
        <!-- /.card -->
      </div>
    </div>
  </div>
</section>