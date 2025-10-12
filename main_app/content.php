<?php 
$page = isset($_GET['page']) ? $_GET['page'] : '';
//Dashboard
if ($page == "beranda"){
  require_once("page/beranda.php");
}
//User Management
if ($page == "user_data" || $page == "user_management"){
  require_once("page/user_management/user_data.php");
}
if ($page == "user_create"){
  require_once("page/user_management/create_user.php");
}
if ($page == "user_edit"){
  require_once("page/user_management/update_user.php");
}
if ($page == "user_delete"){
  require_once("page/user_management/delete_user.php");
}

// Skrining Gizi Pages - Hanya untuk role Gizi
if ($page == "skrining_gizi_dashboard"){
  require_once("page/skrining_gizi/dashboard.php");
}
if ($page == "skrining_gizi_data"){
  require_once("page/skrining_gizi/data_skrining.php");
}
if ($page == "skrining_gizi_detail"){
  require_once("page/skrining_gizi/detail_skrining.php");
}
if ($page == "get_skrining_detail"){
  require_once("page/skrining_gizi/get_skrining_detail.php");
}
if ($page == "get_skrining_hasil_detail"){
  require_once("page/skrining_gizi/get_skrining_hasil_detail.php");
}
// Export Excel
if ($page == "export_excel_skrining_gizi"){
  require_once("page/skrining_gizi/export_excel.php");
}


// Data Asuhan Gizi
if ($page == "data_asuhan_gizi"){
   require_once("page/asuhan_gizi/data_asuhan_gizi.php");
}
if ($page == "get_asuhan_gizi_detail"){
   require_once("page/asuhan_gizi/get_asuhan_gizi_detail.php");
}
// Export Excel
if ($page == "export_excel_data_asuhan_gizi"){
   require_once("page/asuhan_gizi/export_excel.php");
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
// Rekapitulasi IGD (RL 3.3)
if ($page == "RL_rkp_igd"){
   require_once("page/RL_3.3/RL_rkp_igd.php");
}
// Export Excel
if ($page == "export_excel_rl_3_3"){
   require_once("page/RL_3.3/export_excel.php");
}

// Rekap kunjungan
else if ($page == "rekap_pasien_poli"){
  require_once("page/rekap-kunjungan-pasien/rawat-jalan/rekap_pasien_poli.php");
}
else if ($page == "rekap_pasien_ranap"){
  require_once("page/rekap-kunjungan-pasien/rawat-inap/rekap_pasien_ranap.php");
}
  else if ($page == "rekap_px_usia_ranap"){
    require_once("page/rekap-kunjungan-pasien/rawat-inap/rekap_px_usia_ranap.php");
  }
else if ($page == "rekap_px_usia_ralan"){
  require_once("page/rekap-kunjungan-pasien/rawat-jalan/rekap_px_usia_ralan.php");
}
else if ($page == "rekap_pasien_ranap_kabupaten"){
  require_once("page/rekap-kunjungan-pasien/rawat-inap/rekap_pasien_ranap_kabupaten.php");
}
// Picare
else if ($page == "daftar"){
  require_once("page/pi-care/pi-care_daftar.php");
}
else if ($page == 'lap_pi-care_daftar'){
  require_once("page/pi-care/lap_pi-care_daftar.php");
}
else if ($page == "batal"){
  require_once("page/pi-care/pi-care_batal.php");
}
else if ($page == "alasan"){
  require_once("page/pi-care/pi-care_alasan.php");
}


// 10 Besar Penyakit
else if ($page == "10_besar_penyakit_ralan"){
  require_once("page/rkp_besar_penyakit/10_besar_penyakit_ralan.php");
}
else if ($page == "10_besar_penyakit_ranap"){
  require_once("page/rkp_besar_penyakit/10_besar_penyakit_ranap.php");
}
?>