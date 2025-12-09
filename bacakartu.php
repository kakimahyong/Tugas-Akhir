<?php
session_start();
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

// Non-cache utk polling
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Ambil mode dari tabel status (1=Pinjam, 2=Kembali)
$mode_pinjam = 1;
if ($res = $konek->query("SELECT mode FROM status LIMIT 1")) {
    if ($row = $res->fetch_assoc()) $mode_pinjam = (int)$row['mode'];
}
$modeText = ($mode_pinjam === 2) ? "Kembali" : "Pinjam";

// Ambil nomor kartu terbaru dari tmprfid
$nokartu = "";
if ($rt = $konek->query("SELECT nokartu FROM tmprfid LIMIT 1")) {
    if ($rowt = $rt->fetch_assoc()) $nokartu = trim($rowt['nokartu']);
}

// Normalisasi UID
$nokartu = strtoupper(preg_replace('/\s+/', '', $nokartu));

?>
<div class="container-fluid" style="text-align:center;">
    <?php if ($nokartu === ""): ?>
        <h3><?php echo $modeText; ?></h3>
        <h3>Silakan Scan Kartu di Sini...</h3>
        <img src="images/logo.png" style="width:350px"><br>
        <img src="images/Book.gif" alt="">
    <?php
    else:
        // --- Deteksi kartu ini: siswa atau buku? ---

        // 1) Coba sebagai kartu siswa
        $stmt = $konek->prepare("SELECT nokartu, kode_murid, nama, kelas FROM murid WHERE UPPER(REPLACE(REPLACE(REPLACE(nokartu,' ',''),CHAR(13),''),CHAR(10),'')) = ? LIMIT 1");
        $stmt->bind_param("s", $nokartu);
        $stmt->execute();
        $murid = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($murid) {
            // Simpan ke session agar bisa scan beberapa buku
            $_SESSION['peminjam'] = [
                'nokartu' => $murid['nokartu'],
                'noinduk' => $murid['kode_murid'],
                'nama'    => $murid['nama'],
                'kelas'   => $murid['kelas'],
                'set_at'  => time()
            ];

            echo "<h1>Anggota terdeteksi</h1>
                  <h2>" . htmlspecialchars($murid['nama']) . " (" . htmlspecialchars($murid['kode_murid']) . ")</h2>
                  <p>Silakan <b>scan kartu buku</b> untuk " . ($mode_pinjam === 1 ? "meminjam" : "mengembalikan") . ".</p>";

            // Hapus dari tmprfid agar siap baca berikutnya
            $konek->query("DELETE FROM tmprfid");
        } else {
            // 2) Coba sebagai kartu buku
            $stmt = $konek->prepare("SELECT id, nokartu, nama AS judul, kelas AS penulis, buku AS isbn, stok FROM pinjam WHERE UPPER(REPLACE(REPLACE(REPLACE(nokartu,' ',''),CHAR(13),''),CHAR(10),'')) = ? LIMIT 1");
            $stmt->bind_param("s", $nokartu);
            $stmt->execute();
            $buku = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$buku) {
                echo "<h1>Kartu tidak terdaftar sebagai anggota maupun buku.</h1>";
                $konek->query("DELETE FROM tmprfid");
            } else {
                // --- Kartu buku terdeteksi ---
                $tanggal = date('Y-m-d');
                $jam     = date('H:i:s');

                if ($mode_pinjam === 1) {
                    // ===== MODE PINJAM =====
                    if (empty($_SESSION['peminjam'])) {
                        echo "<h1>Silakan scan kartu anggota terlebih dahulu.</h1>";
                        $konek->query("DELETE FROM tmprfid");
                    } else {
                        $p = $_SESSION['peminjam'];

                        // Kadaluarsa session (5 menit)
                        if (time() - ($p['set_at'] ?? 0) > 300) {
                            unset($_SESSION['peminjam']);
                            echo "<h1>Sesi anggota kedaluwarsa. Silakan scan ulang kartu anggota.</h1>";
                            $konek->query("DELETE FROM tmprfid");
                        } else {
                            // Cek stok buku
                            if ((int)$buku['stok'] <= 0) {
                                echo "<h1>Stok buku <i>" . htmlspecialchars($buku['judul']) . "</i> habis.</h1>";
                                $konek->query("DELETE FROM tmprfid");
                            } else {
                                // Cegah buku dipinjam dua kali
                                $stmt = $konek->prepare("SELECT id FROM rekap WHERE UPPER(REPLACE(REPLACE(REPLACE(kode_buku,' ',''),CHAR(13),''),CHAR(10),'')) = ? AND (jam_kembali IS NULL OR jam_kembali='') LIMIT 1");
                                $stmt->bind_param("s", $buku['nokartu']);
                                $stmt->execute();
                                $open = $stmt->get_result()->fetch_assoc();
                                $stmt->close();

                                if ($open) {
                                    echo "<h1>Buku ini masih dipinjam dan belum dikembalikan.</h1>";
                                    $konek->query("DELETE FROM tmprfid");
                                } else {
                                    $jumlah = 1;
                                    // Insert ke rekap
                                    $stmt = $konek->prepare("INSERT INTO rekap (nokartu, noinduk, nama, kelas, kode_buku, jumlah, tanggal, jam_pinjam) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->bind_param("ssssisss", $p['nokartu'], $p['noinduk'], $p['nama'], $p['kelas'], $buku['nokartu'], $jumlah, $tanggal, $jam);

                                    if ($stmt->execute()) {
                                        $stmt->close();
                                        // Kurangi stok
                                        $stmtU = $konek->prepare("UPDATE pinjam SET stok = stok - 1 WHERE id = ? AND stok > 0");
                                        $stmtU->bind_param("i", $buku['id']);
                                        $stmtU->execute();
                                        $stmtU->close();

                                        echo "<h1>Peminjaman Berhasil</h1>
                                              <h2>" . htmlspecialchars($p['nama']) . " &mdash; <i>" . htmlspecialchars($buku['judul']) . "</i></h2>
                                              <p>Silakan scan buku lain untuk anggota ini, atau scan kartu anggota baru.</p>";
                                    } else {
                                        echo "<h1>Gagal menyimpan data peminjaman.</h1>";
                                        $stmt->close();
                                    }
                                    $konek->query("DELETE FROM tmprfid");
                                }
                            }
                        }
                    }
                } else {
                    // ===== MODE KEMBALI =====
                    $stmt = $konek->prepare("SELECT id, noinduk, nama, kelas, COALESCE(jumlah,0) AS jumlah FROM rekap WHERE UPPER(REPLACE(REPLACE(REPLACE(kode_buku,' ',''),CHAR(13),''),CHAR(10),'')) = ? AND (jam_kembali IS NULL OR jam_kembali='') ORDER BY id DESC LIMIT 1");
                    $stmt->bind_param("s", $buku['nokartu']);
                    $stmt->execute();
                    $rek = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$rek) {
                        echo "<h1>Tidak ada transaksi aktif untuk buku ini.</h1>
                              <p><i>" . htmlspecialchars($buku['judul']) . "</i> belum tercatat sedang dipinjam.</p>";
                        $konek->query("DELETE FROM tmprfid");
                    } else {
                        $jumlahKembali = max(0, (int)$rek['jumlah']);
                        $konek->begin_transaction();

                        try {
                            $stmt1 = $konek->prepare("UPDATE rekap SET jam_kembali = ? WHERE id = ?");
                            $stmt1->bind_param("si", $jam, $rek['id']);
                            if (!$stmt1->execute()) throw new Exception("Gagal update jam_kembali");
                            $stmt1->close();

                            $stmt2 = $konek->prepare("UPDATE pinjam SET stok = GREATEST(0, stok + ?) WHERE id = ?");
                            $stmt2->bind_param("ii", $jumlahKembali, $buku['id']);
                            if (!$stmt2->execute()) throw new Exception("Gagal update stok");
                            $stmt2->close();

                            $konek->commit();

                            echo "<h1>Pengembalian Berhasil</h1>
                                  <h2><i>" . htmlspecialchars($buku['judul']) . "</i></h2>
                                  <p>Dipinjam oleh: " . htmlspecialchars($rek['nama']) . " (" . htmlspecialchars($rek['noinduk']) . ")</p>
                                  <p>Jumlah dikembalikan: <b>" . (int)$jumlahKembali . "</b></p>";
                        } catch (Throwable $e) {
                            $konek->rollback();
                            echo "<h1>Gagal memperbarui pengembalian.</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
                        }

                        $konek->query("DELETE FROM tmprfid");
                    }
                }
            }
        }
    endif; ?>
</div>