<?php
// edit.php
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

// Halaman list untuk redirect setelah update
$LIST_PAGE = "datapinjam.php";

// --- Validasi ID ---
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: {$LIST_PAGE}?err=" . urlencode("ID tidak valid"));
    exit;
}
$id = (int)$_GET['id'];

// --- Ambil data buku berdasarkan ID ---
$sql = "SELECT id, nokartu AS kode_buku, nama AS judul_buku, kelas AS penulis, buku AS isbn, stok
        FROM pinjam WHERE id = ? LIMIT 1";
$stmt = $konek->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: {$LIST_PAGE}?err=" . urlencode("Data buku tidak ditemukan"));
    exit;
}

// --- Proses Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    // Ambil input & sanitasi dasar
    $kode_buku  = trim($_POST['kode_buku'] ?? '');
    $judul_buku = trim($_POST['judul_buku'] ?? '');
    $penulis    = trim($_POST['penulis'] ?? '');
    $isbn       = trim($_POST['isbn'] ?? '');
    $stok_raw   = $_POST['stok'] ?? '';

    // Validasi sederhana
    $errors = [];
    if ($kode_buku === '')  $errors[] = "Kode buku tidak boleh kosong.";
    if ($judul_buku === '') $errors[] = "Judul buku tidak boleh kosong.";
    if ($stok_raw === '' || !is_numeric($stok_raw) || (int)$stok_raw < 0) $errors[] = "Stok harus angka ≥ 0.";

    // (Opsional tapi disarankan) validasi panjang ISBN (10/13) tanpa memaksa
    if ($isbn !== '' && strlen(preg_replace('/[^0-9Xx-]/', '', $isbn)) < 10) {
        // bukan error fatal, hanya peringatan kecil — kalau mau jadikan error, ubah jadi $errors[] ...
        // $errors[] = "ISBN terlalu pendek.";
    }

    // Cek duplikasi kode_buku (nokartu) selain dirinya
    if (!$errors) {
        $cek = $konek->prepare("SELECT COUNT(*) c FROM pinjam WHERE nokartu = ? AND id <> ?");
        $cek->bind_param("si", $kode_buku, $id);
        $cek->execute();
        $dupRow = $cek->get_result()->fetch_assoc();
        $dup = (int)($dupRow['c'] ?? 0);
        $cek->close();
        if ($dup > 0) $errors[] = "Kode buku (RFID) sudah dipakai data lain.";
    }

    if (!$errors) {
        $stok_int = (int)$stok_raw;

        // >>>>>>>>>>>>>>>>> PERBAIKAN DI SINI <<<<<<<<<<<<<<<<<
        // urutan kolom: nokartu(s), nama(s), kelas(s), buku(s), stok(i), id(i)
        $upd = $konek->prepare("
            UPDATE pinjam
               SET nokartu = ?, nama = ?, kelas = ?, buku = ?, stok = ?
             WHERE id = ?
             LIMIT 1
        ");
        // pattern benar: ssssii (BUKAN sssisi)
        $upd->bind_param("ssssii", $kode_buku, $judul_buku, $penulis, $isbn, $stok_int, $id);
        if ($upd->execute()) {
            $upd->close();
            header("Location: {$LIST_PAGE}?updated=1");
            exit;
        } else {
            $err = "Gagal menyimpan perubahan.";
            $upd->close();
        }
    } else {
        $err = implode(" ", $errors);
    }

    // kalau gagal, tampilkan kembali nilai form terbaru
    $book['kode_buku']  = $kode_buku;
    $book['judul_buku'] = $judul_buku;
    $book['penulis']    = $penulis;
    $book['isbn']       = $isbn;
    $book['stok']       = (int)$stok_raw;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku</title>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            margin: 0;
            background: #f7f9fc;
        }

        main {
            flex: 1;
        }

        .card {
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(2, 8, 23, .06);
        }

        .form-control,
        .btn {
            border-radius: 10px;
        }

        .muted {
            color: #6b7280;
        }

        /* footer rapi di bawah, dengan garis halus */
        .app-footer {
            border-top: 1px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            font-size: 13px;
            padding: 12px 0;
            margin-top: 12px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Uppercase otomatis untuk kode buku/ RFID (opsional)
            const kode = document.getElementById('kode_buku');
            if (kode) kode.addEventListener('input', () => kode.value = kode.value.toUpperCase());
        });
    </script>
</head>

<body>
    <?php include "menu.php"; ?>

    <main class="container py-3">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-1">Edit Data Buku</h4>
                <div class="muted mb-3">Perbarui informasi buku di bawah ini.</div>

                <?php if (!empty($err)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['warn'])): ?>
                    <div class="alert alert-warning"><?php echo htmlspecialchars($_GET['warn']); ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Kode Buku (RFID)</label>
                        <input type="text" class="form-control" id="kode_buku" name="kode_buku"
                            value="<?php echo htmlspecialchars($book['kode_buku']); ?>" required>
                        <small class="muted">Harus unik. Biasanya sama dengan nomor kartu RFID buku.</small>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Judul Buku</label>
                        <input type="text" class="form-control" name="judul_buku"
                            value="<?php echo htmlspecialchars($book['judul_buku']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Penulis</label>
                        <input type="text" class="form-control" name="penulis"
                            value="<?php echo htmlspecialchars($book['penulis']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ISBN</label>
                        <!-- pakai text agar tidak memotong 0 di depan / tanda '-' -->
                        <input type="text" class="form-control" name="isbn" maxlength="20"
                            value="<?php echo htmlspecialchars($book['isbn']); ?>">
                        <small class="muted">Contoh: 978-623-1234-56-7</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Stok</label>
                        <input type="number" class="form-control" name="stok" min="0"
                            value="<?php echo (int)$book['stok']; ?>" required>
                        <small class="muted">Tidak boleh negatif.</small>
                    </div>

                    <div class="col-12 d-flex gap-2 mt-2">
                        <button type="submit" name="simpan" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="<?php echo htmlspecialchars($LIST_PAGE); ?>" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer dirapikan -->
    <?php include "footer.php"; ?>
</body>

</html>