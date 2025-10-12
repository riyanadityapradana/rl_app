<?php
session_start();
ob_start();
require_once("../config/koneksi.php");
if (!isset($_SESSION['user_id'])) {
    header('Location: ../main_login/login.php?error=Akses ditolak!');
    exit;
}
// Ambil data role user dari session
$user_role = $_SESSION['role'] ?? '';
if (isset($_GET['page'])){ $page = $_GET['page']; }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Staff | IT-RSPI</title>
    <link rel="icon" href="../assets/img/icon.png">
    <!-- Google Font: Source Sans Pro -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
	<!-- Ionicons -->
	<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="../assets/plugins/toastr/toastr.css">
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">

</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white" style="background:#ffc107">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
            <li class="nav-item d-none d-sm-inline-block"><a href="#" class="nav-link">Home</a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#"><i class="far fa-user"></i></a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <a href="dashboard_staff.php?unit=user" class="dropdown-item"><i class="fas fa-user mr-2"></i> Data User</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                    </div>
            </li>
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background:rgb(217, 221, 224, 1)">
        <a href="dashboard_staff.php?unit=beranda" class="brand-link" style="background:rgb(0, 123, 255, 1)">
            <img src="../assets/img/icon.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">IT - RSPI</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item menu-open">
                        <a href="main_app.php?page=beranda" class="nav-link active">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <!-- Menu Rekapitulasi Data - Untuk Admin, RM, dan IT -->
                    <?php if (in_array($user_role, ['Admin', 'RM', 'IT'])): ?>
                    <li class="nav-header" style="color: black;">REKAPITULASI DATA</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar" style="color: black;"></i>
                            <p style="color: black;">Kumpulan RL 3<i class="right fas fa-angle-left" style="color: black;"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="main_app.php?page=RL_rkp_kegiatan_pelayanan_ranap" class="nav-link">
                                    <i class="nav-icon fas fa-procedures" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">RL 3.2 Kegiatan Pelayanan Ranap</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="main_app.php?page=RL_rkp_igd" class="nav-link">
                                    <i class="nav-icon fas fa-procedures" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">RL 3.3 Kegiatan Pelayanan Rawat Darurat</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="main_app.php?page=RL_rkp_pengunjung" class="nav-link">
                                    <i class="nav-icon fas fa-file" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">RL 3.4 Rekapitulasi Pengunjung</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="main_app.php?page=RL_rkp_kunjungan" class="nav-link">
                                    <i class="nav-icon fas fa-file" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">RL 3.5 Rekapitulasi Kunjungan</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Pi-Care - Untuk Admin, IT, dan Pemasaran -->
                    <?php if (in_array($user_role, ['Admin', 'IT', 'Pemasaran'])): ?>
                    <li class="nav-header" style="color: black;">PI-CARE</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fab fa-whatsapp" style="color: green;"></i><p style="color: black;">Grafik Pi-Care<i class="fas fa-angle-left right" style="color: black;"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="main_app.php?page=daftar" class="nav-link">
                                    <i class="nav-icon fas fa-caret-right" style="color: black;"></i><p style="color: black;">PENDAFTARAN</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="main_app.php?page=batal" class="nav-link">
                                    <i class="nav-icon fas fa-caret-right" style="color: black;"></i><p style="color: black;">PEMBATALAN</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="main_app.php?page=alasan" class="nav-link">
                                    <i class="nav-icon fas fa-caret-right" style="color: black;"></i><p style="color: black;">ALASAN PEMBATALAN</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-header" style="color: black; font-size: 13px;">REKAPITULASI KUNJUNGAN PASIEN </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar" style="color: black;"></i>
                            <p style="color: black;">Rekap Kunjungan<i class="right fas fa-angle-left" style="color: black;"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="main_app.php?page=rekap_pasien_poli" class="nav-link">
                                    <i class="nav-icon fas fa-user-injured" style="color: black;"></i>
                                    <p style="font-size: 14px; color: black;">Rekap Pasien Rawat Jalan</p>
                                </a>
                                <a href="main_app.php?page=rekap_pasien_ranap" class="nav-link">
                                    <i class="nav-icon fas fa-procedures" style="color: black;"></i>
                                    <p style="font-size: 14px; color: black;">Rekap Pasien Rawat Inap</p>
                                </a>
                                <a href="main_app.php?page=rekap_px_usia_ranap" class="nav-link">
                                    <i class="nav-icon fas fa-user-injured" style="color: black;"></i>
                                    <p style="font-size: 14px; color: black;">Rkp px usia rawat inap</p>
                                </a>
                                <a href="main_app.php?page=rekap_px_usia_ralan" class="nav-link">
                                    <i class="nav-icon fas fa-user-injured" style="color: black;"></i>
                                    <p style="font-size: 14px; color: black;">Rkp px usia rawat jalan</p>
                                </a>
                                <a href="main_app.php?page=rekap_pasien_ranap_kabupaten" class="nav-link">
                                    <i class="nav-icon fas fa-user-injured" style="color: black;"></i>
                                    <p style="font-size: 14px; color: black;">Rkp px ranap per kabupaten</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-header" style="color: black; font-size: 13px;">STATISTIK 10 BESAR PENYAKIT</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar" style="color: black;"></i>
                            <p style="color: black; font-size: 13px;">Rekap Penyakit Terbanyak<i class="right fas fa-angle-left" style="color: black;"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="main_app.php?page=10_besar_penyakit_ralan" class="nav-link">
                                    <i class="nav-icon fas fa-user-injured" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">10 Besar Penyakit Rawat Jalan</p>
                                </a>
                                <a href="main_app.php?page=10_besar_penyakit_ranap" class="nav-link">
                                    <i class="nav-icon fas fa-procedures" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">10 Besar Penyakit Rawat Inap</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Setting User - Hanya untuk Admin -->
                    <?php if ($user_role == 'Admin' || $user_role == 'IT'): ?>
                    <li class="nav-header" style="color: black;">SETTING USER</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-user-cog" style="color: black;"></i>
                            <p style="color: black;">Management User<i class="right fas fa-angle-left" style="color: black;"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="main_app.php?page=user_data" class="nav-link">
                                    <i class="nav-icon fas fa-users" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">Data User</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Menu Skrining Gizi - Hanya untuk role Gizi -->
                    <?php if ($user_role == 'Gizi'): ?>
                    <li class="nav-header" style="color: black;">SKRINING GIZI</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-heartbeat" style="color: black;"></i>
                            <p style="color: black;">Menu Gizi<i class="right fas fa-angle-left" style="color: black;"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <!-- <li class="nav-item">
                                <a href="main_app.php?page=skrining_gizi_dashboard" class="nav-link">
                                    <i class="nav-icon fas fa-plus-circle" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">Input Data Skrining</p>
                                </a>
                            </li> -->
                            <li class="nav-item">
                                <a href="main_app.php?page=skrining_gizi_data" class="nav-link">
                                    <i class="nav-icon fas fa-file-medical" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">Data Skrining Awal</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="main_app.php?page=data_asuhan_gizi" class="nav-link">
                                    <i class="nav-icon fas fa-file-medical" style="color: black;"></i>
                                    <p style="font-size: 12px; color: black;">Data Asuhan Gizi</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
              <?php require_once ("content.php");?>
            </div>
        </div>
    </div>
