<?php
include "koneksi.php";

// cegah cache saat di-load tiap 1 detik
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$uid = '';
if ($res = $konek->query("SELECT nokartu FROM tmprfid LIMIT 1")) {
    if ($row = $res->fetch_assoc()) {
        $uid = strtoupper(trim($row['nokartu'] ?? ''));
    }
}
?>
<div class="form-group" style="max-width:320px">
    <label>Kode UID</label>
    <input type="text"
        name="nokartu"
        id="nokartu"
        class="form-control"
        placeholder="Tempelkan kartu..."
        value="<?php echo htmlspecialchars($uid, ENT_QUOTES); ?>"
        readonly>
    <small class="text-muted">
        <?php echo $uid === '' ? 'Belum ada kartu terbaca' : 'Kartu terbaca'; ?>
    </small>
</div>