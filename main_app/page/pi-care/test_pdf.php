<?php
// Test file untuk memastikan tidak ada error
echo "Test PDF berhasil!";
echo "<br>Koneksi database: ";
require_once '../../../config/koneksi.php';
if (isset($mysqli)) {
    echo "OK";
} else {
    echo "ERROR";
}
?> 