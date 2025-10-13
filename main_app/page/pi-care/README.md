# 📋 Sistem PI-Care (Pasien Care)
**Sistem Informasi Pendaftaran dan Pembatalan Pasien Rawat Jalan**

## 🎯 Overview

Sistem PI-Care adalah aplikasi web untuk mengelola data pendaftaran dan pembatalan pasien rawat jalan di Rumah Sakit Pelita Insani Martapura. Sistem ini menyediakan fitur monitoring, reporting, dan analisis data pasien dengan visualisasi grafik yang informatif.

## 🏗️ Struktur Sistem

### Direktori Utama
```
rl_app/main_app/page/pi-care/
├── pi-care_daftar.php          # Halaman utama data pendaftaran
├── pi-care_batal.php           # Halaman data pembatalan
├── pi-care_alasan.php           # Halaman analisis alasan pembatalan
├── lap_pi-care_daftar_pdf.php  # Generator PDF laporan pendaftaran
├── lap_pi-care_batal_pdf.php   # Generator PDF laporan pembatalan
├── lap_pi-care_alasan_pdf.php  # Generator PDF laporan alasan
└── README.md                   # Dokumentasi ini
```

## 🗄️ Database Schema

### Tabel `daftar_pasien`
```sql
CREATE TABLE daftar_pasien (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_rkm_medis VARCHAR(15),
    nama_pasien VARCHAR(100),
    poli VARCHAR(50),
    dokter VARCHAR(50),
    tanggal_daftar DATE,
    jam_daftar TIME,
    status ENUM('aktif', 'batal', 'selesai'),
    is_verified TINYINT DEFAULT 0,
    insert_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabel `batal_daftar`
```sql
CREATE TABLE batal_daftar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_rkm_medis VARCHAR(15),
    nama_pasien VARCHAR(100),
    poli VARCHAR(50),
    dokter VARCHAR(50),
    tanggal_batal DATE,
    alasan_batal INT,
    keterangan TEXT,
    is_verified TINYINT DEFAULT 0,
    insert_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel `preset_pesan`
```sql
CREATE TABLE preset_pesan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipe VARCHAR(100),
    pesan TEXT,
    aktif TINYINT DEFAULT 1
);
```

## 🔍 Query Utama

### 1. Query Data Pendaftaran (pi-care_daftar.php)
```sql
SELECT
    DATE_FORMAT(insert_at, '%Y-%m-%d') AS tanggal,
    COUNT(*) AS jumlah
FROM daftar_pasien
WHERE DATE_FORMAT(insert_at, '%Y-%m') = '$bulan_tahun_filter'
    AND is_verified <> '1'
GROUP BY tanggal
ORDER BY tanggal ASC
```

### 2. Query Data Pembatalan (pi-care_batal.php)
```sql
SELECT
    DATE_FORMAT(insert_at, '%Y-%m-%d') AS tanggal,
    COUNT(*) AS jumlah
FROM batal_daftar
WHERE DATE_FORMAT(insert_at, '%Y-%m') = '$bulan_tahun_filter'
    AND is_verified <> '0'
GROUP BY tanggal
ORDER BY tanggal ASC
```

### 3. Query Alasan Pembatalan (pi-care_alasan.php)
```sql
SELECT
    pp.tipe AS alasan_pembatalan,
    COUNT(*) AS jumlah
FROM batal_daftar bd
JOIN preset_pesan pp ON bd.alasan_batal = pp.id
WHERE YEAR(bd.insert_at) = '$tahun_filter'
    AND MONTH(bd.insert_at) = '$bulan_filter'
    AND bd.is_verified <> 0
    AND pp.tipe LIKE 'Pesan Pembatalan%'
GROUP BY pp.tipe
ORDER BY jumlah DESC
```

## ✨ Fitur Sistem

### 🎛️ Filter & Pencarian
- **Filter Bulan/Tahun**: Dropdown untuk memilih periode data
- **Validasi Input**: Memastikan bulan (1-12) dan tahun (2000-2100) valid
- **Reset Filter**: Tombol untuk kembali ke periode saat ini

### 📊 Visualisasi Data
- **Bar Chart**: Untuk data pendaftaran dan pembatalan harian
- **Pie Chart**: Untuk analisis alasan pembatalan
- **Chart.js Integration**: Menggunakan Chart.js v2.9.4
- **Responsive Design**: Grafik menyesuaikan dengan ukuran layar

### 🖨️ Sistem Print & PDF
- **Print Langsung**: Tombol untuk cetak data bulan aktif
- **PDF Custom**: Modal untuk pilih rentang tanggal manual
- **Popup Window**: PDF terbuka dalam window terkontrol
- **Auto Layout**: Layout PDF otomatis dengan header dan footer

