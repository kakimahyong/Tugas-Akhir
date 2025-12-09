<?php
include "koneksi.php";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku</title>

    <style>
        /* Finishing sentuhan kecil */
        .card {
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(2, 8, 23, .06);
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }

        .table tbody tr:hover td {
            background: #f8fbff;
        }

        .toolbar .form-control {
            border-radius: 10px;
        }

        .toolbar .btn {
            border-radius: 10px;
        }

        .badge-soft {
            background: #0d6efd;
            color: #1e293b;
            font-weight: 600;
        }

        .badge-low {
            background: #fff1f2;
            color: #7f1d1d;
        }

        .badge-ok {
            background: #0d6efd;
            color: #064e3b;
        }

        .btn-action {
            border-radius: 10px;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>

    <script>
        // Filter client-side (kolom judul/penulis/isbn/kode)
        function filterRows() {
            const q = (document.getElementById('q')?.value || '').toLowerCase();
            document.querySelectorAll('tbody tr[data-text]').forEach(tr => {
                const t = tr.getAttribute('data-text')?.toLowerCase() || '';
                tr.style.display = (!q || t.includes(q)) ? '' : 'none';
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            const q = document.getElementById('q');
            if (q) q.addEventListener('input', filterRows);
        });

        function confirmDel(ev, url) {
            ev.preventDefault();
            if (confirm('Yakin ingin menghapus buku ini?')) {
                window.location.href = url;
            }
        }
    </script>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-body pb-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="mb-0">Daftar Buku</h4>
                        <small class="text-muted">Kelola data koleksi perpustakaan</small>
                    </div>
                    <div class="toolbar d-flex gap-2">
                        <input id="q" type="text" class="form-control" placeholder="Cari judul / penulis / ISBN / kode…">
                        <a href="tambah.php" class="btn btn-primary btn-action">+ Tambah Buku</a>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="nowrap" style="width:64px">No</th>
                            <th class="nowrap" style="width:160px">Kode Buku</th>
                            <th>Judul Buku</th>
                            <th class="nowrap" style="width:220px">Penulis</th>
                            <th class="nowrap" style="width:160px">ISBN</th>
                            <th class="nowrap" style="width:120px">Stok</th>
                            <th class="nowrap" style="width:180px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = $konek->query("
                    SELECT id,
                           nokartu AS kode_buku,
                           nama    AS judul_buku,
                           kelas   AS penulis,
                           buku    AS isbn,
                           stok
                    FROM pinjam
                    ORDER BY id DESC
                ");
                        $no = 1;
                        if ($sql && $sql->num_rows) {
                            while ($row = $sql->fetch_assoc()):
                                $kode   = $row['kode_buku'] ?? '';
                                $judul  = $row['judul_buku'] ?? '';
                                $penulis = $row['penulis'] ?? '';
                                $isbn   = $row['isbn'] ?? '';
                                $stok   = (int)$row['stok'];
                                // data gabungan buat filter
                                $filterText = trim($kode . ' ' . $judul . ' ' . $penulis . ' ' . $isbn);
                                // badge stok
                                $badgeClass = $stok <= 2 ? 'badge-low' : 'badge-ok';
                        ?>
                                <tr data-text="<?php echo htmlspecialchars($filterText); ?>">
                                    <td class="text-muted"><?php echo $no++; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($kode); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($judul); ?></div>
                                        <div><span class="badge badge-soft">ISBN: <?php echo htmlspecialchars($isbn ?: '-'); ?></span></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($penulis ?: '-'); ?></td>
                                    <td class="nowrap"><?php echo htmlspecialchars($isbn ?: '-'); ?></td>
                                    <td class="nowrap">
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo $stok; ?> eksemplar
                                        </span>
                                    </td>
                                    <td class="nowrap">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="edit.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-warning btn-action">Edit</a>
                                            <a href="hapus.php?id=<?php echo (int)$row['id']; ?>"
                                                class="btn btn-sm btn-danger btn-action"
                                                onclick="confirmDel(event, this.href)">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                        <?php
                            endwhile;
                        } else {
                            echo '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data buku.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer text-end small text-muted">
                Terakhir diperbarui: <?php echo date('d M Y, H:i'); ?>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>