<?php
include "koneksi.php";

// Baca UID dari tabel tmprfid
$cari_kartu = mysqli_query($konek, "SELECT * FROM tmprfid");
$data_kartu = mysqli_fetch_array($cari_kartu);
$nokartu = $data_kartu ? $data_kartu['nokartu'] : '';

if ($nokartu != '') {
    $queryMurid = mysqli_query($konek, "SELECT * FROM murid WHERE nokartu='$nokartu'");
    $data = mysqli_fetch_array($queryMurid);

    if ($data) {
        echo "
        <table class='table table-bordered' style='width:400px'>
            <tr><th>Kode Murid</th><td>{$data['kode_murid']}</td></tr>
            <tr><th>Nama</th><td>{$data['nama']}</td></tr>
            <tr><th>Kelas</th><td>{$data['kelas']}</td></tr>
            <tr><th>Foto</th><td><img src='uploads/foto_murid/{$data['foto']}' width='100'></td></tr>
        </table>
        ";
    } else {
        echo "<div class='alert alert-danger'>Kartu belum terdaftar di database!</div>";
    }
}
