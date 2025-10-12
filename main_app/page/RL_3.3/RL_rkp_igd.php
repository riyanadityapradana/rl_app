<?php
// RL 3.3 - Rekapitulasi Kegiatan Pelayanan Rawat Darurat (IGD)
// Formulir RL 3.3 sesuai dengan ketentuan Kemenkes
require '../assets/vendor/autoload.php';
// Parameter periode
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Query sesuai dengan struktur database SIMRS Khanza untuk RL 3.3 IGD
$query = mysqli_query($config, "
SELECT 
    kategori,
    SUM(CASE WHEN r.stts_daftar = 'Lama' THEN 1 ELSE 0 END) AS rujukan,
    SUM(CASE WHEN r.stts_daftar = 'Baru' THEN 1 ELSE 0 END) AS non_rujukan,

    SUM(CASE WHEN r.stts = 'Dirawat' THEN 1 ELSE 0 END) AS dirawat,
    SUM(CASE WHEN r.stts = 'Dirujuk' THEN 1 ELSE 0 END) AS dirujuk,
    SUM(CASE WHEN r.stts = 'Pulang' THEN 1 ELSE 0 END) AS pulang,

    SUM(CASE WHEN r.stts = 'Meninggal' AND p.jk='L' THEN 1 ELSE 0 END) AS mati_L,
    SUM(CASE WHEN r.stts = 'Meninggal' AND p.jk='P' THEN 1 ELSE 0 END) AS mati_P,

    SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='L' THEN 1 ELSE 0 END) AS doa_L,
    SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='P' THEN 1 ELSE 0 END) AS doa_P,

    SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='L' THEN 1 ELSE 0 END) AS luka_L,
    SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='P' THEN 1 ELSE 0 END) AS luka_P,

    SUM(CASE WHEN d.kd_penyakit BETWEEN 'Z00' AND 'Z99' THEN 1 ELSE 0 END) AS false_emergency
FROM (
    SELECT 
        r.no_rawat,
        CASE
            WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' THEN '1. Bedah di Instalasi Gawat Darurat'
            WHEN d.kd_penyakit BETWEEN 'V01' AND 'Y98' THEN '2. Non Bedah'
            WHEN d.kd_penyakit BETWEEN 'O00' AND 'O99' THEN '3. Kebidanan'
            WHEN d.kd_penyakit BETWEEN 'F00' AND 'F99' THEN '4. Psikiatrik'
            WHEN TIMESTAMPDIFF(MONTH, p.tgl_lahir, r.tgl_registrasi) <= 11 THEN '5. Bayi (0-11 bulan)'
            WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) BETWEEN 1 AND 17 THEN '6. Anak (1-17 tahun)'
            WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) >= 60 THEN '7. Geriatri (≥60 tahun)'
            ELSE '8. Lainnya'
        END AS kategori,
        r.stts_daftar,
        r.stts,
        p.jk,
        d.kd_penyakit
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
    JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
    WHERE poli.nm_poli LIKE '%UGD%'
      AND MONTH(r.tgl_registrasi) = $bulan
      AND YEAR(r.tgl_registrasi) = $tahun
) AS data_igd
JOIN reg_periksa r ON data_igd.no_rawat = r.no_rawat
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
GROUP BY kategori
ORDER BY kategori");