</div>
<footer class="main-footer" style="position:fixed;bottom:0;width:100%;background:#d9dde0;color:#00070c;z-index:9999;padding:0;">
  <div style="overflow:hidden;white-space:nowrap;">
    <marquee behavior="scroll" direction="left" scrollamount="6" style="font-size:16px;padding:8px 0;">
      &copy; <?= date('Y') ?> IT-RSPI | Sistem Informasi Teknologi RSPI. Dikembangkan dengan ❤️ oleh Tim IT-RSPI. Seluruh hak cipta dilindungi undang-undang.
    </marquee>
  </div>
</footer>
<!-- Toastr Success Message -->
<?php if (isset($_GET['msg'])): ?>
  <script>
    toastr.success("<?= addslashes($_GET['msg']) ?>", "Sukses", {positionClass: "toast-top-right"});
  </script>
<?php endif; ?>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="../assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="../assets/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<!-- InputMask -->
<script src="../assets/plugins/moment/moment.min.js"></script>
<script src="../assets/plugins/inputmask/jquery.inputmask.min.js"></script>

<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script>
    $(function() {
    $('.select2').select2();
    $('.select2bs4').select2({ theme: 'bootstrap4' });
    });
</script>
<script src="../assets/plugins/toastr/toastr.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    // Inisialisasi DataTable dengan pengaturan custom
    $('#example1').DataTable({
        lengthChange: true,
        paging: true,
        pagingType: 'numbers',
        scrollCollapse: true,
        ordering: true,
        info: true,
        language: {
            decimal: '',
            emptyTable: 'Tidak ada data yang tersedia pada tabel ini',
            processing: 'Sedang memproses...',
            lengthMenu: 'Tampilkan _MENU_ entri',
            zeroRecords: 'Tidak ditemukan data yang sesuai',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 entri',
            infoFiltered: '(disaring dari _MAX_ entri keseluruhan)',
            infoPostFix: '',
            search: '',
            searchPlaceholder: 'Cari Data..',
            url: '',
            paginate: {
                first: 'Pertama',
                previous: 'Sebelumnya',
                next: 'Selanjutnya',
                last: 'Terakhir'
            }
        }
    });
    // Toastr notification
    <?php if(isset($_GET['msg'])): ?>
        toastr.options = {"positionClass": "toast-top-right", "timeOut": "3000"};
        toastr.success("<?= htmlspecialchars($_GET['msg']) ?>");
    <?php endif; ?>
    <?php if(isset($_GET['err'])): ?>
        toastr.options = {"positionClass": "toast-top-right", "timeOut": "3000"};
        toastr.error("<?= htmlspecialchars($_GET['err']) ?>");
    <?php endif; ?>
    });
</script>
</body>
</html> 