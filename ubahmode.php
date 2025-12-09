<?php
include "koneksi.php";

// Baca mode pinjam
$mode = mysqli_query($konek, "SELECT * FROM status");
$data_mode = mysqli_fetch_array($mode);
$mode_pinjam = $data_mode['mode'];

// Status terakhir kemudian ditambah 1
$mode_pinjam = $mode_pinjam + 1;
if ($mode_pinjam > 2)
    $mode_pinjam = 1;

// Simpan mode pinjam di tabel status
$simpan = mysqli_query($konek, "UPDATE status SET mode='$mode_pinjam'");
if ($simpan) {
    echo "BERHASIL";
} else {
    echo "Gagal";
}
