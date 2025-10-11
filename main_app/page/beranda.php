<?php
// Homepage - Beranda Modern dengan Animasi
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Sistem Informasi Terintegrasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
        }

        /* Animated Background Elements */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .bg-circle:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .bg-circle:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Logo Section with Glass Morphism */
        .logo-section {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInUp 1s ease-out;
        }

        .logo-container {
            position: relative;
            display: inline-block;
        }

        .logo {
            max-width: 180px;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px;
        }

        .logo:hover {
            transform: translateY(-5px) scale(1.05);
        }

        .logo-glow {
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c);
            border-radius: 25px;
            z-index: -1;
            animation: rotate 3s linear infinite;
            opacity: 0.7;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 30px;
            border-radius: 50px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .welcome-icon {
            font-size: 20px;
            color: #ffd700;
            animation: pulse 2s infinite;
        }

        .welcome-text {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #ffd700 50%, #ff6b6b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            color: rgba(0, 0, 0, 0.9);
            max-width: 600px;
            line-height: 1.6;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1000px;
            width: 100%;
            margin-bottom: 50px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px 25px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out both;
        }

        .stat-card:nth-child(1) { animation-delay: 0.5s; }
        .stat-card:nth-child(2) { animation-delay: 0.7s; }
        .stat-card:nth-child(3) { animation-delay: 0.9s; }
        .stat-card:nth-child(4) { animation-delay: 1.1s; }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.25);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #000000ff;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Action Buttons */
        .action-section {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 1s ease-out 1.3s both;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            padding: 15px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .action-btn i {
            font-size: 1.2rem;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            animation: fadeInUp 1s ease-out 1.5s both;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .welcome-text {
                font-size: 2.5rem;
            }

            .stats-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .action-section {
                flex-direction: column;
                align-items: center;
            }

            .action-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .bg-circle {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .welcome-text {
                font-size: 2rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
                padding: 0 20px;
            }

            .stat-card {
                padding: 20px 15px;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Hide loading after page load */
        body.loaded .loading {
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-container">
                <div class="logo-glow"></div>
                <img src="../assets/img/logo.jpg" alt="Logo Sistem" class="logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div class="logo" style="display: none;" id="default-logo">
                    <i class="fas fa-hospital" style="font-size: 4rem; color: rgba(255, 255, 255, 0.8);"></i>
                </div>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-badge">
                <i class="fas fa-star welcome-icon"></i>
                <span>Sistem Informasi Terintegrasi</span>
            </div>

            <h1 class="welcome-text">
                Selamat Datang
            </h1>

            <p class="welcome-subtitle">
                Platform canggih untuk pengelolaan data dan informasi rumah sakit.
                Solusi terdepan untuk efisiensi dan akurasi data klinis.
            </p>
        </div>

        <!-- Stats Cards -->
        <!-- <div class="stats-section"> 
            <div class="stat-card">
                <i class="fas fa-users stat-icon" style="color: #ffd700;"></i>
                <div class="stat-number" id="patientCount">1,234</div>
                <div class="stat-label">Total Pasien</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-stethoscope stat-icon" style="color: #ff6b6b;"></i>
                <div class="stat-number" id="doctorCount">56</div>
                <div class="stat-label">Dokter Aktif</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-procedures stat-icon" style="color: #4ecdc4;"></i>
                <div class="stat-number" id="serviceCount">789</div>
                <div class="stat-label">Layanan Hari Ini</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-chart-line stat-icon" style="color: #45b7d1;"></i>
                <div class="stat-number" id="efficiencyRate">94.2%</div>
                <div class="stat-label">Efisiensi Sistem</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <!-- <div class="action-section">
            <a href="main_app.php?page=dashboard" class="action-btn">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="main_app.php?page=poli" class="action-btn">
                <i class="fas fa-user-md"></i>
                <span>Poliklinik</span>
            </a>
            <a href="main_app.php?page=pasien" class="action-btn">
                <i class="fas fa-users"></i>
                <span>Data Pasien</span>
            </a>
            <a href="main_app.php?page=laporan" class="action-btn">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
        </div> -->

    </div>

    <script>
        // Loading animation
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, 1000);
        });

        // Counter animations
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            function updateCounter() {
                current += increment;
                if (current < target) {
                    element.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target.toLocaleString();
                }
            }

            updateCounter();
        }

        // Initialize counters when page loads
        window.addEventListener('load', function() {
            setTimeout(function() {
                animateCounter(document.getElementById('patientCount'), 1234);
                animateCounter(document.getElementById('doctorCount'), 56);
                animateCounter(document.getElementById('serviceCount'), 789);
                document.getElementById('efficiencyRate').textContent = '94.2%';
            }, 1500);
        });

        // Parallax effect for background circles
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            document.querySelectorAll('.bg-circle').forEach((circle, index) => {
                circle.style.transform = `translateY(${rate * (index + 1)}px)`;
            });
        });

        // Logo error handler
        document.querySelector('.logo').addEventListener('error', function() {
            this.style.display = 'none';
            document.getElementById('default-logo').style.display = 'block';
        });
    </script>
</body>
</html>