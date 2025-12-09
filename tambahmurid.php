<?php
include "koneksi.php";

/* ============ Utils ============ */
function is_allowed_lan_url($url)
{
    $p = @parse_url($url);
    if (!$p || !isset($p['scheme'], $p['host'])) return false;
    if (!in_array(strtolower($p['scheme']), ['http', 'https'])) return false;
    $h = $p['host'];
    if (preg_match('/^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $h)) return true;
    if (preg_match('/^192\.168\.\d{1,3}\.\d{1,3}$/', $h)) return true;
    if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\.\d{1,3}\.\d{1,3}$/', $h)) return true;
    return false;
}
function ensure_dir($p)
{
    if (!is_dir($p)) @mkdir($p, 0777, true);
}

/* ============ AJAX SNAPSHOT (server fetch /capture, simpan TMP) ============ */
if (isset($_GET['action']) && $_GET['action'] === 'snap') {
    header('Content-Type: application/json');
    $base = isset($_GET['base']) ? trim($_GET['base']) : 'http://10.94.217.45';
    if (preg_match('/^https?:\/[^\/]/i', $base)) $base = preg_replace('/^https?:\//i', 'http://', $base);
    if (!preg_match('/^https?:\/\//i', $base)) $base = 'http://' . $base;
    $base = rtrim($base, '/');
    $cap  = $base . '/capture';

    if (!is_allowed_lan_url($cap)) {
        echo json_encode(['ok' => false, 'msg' => 'URL kamera tidak valid.']);
        exit;
    }

    $ctx = stream_context_create([
        'http' => ['timeout' => 8],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    $img = @file_get_contents($cap, false, $ctx);
    if ($img === false || strlen($img) < 1000) {
        echo json_encode(['ok' => false, 'msg' => 'Gagal mengambil foto dari kamera.']);
        exit;
    }

    ensure_dir('uploads/tmp');
    $tmpName = 'snap_' . time() . '_' . mt_rand(1000, 9999) . '.jpg';
    if (@file_put_contents('uploads/tmp/' . $tmpName, $img) === false) {
        echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan file sementara.']);
        exit;
    }

    echo json_encode(['ok' => true, 'file' => $tmpName, 'preview' => 'data:image/jpeg;base64,' . base64_encode($img)]);
    exit;
}

/* ============ SUBMIT FORM (pindah dari TMP -> foto_murid) ============ */
if (isset($_POST['btnKirim'])) {
    $nokartu = strtoupper(trim(str_replace(' ', '', $_POST['nokartu'] ?? '')));
    $nama    = $_POST['nama'];
    $kelas   = $_POST['kelas'];
    $tmpFile = $_POST['snap_tmpfile'] ?? '';
    $fotoUp  = $_FILES['foto']['name'] ?? '';

    ensure_dir('uploads/foto_murid');
    $namaFile = null;

    // Upload manual (jika ada)
    if (!empty($fotoUp)) {
        $tmp = $_FILES['foto']['tmp_name'];
        $namaFile = time() . '_' . basename($fotoUp);
        if (!@move_uploaded_file($tmp, 'uploads/foto_murid/' . $namaFile)) $namaFile = null;
    }

    // Pakai file TMP hasil Tangkap (tanpa jepret ulang)
    if ($namaFile === null && $tmpFile) {
        $src = 'uploads/tmp/' . basename($tmpFile);
        if (is_file($src) && filesize($src) > 1000) {
            $namaFile = time() . '_esp32.jpg';
            $dst = 'uploads/foto_murid/' . $namaFile;
            if (!@rename($src, $dst)) {
                @copy($src, $dst) ? @unlink($src) : $namaFile = null;
            }
        }
    }

    // kode murid otomatis
    $q = mysqli_query($konek, "SELECT MAX(id) AS idTerbesar FROM murid");
    $d = mysqli_fetch_array($q);
    $kode_murid = 'MURID' . str_pad(((int)$d['idTerbesar'] + 1), 3, '0', STR_PAD_LEFT);

    $simpan = mysqli_query($konek, "
        INSERT INTO murid (kode_murid,nama,kelas,nokartu,foto)
        VALUES ('$kode_murid','$nama','$kelas','$nokartu'," . ($namaFile ? "'$namaFile'" : "NULL") . ")
    ");

    if ($simpan) {
        mysqli_query($konek, "DELETE FROM tmprfid");
        echo "<script>alert('✅ Data murid berhasil disimpan!');location.replace('datamurid.php');</script>";
        exit;
    } else {
        echo "<script>alert('❌ Gagal menyimpan data murid!');location.replace('datamurid.php');</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?php include "header.php"; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Murid</title>
    <style>
        body {
            background: #f8fafc
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
            border: none
        }

        h3 {
            font-weight: 600;
            color: #1e293b
        }

        .form-label {
            font-weight: 500;
            color: #334155
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            transition: .2s
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .2)
        }

        .btn-success {
            background: #16a34a;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 500
        }

        .btn-success:hover {
            background: #15803d
        }

        .btn-secondary {
            border-radius: 10px;
            padding: 10px 18px
        }

        .esp32-preview {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            padding: 12px
        }

        .hint {
            font-size: 12px;
            color: #64748b
        }
    </style>
</head>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        async function loadUID() {
            try {
                const r = await fetch('nokartu.php', {
                    cache: 'no-store'
                });
                const html = await r.text();
                const mount = document.getElementById('norfid');
                if (mount) mount.innerHTML = html; // nokartu.php meng-output <input name="nokartu" ...>
            } catch (e) {
                /* diamkan agar tidak ganggu */
            }
        }
        loadUID();
        setInterval(loadUID, 1000);
    });
</script>


<body>
    <?php include "menu.php"; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-center">
            <div class="card p-4" style="max-width:520px;width:100%;">
                <div class="text-center mb-4">
                    <div class="card-icon mb-2"><i class="bi bi-person-plus"></i></div>
                    <h3>Tambah Data Murid SD</h3>
                    <p class="text-muted mb-0">Pindai kartu RFID, lalu isi data murid dengan lengkap</p>
                </div>

                <form method="POST" enctype="multipart/form-data" id="formMurid">
                    <!-- UID -->
                    <div id="norfid" class="mb-3">
                        <label class="form-label">Kode UID</label>
                        <input type="text" name="nokartu" id="nokartu" class="form-control" placeholder="Tempelkan kartu..." readonly>
                        <small class="text-muted">Belum ada kartu terbaca</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Murid</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="kelas" class="form-control" placeholder="Contoh: 6A, 5B" required>
                    </div>

                    <!-- ===== ESP32-CAM ===== -->
                    <div class="mb-3 esp32-preview">
                        <label class="form-label">Ambil Foto via ESP32-CAM</label>

                        <div class="d-flex gap-2 mb-2">
                            <input type="text" id="esp32_host" class="form-control" value="http://10.94.217.45" placeholder="http://10.94.217.45">
                            <button type="button" id="btnShowStream" class="btn btn-secondary"><i class="bi bi-camera-video"></i> Tampilkan</button>
                            <button type="button" id="btnStopStream" class="btn btn-outline-danger"><i class="bi bi-stop-circle"></i> Matikan Stream</button>
                            <button type="button" id="btnSnap" class="btn btn-success"><i class="bi bi-camera"></i> Tangkap Foto</button>
                        </div>

                        <div class="ratio ratio-16x9" style="background:#f1f5f9;border-radius:10px;overflow:hidden;">
                            <img id="esp32Stream" alt="Stream ESP32-CAM" style="width:100%;height:100%;object-fit:cover; display:none;">
                            <div id="streamPlaceholder" class="d-flex align-items-center justify-content-center h-100 text-muted">
                                Masukkan IP lalu klik <b class="ms-1">Tampilkan</b>
                            </div>
                        </div>

                        <div class="mt-3">
                            <input type="hidden" name="snap_tmpfile" id="snap_tmpfile" value="">
                            <label class="form-label mb-1">Hasil Foto</label>
                            <div class="ratio ratio-16x9" style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;overflow:hidden;">
                                <img id="snapPreview" alt="Snapshot" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                            <div class="hint mt-1">Klik <b>Tangkap Foto</b> untuk memotret. Saat <b>Simpan Data</b>, sistem memakai foto yang sudah tertangkap (tanpa jepret ulang). Stream diputus otomatis saat meninggalkan halaman.</div>
                        </div>
                    </div>
                    <!-- ===== /ESP32-CAM ===== -->

                    <!-- Upload manual -->
                    <div class="mb-4">
                        <label class="form-label">Atau unggah file foto (opsional)</label>
                        <input type="file" name="foto" accept="image/*" class="form-control">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="datamurid.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <button class="btn btn-success" name="btnKirim"><i class="bi bi-save2"></i> Simpan Data</button>
                    </div>
                </form>

                <div class="mt-3 text-muted small">* Kartu RFID akan terbaca otomatis (jika script polling diaktifkan).</div>
            </div>
        </div>
    </div>

    <script>
        const hostInput = document.getElementById('esp32_host');
        const btnShow = document.getElementById('btnShowStream');
        const btnStop = document.getElementById('btnStopStream');
        let imgStream = document.getElementById('esp32Stream'); // akan diganti saat stop
        const streamPh = document.getElementById('streamPlaceholder');
        const btnSnap = document.getElementById('btnSnap');
        const snapPrev = document.getElementById('snapPreview');
        const snapTmp = document.getElementById('snap_tmpfile');

        let stoppingStream = false;

        function normalizeHost(h) {
            if (!h) return "";
            h = h.trim();
            if (/^https?:\/[^/]/i.test(h)) h = h.replace(/^https?:\//i, 'http://');
            if (!/^https?:\/\//i.test(h)) h = 'http://' + h;
            return h.replace(/\/+$/, '');
        }

        function warnIfHttpsPage(base) {
            if (location.protocol === 'https:' && base.startsWith('http://')) {
                alert('Halaman via HTTPS, kamera via HTTP. Buka lewat http:// agar tidak diblokir.');
            }
        }

        /* Putus koneksi total */
        function stopStream() {
            stoppingStream = true;
            try {
                imgStream.onerror = null;
                imgStream.src = 'about:blank';
                const fresh = imgStream.cloneNode(false);
                fresh.style.display = 'none';
                fresh.alt = 'Stream ESP32-CAM';
                imgStream.parentNode.replaceChild(fresh, imgStream);
                imgStream = fresh;
            } finally {
                streamPh.style.display = 'flex';
                imgStream.style.display = 'none';
                setTimeout(() => {
                    stoppingStream = false;
                }, 200);
            }
        }

        /* Minta kamera reset stream (lihat firmware) */
        async function resetCameraStream(base) {
            try {
                await fetch(base + '/resetstream', {
                    cache: 'no-store'
                });
            } catch (e) {}
        }

        /* Start stream baru */
        async function startStream(base) {
            if (stoppingStream) return;
            await resetCameraStream(base);
            const url = base + ':81/stream?ts=' + Date.now();
            imgStream.onerror = () => {
                if (stoppingStream) return;
                alert('❌ Gagal memuat stream. Coba buka langsung: ' + url);
                stopStream();
            };
            imgStream.src = url;
            imgStream.style.display = 'block';
            streamPh.style.display = 'none';
        }

        /* Tombol */
        btnShow?.addEventListener('click', async () => {
            const base = normalizeHost(hostInput.value || 'http://10.94.217.45');
            warnIfHttpsPage(base);
            await startStream(base);
        });
        btnStop?.addEventListener('click', stopStream);

        /* Putus otomatis saat keluar / tab disembunyikan / bfcache */
        window.addEventListener('beforeunload', stopStream);
        window.addEventListener('pagehide', stopStream);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) stopStream();
        });

        /* Tombol Tangkap → server ambil /capture (tanpa CORS) */
        btnSnap?.addEventListener('click', async () => {
            const base = normalizeHost(hostInput.value || 'http://10.94.217.45');
            try {
                const r = await fetch(`tambahmurid.php?action=snap&base=${encodeURIComponent(base)}`, {
                    cache: 'no-store'
                });
                const j = await r.json();
                if (!j.ok) throw new Error(j.msg || 'Gagal ambil foto');
                snapPrev.src = j.preview;
                snapTmp.value = j.file;
            } catch (e) {
                alert('❌ Tangkap gagal: ' + e.message);
            }
        });
    </script>

    <?php include "footer.php"; ?>
</body>

</html>