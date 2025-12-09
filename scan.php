<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include "header.php"; ?>
    <title>Scan Kartu</title>

    <!-- Scanning membaca kartu -->
    <script type="text/javascript">
        $(document).ready(function() {
            setInterval(function() {
                $("#cekkartu").load('bacakartu.php');
            }, 1000);
        });
    </script>

    <style>
        body {
            background-color: #f8fafc;
        }

        .scan-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 120px);
            padding: 20px;
        }

        .card-scan {
            background: #fff;
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.07);
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 40px 30px;
            position: relative;
        }

        .scan-icon {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 38px;
            margin: 0 auto 20px;
        }

        .scan-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .scan-desc {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 24px;
        }

        .loading-spinner {
            border: 4px solid #e2e8f0;
            border-top: 4px solid #2563eb;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .status-text {
            font-size: 0.9rem;
            color: #475569;
        }

        @media (max-width: 768px) {
            .card-scan {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <?php include "menu.php"; ?>

    <div class="scan-container">
        <div class="card-scan">
            <div class="scan-icon">
                <i class="bi bi-wifi"></i>
            </div>
            <h2 class="scan-title">Scan Kartu Anda</h2>
            <p class="scan-desc">Tempelkan kartu RFID ke pembaca untuk melanjutkan proses.</p>

            <div id="cekkartu">
                <div class="loading-spinner"></div>
                <div class="status-text">Menunggu kartu terbaca...</div>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>

</html>