<?php
require '../assets/vendor/autoload.php';

// Ambil input bulan dan tahun dari POST, default bulan dan tahun sekarang
$bulan = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// Validasi input
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');

// Query rekap pengunjung
$sql = "
SELECT 
		'Pengunjung Baru' AS jenis_pengunjung,
		COUNT(DISTINCT r.no_rkm_medis) AS jumlah
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE MONTH(r.tgl_registrasi) = ?
	AND YEAR(r.tgl_registrasi) = ?
	AND MONTH(p.tgl_daftar) = ?
	AND YEAR(p.tgl_daftar) = ?
	AND p.nm_pasien NOT LIKE '%TEST%'
	AND p.nm_pasien IS NOT NULL
	AND p.nm_pasien <> ''
UNION ALL

SELECT 
		'Pengunjung Lama' AS jenis_pengunjung,
		COUNT(DISTINCT r.no_rkm_medis) AS jumlah
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE MONTH(r.tgl_registrasi) = ?
	AND YEAR(r.tgl_registrasi) = ?
	AND p.tgl_daftar < DATE_FORMAT(CONCAT(?, '-', ?, '-01'), '%Y-%m-01')
	AND p.nm_pasien NOT LIKE '%TEST%'
	AND p.nm_pasien IS NOT NULL
	AND p.nm_pasien <> ''
UNION ALL

SELECT 
		'TOTAL',
		COUNT(DISTINCT r.no_rkm_medis) AS jumlah
FROM reg_periksa r
JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
WHERE MONTH(r.tgl_registrasi) = ?
	AND YEAR(r.tgl_registrasi) = ?
	AND p.nm_pasien NOT LIKE '%TEST%'
	AND p.nm_pasien IS NOT NULL
	AND p.nm_pasien <> '';
";

// Eksekusi query
$stmt = $config->prepare($sql);
$stmt->bind_param(
		'iiiiiiiiii',
		$bulan, $tahun, $bulan, $tahun, // Pengunjung Baru
		$bulan, $tahun, $tahun, $bulan, // Pengunjung Lama
		$bulan, $tahun // TOTAL
);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
		$data[] = $row;
}
$stmt->close();

// List bulan
$bulanList = [
		'01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June',
		'07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'
];

?>
<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>REKAPITULASI PENGUNJUNG RL 3.4</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="dashboard_staff.php?unit=beranda">Home</a></li>
					<li class="breadcrumb-item active">Rekapitulasi Pengunjung RL 3.4</li>
				</ol>
			</div>
		</div>
	</div>
</section>
<section class="content">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<div class="card-tools" style="float: left; text-align: left;">
							<form method="post" class="mb-3" style="display:flex;align-items:center;gap:10px;">
								<label for="bulan" style="margin-bottom:0;">Bulan:</label>
								<select name="bulan" id="bulan" class="form-control" style="width:auto;display:inline-block;">
									<?php
									foreach($bulanList as $val=>$label) {
										$selected = ($bulan == $val || $bulan == ltrim($val, '0')) ? 'selected' : '';
										echo '<option value="'.$val.'" '.$selected.'>'.$label.'</option>';
									}
									?>
								</select>
								<label for="tahun" style="margin-bottom:0;">Tahun:</label>
								<input type="number" name="tahun" id="tahun" class="form-control" value="<?php echo $tahun; ?>" style="width:90px;display:inline-block;">
								<button type="submit" class="btn btn-primary">Tampilkan Data</button>
							</form>
							<a href="main_app.php?page=export_excel_rl_3_4&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-success">Export Excel</a>
							
						</div>
					</div>
					<div class="card-body">
						<div style="overflow-x: auto; white-space: nowrap;">
							<table class="table table-bordered table-striped text-center align-middle" style="width: 100%; table-layout: auto;">
								<thead style="background:#81a1c1;color:#fff;">
									<tr>
										<th>No</th>
										<th>Jenis Pengunjung</th>
										<th>Jumlah</th>
									</tr>
								</thead>
								<tbody>
									<?php
									if (empty($data)) {
										echo '<tr><td colspan="3" class="text-center">Tidak ada data untuk periode yang dipilih.</td></tr>';
									} else {
										$no = 1;
										foreach ($data as $row) {
											echo '<tr>';
											echo '<td>'.$no++.'</td>';
											echo '<td>'.htmlspecialchars($row['jenis_pengunjung']).'</td>';
											echo '<td>'.htmlspecialchars($row['jumlah']).'</td>';
											echo '</tr>';
										}
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
