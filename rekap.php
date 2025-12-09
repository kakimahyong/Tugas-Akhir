<?php
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

/* ================== helpers ================== */
function keepQS(array $extra = []): string
{
    $qs = $_GET;
    foreach ($extra as $k => $v) {
        if ($v === null) unset($qs[$k]);
        else $qs[$k] = $v;
    }
    return $qs ? ('?' . http_build_query($qs)) : '';
}
function h($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Jika ada error “scan dulu” dari halaman lain, bersihkan query string */
if (isset($_GET['err']) && $_GET['err'] === 'scan_dulu') {
    header("Location: rekap.php" . keepQS(['err' => null]));
    exit;
}

/* Hapus baris rekap (dan pulihkan stok bila transaksi belum kembali) */
if (isset($_POST['hapus'])) {
    $id_hapus = $_POST['id_hapus'] ?? '';
    if ($id_hapus !== '' && ctype_digit($id_hapus)) {

        // Ambil info transaksi yang akan dihapus
        $stmtGet = $konek->prepare("
            SELECT id, kode_buku, jam_kembali, COALESCE(jumlah,0) AS jumlah
            FROM rekap WHERE id = ? LIMIT 1
        ");
        $stmtGet->bind_param("i", $id_hapus);
        $stmtGet->execute();
        $rowDel = $stmtGet->get_result()->fetch_assoc();
        $stmtGet->close();

        $stmtDel = $konek->prepare("DELETE FROM rekap WHERE id = ?");
        $stmtDel->bind_param("i", $id_hapus);
        if ($stmtDel->execute()) {
            $stmtDel->close();

            // Jika belum kembali → pulihkan stok buku sesuai JUMLAH
            if ($rowDel && ($rowDel['jam_kembali'] === null || $rowDel['jam_kembali'] === '')) {
                $kode   = (string)$rowDel['kode_buku'];
                $jumlah = max(0, (int)$rowDel['jumlah']);
                if ($jumlah > 0 && $kode !== '') {
                    // Pulihkan stok ke tabel pinjam berdasar UID buku (pinjam.nokartu)
                    $stmtUp = $konek->prepare("
                        UPDATE pinjam
                        SET stok = GREATEST(0, stok + ?)
                        WHERE REPLACE(UPPER(nokartu),' ','') = REPLACE(UPPER(?),' ','')
                        LIMIT 1
                    ");
                    $stmtUp->bind_param("is", $jumlah, $kode);
                    $stmtUp->execute();
                    $stmtUp->close();
                }
            }

            // Siapkan untuk scan berikutnya (opsional)
            $konek->query("DELETE FROM tmprfid");
            header("Location: rekap.php" . keepQS(['deleted' => 1]));
            exit;
        } else {
            $stmtDel->close();
            header("Location: rekap.php" . keepQS(['err' => 'Gagal menghapus data.']));
            exit;
        }
    } else {
        header("Location: rekap.php" . keepQS(['err' => 'ID tidak valid']));
        exit;
    }
}

/* ================== filter tanggal ================== */
$tanggal_hari_ini = (isset($_GET['tanggal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal']))
    ? $_GET['tanggal'] : date('Y-m-d');

/* ================== query utama ================== */
/*
   KUNCI PERBAIKAN:
   - JOIN buku memakai UID yang sudah dinormalisasi (UPPER + hapus spasi)
   - Jika tidak ketemu, field judul_buku/penulis/isbn akan kosong,
     tapi kita tetap tampilkan kode_buku (UID buku) agar admin tahu.
*/
$sql = "
SELECT
  a.id, a.nokartu, a.noinduk, a.nama, a.kelas,
  a.kode_buku, a.jumlah, a.tanggal, a.jam_pinjam, a.jam_kembali,
  m.foto AS foto_murid,
  p.nama  AS judul_buku,
  p.kelas AS penulis,
  p.buku  AS isbn
FROM rekap a
LEFT JOIN murid  m
  ON REPLACE(UPPER(m.nokartu),' ','') = REPLACE(UPPER(a.nokartu),' ','')
LEFT JOIN pinjam p
  ON REPLACE(UPPER(p.nokartu),' ','') = REPLACE(UPPER(a.kode_buku),' ','')
WHERE a.tanggal = ?
ORDER BY a.jam_pinjam DESC, a.id DESC
";
$stmt = $konek->prepare($sql);
$stmt->bind_param("s", $tanggal_hari_ini);
$stmt->execute();
$result = $stmt->get_result();

/* ================== helper foto ================== */
function pathFotoMurid(?string $namaFile): string
{
    if (!$namaFile) return "uploads/default.png";
    $p = "uploads/foto_murid/" . $namaFile;
    return file_exists($p) ? $p : "uploads/default.png";
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Peminjaman</title>
    <style>
        .card {
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(2, 8, 23, .06)
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #0f172a;
            color: #fff;
            border-color: #0f172a
        }

        .table tbody tr:hover td {
            background: #f8fbff
        }

        .nowrap {
            white-space: nowrap
        }

        .foto {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px #e5e7eb
        }

        .chip {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600
        }

        .chip.ok {
            background: #dcfce7;
            color: #065f46
        }

        .chip.warn {
            background: #fef3c7;
            color: #92400e
        }

        .chip.danger {
            background: #fee2e2;
            color: #991b1b
        }

        .chip.badge {
            background: #e0f2fe;
            color: #075985
        }

        @media (max-width:576px) {

            .toolbar .form-control,
            .toolbar .form-select,
            .toolbar .btn {
                width: 100%
            }
        }
    </style>
    <script>
        function filterRows() {
            const q = (document.getElementById('q').value || '').toLowerCase();
            const s = document.getElementById('status').value;
            document.querySelectorAll('tbody tr[data-row]').forEach(tr => {
                const text = (tr.getAttribute('data-text') || '').toLowerCase();
                const stat = tr.getAttribute('data-status');
                const okText = !q || text.includes(q);
                const okStat = (s === 'all' || s === stat);
                tr.style.display = (okText && okStat) ? '' : 'none';
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('q')?.addEventListener('input', filterRows);
            document.getElementById('status')?.addEventListener('change', filterRows);
        });
    </script>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-body pb-2">
                <div class="text-center mb-3">
                    <h4 class="mb-0">Rekap Peminjaman (<?= h($tanggal_hari_ini) ?>)</h4>
                    <small class="text-muted">Lihat daftar peminjaman dan status pengembalian</small>
                </div>

                <form class="toolbar d-flex justify-content-center flex-wrap gap-2" method="get" action="rekap.php">
                    <input id="q" type="text" name="q" class="form-control" placeholder="Cari nama / kode / ISBN..."
                        value="<?= h($_GET['q'] ?? '') ?>" style="min-width:240px;">
                    <select id="status" class="form-select" style="min-width:180px;">
                        <option value="all">Semua status</option>
                        <option value="pinjam">Masih dipinjam</option>
                        <option value="kembali">Sudah kembali</option>
                        <option value="telat">Terlambat</option>
                    </select>
                    <input type="date" class="form-control" name="tanggal"
                        value="<?= h($tanggal_hari_ini) ?>" style="min-width:180px;">
                    <button class="btn btn-primary" type="submit">Terapkan</button>
                    <a class="btn btn-outline-secondary" href="rekap.php">Hari ini</a>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>No Kartu</th>
                            <th>Kode Murid</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Kode Buku (UID)</th>
                            <th>Judul Buku</th>
                            <th>Penulis</th>
                            <th>ISBN</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Jam Pinjam</th>
                            <th>Status</th>
                            <th>Tenggat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nowDate = new DateTime();
                        if ($result && $result->num_rows > 0):
                            $no = 1;
                            while ($data = $result->fetch_assoc()):
                                $tenggatStr = date('Y-m-d', strtotime(($data['tanggal'] ?? date('Y-m-d')) . ' +7 days'));
                                $fotoPath   = pathFotoMurid($data['foto_murid'] ?? null);
                                $isReturned = !empty($data['jam_kembali']) && $data['jam_kembali'] !== '00:00:00';

                                $statusText = $isReturned ? 'Sudah Kembali' : 'Masih Dipinjam';
                                $statusKey  = $isReturned ? 'kembali' : 'pinjam';
                                $isLate = false;
                                if (!$isReturned) {
                                    $due = DateTime::createFromFormat('Y-m-d', $tenggatStr);
                                    if ($due && $nowDate > $due) {
                                        $isLate = true;
                                        $statusText = 'Terlambat';
                                        $statusKey = 'telat';
                                    }
                                }
                                $chipClass = $isReturned ? 'ok' : ($isLate ? 'danger' : 'warn');

                                $filterText = implode(' ', [
                                    $data['nokartu'] ?? '',
                                    $data['noinduk'] ?? '',
                                    $data['nama'] ?? '',
                                    $data['kelas'] ?? '',
                                    $data['kode_buku'] ?? '',
                                    $data['judul_buku'] ?? '',
                                    $data['penulis'] ?? '',
                                    $data['isbn'] ?? ''
                                ]);

                                $editHref = 'edit1.php?id=' . (int)$data['id']
                                    . (isset($_GET['tanggal']) ? '&tanggal=' . urlencode($_GET['tanggal']) : '');
                        ?>
                                <tr data-row data-status="<?= h($statusKey) ?>" data-text="<?= h($filterText) ?>">
                                    <td><?= $no++ ?></td>
                                    <td><img src="<?= h($fotoPath) ?>" class="foto" alt="foto"></td>
                                    <td><?= h($data['nokartu']) ?></td>
                                    <td><?= h($data['noinduk']) ?></td>
                                    <td><?= h($data['nama']) ?></td>
                                    <td><?= h($data['kelas']) ?></td>

                                    <!-- selalu tampilkan UID buku (kode_buku) apa adanya -->
                                    <td><?= h($data['kode_buku']) ?></td>

                                    <!-- kolom info buku dari master; kalau tidak ketemu, beri tanda "-" -->
                                    <td><?= $data['judul_buku'] !== null && $data['judul_buku'] !== '' ? h($data['judul_buku']) : '-' ?></td>
                                    <td><?= $data['penulis']    !== null && $data['penulis']    !== '' ? h($data['penulis'])    : '-' ?></td>
                                    <td><?= $data['isbn']       !== null && $data['isbn']       !== '' ? h($data['isbn'])       : '-' ?></td>

                                    <td><?= (int)$data['jumlah'] ?></td>
                                    <td><?= h($data['tanggal']) ?></td>
                                    <td><?= h($data['jam_pinjam']) ?></td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="chip <?= $chipClass ?>"><?= h($statusText) ?></span>
                                            <?php if ($isReturned): ?>
                                                <span class="chip badge">Kembali: <?= h($data['jam_kembali']) ?></span>
                                            <?php else: ?>
                                                <span class="chip badge">Belum kembali</span>
                                            <?php endif; ?>
                                            <?php if (($data['judul_buku'] ?? '') === '' && ($data['kode_buku'] ?? '') !== ''): ?>
                                                <span class="chip danger">UID buku belum terdaftar</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= h($tenggatStr) ?></td>
                                    <td class="nowrap">
                                        <form method="POST" action="<?= h('rekap.php' . keepQS()) ?>"
                                            onsubmit="return confirm('Yakin hapus data ini? Jika belum kembali, stok buku akan dipulihkan sesuai jumlah yang dipinjam.');"
                                            style="display:inline;">
                                            <input type="hidden" name="id_hapus" value="<?= (int)$data['id']; ?>">
                                            <button type="submit" name="hapus" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                        <a href="<?= h($editHref) ?>" class="btn btn-sm btn-warning">Edit Jumlah</a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="16" class="text-center text-muted py-4">Tidak ada data peminjaman untuk tanggal ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer text-end small text-muted">
                Terakhir diperbarui: <?= date('d M Y, H:i'); ?>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>
<?php if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close(); ?>