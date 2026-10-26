<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkinExpert - Sistem Pakar Kulit Wajah 🌿</title>
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
    <a href="admin/gejala.php"><span class="icon">🔍</span> Data Gejala</a>
    <a href="admin/kondisi.php"><span class="icon">🌿</span> Kondisi Kulit</a>
    <a href="admin/aturan.php"><span class="icon">🧩</span> Basis Aturan</a>
    <a href="konsultasi.php"><span class="icon">💬</span> Konsultasi</a>
    <a href="riwayat.php"><span class="icon">📋</span> Riwayat</a>
  </aside>

  <main>
    <div class="hero">
      <div class="hero-icon">🌿</div>
      <h1>Selamat Datang di SkinExpert</h1>
      <p class="hero-sub">Sistem Pakar Diagnosis Kondisi Kulit Wajah</p>
      <p class="hero-desc">
        Mengidentifikasi indikasi kondisi kulit wajah Anda menggunakan metode <strong>Backward Chaining</strong>
        berdasarkan gejala klinis yang dialami. Cepat, akurat, dan mudah digunakan.
      </p>
      <a href="konsultasi.php" class="btn btn-primary btn-lg">💬 Mulai Konsultasi</a>
      <p class="hero-tip">✨ Klik tombol <strong>⚙️</strong> di pojok kiri atas untuk membuka menu navigasi ✨</p>
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
