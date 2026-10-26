<?php
require_once __DIR__ . "/../config/database.php";

function hitungData($conn, string $tabel): int
{
    // Nama tabel hanya diambil dari daftar tetap di bawah.
    $daftarTabel = ["gejala", "kondisi_kulit", "rules"];

    if (!in_array($tabel, $daftarTabel, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$tabel`");
    return (int) $result->fetch_assoc()["total"];
}

$totalGejala = hitungData($conn, "gejala");
$totalKondisi = hitungData($conn, "kondisi_kulit");
$totalAturan = hitungData($conn, "rules");

$judulHalaman = "Dashboard";
require __DIR__ . "/header.php";
?>

<h1>Dashboard Admin</h1>
<p>Selamat datang di halaman pengelolaan sistem pakar SkinExpert.</p>

<div class="grid">
    <section class="card">
        <p>Total Gejala</p>
        <div class="angka"><?= $totalGejala ?></div>
        <a class="btn" href="gejala.php">Kelola Gejala</a>
    </section>

    <section class="card">
        <p>Total Kondisi Kulit</p>
        <div class="angka"><?= $totalKondisi ?></div>
        <a class="btn" href="kondisi.php">Kelola Kondisi</a>
    </section>

    <section class="card">
        <p>Total Aturan</p>
        <div class="angka"><?= $totalAturan ?></div>
        <a class="btn" href="aturan.php">Kelola Aturan</a>
    </section>
</div>

<section class="card">
    <h3>Informasi Sistem</h3>
    <p>Sistem menggunakan metode <strong>Forward Chaining</strong>.</p>
    <p>Aturan inferensi disimpan di database, sehingga aturan dapat dikelola tanpa mengubah kode mesin inferensi.</p>
    <p><strong>Catatan:</strong> hasil SkinExpert hanya untuk identifikasi awal dan bukan pengganti diagnosis dokter.</p>
</section>

<?php require __DIR__ . "/footer.php"; ?>
