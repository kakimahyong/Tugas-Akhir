<?php
// cetak_murid.php — versi rapi
require(__DIR__ . '/fpdf/fpdf.php');
include "koneksi.php";
date_default_timezone_set('Asia/Jakarta');

/* ===== Ambil filter kelas dari GET ===== */
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';

/* ===== Query data (pakai prepared untuk yang berfilter) ===== */
if ($kelas !== '') {
    $stmt = $konek->prepare("SELECT * FROM murid WHERE kelas = ? ORDER BY nama ASC, id DESC");
    $stmt->bind_param("s", $kelas);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $konek->query("SELECT * FROM murid ORDER BY kelas ASC, nama ASC, id DESC");
}

/* ===== PDF class dengan Header/Footer ===== */
class MyPDF extends FPDF
{
    public string $subTitle = '';

    function Header()
    {
        // Margin kiri-kanan default = 10mm, lebar area = 190mm
        // Judul
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(190, 10, 'DAFTAR MURID SD', 0, 1, 'C');

        // Subjudul (kelas & waktu cetak)
        $this->SetFont('Arial', '', 10);
        $this->Cell(95, 7, $this->subTitle, 0, 0, 'L');
        $this->Cell(95, 7, 'Dicetak: ' . date('d M Y, H:i'), 0, 1, 'R');

        // Spasi kecil
        $this->Ln(2);

        // Header tabel
        $this->SetFillColor(15, 23, 42);    // #0f172a
        $this->SetTextColor(255, 255, 255); // putih
        $this->SetDrawColor(210, 214, 220); // garis lembut

        $cols = [12, 32, 32, 66, 22, 26]; // total = 190
        $labels = ['No', 'No Kartu', 'Kode Murid', 'Nama', 'Kelas', 'Foto'];

        $this->SetFont('Arial', 'B', 11);
        foreach ($labels as $i => $lab) {
            $this->Cell($cols[$i], 9, $lab, 1, 0, 'C', true);
        }
        $this->Ln();

        // Reset warna teks ke hitam
        $this->SetTextColor(0, 0, 0);
    }

    function Footer()
    {
        // Posisi 15mm dari bawah
        $this->SetY(-15);
        $this->SetDrawColor(230, 230, 230);
        $this->Line(10, $this->GetY(), 200, $this->GetY());

        $this->SetFont('Arial', 'I', 9);
        $this->Cell(95, 8, 'Perpustakaan SD Negeri Gemawang', 0, 0, 'L');
        $this->Cell(95, 8, 'Hal. ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

/* ===== Inisialisasi PDF ===== */
$pdf = new MyPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 18); // auto break dengan margin bawah 18mm
$pdf->subTitle = ($kelas !== '') ? ('Kelas: ' . $kelas) : 'Semua Kelas';
$pdf->AddPage();

$pdf->SetFont('Arial', '', 10);
$pdf->SetDrawColor(210, 214, 220);
$cols = [12, 32, 32, 66, 22, 26]; // width tiap kolom

/* ===== Tabel isi ===== */
$no = 1;
$rowH = 24;            // tinggi baris tetap -> tabel seragam
$padX = 2.5;           // padding horizontal teks
$fotoW = 16;           // lebar foto
$fotoH = 20;           // tinggi foto

if ($result && $result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // simpan posisi awal baris
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();

        // Kolom 1: No
        $pdf->Cell($cols[0], $rowH, $no++, 1, 0, 'C');

        // Kolom 2: No Kartu
        $pdf->Cell($cols[1], $rowH, $data['nokartu'], 1, 0, 'C');

        // Kolom 3: Kode Murid
        $pdf->Cell($cols[2], $rowH, $data['kode_murid'], 1, 0, 'C');

        // Kolom 4: Nama (rata kiri, beri padding manual)
        $pdf->Cell($cols[3], $rowH, ' ', 1, 0); // bingkai dulu
        $xNama = $pdf->GetX() - $cols[3] + $padX;
        $yNama = $yStart + 6; // sedikit turun biar tengah
        $pdf->SetXY($xNama, $yNama);
        $pdf->Cell($cols[3] - 2 * $padX, 6, $data['nama'], 0, 0, 'L');
        $pdf->SetXY($xStart + $cols[0] + $cols[1] + $cols[2] + $cols[3], $yStart);

        // Kolom 5: Kelas
        $pdf->Cell($cols[4], $rowH, $data['kelas'], 1, 0, 'C');

        // Kolom 6: Foto (frame + gambar di tengah)
        $pdf->Cell($cols[5], $rowH, '', 1, 1, 'C');
        $fotoFile = $data['foto'];
        if (!empty($fotoFile) && file_exists("uploads/foto_murid/" . $fotoFile)) {
            $fotoPath = "uploads/foto_murid/" . $fotoFile;
        } else {
            $fotoPath = "uploads/default.png";
        }

        // hitung posisi agar foto center di kolom foto
        $xFotoCellLeft = 10 + $cols[0] + $cols[1] + $cols[2] + $cols[3] + $cols[4];
        $yFotoCellTop  = $yStart;
        $xFoto = $xFotoCellLeft + ($cols[5] - $fotoW) / 2;
        $yFoto = $yFotoCellTop  + ($rowH  - $fotoH) / 2;

        // gambar foto (jika file valid)
        if (is_file($fotoPath)) {
            $pdf->Image($fotoPath, $xFoto, $yFoto, $fotoW, $fotoH);
        }
    }
} else {
    $pdf->Cell(array_sum($cols), 10, 'Tidak ada data.', 1, 1, 'C');
}

/* ===== Output ===== */
$pdf->Output('I', 'Daftar_Murid.pdf');

/* ===== Bersih-bersih ===== */
if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