// Data tambahan untuk sub-kategori bedah dan non-bedah
$sub_query = mysqli_query($config, "
    SELECT
        CASE
            WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' THEN
                CASE
                    WHEN TIMESTAMPDIFF(MONTH, p.tgl_lahir, r.tgl_registrasi) <= 11 THEN '1.1 Kecelakaan lalu lintas darat'
                    WHEN d.kd_penyakit BETWEEN 'V01' AND 'V99' THEN '1.2 Kecelakaan lalu lintas perairan'
                    ELSE '1.3 Kecelakaan lalu lintas udara'
                END
            WHEN d.kd_penyakit BETWEEN 'V01' AND 'Y98' THEN
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) BETWEEN 18 AND 59 THEN '2.1 Kekerasan terhadap Perempuan (≥18 tahun)'
                    WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) BETWEEN 1 AND 17 THEN '2.2 Kekerasan terhadap Anak (<18 tahun)'
                    ELSE '2.3 Kekerasan lainnya'
                END
        END AS sub_kategori,

        SUM(CASE WHEN stts_daftar = 'Lama' THEN 1 ELSE 0 END) AS rujukan,
        SUM(CASE WHEN stts_daftar = 'Baru' THEN 1 ELSE 0 END) AS non_rujukan,

        SUM(CASE WHEN stts = 'Dirawat' THEN 1 ELSE 0 END) AS dirawat,
        SUM(CASE WHEN stts = 'Dirujuk' THEN 1 ELSE 0 END) AS dirujuk,
        SUM(CASE WHEN stts = 'Pulang' THEN 1 ELSE 0 END) AS pulang,

        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='L' THEN 1 ELSE 0 END) AS mati_L,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='P' THEN 1 ELSE 0 END) AS mati_P,

        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='L' THEN 1 ELSE 0 END) AS doa_L,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='P' THEN 1 ELSE 0 END) AS doa_P,

        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='L' THEN 1 ELSE 0 END) AS luka_L,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='P' THEN 1 ELSE 0 END) AS luka_P,

        SUM(CASE WHEN d.kd_penyakit BETWEEN 'Z00' AND 'Z99' THEN 1 ELSE 0 END) AS false_emergency

    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat AND d.prioritas = '1'
    JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
    WHERE poli.nm_poli LIKE '%UGD%'
      AND MONTH(r.tgl_registrasi) = $bulan
      AND YEAR(r.tgl_registrasi) = $tahun
      AND (
          (d.kd_penyakit BETWEEN 'S00' AND 'T88') OR
          (d.kd_penyakit BETWEEN 'V01' AND 'Y98')
      )
    GROUP BY sub_kategori
    ORDER BY sub_kategori
");

// Mengambil data hasil query
$data = [];
while ($row = mysqli_fetch_array($query)) {
    $data[] = $row;
}

// Mengambil data sub-kategori
$sub_data = [];
while ($row = mysqli_fetch_array($sub_query)) {
    $sub_data[] = $row;
}

// Hitung total
$total_query = mysqli_query($config, "
    SELECT
        SUM(CASE WHEN stts_daftar = 'Lama' THEN 1 ELSE 0 END) AS total_rujukan,
        SUM(CASE WHEN stts_daftar = 'Baru' THEN 1 ELSE 0 END) AS total_non_rujukan,
        SUM(CASE WHEN stts = 'Dirawat' THEN 1 ELSE 0 END) AS total_dirawat,
        SUM(CASE WHEN stts = 'Dirujuk' THEN 1 ELSE 0 END) AS total_dirujuk,
        SUM(CASE WHEN stts = 'Pulang' THEN 1 ELSE 0 END) AS total_pulang,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='L' THEN 1 ELSE 0 END) AS total_mati_L,
        SUM(CASE WHEN stts = 'Meninggal' AND p.jk='P' THEN 1 ELSE 0 END) AS total_mati_P,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='L' THEN 1 ELSE 0 END) AS total_doa_L,
        SUM(CASE WHEN d.kd_penyakit = 'R99' AND p.jk='P' THEN 1 ELSE 0 END) AS total_doa_P,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='L' THEN 1 ELSE 0 END) AS total_luka_L,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'S00' AND 'T88' AND p.jk='P' THEN 1 ELSE 0 END) AS total_luka_P,
        SUM(CASE WHEN d.kd_penyakit BETWEEN 'Z00' AND 'Z99' THEN 1 ELSE 0 END) AS total_false_emergency
    FROM reg_periksa r
    JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    JOIN diagnosa_pasien d ON r.no_rawat = d.no_rawat
    JOIN poliklinik poli ON r.kd_poli = poli.kd_poli
    WHERE poli.nm_poli LIKE '%UGD%'
      AND MONTH(r.tgl_registrasi) = $bulan
      AND YEAR(r.tgl_registrasi) = $tahun
");

