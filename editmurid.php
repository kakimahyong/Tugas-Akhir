<?php
include "koneksi.php";

// Ambil data murid berdasarkan ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = mysqli_query($konek, "SELECT * FROM murid WHERE id='$id'");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        echo "<script>alert('Data tidak ditemukan!'); location.replace('datamurid.php');</script>";
        exit;
    }
}

// Proses update data
if (isset($_POST['btnUpdate'])) {
    $nama = $_POST['nama'];
    $kelas = $_POST['kelas'];
    $fotoBaru = $_FILES['foto']['name'];

    // Jika foto baru diupload
    if ($fotoBaru != "") {
        $tmp = $_FILES['foto']['tmp_name'];
        $path = "uploads/" . basename($fotoBaru);

        // Hapus foto lama jika ada
        if (!empty($data['foto']) && file_exists($data['foto'])) {
            unlink($data['foto']);
        }

        move_uploaded_file($tmp, $path);
    } else {
        $path = $data['foto']; // Tetap gunakan foto lama
    }

    $update = mysqli_query($konek, "UPDATE murid SET 
        nama='$nama',
        kelas='$kelas',
        foto='$path'
        WHERE id='$id'
    ");

    if ($update) {
        echo "<script>alert('Data murid berhasil diperbarui!'); location.replace('datamurid.php');</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data murid!'); location.replace('datamurid.php');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include "header.php"; ?>
    <title>Edit Data Murid</title>

    <style>
        img.preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #ccc;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="container-fluid">
        <h3>Edit Data Murid</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Kode Murid</label>
                <input type="text" value="<?php echo $data['kode_murid']; ?>" class="form-control" style="width:200px;" readonly>
            </div>

            <div class="form-group">
                <label>Nama Murid</label>
                <input type="text" name="nama" value="<?php echo $data['nama']; ?>" class="form-control" style="width:300px;" required>
            </div>

            <div class="form-group">
                <label>Kelas</label>
                <input type="text" name="kelas" value="<?php echo $data['kelas']; ?>" class="form-control" style="width:150px;" required>
            </div>

            <div class="form-group">
                <label>Foto (biarkan kosong jika tidak diganti)</label>
                <input type="file" name="foto" accept="image/*" class="form-control" style="width:300px;">
                <?php if (!empty($data['foto'])) { ?>
                    <img src="<?php echo $data['foto']; ?>" class="preview">
                <?php } ?>
            </div>

            <button class="btn btn-primary" name="btnUpdate">Perbarui Data</button>
            <a href="datamurid.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>