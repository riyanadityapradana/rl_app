<?php 
$page = isset($_GET['page']) ? $_GET['page'] : '';
//Dashboard
if ($page == "beranda"){
  require_once("page/beranda.php");
}
// Rekapitulasi Pengunjung (RL 3.2)
if ($page == "RL_rkp_kegiatan_pelayanan_ranap"){
  require_once("page/RL-RANAP/RL_rkp_kegiatan_pelayanan_ranap.php");
}
// Export Excel
if ($page == "export_excel"){
  require_once("page/RL-RANAP/export_excel.php");
}
// Rekapitulasi Pengunjung (RL 3.4)
if ($page == "RL_rkp_pengunjung"){
  require_once("page/RL_3.4/RL_rkp_pengunjung.php");
}
// Export Excel
if ($page == "export_excel_rl_3_4"){
  require_once("page/RL_3.4/export_excel.php");
}
// Rekapitulasi Kunjungan (RL 3.5)
if ($page == "RL_rkp_kunjungan"){
  require_once("page/RL_3.5/RL_rkp_kunjungan.php");
}
// Export Excel
if ($page == "export_excel_rl_3_5"){
  require_once("page/RL_3.5/export_excel.php");
}
?>