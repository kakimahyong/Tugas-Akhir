<?php
// kirimkartu.php - terima ?nokartu=... dari NodeMCU dan simpan ke tmprfid
include "koneksi.php";

// Non-cache (berguna saat polling)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: text/plain; charset=utf-8');

// Pastikan parameter ada
if (!isset($_GET['nokartu']) || trim($_GET['nokartu']) === '') {
    http_response_code(400);
    echo "ERR:nokartu kosong";
    exit;
}

$nokartu = strtoupper(trim($_GET['nokartu'])); // normalisasi

// Mulai transaksi kecil (opsional, tapi aman)
$konek->begin_transaction();

try {
    // kosongkan tabel tmprfid
    $del = $konek->prepare("DELETE FROM tmprfid");
    $delOk = $del->execute();
    $del->close();

    // simpan nokartu dengan prepared statement
    $ins = $konek->prepare("INSERT INTO tmprfid (nokartu) VALUES (?)");
    $ins->bind_param("s", $nokartu);
    $ok = $ins->execute();
    $ins->close();

    if ($ok) {
        $konek->commit();
        // respon singkat agar NodeMCU mudah mengecek
        echo "OK";
    } else {
        $konek->rollback();
        http_response_code(500);
        echo "ERR:insert_failed";
    }
} catch (Throwable $e) {
    $konek->rollback();
    http_response_code(500);
    // jangan tampilkan error detail ke publik kecuali untuk debugging lokal
    echo "ERR:exception";
}
