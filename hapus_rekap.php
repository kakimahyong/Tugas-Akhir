<?php
include "koneksi.php";

// Baca parameter 'nokartu' dari URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Hapus data dari tabel rekap dan pinjam
    $hapus_rekap = mysqli_query($konek, "DELETE FROM rekap WHERE id='$id'");

    // Cek apakah penghapusan berhasil
    if ($hapus_rekap) {
        echo "
        <script>
            alert('Data berhasil dihapus');
            location.replace('rekap.php'); // Kembali ke halaman sebelumnya
        </script>";
    } else {
        echo "
        <script>
            alert('Gagal menghapus data');
            location.replace('rekap.php'); // Tetap kembali ke halaman sebelumnya
        </script>";
    }
} else {
    echo "
    <script>
        alert('Data tidak ditemukan');
        location.replace('rekap'); // Kembali ke halaman sebelumnya jika parameter tidak ada
    </script>";
}
