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

<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
    <div style="color:var(--primary);"><?= icon('leaf', '', 28) ?></div>
    <h1 style="margin:0;">Dashboard Knowledge Base Admin</h1>
</div>
<p style="color:var(--text-muted);margin-bottom:24px;">Pengelolaan basis pengetahuan sistem pakar SkinExpert berbasis inferensi <strong>Backward Chaining</strong>.</p>

<div class="grid">
    <section class="card">
        <p style="display:flex;align-items:center;gap:6px;font-weight:600;"><?= icon('search', '', 16) ?> Fakta Gejala (F/G)</p>
        <div class="angka"><?= $totalGejala ?></div>
        <a class="btn" href="gejala.php"><?= icon('edit', '', 14) ?> Kelola Fakta (F)</a>
    </section>

    <section class="card">
        <p style="display:flex;align-items:center;gap:6px;font-weight:600;"><?= icon('target', '', 16) ?> Masalah / Goal (M/H)</p>
        <div class="angka"><?= $totalKondisi ?></div>
        <a class="btn" href="kondisi.php"><?= icon('edit', '', 14) ?> Kelola Masalah (M)</a>
    </section>

    <section class="card">
        <p style="display:flex;align-items:center;gap:6px;font-weight:600;"><?= icon('rule', '', 16) ?> Basis Aturan (R)</p>
        <div class="angka"><?= $totalAturan ?></div>
        <a class="btn" href="aturan.php"><?= icon('edit', '', 14) ?> Kelola Aturan (R)</a>
    </section>

    <section class="card">
        <p style="display:flex;align-items:center;gap:6px;font-weight:600;"><?= icon('clipboard', '', 16) ?> Riwayat Konsultasi</p>
        <div class="angka"><?= $totalPemeriksaan ?></div>
        <a class="btn" href="../riwayat.php"><?= icon('search', '', 14) ?> Lihat Riwayat</a>
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
