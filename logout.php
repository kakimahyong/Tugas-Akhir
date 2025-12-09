<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_destroy();              // hapus semua data sesi
    header('Location: index.php');  // kembali ke halaman awal/login
    exit();
}

// jika belum login, arahkan balik (tidak mengubah alurmu)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Konfirmasi Logout - Sekolah Dasar</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --danger: #ef4444;
            --danger-600: #dc2626;
            --border: #e5e7eb;
            --ring: rgba(37, 99, 235, .25);
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial, sans-serif;
            color: var(--ink);
            display: grid;
            place-items: center;
            background:
                /* radial-gradient(1200px 600px at -20% -20%, #e0e7ff 0%, transparent 60%),
                radial-gradient(900px 500px at 120% 20%, #fee2e2 0%, transparent 60%), */
                var(--bg);
        }

        .card {
            width: min(420px, 92vw);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(2, 8, 23, .08);
            overflow: hidden;
        }

        .card-header {
            padding: 22px 22px 10px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #fef2f2;
            display: grid;
            place-items: center;
        }

        .icon span {
            font-size: 24px;
            color: var(--danger-600)
        }

        .title {
            margin: 0;
            font-size: 18px;
            line-height: 1.2
        }

        .subtitle {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 13px
        }

        .card-body {
            padding: 16px 22px 22px
        }

        .question {
            margin: 6px 0 16px;
            font-weight: 600;
        }

        .btn-row {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            flex: 1;
            padding: 12px 14px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: .15s transform, .15s filter;
        }

        .btn:active {
            transform: translateY(1px)
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-danger:hover {
            filter: brightness(.95)
        }

        .btn-ghost {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            filter: brightness(.98)
        }

        .footer {
            padding: 10px 22px 18px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            border-top: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(248, 250, 252, .6), #fff);
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="card-header">
            <div class="icon"><span>🚪</span></div>
            <div>
                <h1 class="title">Konfirmasi Logout</h1>
                <p class="subtitle">Keluar dari sesi admin perpustakaan</p>
            </div>
        </div>

        <div class="card-body">
            <p class="question">Apakah Anda yakin ingin keluar sekarang?</p>

            <div class="btn-row">
                <!-- Tetap gunakan POST ke logout.php (sesuai sistemmu) -->
                <form method="POST" action="logout.php" style="flex:1">
                    <button type="submit" class="btn btn-danger">Iya, Logout</button>
                </form>

                <!-- Tombol batal: tetap arahkan ke login.php seperti kodenya -->
                <button class="btn btn-ghost" onclick="window.location.href='login.php'">Tidak, Kembali</button>
            </div>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> Perpustakaan SD Negeri Gemawang
        </div>
    </div>

</body>

</html>