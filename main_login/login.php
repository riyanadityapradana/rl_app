<?php
session_start();

// Include koneksi database
require_once '../config/koneksi.php';

// Cek remember me token (hanya jika belum login dan tidak ada parameter logout)
if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id']) && !isset($_GET['logout'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $token_stmt = $mysqli->prepare("
            SELECT u.id, u.username, u.nama_lengkap, u.role, u.status
            FROM user_remember_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'Aktif'
        ");
        $token_stmt->bind_param("s", $token);
        $token_stmt->execute();
        $token_result = $token_stmt->get_result();

        if ($token_result->num_rows === 1) {
            $user = $token_result->fetch_assoc();

            // Set session untuk user yang sudah terauntentikasi via token
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = date("Y-m-d H:i:s");

            // Update last_login timestamp
            $update_stmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Redirect ke halaman utama
            header("Location: ../main_app/main_app.php?page=beranda");
            exit();
        }

        $token_stmt->close();
    } catch (Exception $e) {
        // Silent fail untuk remember me, hapus cookie jika error
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        error_log("Remember me token error: " . $e->getMessage());
    }
}

// Inisialisasi variabel
$error_message = '';
$success_message = '';

// Cek jika ada pesan logout
if (isset($_GET['message'])) {
    $success_message = $_GET['message'];
}

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);

    // Rate limiting check
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $max_attempts = 5;
    $time_window = 15 * 60; // 15 menit dalam detik

    try {
        // Cek jumlah percobaan login dalam waktu tertentu
        $rate_stmt = $mysqli->prepare("
            SELECT COUNT(*) as attempt_count
            FROM login_attempts
            WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $rate_stmt->bind_param("si", $ip_address, $time_window);
        $rate_stmt->execute();
        $rate_result = $rate_stmt->get_result();
        $rate_data = $rate_result->fetch_assoc();
        $rate_stmt->close();

        if ($rate_data['attempt_count'] >= $max_attempts) {
            $error_message = "Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.";
        } else {
            // Lanjutkan dengan validasi input
            if (empty($username) || empty($password)) {
                $error_message = "Username dan password harus diisi!";
            } else {
                try {
                    // Gunakan prepared statement untuk keamanan
                    $stmt = $mysqli->prepare("SELECT id, username, password, nama_lengkap, role, status FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();

                        // Cek status user
                        if ($user['status'] !== 'Aktif') {
                            $error_message = "Akun Anda tidak aktif. Silakan hubungi administrator!";
                        } else {
                            // Verifikasi password dengan plain text
                            if ($password === $user['password']) {
                                // Set session variables
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['login_time'] = date("Y-m-d H:i:s");

                                // Remember me functionality
                                if ($remember_me) {
                                    $token = bin2hex(random_bytes(32));
                                    setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', false, true); // 30 hari, HttpOnly

                                    // Simpan token di database (tabel user_sessions atau tabel terpisah)
                                    $token_stmt = $mysqli->prepare("INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY)) ON DUPLICATE KEY UPDATE token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)");
                                    $token_stmt->bind_param("iss", $user['id'], $token, $token);
                                    $token_stmt->execute();
                                    $token_stmt->close();
                                }

                                // Update last_login timestamp
                                $update_stmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                                $update_stmt->bind_param("i", $user['id']);
                                $update_stmt->execute();
                                $update_stmt->close();

                                // Redirect ke halaman utama dengan parameter page=beranda
                                header("Location: ../main_app/main_app.php?page=beranda");
                                exit();
                            } else {
                                $error_message = "Username atau password salah!";
                            }
                        }
                    } else {
                        $error_message = "Username atau password salah!";
                    }

                    $stmt->close();
                } catch (Exception $e) {
                    $error_message = "Terjadi kesalahan sistem. Silakan coba lagi!";
                    // Log error untuk debugging (dalam production, gunakan proper logging)
                    error_log("Login error: " . $e->getMessage());
                }
            }
        }

        // Jika ada error, catat attempt untuk rate limiting
        if (!empty($error_message) && $error_message !== "Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.") {
            try {
                $attempt_stmt = $mysqli->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE attempt_time = NOW()");
                $attempt_stmt->bind_param("ss", $ip_address, $username);
                $attempt_stmt->execute();
                $attempt_stmt->close();
            } catch (Exception $e) {
                // Silent fail untuk rate limiting
                error_log("Rate limiting error: " . $e->getMessage());
            }
        }

    } catch (Exception $e) {
        $error_message = "Terjadi kesalahan sistem. Silakan coba lagi!";
        error_log("Rate limiting error: " . $e->getMessage());
    }
}

// Cek jika sudah login, redirect ke main app (kecuali jika ini logout)
if (isset($_SESSION['user_id']) && !isset($_GET['logout'])) {
    header("Location: ../main_app/main_app.php?page=beranda");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: gradient 2s linear infinite;
        }

        @keyframes gradient {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
            pointer-events: none;
            transition: all 0.3s ease;
            background: white;
            padding: 0 5px;
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            color: #667eea;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:focus + label {
            color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }

        /* Loading animation untuk button */
        .loading {
            position: relative;
            color: transparent !important;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: button-loading-spinner 1s ease infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }
            to {
                transform: rotate(1turn);
            }
        }

        /* Floating icons animation */
        .form-group {
            position: relative;
        }

        .form-group::before {
            content: '';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }

        .form-group:focus-within::before {
            opacity: 0.8;
        }

        /* Success/Error message animations */
        .alert {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Checkbox styling untuk Remember Me */
        .checkbox-group {
            margin-bottom: 20px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            user-select: none;
        }

        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            height: 18px;
            width: 18px;
            background-color: #eee;
            border-radius: 3px;
            margin-right: 10px;
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid #ddd;
        }

        .checkbox-container:hover .checkmark {
            background-color: #ccc;
        }

        .checkbox-container input:checked ~ .checkmark {
            background-color: #667eea;
            border-color: #667eea;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 1px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Selamat Datang</h1>
            <p>Silakan masuk untuk melanjutkan</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder=" "
                       required autocomplete="username">
                <label for="username">👤 Username</label>
            </div>

            <div class="form-group">
                <input type="password" id="password" name="password" placeholder=" "
                       required autocomplete="current-password">
                <label for="password">🔒 Password</label>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <span class="checkmark"></span>
                    Ingat saya
                </label>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                Masuk
            </button>
        </form>

        <div class="login-footer">
            <p>Butuh bantuan? <a href="#">Hubungi Admin</a></p>
            <p>&copy; 2024 Sistem Informasi. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Tambahan JavaScript untuk enhancement
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const originalText = btn.textContent;

            btn.textContent = 'Memproses...';
            btn.classList.add('loading');

            // Simulate processing time
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('loading');
            }, 2000);
        });

        // Auto-focus pada username field
        document.getElementById('username').focus();

        // Enter key pada password field
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Animasi untuk error messages
        <?php if (!empty($error_message)): ?>
            setTimeout(() => {
                const alert = document.querySelector('.alert-error');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>