<?php
// Mulai session untuk memastikan session tersedia
session_start();

// Hapus semua session variables
$_SESSION = array();

// Hapus session cookie jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Hapus remember me token jika ada
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Hapus semua cookie yang mungkin terkait session
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'PHPSESSID') === 0 || strpos($name, 'remember_') === 0) {
        setcookie($name, '', time() - 3600, '/');
    }
}

// Hapus semua cookie session yang mungkin ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Pastikan tidak ada output sebelum header
if (ob_get_level()) {
    ob_clean();
}

// Redirect ke halaman login dengan parameter logout untuk mencegah auto-login
header("Location: ../index.php?logout=1");
exit();
?>