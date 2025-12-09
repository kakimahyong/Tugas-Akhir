<?php // menu.php 
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<!-- Bootstrap Icons (WAJIB agar ikon muncul) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Bootstrap JS Bundle (sudah termasuk Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<style>
    :root {
        --sidebar-width: 240px;
    }

    body {
        min-height: 100vh;
        background-color: #f8fafc;
    }

    .app-wrap {
        display: flex;
    }

    /* ==== SIDEBAR ==== */
    .sidebar {
        width: var(--sidebar-width);
        min-height: 100vh;
        position: sticky;
        top: 0;
        background: #0d6efd;
        color: #fff;
        font-size: 16px;
        /* ukuran teks seragam */
    }

    .sidebar .navbar-brand {
        font-size: 1.1rem;
        letter-spacing: .3px;
    }

    .sidebar hr {
        opacity: .25;
        margin: 0.75rem 0 1rem;
    }

    /* ==== ITEM MENU ==== */
    .sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        /* jarak ikon-teks */
        padding: 12px 18px;
        /* tinggi dan jarak seragam */
        font-size: 1rem;
        /* ukuran teks seragam */
        color: #e9f2ff;
        margin: 2px 6px;
        border-radius: 10px;
        transition: all 0.2s ease-in-out;
    }

    .sidebar .nav-link i {
        font-size: 1.2rem;
        width: 22px;
        /* agar ikon sejajar rapi */
        text-align: center;
    }

    .sidebar .nav-link span {
        flex: 1;
    }

    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .sidebar .mt-auto {
        font-size: 0.85rem;
        opacity: 0.7;
    }

    /* ==== KONTEN / TOPBAR ==== */
    .content {
        flex: 1;
    }

    .topbar {
        position: sticky;
        top: 0;
        z-index: 1020;
        backdrop-filter: blur(6px);
    }

    /* ==== RESPONSIVE ==== */
    @media (max-width: 991.98px) {
        .sidebar {
            position: fixed;
            transform: translateX(-100%);
            transition: .25s;
            z-index: 1040;
        }

        .sidebar.show {
            transform: none;
        }

        .content {
            margin-left: 0;
        }
    }

    @media (min-width: 992px) {
        .content {
            margin-left: var(--sidebar-width);
        }

        .sidebar {
            position: fixed;
            left: 0;
        }
    }
</style>

<div class="app-wrap">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar d-flex flex-column p-3">
        <a class="navbar-brand text-white fw-bold mb-3 d-flex align-items-center" href="login.php">
            <i class="bi bi-journal-bookmark me-2"></i> <span>PERPUSTAKAAN</span>
        </a>
        <hr class="border-light">

        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : ''; ?>" href="login.php">
                    <i class="bi bi-house-door"></i><span>Home</span>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'datapinjam.php' ? 'active' : ''; ?>" href="datapinjam.php">
                    <i class="bi bi-journals"></i><span>Daftar Buku</span>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'datamurid.php' ? 'active' : ''; ?>" href="datamurid.php">
                    <i class="bi bi-people"></i><span>Daftar Murid</span>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'rekap.php' ? 'active' : ''; ?>" href="rekap.php">
                    <i class="bi bi-clipboard-data"></i><span>Rekap Peminjaman</span>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'scan.php' ? 'active' : ''; ?>" href="scan.php">
                    <i class="bi bi-upc-scan"></i><span>Scan</span>
                </a>
            </li>
            <li>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                </a>
            </li>
        </ul>

        <div class="mt-auto text-white-50 small">© Perpustakaan</div>
    </nav>

    <!-- Konten -->
    <div class="content">
        <!-- Topbar -->
        <nav class="topbar navbar navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button class="btn btn-outline-primary d-lg-none" id="toggleSidebar" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-text ms-auto">
                    Admin • <i class="bi bi-person-circle"></i>
                </span>
            </div>
        </nav>

        <!-- Area Konten -->
        <div class="container-fluid py-4">