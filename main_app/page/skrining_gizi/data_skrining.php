<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Data Skrining Gizi - <?php
          $kategori_default = isset($_GET['kategori_umur']) ? $_GET['kategori_umur'] : 'balita';
          echo get_kategori_umur_label($kategori_default);
        ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=skrining_gizi_dashboard">Dashboard Gizi</a></li>
          <li class="breadcrumb-item active">Data Skrining</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Filter Section -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Filter Periode Skrining</h3>
          </div>
          <div class="card-body">
            <form method="GET" action="">
              <input type="hidden" name="page" value="skrining_gizi_data">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="tahun">Tahun:</label>
                    <select class="form-control" id="tahun" name="tahun" required>
                      <?php
                      $current_year = date('Y');
                      for ($year = $current_year; $year >= $current_year - 5; $year--) {
                        $selected = (isset($_GET['tahun']) && $_GET['tahun'] == $year) ? 'selected' : '';
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
                      $nama_bulan = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                      ];

                      for ($month = 1; $month <= 12; $month++) {
                        $selected = (isset($_GET['bulan']) && $_GET['bulan'] == $month) ? 'selected' : '';
                        echo "<option value='$month' $selected>" . $nama_bulan[$month] . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="kategori_umur">Kategori Umur (KEMENKES RI):</label>
                    <select class="form-control" id="kategori_umur" name="kategori_umur">
                      <?php
                      $kategori_umur = [
                        'bayi' => 'Bayi (0 - <1 tahun)',
                        'balita' => 'Balita (1 - <5 tahun)',
                        'anak' => 'Anak-anak (5 - <12 tahun)',
                        'remaja' => 'Remaja (12 - <18 tahun)',
                        'dewasa_muda' => 'Dewasa Muda (18 - <30 tahun)',
                        'dewasa' => 'Dewasa (30 - <45 tahun)',
                        'lansia_awal' => 'Lansia Awal (45 - <60 tahun)',
                        'lansia_akhir' => 'Lansia Akhir (≥60 tahun)'
                      ];

                      $selected_kategori = isset($_GET['kategori_umur']) ? $_GET['kategori_umur'] : 'balita';

                      foreach ($kategori_umur as $key => $label) {
                        $selected = $selected_kategori == $key ? 'selected' : '';
                        echo "<option value='$key' $selected>$label</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="main_app.php?page=skrining_gizi_data" class="btn btn-secondary">Reset</a>
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
            <div class="card-tools" style="float: left; text-align: left;">
              <a href="javascript:void(0);" onclick="exportExcel()" class="btn btn-tool btn-sm" style="background:rgba(34, 139, 34, 1); color: white;">
                <i class="fas fa-file-excel" style="color: white;"></i> Export Excel
              </a>
            </div>
            <div class="card-tools" style="float: right; text-align: right;">
              <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
                <i class="fas fa-bars"></i>
              </a>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
              <thead style="background:rgb(129, 2, 0, 1)">
                <tr>
                  <th style="text-align: center; color: white;">No</th>
                  <th style="text-align: center; color: white;">Tanggal</th>
                  <th style="text-align: center; color: white;">No. RM</th>
                  <th style="text-align: center; color: white;">Nama Pasien</th>
                  <th style="text-align: center; color: white;">Usia</th>
                  <th style="text-align: center; color: white;">Jenis Kelamin</th>
                  <th style="text-align: center; color: white;">Status Gizi</th>
                  <th style="text-align: center; color: white;">Aksi</th>
                </tr>
              </thead>
              <tbody>
               <?php
               // Fungsi untuk mengkonversi kategori umur ke batas usia maksimal
               function get_max_age_from_category($kategori) {
                   $batas_umur = [
                       'bayi' => 1,           // Bayi: 0 - <1 tahun
                       'balita' => 5,         // Balita: 1 - <5 tahun
                       'anak' => 12,          // Anak-anak: 5 - <12 tahun
                       'remaja' => 18,        // Remaja: 12 - <18 tahun
                       'dewasa_muda' => 30,   // Dewasa Muda: 18 - <30 tahun
                       'dewasa' => 45,        // Dewasa: 30 - <45 tahun
                       'lansia_awal' => 60,   // Lansia Awal: 45 - <60 tahun
                       'lansia_akhir' => 200   // Lansia Akhir: 60+ tahun
                   ];
                   return $batas_umur[$kategori] ?? 5;
               }

               // Fungsi untuk mendapatkan label kategori umur
               function get_kategori_umur_label($kategori) {
                   $labels = [
                       'bayi' => 'Bayi (0 - <1 tahun)',
                       'balita' => 'Balita (1 - <5 tahun)',
                       'anak' => 'Anak-anak (5 - <12 tahun)',
                       'remaja' => 'Remaja (12 - <18 tahun)',
                       'dewasa_muda' => 'Dewasa Muda (18 - <30 tahun)',
                       'dewasa' => 'Dewasa (30 - <45 tahun)',
                       'lansia_awal' => 'Lansia Awal (45 - <60 tahun)',
                       'lansia_akhir' => 'Lansia Akhir (≥60 tahun)'
                   ];
                   return $labels[$kategori] ?? 'Balita (1 - <5 tahun)';
               }

               // Set default values
               $tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
               $bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
               $kategori_umur = isset($_GET['kategori_umur']) ? $_GET['kategori_umur'] : 'balita';
               $usia_max = get_max_age_from_category($kategori_umur);

               // Query sesuai dengan struktur database server dengan kategori umur
               $query = mysqli_query($config, "
                   SELECT
                       psad.tanggal,
                       psad.no_rawat,
                       p.no_rkm_medis,
                       p.nm_pasien,
                       p.jk AS jenis_kelamin,
                       p.tgl_lahir,
                       TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) AS umur_tahun,
                       MOD(TIMESTAMPDIFF(MONTH, p.tgl_lahir, psad.tanggal), 12) AS umur_bulan,
                       psad.keluhan,
                       psad.riwayat_penyakit,
                       psad.fisik_klinis,
                       psad.biokimia,
                       psad.riwayat_makan
                   FROM pilot_skrining_awal_diet AS psad
                   JOIN reg_periksa AS rp ON psad.no_rawat = rp.no_rawat
                   JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
                   WHERE TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) < $usia_max
                     AND YEAR(psad.tanggal) = $tahun
                     AND MONTH(psad.tanggal) = $bulan
                   ORDER BY psad.tanggal ASC
               ");

               $no = 1;
               while ($data = mysqli_fetch_array($query)) {
                   // Hitung umur dalam tahun dan bulan
                   $umur_tahun = $data['umur_tahun'];
                   $umur_bulan = $data['umur_bulan'];
                   $usia_display = $umur_tahun . ' thn ' . $umur_bulan . ' bln';
               ?>
               <tr>
                 <td><?php echo $no++; ?></td>
                 <td><?php echo date('d/m/Y', strtotime($data['tanggal'])); ?></td>
                 <td><?php echo htmlspecialchars($data['no_rkm_medis']); ?></td>
                 <td><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                 <td><?php echo $usia_display; ?></td>
                 <td>
                   <span class="badge badge-<?php echo $data['jenis_kelamin'] == 'L' ? 'primary' : 'info'; ?>">
                     <?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                   </span>
                 </td>
                 <td>
                   <?php if (!empty($data['keluhan']) || !empty($data['fisik_klinis']) || !empty($data['biokimia'])): ?>
                   <span class="badge badge-warning">Perlu Perhatian</span>
                   <?php else: ?>
                   <span class="badge badge-success">Normal</span>
                   <?php endif; ?>
                 </td>
                 <td>
                   <button class="btn btn-sm btn-info" onclick="showDetail('<?php echo $data['no_rawat']; ?>')">Detail</button>
                 </td>
               </tr>
               <?php } ?>
             </tbody>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
    </div>
  </div>
</section>

<!-- Modal Detail Skrining -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">Detail Skrining Gizi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="detailContent">
        <!-- Detail content will be loaded here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
function showDetail(no_rawat) {
    // Get periode filter
    const urlParams = new URLSearchParams(window.location.search);
    const tahun = urlParams.get('tahun') || '<?php echo date('Y'); ?>';
    const bulan = urlParams.get('bulan') || '<?php echo date('m'); ?>';

    $.ajax({
        url: 'page/skrining_gizi/get_skrining_detail.php',
        method: 'GET',
        data: {
            no_rawat: no_rawat,
            tahun: tahun,
            bulan: bulan
        },
        success: function(response) {
            $('#detailContent').html(response);
            $('#detailModal').modal('show');
        },
        error: function() {
            alert('Gagal mengambil data detail skrining');
        }
    });
}

function exportExcel() {
    // Get form values
    const tahun = document.getElementById('tahun').value;
    const bulan = document.getElementById('bulan').value;
    const kategori_umur = document.getElementById('kategori_umur').value;

    // Construct export URL
    const exportUrl = `main_app.php?page=export_excel_skrining_gizi&tahun=${tahun}&bulan=${bulan}&kategori_umur=${kategori_umur}`;

    // Open in new window/tab
    window.open(exportUrl, '_blank');
}
</script>
