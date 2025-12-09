<?php
session_start();

// Redirect jika sudah login (tetap seperti aslinya)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: login.php'); // ganti dengan halaman utama kamu
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars(trim($_POST['password']));

    // Hardcode login: admin / admin123 (tetap seperti aslinya)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header('Location: index.php'); // redirect ke menu utama
        exit();
    } else {
        $error_message = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Admin - Sekolah Dasar</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-600: #1d4ed8;
            --border: #e5e7eb;
            --danger: #ef4444;
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
            background:
                /* radial-gradient(1200px 600px at -20% -20%, #e0e7ff 0%, transparent 60%), */
                /* radial-gradient(900px 500px at 120% 20%, #ffffffff 0%, transparent 60%), */
                var(--bg);
            display: grid;
            place-items: center;
            color: var(--ink);
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
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(2, 8, 23, .12);
        }

        .brand h1 {
            font-size: 18px;
            margin: 0;
            line-height: 1.2;
        }

        .brand small {
            color: var(--muted)
        }

        .card-body {
            padding: 16px 22px 22px
        }

        .alert {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .field {
            margin-top: 12px
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .control {
            position: relative;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 44px 12px 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            font-size: 15px;
            outline: none;
            transition: .15s border-color, .15s box-shadow;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .toggle-eye {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            color: var(--muted);
            padding: 6px;
            border-radius: 8px;
        }

        .toggle-eye:hover {
            color: var(--primary-600);
        }

        .btn {
            width: 100%;
            padding: 12px 14px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            letter-spacing: .2px;
            cursor: pointer;
            transition: .15s background, .15s transform;
        }

        .btn:hover {
            background: var(--primary-600);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .helper {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer {
            padding: 12px 22px 20px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            border-top: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(248, 250, 252, .6), #fff);
        }

        /* small */
        @media (max-width:420px) {
            .card-header {
                padding: 18px 18px 8px
            }

            .card-body {
                padding: 12px 18px 18px
            }

            .footer {
                padding: 10px 18px 16px
            }
        }
    </style>
</head>

<body>

    <form class="card" method="POST" action="">
        <div class="card-header">
            <div class="brand">
                <img src="https://cdn-icons-png.flaticon.com/512/2942/2942206.png" alt="School Icon" />
                <div>
                    <h1>Login Admin Perpustakaan</h1>
                    <small>Sistem Peminjaman Buku SD</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($error_message)): ?>
                <div class="alert"><?= $error_message; ?></div>
            <?php endif; ?>

            <div class="field">
                <label for="username">Username</label>
                <div class="control">
                    <input type="text" id="username" name="username" placeholder="Masukkan Username" required />
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="control">
                    <input type="password" id="password" name="password" placeholder="Masukkan Password" required />
                    <button class="toggle-eye" type="button" aria-label="Tampilkan password" onclick="togglePass()">
                        👁️
                    </button>
                </div>
            </div>

            <div class="field" style="margin-top:16px">
                <button class="btn" type="submit">Masuk</button>
            </div>

            <!-- <div class="helper">
                <span>Tips:</span><span>admin / admin123</span>
            </div> -->
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> Perpustakaan SD Negeri Gemawang
        </div>
    </form>

    <script>
        function togglePass() {
            const el = document.getElementById('password');
            el.type = (el.type === 'password') ? 'text' : 'password';
        }
        // fokus otomatis ke username
        window.addEventListener('DOMContentLoaded', () => document.getElementById('username')?.focus());
    </script>
</body>

</html>