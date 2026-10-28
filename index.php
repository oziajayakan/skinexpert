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
    <a href="konsultasi.php"><span class="icon">💬</span> Konsultasi (Kulit)</a>
    <a href="latihan.php"><span class="icon">🧪</span> Skenario Latihan (UNWAHA)</a>
    <a href="admin/gejala.php"><span class="icon">🔍</span> Data Fakta (F)</a>
    <a href="admin/kondisi.php"><span class="icon">🌿</span> Data Masalah (M)</a>
    <a href="admin/aturan.php"><span class="icon">🧩</span> Basis Aturan (R)</a>
    <a href="riwayat.php"><span class="icon">📋</span> Riwayat</a>
  </aside>

  <main>
    <!-- HERO SECTION -->
    <div class="hero">
      <div class="hero-icon">🌿</div>
      <div style="display:inline-block;padding:0.35rem 1rem;background:#ccfbf1;color:#0f766e;border-radius:30px;font-size:0.85rem;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:0.9rem;">
        Sistem Pakar Berbasis Logika Inferensi
      </div>
      <h1>Selamat Datang di SkinExpert</h1>
      <p class="hero-sub">Metode Dynamic Backward Chaining (Goal-Driven Reasoning)</p>
      <p class="hero-desc">
        Mengidentifikasi indikasi masalah dan kondisi kulit wajah Anda secara ilmiah menggunakan penalaran mundur 
        dari <strong>Masalah / Goal (M)</strong> menuju <strong>Fakta (F)</strong> melalui aturan implikasi <strong>Rule (R)</strong>.
      </p>

      <div style="display:flex;gap:0.9rem;justify-content:center;flex-wrap:wrap;margin-bottom:2rem;">
        <a href="konsultasi.php" class="btn btn-primary btn-lg">💬 Mulai Konsultasi Kulit</a>
        <a href="latihan.php" class="btn btn-secondary btn-lg">🧪 Skenario Latihan (UNWAHA)</a>
      </div>

      <p class="hero-tip">✨ Klik tombol <strong>⚙️</strong> di pojok kiri atas untuk membuka navigasi lengkap ✨</p>
    </div>

    <!-- STATS SUMMARY WIDGET -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:2rem;">
      <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;">
        <span class="chevron-badge c-green" style="font-size:1.1rem;padding:0.6rem 1.4rem;">F</span>
        <div>
          <div style="font-size:1.8rem;font-weight:900;color:var(--primary-dark);line-height:1;"><?= $totalGejala ?></div>
          <div class="muted" style="font-size:0.82rem;font-weight:600;">Fakta Gejala Klinis</div>
        </div>
      </div>

      <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;">
        <span class="chevron-badge c-orange" style="font-size:1.1rem;padding:0.6rem 1.4rem;">M</span>
        <div>
          <div style="font-size:1.8rem;font-weight:900;color:#ea580c;line-height:1;"><?= $totalKondisi ?></div>
          <div class="muted" style="font-size:0.82rem;font-weight:600;">Masalah / Kondisi Kulit</div>
        </div>
      </div>

      <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;">
        <span class="chevron-badge c-blue" style="font-size:1.1rem;padding:0.6rem 1.4rem;">R</span>
        <div>
          <div style="font-size:1.8rem;font-weight:900;color:#2563eb;line-height:1;"><?= $totalRules ?></div>
          <div class="muted" style="font-size:0.82rem;font-weight:600;">Basis Aturan Aktif</div>
        </div>
      </div>
    </div>

    <!-- FITUR UTAMA & PARADIGMA PENALARAN -->
    <div class="card">
      <h2>🧠 Bagaimana Backward Chaining Bekerja pada SkinExpert?</h2>
      <p class="muted" style="margin-bottom:1.5rem;">
        Berdasarkan literatur sistem pakar dan materi latihan logika penalaran:
      </p>

      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:1.2rem;">
        <div style="background:#f8fafc;padding:1.2rem;border-radius:14px;border:1px solid var(--border);">
          <div style="font-size:1.6rem;margin-bottom:0.5rem;">🎯</div>
          <h3 style="color:#0f172a;margin-bottom:0.4rem;">1. Penetapan Goal (M)</h3>
          <p class="muted" style="font-size:0.88rem;">
            Penalaran dimulai dari hipotesis tujuan: <em>"Apakah pengguna mengalami Kondisi Kulit M?"</em>
          </p>
        </div>

        <div style="background:#f8fafc;padding:1.2rem;border-radius:14px;border:1px solid var(--border);">
          <div style="font-size:1.6rem;margin-bottom:0.5rem;">🧩</div>
          <h3 style="color:#0f172a;margin-bottom:0.4rem;">2. Penelusuran Aturan (R)</h3>
          <p class="muted" style="font-size:0.88rem;">
            Sistem mencari aturan $(P_1 \land P_2 \dots) \rightarrow Q$ yang menghasilkan kesimpulan Masalah tersebut.
          </p>
        </div>

        <div style="background:#f8fafc;padding:1.2rem;border-radius:14px;border:1px solid var(--border);">
          <div style="font-size:1.6rem;margin-bottom:0.5rem;">🔍</div>
          <h3 style="color:#0f172a;margin-bottom:0.4rem;">3. Verifikasi Fakta (F)</h3>
          <p class="muted" style="font-size:0.88rem;">
            Setiap premis dicocokkan dengan fakta gejala yang dialami pengguna. Jika premis salah, sistem melakukan <em>backtracking</em>.
          </p>
        </div>
      </div>
    </div>

    <!-- QUICK ACCESS MENU -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1.2rem;">
      <a href="konsultasi.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="height:100%;transition:all 0.3s var(--cute);cursor:pointer;border-left:5px solid var(--primary);">
          <h3 style="color:var(--primary-dark);margin-bottom:0.4rem;">💬 Konsultasi Mandiri</h3>
          <p class="muted" style="font-size:0.88rem;">
            Pilih gejala kulit wajah Anda dan biarkan sistem pakar membuktikan hipotesis kondisi kulit secara bertahap.
          </p>
        </div>
      </a>

      <a href="latihan.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="height:100%;transition:all 0.3s var(--cute);cursor:pointer;border-left:5px solid #6366f1;">
          <h3 style="color:#4f46e5;margin-bottom:0.4rem;">🧪 Skenario Latihan (UNWAHA)</h3>
          <p class="muted" style="font-size:0.88rem;">
            Simulasi visual interaktif contoh perkuliahan: Apakah Ali Masuk Kuliah? dan Apakah Ani Bolos Kuliah?
          </p>
        </div>
      </a>

      <a href="admin/aturan.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="height:100%;transition:all 0.3s var(--cute);cursor:pointer;border-left:5px solid #f59e0b;">
          <h3 style="color:#d97706;margin-bottom:0.4rem;">🧩 Basis Pengetahuan & Aturan</h3>
          <p class="muted" style="font-size:0.88rem;">
            Kelola data Fakta Gejala (F), Masalah (M), dan relasi aturan inferensi (R) melalui panel kontrol pakar.
          </p>
        </div>
      </a>

      <a href="riwayat.php" style="text-decoration:none;color:inherit;">
        <div class="card" style="height:100%;transition:all 0.3s var(--cute);cursor:pointer;border-left:5px solid #10b981;">
          <h3 style="color:#059669;margin-bottom:0.4rem;">📋 Riwayat Pemeriksaan</h3>
          <p class="muted" style="font-size:0.88rem;">
            Lihat daftar riwayat konsultasi dan jejak diagnosis pemeriksaan yang telah tersimpan di sistem.
          </p>
        </div>
      </a>
    </div>

  </main>

  <footer>© <?= date('Y') ?> SkinExpert — Sistem Pakar Diagnosis Kulit Wajah Backward Chaining</footer>

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

    document.querySelectorAll('.btn').forEach(b => {
      b.addEventListener('mousemove', e => {
        const r = b.getBoundingClientRect();
        b.style.setProperty('--x', (e.clientX - r.left) + 'px');
        b.style.setProperty('--y', (e.clientY - r.top) + 'px');
      });
    });

    document.querySelectorAll('.sidebar a').forEach(a => {
      a.addEventListener('click', () => toggleMenu(false));
    });
  </script>
</body>
</html>
