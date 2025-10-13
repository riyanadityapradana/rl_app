# Modul Rekap Kunjungan Pasien Rawat Jalan

## Deskripsi
Modul ini digunakan untuk melakukan rekapitulasi data kunjungan pasien rawat jalan (outpatient) di rumah sakit berdasarkan poliklinik dan jenis pembayaran.

## Fitur Utama

### 1. Rekap Kunjungan Pasien Poli (`rekap_pasien_poli.php`)
- Menampilkan rekap kunjungan pasien per poliklinik per minggu
- Pengelompokan berdasarkan jenis pembayaran (UMUM, BPJS, ASURANSI)
- Visualisasi grafik harian melalui modal popup
- Export data dalam format tabel

### 2. Jumlah Pasien Rawat Jalan (`jum_px_ralan.php`)
- Menampilkan total jumlah pasien rawat jalan
- Perhitungan berdasarkan periode waktu tertentu

### 3. Rekap Pasien Berdasarkan Usia (`rekap_px_usia_ralan.php`)
- Analisis kunjungan pasien berdasarkan kelompok usia
- Kategorisasi usia untuk analisis demografi

## Struktur Poliklinik

Modul ini mengelompokkan poliklinik ke dalam kategori utama sebagai berikut:

| Kategori | Kode Poli | Sub Poliklinik |
|----------|-----------|----------------|
| **GIGI** | U0008 | U0008, U0025, U0042, U0043, U0052, U0057, U0065 |
| **BEDAH** | U0002 | U0004, U0015, U0054, U0066 |
| **ANAK** | U0003 | U0002, U0026 |
| **THT** | U0006 | U0011 |
| **PENYAKIT DALAM** | U0004 | U0003, U0030, U0031, U0033, U0034, U0035, U0036, U0037, U0038, U0039, U0040, U0041, U0063 |
| **PARU** | U0019 | U0019 |
| **SARAF** | U0007 | U0007, U0049, U0050 |
| **MATA** | U0005 | U0005, U0061 |
| **KANDUNGAN** | U0010 | U0010, U0024, U0044, U0045, U0046, U0047, U0048, U0051, U0059, U0060 |
| **REHABILITASI MEDIK** | kfr | kfr |
| **JANTUNG** | U0012 | U0012, U0032 |
| **JIWA** | U0013 | U0013, U0018 |
| **ORTHOPEDI** | U0014 | U0016 |

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
   - Rekap Poli: `main_app.php?page=rekap_pasien_poli`
   - Jumlah Pasien: `main_app.php?page=jum_px_ralan`
   - Rekap Usia: `main_app.php?page=rekap_px_usia_ralan`

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
- Exclude pasien dengan nama mengandung "test"

## Query Utama

```sql
SELECT kd_pj, COUNT(*) as jml
FROM reg_periksa
WHERE tgl_registrasi BETWEEN 'start_date' AND 'end_date'
AND kd_poli IN ('list_kode_poli')
AND kd_pj IN ('A09','BPJ','A92')
AND stts='Sudah'
AND status_bayar='Sudah Bayar'
AND no_rkm_medis NOT IN (
    SELECT no_rkm_medis FROM pasien
    WHERE LOWER(nm_pasien) LIKE '%test%'
)
GROUP BY kd_pj
```

## Output

- **Tabel Rekap**: Menampilkan data dalam format tabel dengan kolom:
  - Nama Poliklinik
  - Data per minggu (UMUM, BPJS, ASURANSI, JUMLAH)
  - Total per jenis pembayaran
  - Total keseluruhan per minggu

- **Grafik**: Visualisasi data harian dalam bentuk line chart

## Maintenance

Untuk mengubah konfigurasi poliklinik atau jenis pembayaran:
1. Edit array `$mapping_poli` untuk menambah/mengurangi poliklinik
2. Edit array `$penjamin` untuk menambah jenis pembayaran baru
3. Pastikan kode poli sudah sesuai dengan data di tabel `poliklinik`

## Dependencies

- Database MySQL dengan tabel:
  - `reg_periksa` (data registrasi pasien)
  - `poliklinik` (master poliklinik)
  - `pasien` (master data pasien)

- Library JavaScript:
  - Chart.js untuk grafik
  - jQuery untuk DOM manipulation