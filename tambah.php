<?php
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

$err = $ok = "";

if (isset($_POST['btnKirim'])) {
    $kode_buku = trim($_POST['nokartu'] ?? '');
    $judul     = trim($_POST['nama'] ?? '');
    $penulis   = trim($_POST['kelas'] ?? '');
    $isbn      = trim($_POST['buku'] ?? '');
    $stok      = (int)($_POST['stok'] ?? 0);

    if ($kode_buku === '' || $judul === '') {
        $err = "Kode buku & judul wajib diisi.";
    } else {
        $stmtCek = $konek->prepare("SELECT 1 FROM pinjam WHERE nokartu = ? LIMIT 1");
        $stmtCek->bind_param("s", $kode_buku);
        $stmtCek->execute();
        $dupe = $stmtCek->get_result()->fetch_row();
        $stmtCek->close();

        if ($dupe) {
            $err = "Kode buku sudah terdaftar.";
        } else {
            $stmt = $konek->prepare("INSERT INTO pinjam (nokartu, nama, kelas, buku, stok) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssssi", $kode_buku, $judul, $penulis, $isbn, $stok);
            if ($stmt->execute()) {
                $stmt->close();
                $konek->query("DELETE FROM tmprfid");
                echo "<script>alert('Tersimpan');location.replace('datapinjam.php');</script>";
                exit;
            } else {
                $err = "Gagal tersimpan: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku</title>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(function() {
                fetch('nokartu.php', {
                        cache: 'no-store'
                    })
                    .then(r => r.text())
                    .then(html => {
                        document.getElementById('norfid').innerHTML = html;
                    });
            }, 1000);
        });
    </script>

    <style>
        body {
            background: #f8fafc;
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            border: none;
        }

        h3 {
            font-weight: 600;
            color: #1e293b;
        }

        .form-label {
            font-weight: 500;
            color: #334155;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            transition: all .2s ease-in-out;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .btn-success {
            background-color: #16a34a;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 500;
        }

        .btn-success:hover {
            background-color: #15803d;
        }

        .btn-secondary {
            border-radius: 10px;
            padding: 10px 18px;
        }

        .alert {
            border-radius: 10px;
        }

        .card-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            font-size: 28px;
        }

        @media (max-width: 768px) {
            .card {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-center">
            <div class="card p-4" style="max-width: 520px; width:100%;">
                <div class="text-center mb-4">
                    <div class="card-icon mb-2">
                        <i class="bi bi-journal-plus"></i>
                    </div>
                    <h3>Tambah Buku Perpustakaan</h3>
                    <p class="text-muted mb-0">Isi data buku baru untuk koleksi perpustakaan</p>
                </div>

                <?php if ($err): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?php echo htmlspecialchars($err); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div id="norfid"></div>

                    <div class="mb-3">
                        <label class="form-label">Judul Buku</label>
                        <input type="text" name="nama" id="nama" placeholder="Masukkan judul buku"
                            class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Penulis</label>
                        <input type="text" name="kelas" id="kelas" placeholder="Masukkan nama penulis"
                            class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="buku" id="buku" placeholder="Masukkan nomor ISBN"
                            class="form-control">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" id="stok" placeholder="Jumlah stok"
                            class="form-control" min="0" value="0">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="datapinjam.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <button class="btn btn-success" name="btnKirim" id="btnKirim">
                            <i class="bi bi-save2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>