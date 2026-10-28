<?php
require_once __DIR__ . '/config/database.php';

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
  <title>SkinExpert - Sistem Pakar Diagnosis Kulit Wajah 🌿</title>
  <link rel="stylesheet" href="static/style.css">
</head>
<body>

  <!-- Tombol Menu (pojok kiri atas) -->
  <button class="settings-btn" id="settingsBtn" aria-label="Buka menu">⚙️</button>

  <!-- Overlay -->
  <div class="overlay" id="overlay" onclick="toggleMenu(false)"></div>

  <!-- Sidebar Menu -->
  <aside class="sidebar" id="sidebar">
    <p class="sidebar-title">Menu SkinExpert</p>
    <a href="index.php"><span class="icon">🏠</span> Beranda</a>
    <a href="konsultasi.php"><span class="icon">💬</span> Konsultasi Kulit</a>
    <a href="latihan.php"><span class="icon">🧪</span> Skenario Latihan (UNWAHA)</a>
    <a href="admin/gejala.php"><span class="icon">🔍</span> Data Fakta Gejala (G)</a>
    <a href="admin/kondisi.php"><span class="icon">🌿</span> Data Kondisi Kulit (H)</a>
    <a href="admin/aturan.php"><span class="icon">🧩</span> Basis Aturan (R)</a>
    <a href="riwayat.php"><span class="icon">📋</span> Riwayat Pemeriksaan</a>
  </aside>

  <main>
    <!-- HERO SECTION RINGKAS & PANDUAN CEPAT -->
    <div class="hero">
      <div class="hero-icon">🌿</div>
      <div style="display:inline-block;padding:0.3rem 0.9rem;background:#ccfbf1;color:#0f766e;border-radius:30px;font-size:0.82rem;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:0.75rem;">
        Pra-Diagnosis & Identifikasi Berbasis Pakar
      </div>
      <h1>SkinExpert</h1>
      <p class="hero-sub">Sistem Pakar Identifikasi Kondisi Kulit Wajah</p>
      <p class="hero-desc">
        Buktikan dugaan kondisi kulit wajah Anda berdasarkan gejala klinis menggunakan metode inferensi <strong>Backward Chaining</strong>.
      </p>

      <div style="display:flex;gap:0.8rem;justify-content:center;flex-wrap:wrap;margin-bottom:1.5rem;">
        <a href="konsultasi.php" class="btn btn-primary btn-lg">💬 Mulai Konsultasi Kulit</a>
        <a href="latihan.php" class="btn btn-secondary btn-lg">🧪 Simulasi Latihan</a>
      </div>

      <p class="hero-tip">✨ Klik ikon <strong>⚙️</strong> di pojok kiri atas untuk membuka menu panel ✨</p>
    </div>

    <!-- STATS SUMMARY WIDGET -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.8rem;">
      <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;padding:1.2rem;">
        <span class="chevron-badge c-green" style="font-size:1rem;padding:0.5rem 1.2rem;">G</span>
        <div>
          <div style="font-size:1.6rem;font-weight:900;color:var(--primary-dark);line-height:1;"><?= $totalGejala ?></div>
          <div class="muted" style="font-size:0.82rem;font-weight:600;">Gejala Klinis (Fakta)</div>
        </div>
      </div>

      <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;padding:1.2rem;">
        <span class="chevron-badge c-orange" style="font-size:1rem;padding:0.5rem 1.2rem;">H</span>
        <div>
          <div style="font-size:1.6rem;font-weight:900;color:#ea580c;line-height:1;"><?= $totalKondisi ?></div>
          <div class="muted" style="font-size:0.82rem;font-weight:600;">Hipotesis Kondisi Kulit</div>
        </div>
      </div>

      <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;padding:1.2rem;">
        <span class="chevron-badge c-blue" style="font-size:1rem;padding:0.5rem 1.2rem;">R</span>
        <div>
          <div style="font-size:1.6rem;font-weight:900;color:#2563eb;line-height:1;"><?= $totalRules ?></div>
          <div class="muted" style="font-size:0.82rem;font-weight:600;">Aturan Inferensi Aktif</div>
        </div>
      </div>
    </div>

    <!-- PANDUAN RINGKAS 3 LANGKAH -->
    <div class="card" style="margin-bottom:1.8rem;">
      <h2 style="font-size:1.25rem;margin-bottom:0.3rem;">📋 Panduan Menggunakan SkinExpert</h2>
      <p class="muted" style="font-size:0.88rem;margin-bottom:1.2rem;">
        Hanya butuh 3 langkah sederhana untuk mengetahui kondisi kulit dan saran perawatan:
      </p>

      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));gap:1rem;">
        <div style="background:#f8fafc;padding:1.1rem;border-radius:12px;border:1px solid var(--border);">
          <div style="font-size:1.5rem;margin-bottom:0.4rem;">🔍</div>
          <h3 style="color:#0f172a;font-size:1rem;margin-bottom:0.3rem;">1. Pilih Gejala</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Centang keluhan yang tampak atau terasa di wajah (misal: kulit mengelupas, pori-pori tersumbat, atau kemerahan).
          </p>
        </div>

        <div style="background:#f8fafc;padding:1.1rem;border-radius:12px;border:1px solid var(--border);">
          <div style="font-size:1.5rem;margin-bottom:0.4rem;">🎯</div>
          <h3 style="color:#0f172a;font-size:1rem;margin-bottom:0.3rem;">2. Uji Hipotesis</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Sistem menelusuri aturan secara mundur untuk membuktikan apakah gejala Anda memenuhi syarat dugaan kondisi tertentu.
          </p>
        </div>

        <div style="background:#f8fafc;padding:1.1rem;border-radius:12px;border:1px solid var(--border);">
          <div style="font-size:1.5rem;margin-bottom:0.4rem;">🩺</div>
          <h3 style="color:#0f172a;font-size:1rem;margin-bottom:0.3rem;">3. Saran Perawatan</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Dapatkan hasil pra-diagnosis yang terbukti beserta panduan perawatan harian yang sesuai dan aman.
          </p>
        </div>
      </div>
    </div>

    <!-- MENU CEPAT -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:1rem;">
      <a href="konsultasi.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid var(--primary);padding:1.2rem;">
          <h3 style="color:var(--primary-dark);font-size:1.05rem;margin-bottom:0.3rem;">💬 Konsultasi Kulit</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Mulai pra-diagnosis mandiri dengan memilih gejala yang sedang Anda rasakan.
          </p>
        </div>
      </a>

      <a href="latihan.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid #6366f1;padding:1.2rem;">
          <h3 style="color:#4f46e5;font-size:1.05rem;margin-bottom:0.3rem;">🧪 Skenario Latihan</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Simulasi visual interaktif contoh logika materi kuliah UNWAHA (F1, F2, M1, M2).
          </p>
        </div>
      </a>

      <a href="riwayat.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid #10b981;padding:1.2rem;">
          <h3 style="color:#059669;font-size:1.05rem;margin-bottom:0.3rem;">📋 Riwayat Pemeriksaan</h3>
          <p class="muted" style="font-size:0.85rem;margin:0;">
            Lihat daftar riwayat konsultasi dan jejak diagnosis yang pernah disimpan.
          </p>
        </div>
      </a>

      <a href="admin/index.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="margin:0;height:100%;transition:transform 0.25s ease;cursor:pointer;border-left:4px solid #f59e0b;padding:1.2rem;">
          <h3 style="color:#d97706;font-size:1.05rem;margin-bottom:0.3rem;">🧩 Basis Pengetahuan Admin</h3>
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
