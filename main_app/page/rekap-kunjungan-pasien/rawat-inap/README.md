# Modul Rekap Kunjungan Pasien Rawat Inap

## Deskripsi
Modul ini digunakan untuk melakukan rekapitulasi data kunjungan pasien rawat inap (inpatient) di rumah sakit berdasarkan kamar rawat dan jenis pembayaran.

## Fitur Utama

### 1. Rekap Kunjungan Pasien Rawat Inap (`rekap_pasien_ranap.php`)
- Menampilkan rekap kunjungan pasien per kamar per minggu
- Pengelompokan berdasarkan jenis pembayaran (UMUM, BPJS, ASURANSI)
- Visualisasi grafik harian melalui modal popup
- Export data dalam format tabel

### 2. Jumlah Pasien Rawat Inap (`jum_px_ranap.php`)
- Menampilkan total jumlah pasien rawat inap
- Perhitungan berdasarkan periode waktu tertentu

### 3. Rekap Pasien Berdasarkan Usia (`rekap_px_usia_ranap.php`)
- Analisis kunjungan pasien berdasarkan kelompok usia
- Kategorisasi usia untuk analisis demografi

### 4. Rekap Pasien Rawat Inap Berdasarkan Kabupaten (`rekap_pasien_ranap_kabupaten.php`)
- Analisis kunjungan berdasarkan asal kabupaten pasien
- Data geografis untuk perencanaan layanan

## Struktur Kamar Rawat

Modul ini mengelompokkan kamar rawat berdasarkan kategori sebagai berikut:

| Grup Kamar | Sub Kamar | Keterangan |
|------------|-----------|------------|
| **BERLIAN** | BERLIAN | Kamar rawat kelas VIP |
| **SAFIR** | SAFIR | Kamar rawat kelas 1 |
| **RUBY** | RUBY A, RUBY B | Kamar rawat kelas 2 |
| **ZAMRUD** | ZAMRUD A, ZAMRUD B, ZAMRUD C | Kamar rawat kelas 3 |
| **ISOLASI** | ISOLASI | Kamar isolasi khusus |
| **KECUBUNG** | KECUBUNG A, KECUBUNG B1-B4 | Kamar rawat kelas 3 |
| **YAKUT** | YAKUT A, YAKUT B, YAKUT C | Kamar rawat kelas 3 |

## Jenis Pembayaran

Data dikategorikan berdasarkan jenis pembayaran sebagai berikut:

| Kode | Jenis Pembayaran | Keterangan |
|------|------------------|------------|
| A09 | UMUM | Pasien umum/swasta |
| BPJ | BPJS | Peserta BPJS Kesehatan |
| A92 | ASURANSI | Pasien dengan asuransi kesehatan |

## Teknologi yang Digunakan

- **Backend**: PHP 7.x
- **Database**: MySQL (sik9)
- **Frontend**: HTML5, CSS3, JavaScript
- **Library**: Chart.js untuk visualisasi grafik
- **Framework**: AdminLTE (Bootstrap 4)

## Konfigurasi Database

```php
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
```

## Cara Penggunaan

1. **Akses Halaman**:
   - Rekap Rawat Inap: `main_app.php?page=rekap_pasien_ranap`
   - Jumlah Pasien: `main_app.php?page=jum_px_ranap`
   - Rekap Usia: `main_app.php?page=rekap_px_usia_ranap`
   - Rekap Kabupaten: `main_app.php?page=rekap_pasien_ranap_kabupaten`

2. **Melihat Grafik**:
   - Klik tombol "Lihat Grafik Harian" untuk melihat visualisasi data
   - Modal akan menampilkan grafik line chart kunjungan harian

3. **Periode Data**:
   - Data ditampilkan per minggu dalam bulan berjalan
   - Grafik menampilkan data harian untuk periode tertentu

## Filter Data

Sistem melakukan filter otomatis untuk:
- Pasien dengan status "Sudah" (stts='Sudah')
- Pembayaran sudah lunas (status_bayar='Sudah Bayar')
- Data berdasarkan tanggal masuk (tgl_masuk) pasien

## Query Utama

```sql
SELECT rp.kd_pj, COUNT(*) as jml
FROM kamar_inap ki
JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
JOIN kamar k ON ki.kd_kamar = k.kd_kamar
JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
WHERE k.kd_kamar LIKE 'PREFIX%'
AND rp.kd_pj IN ('A09','BPJ','A92')
AND rp.stts='Sudah'
AND rp.status_bayar='Sudah Bayar'
AND ki.tgl_masuk BETWEEN 'start_date' AND 'end_date'
GROUP BY rp.kd_pj
```

## Output

- **Tabel Rekap**: Menampilkan data dalam format tabel dengan kolom:
  - Grup Kamar Rawat
  - Data per minggu (UMUM, BPJS, ASURANSI, JUMLAH)
  - Total per jenis pembayaran
  - Total keseluruhan per minggu

- **Grafik**: Visualisasi data harian dalam bentuk line chart dengan konfigurasi:
  - Max Y-axis: 50 pasien per hari
  - Step size: 5 pasien
  - Warna hijau untuk line chart

## Dependencies Database

- Tabel yang digunakan:
  - `kamar_inap` (data rawat inap pasien)
  - `reg_periksa` (data registrasi pasien)
  - `kamar` (master data kamar)
  - `bangsal` (master data bangsal/ruangan)

## Mapping Grup Kamar

Grup kamar dibuat untuk mengelompokkan kamar-kamar individual:

```php
$mapping_kamar = [
    'YAKUT C' => 'YAKUT C',
    'YAKUT A' => 'YAKUT A',
    'YAKUT B' => 'YAKUT B',
    'ZAMRUD'  => 'ZAMRUD',
    'KECUBUNG' => 'KECUBUNG',
    'RUBY'    => 'RUBY',
    'SAFIR'   => 'SAFIR',
    'BERLIAN' => 'BERLIAN',
    // dst...
];
```

## Cara Kerja Sistem

1. **Pengelompokan Data**: Sistem mengelompokkan kamar individual ke dalam grup yang lebih besar
2. **Perhitungan Mingguan**: Data dihitung per minggu dalam bulan berjalan
3. **Agregasi Data**: Data dari setiap grup kamar dijumlahkan untuk mendapatkan total
4. **Visualisasi**: Data ditampilkan dalam tabel dan grafik untuk kemudahan analisis

## Konfigurasi Grafik

- **Tipe Chart**: Line Chart
- **Warna**: Hijau (#4CAF50)
- **Max Point**: 50 pasien per hari
- **Responsive**: Ya, menyesuaikan dengan ukuran modal
- **Interaksi**: Tooltip menampilkan informasi detail saat hover

## Troubleshooting

### Masalah Umum:
1. **Data tidak muncul**: Periksa koneksi database dan tabel yang digunakan
2. **Grafik tidak tampil**: Pastikan library Chart.js sudah ter-load dengan benar
3. **Data kosong**: Periksa filter data dan periode waktu yang digunakan

### Debug Query:
Untuk debugging, uncomment atau tambahkan log query untuk melihat SQL yang dijalankan.

## Pengembangan

Untuk pengembangan lebih lanjut:
1. Tambahkan filter berdasarkan bulan/tahun
2. Export ke format Excel/PDF
3. Tambahkan grafik perbandingan bulan sebelumnya
4. Integrasi dengan sistem informasi rumah sakit lainnya

## Lisensi

Modul ini merupakan bagian dari sistem informasi rumah sakit dan tunduk pada ketentuan penggunaan yang berlaku.