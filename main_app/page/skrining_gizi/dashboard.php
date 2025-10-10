<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Dashboard Skrining Gizi</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
          <li class="breadcrumb-item active">Dashboard Gizi</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?php
              $tahun = date('Y');
              $bulan = date('m');
              $usia_max = 5; // Default untuk balita
              $query = mysqli_query($config, "
                SELECT COUNT(*) as total FROM pilot_skrining_awal_diet psad
                JOIN reg_periksa rp ON psad.no_rawat = rp.no_rawat
                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                WHERE TIMESTAMPDIFF(YEAR, p.tgl_lahir, psad.tanggal) < $usia_max
                AND YEAR(psad.tanggal) = $tahun AND MONTH(psad.tanggal) = $bulan
              ");
              $data = mysqli_fetch_assoc($query);
              echo $data['total'] ?? 0;
            ?></h3>
            <p>Skrining Bulan Ini</p>
          </div>
          <div class="icon">
            <i class="ion ion-clipboard"></i>
          </div>
          <a href="main_app.php?page=skrining_gizi_data" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
      <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
          <div class="inner">
            <h3><?php
              $tahun = date('Y');
              $bulan = date('m');
              $query = mysqli_query($config, "
                SELECT COUNT(*) as total FROM skrining_gizi
                WHERE status = 'Normal' AND YEAR(created_at) = $tahun AND MONTH(created_at) = $bulan
              ");
              $data = mysqli_fetch_assoc($query);
              echo $data['total'] ?? 0;
            ?></h3>
            <p>Status Gizi Normal</p>
          </div>
          <div class="icon">
            <i class="ion ion-checkmark-circled"></i>
          </div>
          <a href="main_app.php?page=skrining_gizi_laporan" class="small-box-footer">Laporan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
      <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-warning">
          <div class="inner">
            <h3><?php
              $tahun = date('Y');
              $bulan = date('m');
              $query = mysqli_query($config, "
                SELECT COUNT(*) as total FROM skrining_gizi
                WHERE (status = 'Risiko' OR status = 'Malnutrisi')
                AND YEAR(created_at) = $tahun AND MONTH(created_at) = $bulan
              ");
              $data = mysqli_fetch_assoc($query);
              echo $data['total'] ?? 0;
            ?></h3>
            <p>Perlu Intervensi</p>
          </div>
          <div class="icon">
            <i class="ion ion-alert-circled"></i>
          </div>
          <a href="main_app.php?page=skrining_gizi_data" class="small-box-footer">Lihat Data <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
      <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
          <div class="inner">
            <h3><?php
              $query = mysqli_query($mysqli, "SELECT COUNT(*) as total FROM skrining_gizi");
              $data = mysqli_fetch_assoc($query);
              echo $data['total'] ?? 0;
            ?></h3>
            <p>Total Skrining</p>
          </div>
          <div class="icon">
            <i class="ion ion-stats-bars"></i>
          </div>
          <a href="main_app.php?page=export_excel_fixed&tahun=<?php echo date('Y'); ?>&bulan=<?php echo date('m'); ?>&kategori_umur=balita" class="small-box-footer">Export Excel <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
    </div>
    <!-- /.row -->

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Aktivitas Skrining Terbaru</h3>
            <div class="card-tools">
              <a href="main_app.php?page=export_excel_universal&tahun=<?php echo date('Y'); ?>&bulan=<?php echo date('m'); ?>&kategori_umur=balita" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export Excel
              </a>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Nama Pasien</th>
                  <th>Status Gizi</th>
                  <th>Usia</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $query = mysqli_query($config, "
                  SELECT
                    created_at as tanggal,
                    no_rawat,
                    no_rm,
                    nama_pasien,
                    jenis_kelamin,
                    usia,
                    status,
                    skor_total
                  FROM skrining_gizi
                  ORDER BY created_at DESC
                  LIMIT 10
                ");
                $no = 1;
                while ($data = mysqli_fetch_array($query)) {
                  // Data sudah tersedia dari tabel skrining_gizi
                  $usia_display = $data['usia'] . ' tahun';
                  $status = $data['status'];
                ?>
                <tr>
                  <td><?php echo $no++; ?></td>
                  <td><?php echo date('d/m/Y', strtotime($data['tanggal'])); ?></td>
                  <td><?php echo htmlspecialchars($data['nama_pasien']); ?></td>
                  <td>
                    <span class="badge badge-<?php
                      echo $status == 'Normal' ? 'success' : ($status == 'Malnutrisi' ? 'danger' : 'warning');
                    ?>">
                      <?php echo $status; ?>
                    </span>
                  </td>
                  <td><?php echo $usia_display; ?></td>
                  <td>
                    <button class="btn btn-sm btn-info" onclick="showDetailSkrining('<?php echo $data['no_rawat']; ?>')">Detail</button>
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
    <!-- /.row -->
  </div><!-- /.container-fluid -->
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
function showDetailSkrining(no_rawat) {
    $.ajax({
        url: 'page/skrining_gizi/get_skrining_hasil_detail.php',
        method: 'GET',
        data: {
            no_rawat: no_rawat
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
</script>