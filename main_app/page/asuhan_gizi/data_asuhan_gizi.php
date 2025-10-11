<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Data Asuhan Gizi Anak (0-5 Tahun) - <?php
          $tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
          $bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
          echo "Periode " . $bulan . "/" . $tahun;
        ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item"><a href="main_app.php?page=asuhan_gizi_dashboard">Dashboard Gizi</a></li>
          <li class="breadcrumb-item active">Data Asuhan Gizi</li>
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
            <h3 class="card-title">Filter Periode Asuhan Gizi</h3>
          </div>
          <div class="card-body">
            <form method="GET" action="">
              <input type="hidden" name="page" value="data_asuhan_gizi">
              <div class="row">
                <div class="col-md-4">
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
                <div class="col-md-4">
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
                <div class="col-md-4">
                  <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="main_app.php?page=data_asuhan_gizi" class="btn btn-secondary">Reset</a>
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
                  <th style="text-align: center; color: white;">Kategori</th>
                  <th style="text-align: center; color: white;">BB (kg)</th>
                  <th style="text-align: center; color: white;">TB (cm)</th>
                  <th style="text-align: center; color: white;">IMT</th>
                  <th style="text-align: center; color: white;">Status Gizi</th>
                  <th style="text-align: center; color: white;">Aksi</th>
                </tr>
              </thead>
              <tbody>
               <?php
               // Set default values
               $tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
               $bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');

               // Query sesuai dengan struktur database untuk asuhan gizi anak 0-5 tahun
               $query = mysqli_query($config, "
                   SELECT
                       ag.tanggal,
                       ag.no_rawat,
                       p.no_rkm_medis,
                       p.nm_pasien,
                       p.jk AS jenis_kelamin,
                       p.tgl_lahir,
                       TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) AS umur_tahun,
                       MOD(TIMESTAMPDIFF(MONTH, p.tgl_lahir, ag.tanggal), 12) AS umur_bulan,

                       -- Kategori umur (anak, remaja, dewasa, lansia)
                       CASE
                           WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) < 12 THEN 'Anak'
                           WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) BETWEEN 12 AND 17 THEN 'Remaja'
                           WHEN TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) BETWEEN 18 AND 59 THEN 'Dewasa'
                           ELSE 'Lansia'
                       END AS kategori_umur,

                       -- Kolom penting dari asuhan gizi
                       ag.antropometri_bb,
                       ag.antropometri_tb,
                       ag.antropometri_imt,
                       ag.antropometri_lla,
                       ag.fisik_klinis,
                       ag.biokimia,
                       ag.diagnosis,
                       ag.intervensi_gizi,
                       ag.monitoring_evaluasi,
                       ag.pola_makan
                   FROM asuhan_gizi AS ag
                   JOIN reg_periksa AS rp ON ag.no_rawat = rp.no_rawat
                   JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
                   WHERE
                       -- Filter anak usia ≤ 5 tahun
                       TIMESTAMPDIFF(YEAR, p.tgl_lahir, ag.tanggal) <= 5
                       -- Filter berdasarkan bulan dan tahun yang diatur
                       AND YEAR(ag.tanggal) = $tahun
                       AND MONTH(ag.tanggal) = $bulan
                   ORDER BY ag.tanggal ASC
               ");

               $no = 1;

               // Fungsi untuk mengkonversi nilai numerik dari database
               function safe_number_format($value, $decimals = 1) {
                   if (!$value || $value === '' || $value === null) {
                       return false;
                   }

                   // Konversi string dengan koma menjadi float
                   $clean_value = str_replace(',', '.', trim($value));
                   $float_value = floatval($clean_value);

                   return number_format($float_value, $decimals);
               }

               while ($data = mysqli_fetch_array($query)) {
                   // Hitung umur dalam tahun dan bulan
                   $umur_tahun = $data['umur_tahun'];
                   $umur_bulan = $data['umur_bulan'];
                   $usia_display = $umur_tahun . ' thn ' . $umur_bulan . ' bln';

                   // Status Gizi berdasarkan IMT dengan konversi yang aman
                   $imt_raw = $data['antropometri_imt'];
                   $imt_clean = str_replace(',', '.', trim($imt_raw));
                   $imt = floatval($imt_clean);

                   $status_gizi = 'Normal';
                   $badge_class = 'success';

                   if ($imt > 0) {
                       if ($imt < 18.5) {
                           $status_gizi = 'Underweight';
                           $badge_class = 'warning';
                       } elseif ($imt >= 18.5 && $imt < 25) {
                           $status_gizi = 'Normal';
                           $badge_class = 'success';
                       } elseif ($imt >= 25 && $imt < 30) {
                           $status_gizi = 'Overweight';
                           $badge_class = 'danger';
                       } else {
                           $status_gizi = 'Obesitas';
                           $badge_class = 'danger';
                       }
                   }
               ?>
               <tr>
                 <td><?php echo $no++; ?></td>
                 <td><?php echo date('d/m/Y', strtotime($data['tanggal'])); ?></td>
                 <td><?php echo htmlspecialchars($data['no_rkm_medis']); ?></td>
                 <td><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                 <td><?php echo $usia_display; ?></td>
                 <td>
                   <span class="badge badge-<?php echo $data['kategori_umur'] == 'Anak' ? 'primary' : 'info'; ?>">
                     <?php echo $data['kategori_umur']; ?>
                   </span>
                 </td>
                 <td><?php echo safe_number_format($data['antropometri_bb'], 1) ? safe_number_format($data['antropometri_bb'], 1) . ' kg' : '-'; ?></td>
                 <td><?php echo safe_number_format($data['antropometri_tb'], 1) ? safe_number_format($data['antropometri_tb'], 1) . ' cm' : '-'; ?></td>
                 <td><?php echo safe_number_format($data['antropometri_imt'], 1) ? safe_number_format($data['antropometri_imt'], 1) : '-'; ?></td>
                 <td>
                   <span class="badge badge-<?php echo $badge_class; ?>"><?php echo $status_gizi; ?></span>
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

<!-- Modal Detail Asuhan Gizi -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">Detail Asuhan Gizi</h5>
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
        url: 'page/asuhan_gizi/get_asuhan_gizi_detail.php',
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
            alert('Gagal mengambil data detail asuhan gizi');
        }
    });
}

function exportExcel() {
    // Get form values
    const tahun = document.getElementById('tahun').value;
    const bulan = document.getElementById('bulan').value;

    // Construct export URL
    const exportUrl = `main_app.php?page=export_excel_data_asuhan_gizi&tahun=${tahun}&bulan=${bulan}`;

    // Open in new window/tab
    window.open(exportUrl, '_blank');
}
</script>