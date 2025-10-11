<?php
$host = '192.168.1.4';
$user = 'root';
$pass = '';
$db   = 'sik9';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
        die('Koneksi gagal: ' . $conn->connect_error);
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$tahun = intval($tahun);
$sql = "SELECT kab.nm_kab AS kabupaten,
        SUM(CASE WHEN rp.kd_pj = 'A09' THEN 1 ELSE 0 END) AS Umum,
        SUM(CASE WHEN rp.kd_pj = 'BPJ' THEN 1 ELSE 0 END) AS BPJS,
        SUM(CASE WHEN rp.kd_pj = 'A92' THEN 1 ELSE 0 END) AS Asuransi
FROM reg_periksa rp
JOIN pasien p      ON p.no_rkm_medis = rp.no_rkm_medis
JOIN penjab pj     ON pj.kd_pj = rp.kd_pj
JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
JOIN kamar k       ON k.kd_kamar = ki.kd_kamar
JOIN bangsal b     ON b.kd_bangsal = k.kd_bangsal
JOIN kabupaten kab ON kab.kd_kab = p.kd_kab
JOIN kecamatan kec ON kec.kd_kec = p.kd_kec
JOIN kelurahan kel ON kel.kd_kel = p.kd_kel
WHERE YEAR(rp.tgl_registrasi) = $tahun
        AND rp.status_lanjut = 'Ranap'
        AND rp.stts <> 'Batal'
        AND ki.stts_pulang <> 'Pindah Kamar'
        AND kab.nm_kab IN ('BANJARMASIN', 'BANJARBARU', 'BANJAR')
        AND rp.kd_pj IN ('A09','A92','BPJ')
GROUP BY kabupaten
ORDER BY FIELD(kabupaten, 'BANJARMASIN', 'BANJARBARU', 'BANJAR')";
$result = $conn->query($sql);
$total_umum = 0;
$total_bpjs = 0;
$total_asuransi = 0;
$labels = [];
$umum = [];
$bpjs = [];
$asuransi = [];
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>REKAP PASIEN RAWAT INAP PER KABUPATEN</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="main_staff.php?unit=beranda">Home</a></li>
                    <li class="breadcrumb-item active">Rekap Kunjungan</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-warning">
                    <div class="card-header" style="background:orange; color:white;">
                        <h3 class="card-title">Grafik Batang Jumlah Pasien per Kabupaten</h3>
                    </div>
                    <div class="card-body" style="background:rgb(203, 212, 212); min-height: 400px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="grafikBar" style="min-height: 350px; height: 350px; max-height: 400px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-danger">
                    <div class="card-header" style="background:#dc3545; color:white;">
                        <h3 class="card-title">DATA PASIEN</h3>
                    </div>
                    <div class="card-body" style="background:rgb(203, 212, 212); min-height: 400px;">
                        <table id="tabelPasien" class="table table-bordered table-striped" style="width:100%;">
                            <thead style="background:#007bff; color:white;">
                                <tr>
                                    <th style="text-align:center;">Kabupaten</th>
                                    <th style="text-align:center;">Umum</th>
                                    <th style="text-align:center;">BPJS</th>
                                    <th style="text-align:center;">Asuransi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result->data_seek(0);
                                $total_umum = 0;
                                $total_bpjs = 0;
                                $total_asuransi = 0;
                                $labels = [];
                                $umum = [];
                                $bpjs = [];
                                $asuransi = [];
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td align="center">' . htmlspecialchars($row['kabupaten']) . '</td>';
                                        echo '<td align="center">' . $row['Umum'] . '</td>';
                                        echo '<td align="center">' . $row['BPJS'] . '</td>';
                                        echo '<td align="center">' . $row['Asuransi'] . '</td>';
                                        echo '</tr>';
                                        $total_umum += $row['Umum'];
                                        $total_bpjs += $row['BPJS'];
                                        $total_asuransi += $row['Asuransi'];
                                        $labels[] = $row['kabupaten'];
                                        $umum[] = $row['Umum'];
                                        $bpjs[] = $row['BPJS'];
                                        $asuransi[] = $row['Asuransi'];
                                    }
                                    // Baris total
                                    echo '<tr style="background: orange; color: white; font-weight: bold;">';
                                    echo '<td align="center">JUMLAH</td>';
                                    echo '<td align="center">' . $total_umum . '</td>';
                                    echo '<td align="center">' . $total_bpjs . '</td>';
                                    echo '<td align="center">' . $total_asuransi . '</td>';
                                    echo '</tr>';
                                } else {
                                    echo '<tr><td colspan="4" align="center">Tidak ada data</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<!-- DataTables JS & CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabelPasien').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthChange: false,
        pageLength: 10,
        language: {
            search: 'Search:',
            emptyTable: 'Tidak ada data',
            paginate: { previous: 'Previous', next: 'Next' }
        }
    });
});
</script>
        </div>
    </div>
</section>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('grafikBar').getContext('2d');
const chart = new Chart(ctx, {
        type: 'bar',
        data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                        {
                                label: 'Umum',
                                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                data: <?php echo json_encode($umum); ?>
                        },
                        {
                                label: 'BPJS',
                                backgroundColor: 'rgba(33, 150, 243, 0.8)',
                                borderColor: 'rgba(33, 150, 243, 1)',
                                data: <?php echo json_encode($bpjs); ?>
                        },
                        {
                                label: 'Asuransi',
                                backgroundColor: 'rgba(76, 175, 80, 0.8)',
                                borderColor: 'rgba(76, 175, 80, 1)',
                                data: <?php echo json_encode($asuransi); ?>
                        }
                ]
        },
        options: {
                responsive: true,
                plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Grafik Batang Jumlah Pasien per Kabupaten' }
                },
                scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                }
        }
});
</script>