<?php
// edit1.php — Ubah jumlah pinjaman + sinkron stok
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

/* ---------- helper: bawa query string saat redirect ---------- */
function keepQS(array $extra = []): string
{
    $qs = $_GET;
    foreach ($extra as $k => $v) {
        if ($v === null) unset($qs[$k]);
        else $qs[$k] = $v;
    }
    return $qs ? ('?' . http_build_query($qs)) : '';
}

/* ---------- Validasi ID ---------- */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: rekap.php" . keepQS(['err' => 'ID tidak valid']));
    exit;
}
$id = (int)$_GET['id'];

/* ---------- Ambil data rekap + buku ---------- */
$sql = "
SELECT
  r.id,
  r.nokartu, r.noinduk, r.nama, r.kelas,
  r.kode_buku, r.jumlah AS jumlah_lama, r.tanggal, r.jam_pinjam, r.jam_kembali,
  p.id   AS id_buku,
  p.nama AS judul_buku,
  p.kelas AS penulis,
  p.buku  AS isbn,
  p.stok  AS stok_sisa
FROM rekap r
LEFT JOIN pinjam p ON p.nokartu = r.kode_buku
WHERE r.id = ?
LIMIT 1";
$stmt = $konek->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: rekap.php" . keepQS(['err' => 'Data tidak ditemukan']));
    exit;
}

/* ---------- Larang edit bila sudah kembali ---------- */
if (!empty($data['jam_kembali']) && $data['jam_kembali'] !== '00:00:00') {
    header("Location: rekap.php" . keepQS(['err' => 'Tidak bisa ubah: buku sudah dikembalikan.']));
    exit;
}

$jumlah_lama = (int)$data['jumlah_lama'];
$stok_sisa   = (int)($data['stok_sisa'] ?? 0);
$id_buku     = (int)($data['id_buku'] ?? 0);

/* ---------- Simpan perubahan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    // nilai baru, minimal 1
    $jumlah_baru = max(1, (int)($_POST['jumlah'] ?? $jumlah_lama));
    $delta = $jumlah_baru - $jumlah_lama;

    // kalau nambah pinjaman → butuh stok cukup
    if ($delta > 0) {
        if ($stok_sisa < $delta || $id_buku <= 0) {
            header("Location: edit1.php" . keepQS(['err' => "Stok tidak cukup. Sisa {$stok_sisa}."]));
            exit;
        }
        $u = $konek->prepare("UPDATE pinjam SET stok = stok - ? WHERE id = ? AND stok >= ?");
        $u->bind_param("iii", $delta, $id_buku, $delta);
        if (!$u->execute() || $u->affected_rows === 0) {
            $u->close();
            header("Location: edit1.php" . keepQS(['err' => "Gagal mengurangi stok."]));
            exit;
        }
        $u->close();
    }

    // kalau kurangi pinjaman → kembalikan stok
    if ($delta < 0 && $id_buku > 0) {
        $rebate = -$delta;
        $u = $konek->prepare("UPDATE pinjam SET stok = stok + ? WHERE id = ?");
        $u->bind_param("ii", $rebate, $id_buku);
        if (!$u->execute()) {
            $u->close();
            header("Location: edit1.php" . keepQS(['err' => "Gagal menambah stok."]));
            exit;
        }
        $u->close();
    }

    // update jumlah di rekap
    $r = $konek->prepare("UPDATE rekap SET jumlah = ? WHERE id = ?");
    $r->bind_param("ii", $jumlah_baru, $id);
    if ($r->execute()) {
        $r->close();
        header("Location: rekap.php" . keepQS(['success' => 1, 'err' => null]));
        exit;
    }
    $r->close();
    header("Location: edit1.php" . keepQS(['err' => "Gagal menyimpan perubahan."]));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jumlah Pinjam</title>
    <link rel="stylesheet" href="css/custom.css">
    <style>
        body {
            background: #f7f9fc;
        }

        .form-card {
            max-width: 860px;
            margin: 18px auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(2, 8, 23, .06);
        }

        .row-flex {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .col {
            flex: 1 1 260px;
        }

        .label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .ctrl {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .readonly {
            background: #f8fafc;
        }

        .btn {
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #0ea5e9;
            border-color: #0ea5e9;
            color: #fff;
        }

        .btn-ghost {
            background: #fff;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .muted {
            color: #64748b;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .alert-error {
            background: #fff1f2;
            border: 1px solid #fecaca;
            color: #7f1d1d;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 12px;
        }

        .badge-soft {
            background: #e0f2fe;
            color: #075985;
        }
    </style>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="container">
        <div class="form-card">
            <h3 style="margin:0 0 12px;">Edit Jumlah Pinjam</h3>

            <?php if (isset($_GET['err'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
            <?php endif; ?>

            <div class="row-flex" style="margin-bottom:12px;">
                <div class="col">
                    <span class="label">Anggota</span>
                    <div class="ctrl readonly">
                        <?php
                        echo htmlspecialchars($data['nama']) . " (" . htmlspecialchars($data['noinduk']) . ") • Kelas " . htmlspecialchars($data['kelas']);
                        ?>
                    </div>
                </div>
                <div class="col">
                    <span class="label">Tanggal & Jam Pinjam</span>
                    <div class="ctrl readonly">
                        <?php echo htmlspecialchars($data['tanggal']) . " • " . htmlspecialchars($data['jam_pinjam']); ?>
                    </div>
                </div>
            </div>

            <div class="row-flex" style="margin-bottom:12px;">
                <div class="col">
                    <span class="label">Judul Buku</span>
                    <div class="ctrl readonly"><?php echo htmlspecialchars($data['judul_buku'] ?? '-'); ?></div>
                </div>
                <div class="col">
                    <span class="label">Penulis</span>
                    <div class="ctrl readonly"><?php echo htmlspecialchars($data['penulis'] ?? '-'); ?></div>
                </div>
                <div class="col">
                    <span class="label">ISBN</span>
                    <div class="ctrl readonly"><?php echo htmlspecialchars($data['isbn'] ?? '-'); ?></div>
                </div>
            </div>

            <form method="post" class="row-flex" style="align-items:flex-end;">
                <div class="col">
                    <span class="label">Jumlah Dipinjam</span>
                    <?php $max = $jumlah_lama + max(0, $stok_sisa); ?>
                    <input type="number" name="jumlah" class="ctrl" min="1" max="<?php echo (int)$max; ?>"
                        value="<?php echo (int)$jumlah_lama; ?>" required>
                    <div class="muted" style="margin-top:6px;">
                        Stok tersedia: <b><?php echo (int)$stok_sisa; ?></b>
                        <span class="badge badge-soft" style="margin-left:8px;">Maks: <?php echo (int)$max; ?></span>
                    </div>
                </div>
                <div class="col" style="display:flex; gap:8px;">
                    <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                    <a href="<?php echo 'rekap.php' . keepQS(); ?>" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>