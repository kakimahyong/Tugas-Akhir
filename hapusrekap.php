<?php
include "koneksi.php";

// baca id yang akan dihapus
$id = $_GET['id'];

// hapus data
$hapus = mysqli_query($konek, "DELETE FROM rekap WHERE id='$id'");

// jika berhasil tampilkan pesan
if ($hapus) {
    echo "
    <script> 
        alert('Terhapus');
        location.replace('datapinjam.php');
    </script>
    ";
} else {
    echo "
    <script> 
        alert('Gagal Terhapus');
        location.replace('datapinjam.php');
    </script>
    ";
}
