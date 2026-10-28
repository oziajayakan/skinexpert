<?php
session_start();
require_once __DIR__ . '/proses/backward_chaining.php';

// Fakta default dari slide: F1 = Hari Senin, F2 = Yani Kuliah
$faktaTerpilih = $_POST['fakta'] ?? ['F1', 'F2'];
if (!is_array($faktaTerpilih)) {
    $faktaTerpilih = ['F1', 'F2'];
}

$targetMasalah = $_POST['target_masalah'] ?? 'M1';
$hasilSimulasi = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || isset($_GET['auto'])) {
    $hasilSimulasi = evaluasiLatihanUNWAHA($faktaTerpilih, $targetMasalah);
} else {
    // Jalankan default M1
    $hasilSimulasi = evaluasiLatihanUNWAHA(['F1', 'F2'], 'M1');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulasi Backward Chaining - Skenario UNWAHA 🎓</title>
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
    <div style="margin-bottom:1.5rem;">
      <a href="index.php" class="btn btn-secondary btn-sm" style="margin-bottom:0.8rem;">← Kembali ke Beranda</a>
      <h1>🎓 Skenario Latihan Backward Chaining (Materi UNWAHA)</h1>
      <p class="muted">
        Halaman ini mereplikasi secara visual dan komputasional studi kasus latihan logika dari slide kuliah 
        <strong>Universitas KH. A. Wahab Hasbullah (UNWAHA)</strong>, membuktikan bagaimana penelusuran mundur 
        bekerja dari Masalah (M) menuju Fakta (F) melalui Aturan (R).
      </p>
    </div>

    <!-- MONITOR 1: FAKTA (F) & MASALAH (M) -->
    <div class="slide-monitor">
      <div class="slide-screen-header">
        <div class="slide-screen-title">
          <span>📺 Skenario Latihan (Fakta (F) & Masalah (M))</span>
        </div>
        <div class="slide-rainbow-bar"></div>
      </div>

      <!-- FAKTA SLIDE -->
      <div style="margin-bottom:1.8rem;">
        <p style="font-size:0.95rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:0.8rem;">
          DAFTAR FAKTA (F):
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:0.85rem;">
          <div class="visual-arrow-row">
            <span class="chevron-badge c-green">F1</span>
            <div class="arrow-text-box box-blue">
              <span>Hari Senin</span>
              <span class="badge" style="background:#22c55e;color:white;">Fakta Awal</span>
            </div>
          </div>
          <div class="visual-arrow-row">
            <span class="chevron-badge c-orange">F2</span>
            <div class="arrow-text-box box-purple">
              <span>Yani Kuliah</span>
              <span class="badge" style="background:#f97316;color:white;">Fakta Awal</span>
            </div>
          </div>
        </div>
      </div>

      <!-- MASALAH SLIDE -->
      <div>
        <p style="font-size:0.95rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:0.8rem;">
          MASALAH / GOAL YANG INGIN DIBUKTIKAN (M):
        </p>
        <div class="problem-grid" style="margin-top:0;">
          <div class="problem-card-box pb-green">
            <div class="problem-badge-title">M1</div>
            <div class="problem-question">• Apakah Ali Masuk Kuliah ?</div>
            <div style="font-size:0.85rem;margin-top:0.6rem;opacity:0.9;">Target Hipotesis 1</div>
          </div>
          <div class="problem-card-box pb-orange">
            <div class="problem-badge-title">M2</div>
            <div class="problem-question">• Apakah Ani Bolos Kuliah ?</div>
            <div style="font-size:0.85rem;margin-top:0.6rem;opacity:0.9;">Target Hipotesis 2</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MONITOR 2: BASIS ATURAN (RULE - R) -->
    <div class="slide-monitor">
      <div class="slide-screen-header">
        <div class="slide-screen-title">
          <span>🧩 Skenario Latihan (Aturan (Rule))</span>
        </div>
        <div class="slide-rainbow-bar"></div>
      </div>

      <div class="rule-visual-row">
        <span class="chevron-badge c-green">R6</span>
        <div class="rule-if-box">
          <span>Jika Mahasiswa UNWAHA Kuliah & Ali Sakit</span>
        </div>
        <div class="rule-then-box">
          <span>Maka Ali Tidak Kuliah</span>
        </div>
      </div>

      <div class="rule-visual-row">
        <span class="chevron-badge c-orange">R7</span>
        <div class="rule-if-box">
          <span>Jika Ali Kuliah & Ani Tidak Kuliah</span>
        </div>
        <div class="rule-then-box">
          <span>Maka Ani Sakit</span>
        </div>
      </div>

      <div class="rule-visual-row">
        <span class="chevron-badge c-blue">R8</span>
        <div class="rule-if-box">
          <span>Jika Hari Senin & Ani Sakit</span>
        </div>
        <div class="rule-then-box">
          <span>Maka Kuliah Tidak Libur</span>
        </div>
      </div>

      <div class="rule-visual-row">
        <span class="chevron-badge c-yellow">R9</span>
        <div class="rule-if-box">
          <span>Jika Mahasiswa UNWAHA Kuliah</span>
        </div>
        <div class="rule-then-box">
          <span>Maka Kuliah Tidak Libur</span>
        </div>
      </div>

      <div class="rule-visual-row">
        <span class="chevron-badge c-red">R10</span>
        <div class="rule-if-box">
          <span>Jika Kuliah Tidak Libur</span>
        </div>
        <div class="rule-then-box">
          <span>Maka Ali Belajar di Kampus UNWAHA</span>
        </div>
      </div>
    </div>

    <!-- FORM INTERAKTIF PENGUJIAN -->
    <div class="card">
      <h2>🎮 Panel Uji Interaktif Backward Chaining</h2>
      <p class="muted" style="margin-bottom:1.2rem;">
        Anda dapat mengubah fakta yang aktif dan memilih target masalah yang ingin dibuktikan secara dinamis.
      </p>

      <form method="post" action="latihan.php">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.5rem;margin-bottom:1.5rem;">
          
          <!-- PILIH TARGET MASALAH -->
          <div>
            <label style="font-weight:700;display:block;margin-bottom:0.6rem;">🎯 Pilih Target Masalah (Goal):</label>
            <div style="display:flex;flex-direction:column;gap:0.6rem;">
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="radio" name="target_masalah" value="M1" <?= $targetMasalah === 'M1' ? 'checked' : '' ?> style="accent-color:var(--primary);width:18px;height:18px;">
                <div>
                  <strong>M1: Apakah Ali Masuk Kuliah ?</strong>
                  <div class="muted" style="font-size:0.8rem;">Ditelusuri via R3 → R10 → R9 → R2 → F2</div>
                </div>
              </label>

              <label class="checkbox-item" style="cursor:pointer;">
                <input type="radio" name="target_masalah" value="M2" <?= $targetMasalah === 'M2' ? 'checked' : '' ?> style="accent-color:var(--primary);width:18px;height:18px;">
                <div>
                  <strong>M2: Apakah Ani Bolos Kuliah ?</strong>
                  <div class="muted" style="font-size:0.8rem;">Ditelusuri via R4 (Ani Tidak Kuliah & Kuliah Tidak Libur)</div>
                </div>
              </label>
            </div>
          </div>

          <!-- PILIH FAKTA AKTIF -->
          <div>
            <label style="font-weight:700;display:block;margin-bottom:0.6rem;">📌 Atur Fakta Aktif (Working Memory):</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;">
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="checkbox" name="fakta[]" value="F1" <?= in_array('F1', $faktaTerpilih) ? 'checked' : '' ?>>
                <span><strong>F1:</strong> Hari Senin</span>
              </label>
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="checkbox" name="fakta[]" value="F2" <?= in_array('F2', $faktaTerpilih) ? 'checked' : '' ?>>
                <span><strong>F2:</strong> Yani Kuliah</span>
              </label>
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="checkbox" name="fakta[]" value="F3" <?= in_array('F3', $faktaTerpilih) ? 'checked' : '' ?>>
                <span><strong>F3:</strong> Ali Sakit</span>
              </label>
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="checkbox" name="fakta[]" value="F4" <?= in_array('F4', $faktaTerpilih) ? 'checked' : '' ?>>
                <span><strong>F4:</strong> Ani Tdk Kuliah</span>
              </label>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
          ⚡ Jalankan Pembuktian Logika
        </button>
      </form>
    </div>

    <!-- HASIL INFERENSI STEP-BY-STEP -->
    <?php if ($hasilSimulasi): ?>
      <div class="card" style="border:2px solid <?= $hasilSimulasi['is_proven'] ? '#22c55e' : '#f87171' ?>;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;margin-bottom:1rem;">
          <h2>
            📊 Hasil Inferensi: <?= $hasilSimulasi['target'] === 'M1' ? 'M1 (Ali Masuk Kuliah)' : 'M2 (Ani Bolos Kuliah)' ?>
          </h2>
          <span class="proof-status-tag <?= $hasilSimulasi['is_proven'] ? 'tag-proven' : 'tag-gugur' ?>" style="font-size:1rem;padding:0.4rem 1.2rem;">
            <?= $hasilSimulasi['is_proven'] ? '🟢 HIPOTESIS TERBUKTI BENAR' : '🔴 HIPOTESIS TIDAK TERBUKTI' ?>
          </span>
        </div>

        <!-- FORMULA BOX -->
        <div class="formula-box">
          Metode: <span class="highlight">Goal-Driven Reasoning (Top-Down)</span><br>
          Penarikan Kesimpulan: <span class="highlight">Modus Ponens Chaining: $P_1, P_1 \rightarrow P_2, \dots \vdash Q$</span>
        </div>

        <!-- LANGKAH PEMBUKTIAN -->
        <div style="margin-top:1.2rem;">
          <h3 style="margin-bottom:0.8rem;">Jejak Eksekusi Rantai Inferensi (Trace Log):</h3>
          
          <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($hasilSimulasi['steps'] as $s): ?>
              <div style="background:#f8fafc;border-left:4px solid var(--primary);border-radius:0 10px 10px 0;padding:0.85rem 1.2rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:var(--primary-dark);font-size:0.9rem;margin-bottom:0.3rem;">
                  <span>Langkah <?= $s['step'] ?>:</span>
                  <span><?= htmlspecialchars($s['action']) ?></span>
                </div>
                <div style="color:#334155;font-size:0.92rem;line-height:1.5;white-space:pre-line;">
                  <?= htmlspecialchars($s['desc']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="margin-top:1.5rem;padding:1.2rem;border-radius:12px;background:<?= $hasilSimulasi['is_proven'] ? '#ecfdf5' : '#fff1f2' ?>;color:<?= $hasilSimulasi['is_proven'] ? '#065f46' : '#9f1239' ?>;">
          <strong>🎯 Kesimpulan Logika:</strong><br>
          <?= $hasilSimulasi['is_proven'] 
              ? "Berdasarkan penelusuran berantai dari fakta [F2: Yani Kuliah] membuktikan bahwa perkuliahan tidak libur dan Ali belajar di kampus UNWAHA, sehingga Masalah {$hasilSimulasi['target']} TERBUKTI BENAR." 
              : "Basis fakta yang aktif tidak mencukupi untuk memenuhi premis aturan yang diperlukan, sehingga Masalah {$hasilSimulasi['target']} TIDAK DAPAT DIBUKTIKAN." ?>
        </div>
      </div>
    <?php endif; ?>

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
