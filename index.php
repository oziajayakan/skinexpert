<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/icons.php';

// Ambil statistik ringkas untuk widget
$totalGejala = 0;
$totalKondisi = 0;
$totalRules = 0;

try {
    $resG = $conn->query("SELECT COUNT(*) as c FROM gejala");
    if ($resG) $totalGejala = (int)$resG->fetch_assoc()['c'];

    $resK = $conn->query("SELECT COUNT(*) as c FROM kondisi_kulit");
    if ($resK) $totalKondisi = (int)$resK->fetch_assoc()['c'];

    $resR = $conn->query("SELECT COUNT(*) as c FROM rules WHERE aktif = 1");
    if ($resR) $totalRules = (int)$resR->fetch_assoc()['c'];
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkinExpert - Sistem Pakar Diagnosis Kulit Wajah</title>
  <link rel="stylesheet" href="static/style.css">
</head>
<body>

  <!-- Tombol Menu (pojok kiri atas) -->
  <button class="settings-btn" id="settingsBtn" aria-label="Buka menu"><?= icon('settings', '', 22) ?></button>

  <!-- Overlay -->
  <div class="overlay" id="overlay" onclick="toggleMenu(false)"></div>

  <!-- Sidebar Menu -->
  <aside class="sidebar" id="sidebar">
    <p class="sidebar-title">Menu SkinExpert</p>
    <a href="index.php"><span class="icon"><?= icon('home', '', 18) ?></span> Beranda</a>
    <a href="konsultasi.php"><span class="icon"><?= icon('chat', '', 18) ?></span> Konsultasi Kulit</a>
    <a href="riwayat.php"><span class="icon"><?= icon('clipboard', '', 18) ?></span> Riwayat Pemeriksaan</a>
    <a href="admin/gejala.php"><span class="icon"><?= icon('search', '', 18) ?></span> Data Fakta Gejala (G)</a>
    <a href="admin/kondisi.php"><span class="icon"><?= icon('target', '', 18) ?></span> Data Kondisi Kulit (H)</a>
    <a href="admin/aturan.php"><span class="icon"><?= icon('rule', '', 18) ?></span> Basis Aturan (R)</a>
  </aside>

  <main>
    <!-- HERO SECTION RINGKAS & PANDUAN CEPAT -->
    <div class="hero">
      <div style="display:flex;justify-content:center;">
        <?= icon('leaf', 'hero-svg-icon', 68) ?>
      </div>
      <div style="display:inline-block;padding:0.3rem 0.9rem;background:#ccfbf1;color:#0f766e;border-radius:30px;font-size:0.82rem;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:0.75rem;">
        Pra-Diagnosis & Identifikasi Klinis Wajah
      </div>
      <h1>SkinExpert</h1>
      <p class="hero-sub">Sistem Pakar Identifikasi Kondisi Kulit Wajah</p>
      <p class="hero-desc">
        Identifikasi dan buktikan kondisi kulit wajah Anda berdasarkan gejala klinis menggunakan metode inferensi <strong>Backward Chaining</strong>.
      </p>

      <div style="display:flex;gap:0.8rem;justify-content:center;flex-wrap:wrap;margin-bottom:1.5rem;">
        <a href="konsultasi.php" class="btn btn-primary btn-lg"><?= icon('chat', '', 18) ?> Mulai Konsultasi Kulit</a>
        <a href="riwayat.php" class="btn btn-secondary btn-lg"><?= icon('clipboard', '', 18) ?> Riwayat Diagnosis</a>
      </div>

      <p class="hero-tip">Gunakan menu di pojok kiri atas untuk navigasi basis data pakar</p>
    </div>

    <!-- STATS SUMMARY WIDGET -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.8rem;">
      <div class="stat-card">
        <div class="stat-icon-wrap green"><?= icon('search', '', 22) ?></div>
        <div>
          <div class="stat-num" style="color:var(--primary-dark);"><?= $totalGejala ?></div>
          <div class="stat-label">Gejala Klinis (G)</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrap orange"><?= icon('target', '', 22) ?></div>
        <div>
          <div class="stat-num" style="color:#ea580c;"><?= $totalKondisi ?></div>
          <div class="stat-label">Kondisi Kulit (H)</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrap blue"><?= icon('rule', '', 22) ?></div>
        <div>
          <div class="stat-num" style="color:#2563eb;"><?= $totalRules ?></div>
          <div class="stat-label">Aturan Inferensi (R)</div>
        </div>
      </div>
    </div>

    <!-- PANDUAN RINGKAS 3 LANGKAH -->
    <div class="card" style="margin-bottom:1.8rem;">
      <h2 style="font-size:1.25rem;margin-bottom:0.3rem;">Panduan Konsultasi SkinExpert</h2>
      <p class="muted" style="font-size:0.88rem;margin-bottom:1.2rem;">
        Hanya butuh 3 langkah sederhana untuk mengetahui dugaan kondisi kulit dan saran perawatan:
      </p>

      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));gap:1rem;">
        <div style="background:#f8fafc;padding:1.2rem;border-radius:12px;border:1px solid var(--border);">
          <div style="color:var(--primary);margin-bottom:0.6rem;"><?= icon('search', '', 26) ?></div>
          <h3 style="color:#0f172a;font-size:1rem;margin-bottom:0.3rem;">1. Pilih Gejala</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Centang keluhan yang tampak atau dirasakan di wajah (kulit kering, bersisik, berminyak, pori besar, atau jerawat).
          </p>
        </div>

        <div style="background:#f8fafc;padding:1.2rem;border-radius:12px;border:1px solid var(--border);">
          <div style="color:#ea580c;margin-bottom:0.6rem;"><?= icon('target', '', 26) ?></div>
          <h3 style="color:#0f172a;font-size:1rem;margin-bottom:0.3rem;">2. Inferensi Pakar</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Sistem menelusuri aturan secara mundur untuk memverifikasi apakah gejala memenuhi premis kaidah Horn clause.
          </p>
        </div>

        <div style="background:#f8fafc;padding:1.2rem;border-radius:12px;border:1px solid var(--border);">
          <div style="color:#10b981;margin-bottom:0.6rem;"><?= icon('stethoscope', '', 26) ?></div>
          <h3 style="color:#0f172a;font-size:1rem;margin-bottom:0.3rem;">3. Saran Klinis</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Dapatkan hasil pra-diagnosis yang terbukti beserta panduan perawatan harian yang sesuai dan aman.
          </p>
        </div>
      </div>
    </div>

    <!-- MENU CEPAT -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1rem;">
      <a href="konsultasi.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid var(--primary);padding:1.2rem;">
          <h3 style="color:var(--primary-dark);font-size:1.05rem;margin-bottom:0.3rem;display:flex;align-items:center;gap:0.4rem;">
            <?= icon('chat', '', 18) ?> Konsultasi Kulit
          </h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Mulai pra-diagnosis mandiri dengan memilih gejala yang sedang Anda rasakan.
          </p>
        </div>
      </a>

      <a href="riwayat.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid #10b981;padding:1.2rem;">
          <h3 style="color:#059669;font-size:1.05rem;margin-bottom:0.3rem;display:flex;align-items:center;gap:0.4rem;">
            <?= icon('clipboard', '', 18) ?> Riwayat Pemeriksaan
          </h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Lihat daftar riwayat konsultasi dan jejak diagnosis yang pernah disimpan.
          </p>
        </div>
      </a>

      <a href="admin/index.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid #f59e0b;padding:1.2rem;">
          <h3 style="color:#d97706;font-size:1.05rem;margin-bottom:0.3rem;display:flex;align-items:center;gap:0.4rem;">
            <?= icon('rule', '', 18) ?> Basis Pengetahuan Admin
          </h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Kelola data gejala, kondisi kulit, dan aturan inferensi sistem pakar.
          </p>
        </div>
      </a>
    </div>

  </main>

  <footer>© <?= date('Y') ?> SkinExpert — Sistem Pakar Identifikasi Kondisi Kulit Wajah Backward Chaining</footer>

  <script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const settingsBtn = document.getElementById('settingsBtn');

    function toggleMenu(force) {
      const open = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');
      sidebar.classList.toggle('open', open);
      overlay.classList.toggle('show', open);
      settingsBtn.classList.toggle('active', open);
    }
    settingsBtn.addEventListener('click', () => toggleMenu());

    document.querySelectorAll('.sidebar a').forEach(a => {
      a.addEventListener('click', () => toggleMenu(false));
    });
  </script>
</body>
</html>
