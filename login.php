<?php
include "koneksi.php";
session_start();
date_default_timezone_set('Asia/Jakarta');

/* =======================
   METRICS
   ======================= */
// Total judul buku
$r = $konek->query("SELECT COUNT(*) n FROM pinjam");
$totalJudul = (int)($r->fetch_assoc()['n'] ?? 0);

// Eksemplar tersedia (Σ stok)
$r = $konek->query("SELECT COALESCE(SUM(stok),0) n FROM pinjam");
$totalTersedia = (int)($r->fetch_assoc()['n'] ?? 0);

// Sedang dipinjam (Σ jumlah yang belum kembali)
$r = $konek->query("
  SELECT COALESCE(SUM(jumlah),0) n
  FROM rekap
  WHERE (jam_kembali IS NULL OR jam_kembali='' OR jam_kembali='00:00:00')
");
$totalDipinjam = (int)($r->fetch_assoc()['n'] ?? 0);

// Total anggota
$r = $konek->query("SELECT COUNT(*) n FROM murid");
$totalAnggota = (int)($r->fetch_assoc()['n'] ?? 0);

// Terlambat: belum kembali & > 7 hari dari tanggal pinjam
$r = $konek->query("
  SELECT COUNT(*) n
  FROM rekap
  WHERE (jam_kembali IS NULL OR jam_kembali='' OR jam_kembali='00:00:00')
    AND CURDATE() > DATE_ADD(tanggal, INTERVAL 7 DAY)
");
$terlambat = (int)($r->fetch_assoc()['n'] ?? 0);

/* =======================
   TREN 7 HARI (SUM jumlah per tanggal)
   ======================= */
$labels = [];
$data7  = [];
$map    = [];

// init 7 hari ke belakang (termasuk hari ini)
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $labels[] = date('d/m', strtotime($d));
    $map[$d] = 0;
}
// ambil data dari DB
$q = $konek->query("
  SELECT tanggal, COALESCE(SUM(jumlah),0) total
  FROM rekap
  WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY tanggal
  ORDER BY tanggal ASC
");
while ($row = $q->fetch_assoc()) {
    $tgl = $row['tanggal'];
    if (isset($map[$tgl])) $map[$tgl] = (int)$row['total'];
}
foreach ($map as $v) $data7[] = $v;

/* =======================
   PEMINJAMAN TERBARU
   ======================= */
$latest = $konek->query("
  SELECT
    r.id, r.nama, r.kelas, r.jumlah, r.tanggal, r.jam_pinjam, r.jam_kembali,
    p.nama AS judul_buku, p.buku AS isbn
  FROM rekap r
  LEFT JOIN pinjam p ON p.nokartu = r.kode_buku
  ORDER BY r.id DESC
  LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perpustakaan</title>

    <style>
        .card-stat .icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: grid;
            place-items: center;
        }

        .card-stat .value {
            font-size: 1.75rem;
            font-weight: 800;
        }

        .muted {
            color: #64748b;
        }

        /* kecilkan badge status agar tidak terlalu besar */
        .badge,
        .chip {
            font-size: .8rem;
            padding: .35rem .5rem;
            background: #0d6efd;
        }

        /* ketinggian kartu agar rata */
        .h-100 {
            height: 100%;
        }

        /* panel judul */
        .panel-title {
            font-weight: 600;
        }

        /* tabel */
        .table thead th {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <?php include "menu.php"; ?>

    <!-- Konten -->
    <div class="container-fluid py-4">

        <!-- Row: Stats -->
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-stat shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="muted small">Total Judul</div>
                            <div class="value"><?= number_format($totalJudul) ?></div>
                        </div>
                        <div class="icon bg-primary-subtle text-primary">
                            <i class="bi bi-journal-text fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-stat shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="muted small">Eksemplar Tersedia</div>
                            <div class="value"><?= number_format($totalTersedia) ?></div>
                        </div>
                        <div class="icon bg-success-subtle text-success">
                            <i class="bi bi-bookshelf fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-stat shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="muted small">Sedang Dipinjam</div>
                            <div class="value"><?= number_format($totalDipinjam) ?></div>
                        </div>
                        <div class="icon bg-warning-subtle text-warning">
                            <i class="bi bi-arrow-left-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-stat shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="muted small">Anggota</div>
                            <div class="value"><?= number_format($totalAnggota) ?></div>
                            <div class="small text-danger mt-1">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?= $terlambat ?> Terlambat
                            </div>
                        </div>
                        <div class="icon bg-info-subtle text-info">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row: Charts -->
        <div class="row g-3 mt-1">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <span class="panel-title">Tren Peminjaman 7 Hari</span>
                    </div>
                    <div class="card-body">
                        <canvas id="line7"></canvas>
                        <div class="small muted mt-2">*Jumlah peminjaman per hari (Σ eksemplar dipinjam)</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <span class="panel-title">Status Koleksi</span>
                        <span class="small muted ms-1">(lebih kecil sesuai permintaan)</span>
                    </div>
                    <div class="card-body">
                        <div style="max-width: 300px; margin: 0 auto;">
                            <canvas id="donut"></canvas>
                        </div>
                        <div class="text-center mt-2 small">
                            <span class="me-3"><i class="bi bi-circle-fill text-primary me-1"></i>Tersedia</span>
                            <span><i class="bi bi-circle-fill text-danger me-1"></i>Dipinjam</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row: Latest table -->
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <span class="panel-title">Peminjaman Terbaru</span>
                <a href="rekap.php" class="text-decoration-none small">Lihat semua <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Judul Buku</th>
                                <th>Jumlah</th>
                                <th>Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($latest && $latest->num_rows):
                                while ($r = $latest->fetch_assoc()):
                                    $returned = !empty($r['jam_kembali']) && $r['jam_kembali'] !== '00:00:00';
                                    $badge = $returned ? 'success' : 'warning';
                                    $statusText = $returned ? 'Kembali' : 'Dipinjam';
                            ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($r['nama'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($r['kelas'] ?: '-') ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($r['judul_buku'] ?: '-') ?></div>
                                            <div class="small muted">ISBN: <?= htmlspecialchars($r['isbn'] ?: '-') ?></div>
                                        </td>
                                        <td><?= (int)($r['jumlah'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars(($r['tanggal'] ?: '') . ' ' . ($r['jam_pinjam'] ?: '')) ?></td>
                                        <td><span class="badge text-bg-<?= $badge ?>"><?= $statusText ?></span></td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /container-fluid -->

    <?php include "footer.php"; ?>

    <script>
        // Donut kecil (Status Koleksi)
        document.addEventListener('DOMContentLoaded', function() {
            const dEl = document.getElementById('donut');
            if (dEl && typeof Chart !== 'undefined') {
                new Chart(dEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Tersedia', 'Dipinjam'],
                        datasets: [{
                            data: [<?= $totalTersedia ?>, <?= $totalDipinjam ?>]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        cutout: '70'
                    }
                });
            }

            // Line 7 hari
            const lEl = document.getElementById('line7');
            if (lEl && typeof Chart !== 'undefined') {
                new Chart(lEl, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Dipinjam',
                            data: <?= json_encode($data7) ?>,
                            fill: false,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>