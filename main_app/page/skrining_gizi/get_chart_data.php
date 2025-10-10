<?php
require_once("../../config/koneksi.php");

// Data untuk grafik status gizi
$query_status = mysqli_query($mysqli, "
    SELECT status, COUNT(*) as jumlah
    FROM skrining_gizi
    GROUP BY status
");

$status_labels = [];
$status_data = [];

while ($row = mysqli_fetch_assoc($query_status)) {
    $status_labels[] = $row['status'];
    $status_data[] = (int)$row['jumlah'];
}

// Data untuk grafik berdasarkan usia
$query_usia = mysqli_query($mysqli, "
    SELECT
        CASE
            WHEN usia < 18 THEN 'Anak-anak (<18)'
            WHEN usia BETWEEN 18 AND 59 THEN 'Dewasa (18-59)'
            ELSE 'Lansia (60+)'
        END as kategori_usia,
        COUNT(*) as jumlah
    FROM skrining_gizi
    GROUP BY kategori_usia
");

$usia_labels = [];
$usia_data = [];

while ($row = mysqli_fetch_assoc($query_usia)) {
    $usia_labels[] = $row['kategori_usia'];
    $usia_data[] = (int)$row['jumlah'];
}

echo json_encode([
    'status_labels' => $status_labels,
    'status_data' => $status_data,
    'usia_labels' => $usia_labels,
    'usia_data' => $usia_data
]);
?>