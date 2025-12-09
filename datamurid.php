<?php
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

// Ambil daftar kelas untuk dropdown filter (dan untuk cetak)
$kelas_rs = mysqli_query($konek, "SELECT DISTINCT kelas FROM murid WHERE kelas IS NOT NULL AND kelas<>'' ORDER BY kelas ASC");
$kelas_options = [];
while ($r = mysqli_fetch_assoc($kelas_rs)) {
    $kelas_options[] = $r['kelas'];
}

// Ambil data murid untuk tabel
$sql = mysqli_query($konek, "SELECT * FROM murid ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Murid</title>

    <style>
        body {
            background: #f8fafc
        }

        .card {
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(2, 8, 23, .06);
            border: 1px solid #e5e7eb;
            overflow: hidden
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem
        }

        .table-hover tbody tr:hover td {
            background: #f8fbff
        }

        .avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px #e5e7eb
        }

        .toolbar .form-control,
        .toolbar .form-select,
        .btn {
            border-radius: 10px
        }

        .badge-soft {
            background: #0d6efd;
            color: #1e293b;
            font-weight: 600
        }

        .btn-action {
            border-radius: 10px
        }

        .table td,
        .table th {
            vertical-align: middle
        }

        .nowrap {
            white-space: nowrap
        }

        .empty {
            padding: 36px 0;
            color: #6b7280
        }
    </style>

    <script>
        // Filter client-side untuk tabel
        function filterRows() {
            const q = (document.getElementById('q')?.value || '').toLowerCase();
            const fk = document.getElementById('filterKelas')?.value || '';
            document.querySelectorAll('tbody tr[data-text]').forEach(tr => {
                const t = tr.getAttribute('data-text')?.toLowerCase() || '';
                const k = tr.getAttribute('data-kelas') || '';
                const okText = !q || t.includes(q);
                const okKelas = !fk || k === fk;
                tr.style.display = (okText && okKelas) ? '' : 'none';
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            const q = document.getElementById('q');
            const fk = document.getElementById('filterKelas');
            if (q) q.addEventListener('input', filterRows);
            if (fk) fk.addEventListener('change', filterRows);
        });

        function confirmDel(ev, url) {
            ev.preventDefault();
            if (confirm('Yakin ingin menghapus murid ini?')) location.href = url;
        }
    </script>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="mb-0">Daftar Murid SD</h4>
                        <small class="text-muted">Kelola data siswa, foto, dan kelas</small>
                    </div>

                    <!-- Penting: Select kelas berada DI DALAM form yang menuju cetak_murid.php -->
                    <div class="toolbar d-flex flex-wrap gap-2">
                        <input id="q" type="text" class="form-control" placeholder="Cari nama / kode murid / no. kartu / kelas…">

                        <form action="cetak_murid.php" method="get" target="_blank" class="d-flex flex-wrap gap-2">
                            <!-- name='kelas' akan terkirim saat klik Cetak PDF -->
                            <select id="filterKelas" name="kelas" class="form-select">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_options as $k): ?>
                                    <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($k); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <a href="tambahmurid.php" class="btn btn-primary btn-action">+ Tambah Murid</a>
                            <button type="submit" class="btn btn-success btn-action">📄 Cetak PDF</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th class="nowrap" style="width:70px;">No</th>
                            <th class="nowrap" style="width:140px;">No. Kartu</th>
                            <th class="nowrap" style="width:140px;">Kode Murid</th>
                            <th>Nama</th>
                            <th class="nowrap" style="width:100px;">Kelas</th>
                            <th class="nowrap" style="width:120px;">Foto</th>
                            <th class="nowrap" style="width:180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($sql && mysqli_num_rows($sql) > 0):
                            while ($data = mysqli_fetch_assoc($sql)):
                                $fotoFile = $data['foto'] ?? '';
                                $fotoPath = (!empty($fotoFile) && file_exists("uploads/foto_murid/" . $fotoFile))
                                    ? "uploads/foto_murid/" . $fotoFile
                                    : "uploads/default.png";
                                $filterText = trim(($data['nokartu'] ?? '') . ' ' . ($data['kode_murid'] ?? '') . ' ' . ($data['nama'] ?? '') . ' ' . ($data['kelas'] ?? ''));
                        ?>
                                <tr data-text="<?php echo htmlspecialchars($filterText); ?>" data-kelas="<?php echo htmlspecialchars($data['kelas'] ?? ''); ?>">
                                    <td class="text-muted"><?php echo $no++; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($data['nokartu']); ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($data['kode_murid']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($data['nama']); ?></div>
                                        <div><span class="badge badge-soft">ID: <?php echo (int)$data['id']; ?></span></div>
                                    </td>
                                    <td class="nowrap"><?php echo htmlspecialchars($data['kelas']); ?></td>
                                    <td><img src="<?php echo htmlspecialchars($fotoPath); ?>" alt="Foto Murid" class="avatar"></td>
                                    <td class="nowrap">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="editmurid.php?id=<?php echo (int)$data['id']; ?>" class="btn btn-sm btn-warning btn-action">Edit</a>
                                            <a href="hapusmurid.php?id=<?php echo (int)$data['id']; ?>" class="btn btn-sm btn-danger btn-action"
                                                onclick="confirmDel(event, this.href)">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="7" class="text-center empty">Belum ada data murid.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center small text-muted">
                <span>Total murid: <?php echo max(0, $no - 1); ?></span>
                <span>Terakhir diperbarui: <?php echo date('d M Y, H:i'); ?></span>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>