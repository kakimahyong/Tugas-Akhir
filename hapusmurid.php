<?php
include "koneksi.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Cek data foto terlebih dahulu
    $query = mysqli_query($konek, "SELECT foto FROM murid WHERE id='$id'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        // Hapus file foto jika ada
        if (!empty($data['foto']) && file_exists($data['foto'])) {
            unlink($data['foto']);
        }

        // Hapus data murid dari database
        $hapus = mysqli_query($konek, "DELETE FROM murid WHERE id='$id'");

        if ($hapus) {
            echo "<script>alert('Data murid berhasil dihapus!'); location.replace('datamurid.php');</script>";
        } else {
            echo "<script>alert('Gagal menghapus data murid!'); location.replace('datamurid.php');</script>";
        }
    } else {
        echo "<script>alert('Data tidak ditemukan!'); location.replace('datamurid.php');</script>";
    }
} else {
    echo "<script>alert('ID murid tidak ditemukan!'); location.replace('datamurid.php');</script>";
}
