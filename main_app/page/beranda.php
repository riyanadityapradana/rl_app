<?php
// Homepage - Beranda
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Sistem Informasi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        .logo {
            max-width: 200px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .welcome-text {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .description {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="logo-container">
            <img src="../assets/img/logo.jpg" alt="Logo" class="logo" onerror="this.style.display='none'">
        </div>

        <div class="welcome-text">
            Selamat Datang di Beranda Utama
        </div>

        <div class="description">
            <p>Sistem informasi ini dirancang untuk memudahkan pengelolaan data dan informasi.</p>
            <p>Silakan pilih menu yang tersedia untuk melanjutkan.</p>
        </div>
    </div>
</body>
</html>