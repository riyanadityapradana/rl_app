<?php
require '../assets/vendor/autoload.php';

// --- AMBIL INPUT ---
$bulan = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');
if ((int)$bulan < 1 || (int)$bulan > 12) $bulan = date('m');
if ((int)$tahun < 2000 || (int)$tahun > 2100) $tahun = date('Y');

// --- QUERY REKAP ---
$sql = "
SELECT 
	CASE 
		WHEN pl.nm_poli LIKE '%Kandungan%' THEN 'Poli Kandungan'
		WHEN pl.nm_poli LIKE '%Anak%' THEN 'Poli Anak'
		WHEN pl.nm_poli LIKE '%Bedah%' THEN 'Poli Bedah'
		WHEN pl.nm_poli LIKE '%Gigi%' THEN 'Poli Gigi'
		WHEN pl.nm_poli LIKE '%Mata%' THEN 'Poli Mata'
		WHEN pl.nm_poli LIKE '%Jantung%' THEN 'Poli Jantung'
		WHEN pl.nm_poli LIKE '%Kejiwaan%' THEN 'Poli Kejiwaan'
		WHEN pl.nm_poli LIKE '%P.Dalam%' THEN 'Poli Penyakit Dalam'
		WHEN pl.nm_poli LIKE '%Syaraf%' OR pl.nm_poli LIKE '%Neurolog%' THEN 'Poli Syaraf'
		WHEN pl.nm_poli LIKE '%Jiwa%' OR pl.nm_poli LIKE '%Kejwa%' THEN 'Poli Jiwa'
		WHEN pl.nm_poli LIKE '%Kulit%' OR pl.nm_poli LIKE '%Kelamin%' THEN 'Poli Kulit & Kelamin'
		ELSE pl.nm_poli
	END AS 'Jenis Kegiatan',

	SUM(CASE WHEN ps.jk='L' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Dalam Kab/Kota Laki-laki',
	SUM(CASE WHEN ps.jk='P' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Dalam Kab/Kota Perempuan',
	SUM(CASE WHEN ps.jk='L' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Luar Kab/Kota Laki-laki',
	SUM(CASE WHEN ps.jk='P' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END) AS 'Luar Kab/Kota Perempuan',
	COUNT(r.no_rawat) AS 'Total Kunjungan'

FROM reg_periksa r
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
LEFT JOIN kabupaten k ON ps.kd_kab = k.kd_kab
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli

WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien IS NOT NULL
  AND ps.nm_pasien <> ''
  AND pl.nm_poli NOT REGEXP 'TEST|PONEK|OBGYN|Vaksin|Gizi|HEMODIALISA|Laboratorium|Rawat Inap|IGD|UGD'

GROUP BY 
	CASE 
		WHEN pl.nm_poli LIKE '%Kandungan%' THEN 'Poli Kandungan'
		WHEN pl.nm_poli LIKE '%Anak%' THEN 'Poli Anak'
		WHEN pl.nm_poli LIKE '%Bedah%' THEN 'Poli Bedah'
		WHEN pl.nm_poli LIKE '%Gigi%' THEN 'Poli Gigi'
		WHEN pl.nm_poli LIKE '%Mata%' THEN 'Poli Mata'
		WHEN pl.nm_poli LIKE '%Jantung%' THEN 'Poli Jantung'
		WHEN pl.nm_poli LIKE '%Kejiwaan%' THEN 'Poli Kejiwaan'
		WHEN pl.nm_poli LIKE '%P.Dalam%' THEN 'Poli Penyakit Dalam'
		WHEN pl.nm_poli LIKE '%Syaraf%' OR pl.nm_poli LIKE '%Neurolog%' THEN 'Poli Syaraf'
		WHEN pl.nm_poli LIKE '%Jiwa%' OR pl.nm_poli LIKE '%Kejwa%' THEN 'Poli Jiwa'
		WHEN pl.nm_poli LIKE '%Kulit%' OR pl.nm_poli LIKE '%Kelamin%' THEN 'Poli Kulit & Kelamin'
		ELSE pl.nm_poli
	END

UNION ALL

SELECT 
	'TOTAL' AS 'Jenis Kegiatan',
	SUM(CASE WHEN ps.jk='L' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END),
	SUM(CASE WHEN ps.jk='P' AND k.nm_kab LIKE '%BANJAR%' THEN 1 ELSE 0 END),
	SUM(CASE WHEN ps.jk='L' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END),
	SUM(CASE WHEN ps.jk='P' AND k.nm_kab NOT LIKE '%BANJAR%' THEN 1 ELSE 0 END),
	COUNT(r.no_rawat)
FROM reg_periksa r
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
LEFT JOIN kabupaten k ON ps.kd_kab = k.kd_kab
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien IS NOT NULL
  AND ps.nm_pasien <> ''
  AND pl.nm_poli NOT REGEXP 'TEST|PONEK|OBGYN|Vaksin|Gizi|HEMODIALISA|Laboratorium|Rawat Inap|IGD|UGD'

UNION ALL

SELECT 
	'Rata-Rata Hari Poliklinik Buka' AS 'Jenis Kegiatan',
	NULL, NULL, NULL, NULL,
	ROUND(COUNT(DISTINCT CONCAT(j.kd_poli, j.hari_kerja)) / COUNT(DISTINCT j.kd_poli), 2)
FROM jadwal j

UNION ALL

SELECT 
	'Rata-Rata Kunjungan per Hari' AS 'Jenis Kegiatan',
	NULL, NULL, NULL, NULL,
	ROUND(
		COUNT(r.no_rawat) / 
		(SELECT ROUND(COUNT(DISTINCT CONCAT(j.kd_poli, j.hari_kerja)) / COUNT(DISTINCT j.kd_poli), 2) FROM jadwal j)
	, 2)
FROM reg_periksa r
JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
WHERE MONTH(r.tgl_registrasi) = ?
  AND YEAR(r.tgl_registrasi) = ?
  AND ps.nm_pasien NOT LIKE '%TEST%'
  AND ps.nm_pasien IS NOT NULL
  AND ps.nm_pasien <> ''
  AND pl.nm_poli NOT REGEXP 'TEST|PONEK|OBGYN|Vaksin|Gizi|HEMODIALISA|Laboratorium|Rawat Inap|IGD|UGD'
";

// --- EKSEKUSI QUERY ---
$stmt = $config->prepare($sql);
$stmt->bind_param('iiiiii', $bulan, $tahun, $bulan, $tahun, $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- LIST BULAN ---
$bulanList = [
	'01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June',
	'07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'
];
?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>REKAPITULASI KUNJUNGAN RL 3.5</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="dashboard_staff.php?unit=beranda">Home</a></li>
					<li class="breadcrumb-item active">Rekapitulasi Kunjungan RL 3.5</li>
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
						<form method="post" class="mb-3 d-flex align-items-center gap-2">
							<label for="bulan">Bulan:</label>
							<select name="bulan" id="bulan" class="form-control" style="width:auto;">
								<?php
								foreach($bulanList as $val=>$label) {
									$selected = ($bulan == $val || $bulan == ltrim($val, '0')) ? 'selected' : '';
									echo "<option value='$val' $selected>$label</option>";
								}
								?>
							</select>
							<label for="tahun">Tahun:</label>
							<input type="number" name="tahun" id="tahun" class="form-control" value="<?= $tahun ?>" style="width:90px;">
							<button type="submit" class="btn btn-primary">Tampilkan Data</button>
						</form>
						<a href="main_app.php?page=export_excel_rl_3_5&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-success">Export Excel</a>
					</div>

					<div class="card-body">
						<div style="overflow-x:auto;">
							<table class="table table-bordered table-striped text-center align-middle">
								<thead style="background:#81a1c1;color:#fff;">
									<tr>
										<th>No</th>
										<th>Jenis Kegiatan</th>
										<th>Dalam Kab/Kota<br>Laki-laki</th>
										<th>Dalam Kab/Kota<br>Perempuan</th>
										<th>Luar Kab/Kota<br>Laki-laki</th>
										<th>Luar Kab/Kota<br>Perempuan</th>
										<th>Total Kunjungan</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($data)): ?>
										<tr><td colspan="7">Tidak ada data untuk periode yang dipilih.</td></tr>
									<?php else: 
										foreach ($data as $idx => $row):
											$no = $row['Jenis Kegiatan'] === 'TOTAL' ? 99 :
												($row['Jenis Kegiatan'] === 'Rata-Rata Hari Poliklinik Buka' ? 66 :
												($row['Jenis Kegiatan'] === 'Rata-Rata Kunjungan per Hari' ? 77 : $idx + 1));
									?>
										<tr>
											<?php if ($row['Jenis Kegiatan'] === 'TOTAL'): ?>
												<td><?= $no ?></td>
												<td><?= htmlspecialchars($row['Jenis Kegiatan']) ?></td>
												<td><?= number_format($row['Dalam Kab/Kota Laki-laki'] ?? 0) ?></td>
												<td><?= number_format($row['Dalam Kab/Kota Perempuan'] ?? 0) ?></td>
												<td><?= number_format($row['Luar Kab/Kota Laki-laki'] ?? 0) ?></td>
												<td><?= number_format($row['Luar Kab/Kota Perempuan'] ?? 0) ?></td>
												<td><?= number_format($row['Total Kunjungan'] ?? 0) ?></td>
											<?php elseif ($row['Jenis Kegiatan'] === 'Rata-Rata Hari Poliklinik Buka' || $row['Jenis Kegiatan'] === 'Rata-Rata Kunjungan per Hari'): ?>
												<?php $no = $row['Jenis Kegiatan'] === 'Rata-Rata Hari Poliklinik Buka' ? '66' : '77'; ?>
												<td><?= $no ?></td>
												<td colspan="5" style="text-align:right; font-weight:bold;"><?= htmlspecialchars($row['Jenis Kegiatan']) ?></td>
												<td><?= number_format($row['Total Kunjungan'] ?? 0) ?></td>
											<?php else: ?>
												<td><?= $no ?></td>
												<td><?= htmlspecialchars($row['Jenis Kegiatan']) ?></td>
												<td><?= number_format($row['Dalam Kab/Kota Laki-laki'] ?? 0) ?></td>
												<td><?= number_format($row['Dalam Kab/Kota Perempuan'] ?? 0) ?></td>
												<td><?= number_format($row['Luar Kab/Kota Laki-laki'] ?? 0) ?></td>
												<td><?= number_format($row['Luar Kab/Kota Perempuan'] ?? 0) ?></td>
												<td><?= number_format($row['Total Kunjungan'] ?? 0) ?></td>
											<?php endif; ?>
										</tr>
									<?php endforeach; endif; ?>
								</tbody>
							</table>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</section>