$total_data = mysqli_fetch_array($total_query);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>RL 3.3 - Rekapitulasi Kegiatan Pelayanan Rawat Darurat</h1>
        <p class="text-muted">Formulir Kunjungan Rawat Darurat - Periode <?php
          $nama_bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
          ];
          echo $nama_bulan[$bulan] . ' ' . $tahun;
        ?></p>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item active">RL 3.3 IGD</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Filter Section -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Filter Periode Laporan</h3>
          </div>
          <div class="card-body">
            <form method="GET" action="">
              <input type="hidden" name="page" value="RL_rkp_igd">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="tahun">Tahun:</label>
                    <select class="form-control" id="tahun" name="tahun" required>
                      <?php
                      $current_year = date('Y');
                      for ($year = $current_year; $year >= $current_year - 5; $year--) {
                        $selected = ($tahun == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="bulan">Bulan:</label>
                    <select class="form-control" id="bulan" name="bulan" required>
                      <?php
                      for ($month = 1; $month <= 12; $month++) {
                        $selected = ($bulan == $month) ? 'selected' : '';
                        echo "<option value='$month' $selected>" . $nama_bulan[$month] . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="main_app.php?page=RL_rkp_igd" class="btn btn-secondary">Reset</a>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <a href="javascript:void(0);" onclick="exportExcel()" class="btn btn-success">
                      <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Formulir RL 3.3 Rekapitulasi Kegiatan Pelayanan Rawat Darurat</h3>
            <div class="card-tools">
              <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse">
                <i class="fas fa-bars"></i>
              </a>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                  <tr>
                    <th rowspan="3" class="text-center align-middle">No.</th>
                    <th rowspan="3" class="text-center align-middle">Jenis Pelayanan</th>
                    <th colspan="2" class="text-center">Total Pasien</th>
                    <th colspan="3" class="text-center">Tidak Lanjut Pelayanan</th>
                    <th colspan="2" class="text-center">Mati di IGD</th>
                    <th colspan="2" class="text-center">DOA</th>
                    <th colspan="2" class="text-center">Luka-luka</th>
                    <th rowspan="3" class="text-center align-middle">False Emergency</th>
                  </tr>
                  <tr>
                    <th class="text-center">Rujukan</th>
                    <th class="text-center">Non Rujukan</th>
                    <th class="text-center">Dirawat</th>
                    <th class="text-center">Dirujuk</th>
                    <th class="text-center">Pulang</th>
                    <th class="text-center">Laki-laki</th>
                    <th class="text-center">Perempuan</th>
                    <th class="text-center">Laki-laki</th>
                    <th class="text-center">Perempuan</th>
                    <th class="text-center">Laki-laki</th>
                    <th class="text-center">Perempuan</th>
                  </tr>
                  <tr>
                    <th class="text-center">(1)</th>
                    <th class="text-center">(2)</th>
                    <th class="text-center">(3)</th>
                    <th class="text-center">(4)</th>
                    <th class="text-center">(5)</th>
                    <th class="text-center">(6)</th>
                    <th class="text-center">(7)</th>
                    <th class="text-center">(8)</th>
                    <th class="text-center">(9)</th>
                    <th class="text-center">(10)</th>
                    <th class="text-center">(11)</th>
                    <th class="text-center">(12)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  foreach ($data as $row) {
                      // Parse kategori untuk mendapatkan nomor dan nama
                      preg_match('/(\d+)\.\s*(.+)/', $row['kategori'], $matches);
                      $nomor = $matches[1] ?? '';
                      $nama_kategori = $matches[2] ?? $row['kategori'];

                      // Hitung total pasien
                      $total_pasien = $row['rujukan'] + $row['non_rujukan'];
                      $tidak_lanjut = $row['dirawat'] + $row['dirujuk'] + $row['pulang'];
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold"><?php echo $nomor; ?></td>
                    <td class="font-weight-bold"><?php echo $nama_kategori; ?></td>
                    <td class="text-center bg-light"><?php echo number_format($row['rujukan']); ?></td>
                    <td class="text-center bg-light"><?php echo number_format($row['non_rujukan']); ?></td>
                    <td class="text-center"><?php echo number_format($row['dirawat']); ?></td>
                    <td class="text-center"><?php echo number_format($row['dirujuk']); ?></td>
                    <td class="text-center"><?php echo number_format($row['pulang']); ?></td>
                    <td class="text-center text-danger"><?php echo number_format($row['mati_L']); ?></td>
                    <td class="text-center text-danger"><?php echo number_format($row['mati_P']); ?></td>
                    <td class="text-center text-info"><?php echo number_format($row['doa_L']); ?></td>
                    <td class="text-center text-info"><?php echo number_format($row['doa_P']); ?></td>
                    <td class="text-center text-warning"><?php echo number_format($row['luka_L']); ?></td>
                    <td class="text-center text-warning"><?php echo number_format($row['luka_P']); ?></td>
                    <td class="text-center text-muted"><?php echo number_format($row['false_emergency']); ?></td>
                  </tr>

                  <?php
                      // Tambahkan sub-kategori untuk Bedah dan Non Bedah
                      if (in_array($nomor, ['1', '2'])) {
                          foreach ($sub_data as $sub_row) {
                              if (strpos($sub_row['sub_kategori'], $nomor . '.') === 0) {
                                  preg_match('/' . $nomor . '\.(\d+)\s*(.+)/', $sub_row['sub_kategori'], $sub_matches);
                                  $sub_nomor = $sub_matches[1] ?? '';
                                  $sub_nama = $sub_matches[2] ?? $sub_row['sub_kategori'];
                                  $sub_total_pasien = $sub_row['rujukan'] + $sub_row['non_rujukan'];
                                  $sub_tidak_lanjut = $sub_row['dirawat'] + $sub_row['dirujuk'] + $sub_row['pulang'];
                      ?>
                      <tr>
                        <td class="text-center"><?php echo $nomor . '.' . $sub_nomor; ?></td>
                        <td class="pl-4"><em><?php echo $sub_nama; ?></em></td>
                        <td class="text-center bg-light"><?php echo number_format($sub_row['rujukan']); ?></td>
                        <td class="text-center bg-light"><?php echo number_format($sub_row['non_rujukan']); ?></td>
                        <td class="text-center"><?php echo number_format($sub_row['dirawat']); ?></td>
                        <td class="text-center"><?php echo number_format($sub_row['dirujuk']); ?></td>
                        <td class="text-center"><?php echo number_format($sub_row['pulang']); ?></td>
                        <td class="text-center text-danger"><?php echo number_format($sub_row['mati_L']); ?></td>
                        <td class="text-center text-danger"><?php echo number_format($sub_row['mati_P']); ?></td>
                        <td class="text-center text-info"><?php echo number_format($sub_row['doa_L']); ?></td>
                        <td class="text-center text-info"><?php echo number_format($sub_row['doa_P']); ?></td>
                        <td class="text-center text-warning"><?php echo number_format($sub_row['luka_L']); ?></td>
                        <td class="text-center text-warning"><?php echo number_format($sub_row['luka_P']); ?></td>
                        <td class="text-center text-muted"><?php echo number_format($sub_row['false_emergency']); ?></td>
                      </tr>
                      <?php
                              }
                          }
                      }
                      $no++;
                  }
                  ?>

                  <!-- TOTAL -->
                  <tr class="bg-dark text-white font-weight-bold">
                    <td class="text-center">99</td>
                    <td><strong>TOTAL</strong></td>
                    <td class="text-center bg-light"><strong><?php echo number_format($total_data['total_rujukan']); ?></strong></td>
                    <td class="text-center bg-light"><strong><?php echo number_format($total_data['total_non_rujukan']); ?></strong></td>
                    <td class="text-center"><strong><?php echo number_format($total_data['total_dirawat']); ?></strong></td>
                    <td class="text-center"><strong><?php echo number_format($total_data['total_dirujuk']); ?></strong></td>
                    <td class="text-center"><strong><?php echo number_format($total_data['total_pulang']); ?></strong></td>
                    <td class="text-center text-danger"><strong><?php echo number_format($total_data['total_mati_L']); ?></strong></td>
                    <td class="text-center text-danger"><strong><?php echo number_format($total_data['total_mati_P']); ?></strong></td>
                    <td class="text-center text-info"><strong><?php echo number_format($total_data['total_doa_L']); ?></strong></td>
                    <td class="text-center text-info"><strong><?php echo number_format($total_data['total_doa_P']); ?></strong></td>
                    <td class="text-center text-warning"><strong><?php echo number_format($total_data['total_luka_L']); ?></strong></td>
                    <td class="text-center text-warning"><strong><?php echo number_format($total_data['total_luka_P']); ?></strong></td>
                    <td class="text-center text-muted"><strong><?php echo number_format($total_data['total_false_emergency']); ?></strong></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function exportExcel() {
    const tahun = document.getElementById('tahun').value;
    const bulan = document.getElementById('bulan').value;
    const exportUrl = `main_app.php?page=export_excel_rl_3_3&tahun=${tahun}&bulan=${bulan}`;
    window.open(exportUrl, '_blank');
}
</script>