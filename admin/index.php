<?php
require_once __DIR__ . "/../config/database.php";

function hitungData($conn, string $tabel): int
{
    $daftarTabel = ["gejala", "kondisi_kulit", "rules", "pemeriksaan"];

    if (!in_array($tabel, $daftarTabel, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$tabel`");
    return $result ? (int) $result->fetch_assoc()["total"] : 0;
}

$totalGejala = hitungData($conn, "gejala");
$totalKondisi = hitungData($conn, "kondisi_kulit");
$totalAturan = hitungData($conn, "rules");
$totalPemeriksaan = hitungData($conn, "pemeriksaan");

$judulHalaman = "Dashboard";
require __DIR__ . "/header.php";
?>

<h1>🌿 Dashboard Knowledge Base Admin</h1>
<p>Pengelolaan basis pengetahuan sistem pakar SkinExpert berbasis inferensi <strong>Backward Chaining</strong>.</p>

<div class="grid">
    <section class="card">
        <p><strong>Fakta Gejala (F)</strong></p>
        <div class="angka"><?= $totalGejala ?></div>
        <a class="btn" href="gejala.php">Kelola Fakta (F)</a>
    </section>

    <section class="card">
        <p><strong>Masalah / Goal (M)</strong></p>
        <div class="angka"><?= $totalKondisi ?></div>
        <a class="btn" href="kondisi.php">Kelola Masalah (M)</a>
    </section>

    <section class="card">
        <p><strong>Basis Aturan (R)</strong></p>
        <div class="angka"><?= $totalAturan ?></div>
        <a class="btn" href="aturan.php">Kelola Aturan (R)</a>
    </section>

    <section class="card">
        <p><strong>Riwayat Konsultasi</strong></p>
        <div class="angka"><?= $totalPemeriksaan ?></div>
        <a class="btn" href="../riwayat.php">Lihat Riwayat</a>
    </section>
</div>

<section class="card" style="margin-top: 15px;">
    <h3>Metodologi Inferensi: Backward Chaining (Goal-Driven Reasoning)</h3>
    <p>
        Sistem bekerja secara <em>top-down</em> dari kesimpulan / hipotesis kondisi kulit (<strong>Masalah M</strong>) menuju premis prasyarat (<strong>Fakta F</strong>) melalui aturan implikasi Horn clause:
    </p>
    <div style="background: #1e293b; color: #38bdf8; padding: 12px 18px; border-radius: 8px; font-family: monospace; font-size: 15px; margin: 12px 0;">
        (P₁ ∧ P₂ ∧ ... ∧ Pₙ) → Q &nbsp;&nbsp;|&nbsp;&nbsp; Modus Ponens: {P₁...Pₙ}, {(P₁...Pₙ) → Q} ⊢ Q
    </div>
    <p>Semua aturan dan relasi tersimpan secara dinamis di database sehingga pakar medis dapat menambah atau memodifikasi aturan kapan saja.</p>
</section>

<?php require __DIR__ . "/footer.php"; ?>