### 📈 Laporan & Analisis
- **Summary Statistics**: Total, rata-rata, maksimal, minimal
- **Periode Analysis**: Analisis berdasarkan bulan/tahun
- **Trend Monitoring**: Monitoring tren pendaftaran/pembatalan

## 🚀 Cara Penggunaan

### Akses Halaman
1. **Login** ke sistem aplikasi
2. **Navigasi** ke menu PI-Care
3. **Pilih** salah satu halaman:
   - `pi-care_daftar.php` - Data pendaftaran pasien
   - `pi-care_batal.php` - Data pembatalan pasien
   - `pi-care_alasan.php` - Analisis alasan pembatalan

### Menggunakan Filter
1. **Pilih Bulan** dari dropdown (Januari-Desember)
2. **Pilih Tahun** dari dropdown (5 tahun terakhir)
3. **Klik "Filter"** untuk melihat data periode tersebut
4. **Klik "Reset"** untuk kembali ke bulan/tahun saat ini

### Print Laporan
1. **Filter data** sesuai periode yang diinginkan
2. **Klik tombol "Print"** untuk cetak bulan aktif
3. **Klik tombol "PDF Custom"** untuk pilih tanggal manual
4. **PDF terbuka** dalam popup window
5. **Klik tombol "🖨️ Print PDF"** di dalam PDF

## ⚙️ Konfigurasi Teknis

### Dependencies
- **PHP 7.4+**
- **MySQL 5.7+**
- **Chart.js 2.9.4**
- **Bootstrap 4+**
- **jQuery 3.5+**

### File Konfigurasi
- `../../../config/koneksi.php` - Konfigurasi database
- `../../assets/plugins/chart.js/Chart.min.js` - Library grafik
- `../../../assets/css/custom.css` - Styling khusus

### Environment Setup
```php
// Konfigurasi Database
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';

// Set timezone
date_default_timezone_set('Asia/Singapore');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🔒 Keamanan

### Input Validation
- **SQL Injection Prevention**: Menggunakan parameterized queries
- **XSS Protection**: htmlspecialchars() untuk output
- **Input Sanitization**: Validasi bulan/tahun range
- **CSRF Protection**: POST method untuk form penting

### Access Control
- **Session Management**: Cek session login sebelum akses
- **File Permission**: Pastikan file PHP tidak dapat diakses langsung
- **Error Handling**: Logging error tanpa expose informasi sensitif

## 🚨 Troubleshooting

### Grafik Tidak Muncul
```javascript
// Pastikan Chart.js dimuat
if (typeof Chart === 'undefined') {
    console.error('Chart.js not loaded!');
    return;
}
```

### PDF Tidak Terbuka
```javascript
// Cek path file PDF
href="lap_pi-care_daftar_pdf.php?dari=2025-01-01&sampai=2025-01-31"
```

### Database Connection Error
```php
// Cek konfigurasi koneksi
if (!isset($conn)) {
    die("Database connection not available.");
}
```

### Query Error
```sql
-- Cek index tabel
SHOW INDEX FROM daftar_pasien;
SHOW INDEX FROM batal_daftar;
```

## 📊 Monitoring & Maintenance

### Log Files
- **Error Log**: `error_log()` untuk debugging
- **Query Log**: Console browser untuk debug query
- **Performance**: Monitor query execution time

### Database Maintenance
```sql
-- Optimasi tabel
OPTIMIZE TABLE daftar_pasien;
OPTIMIZE TABLE batal_daftar;
OPTIMIZE TABLE preset_pesan;

-- Cek ukuran tabel
SELECT table_name, table_rows, data_length
FROM information_schema.tables
WHERE table_schema = 'sik9';
```

## 🔄 Update & Development

### Menambah Fitur Baru
1. **Analisis requirement** dengan user
2. **Design database schema** jika diperlukan
3. **Develop fitur** dengan test case
4. **Testing** di environment staging
5. **Deploy** ke production

### Best Practices
- **Code Documentation**: Comment setiap function
- **Responsive Design**: Test di berbagai device
- **Performance Optimization**: Minimasi query dan asset
- **Security First**: Input validation dan sanitization

## 📞 Support

### Developer Contact
- **Team**: IT RSPI Development Team
- **Email**: it.rspi@pelitainsani-martapura.com
- **Phone**: +62-xxx-xxxx-xxxx

### Documentation Update
- **Last Update**: 13 Oktober 2025
- **Version**: 1.0.0
- **Changelog**: Lihat commit history

---

**© 2025 Rumah Sakit Pelita Insani Martapura - IT Department**