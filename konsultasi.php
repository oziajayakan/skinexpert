<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/proses/backward_chaining.php';

$error = '';
$hasilDiagnosa = null;

// Ambil semua gejala untuk formulir
$gejala_res = $conn->query("SELECT id, kode, nama, deskripsi FROM gejala ORDER BY kode ASC");
$daftar_gejala = [];
while ($g = $gejala_res->fetch_assoc()) {
    $daftar_gejala[] = $g;
}

// Proses formulir konsultasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_ids = $_POST['gejala'] ?? [];
    if (!is_array($selected_ids)) {
        $selected_ids = [];
    }

    if (empty($selected_ids)) {
        $error = 'Pilih minimal satu gejala terlebih dahulu.';
    } else {
        $hasilDiagnosa = evaluasiBackwardChaining($conn, $selected_ids);

        // Simpan ke riwayat database
        $top = $hasilDiagnosa['top'];
        $hasil_str = $top 
            ? "{$top['kondisi']['nama']} ({$top['score']}%)"
            : "Tidak teridentifikasi";

        $ringkasanJson = json_encode($hasilDiagnosa['trace'], JSON_UNESCAPED_UNICODE);

        try {
            $conn->begin_transaction();
            $stmt = $conn->prepare("INSERT INTO pemeriksaan (tanggal, hasil) VALUES (datetime('now', 'localtime'), ?)");
            $stmt->bind_param("s", $hasil_str);
            $stmt->execute();
            $pemeriksaanId = $conn->insert_id;
            $stmt->close();

            if ($pemeriksaanId && !empty($hasilDiagnosa['selected_set'])) {
                $stmtG = $conn->prepare("INSERT INTO pemeriksaan_gejala (pemeriksaan_id, gejala_id) VALUES (?, ?)");
                foreach ($hasilDiagnosa['selected_set'] as $gid) {
                    $stmtG->bind_param("ii", $pemeriksaanId, $gid);
                    $stmtG->execute();
                }
                $stmtG->close();
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            // Abaikan kesalahan log agar pengguna tetap dapat melihat hasil
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konsultasi - SkinExpert 🌿</title>
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
    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($hasilDiagnosa === null): ?>
      {# ==================== TAMPILAN FORM KONSULTASI ==================== #}
      <h1>💬 Konsultasi Kondisi Kulit Wajah</h1>
      <p class="muted" style="margin-bottom:1.2rem">
        Pilih gejala yang sedang dialami kulit wajah Anda, lalu klik <strong>Proses Diagnosa</strong>.
      </p>

      <form method="post" action="konsultasi.php" class="card">
        <input type="text" id="filter" placeholder="🔍 Cari gejala kulit..." class="search-input" autocomplete="off">
        
        <div class="checkbox-grid" id="gejala-list">
          <?php foreach ($daftar_gejala as $g): ?>
            <label class="checkbox-item" data-nama="<?= htmlspecialchars(strtolower($g['nama'])) ?>">
              <input type="checkbox" name="gejala[]" value="<?= $g['id'] ?>">
              <span><strong><?= htmlspecialchars($g['kode']) ?></strong> — <?= htmlspecialchars($g['nama']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-lg">🔎 Proses Diagnosa</button>
          <a href="index.php" class="btn btn-secondary">🏠 Batal</a>
        </div>
      </form>

    <?php else: ?>
      {# ==================== TAMPILAN HASIL DIAGNOSA ==================== #}
      <?php 
        $top = $hasilDiagnosa['top'];
        $trace = $hasilDiagnosa['trace'];
      ?>
      <h1>📊 Hasil Diagnosa</h1>
      <p class="muted" style="margin-bottom:1.2rem">
        <?= $hasilDiagnosa['n_selected'] ?> dari <?= $hasilDiagnosa['n_gejala'] ?> gejala dipilih.
      </p>

      <?php if ($top): ?>
        <div class="result-box success">
          <h2>🎯 Kesimpulan Utama</h2>
          <h3><?= htmlspecialchars($top['kondisi']['kode']) ?> — <?= htmlspecialchars($top['kondisi']['nama']) ?></h3>
          <p><strong>Tingkat Kecocokan:</strong> <?= $top['score'] ?>%</p>
          <p style="margin-top:.5rem"><?= htmlspecialchars($top['kondisi']['deskripsi']) ?></p>
          <h4>🩺 Solusi / Saran Penanganan:</h4>
          <p><?= htmlspecialchars($top['kondisi']['saran']) ?></p>
          <?php if ($top['score'] < 50): ?>
            <p class="muted" style="margin-top:.6rem">⚠️ Tingkat kecocokan rendah — sebaiknya periksa lebih lanjut atau tambahkan gejala.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="result-box warning">
          <h2>❌ Tidak Teridentifikasi</h2>
          <p>Tidak ada kondisi yang cocok dengan gejala yang dipilih. Coba pilih gejala lain.</p>
        </div>
      <?php endif; ?>

      <div class="card">
        <h2>🧠 Jejak Penalaran Backward Chaining</h2>
        <p class="muted" style="margin-bottom:1rem">
          Sistem dimulai dari hipotesis kondisi kulit, lalu memverifikasi subgoal gejala yang harus terpenuhi.
        </p>

        <?php if (!empty($trace)): ?>
          <?php foreach ($trace as $t): ?>
            <div class="trace-item">
              <div class="trace-header">
                <span class="badge"><?= htmlspecialchars($t['kondisi']['kode']) ?></span>
                <strong><?= htmlspecialchars($t['kondisi']['nama']) ?></strong>
                <span class="score-badge <?= $t['full'] ? 'full' : 'partial' ?>"><?= $t['score'] ?>%</span>
              </div>
              <div class="trace-body">
                <p>
                  <strong>Aturan:</strong> IF 
                  <?php 
                    $kodeGejala = array_map(fn($g) => $g['kode'], $t['all_gejala']);
                    echo htmlspecialchars(implode(" AND ", $kodeGejala));
                  ?>
                  THEN <?= htmlspecialchars($t['kondisi']['kode']) ?>
                </p>

                <?php if (!empty($t['matched_gejala'])): ?>
                  <p>✅ <strong>Subgoal terpenuhi:</strong>
                    <?php 
                      $items = array_map(fn($g) => "{$g['kode']} ({$g['nama']})", $t['matched_gejala']);
                      echo htmlspecialchars(implode(", ", $items));
                    ?>
                  </p>
                <?php endif; ?>

                <?php if (!empty($t['missing_gejala'])): ?>
                  <p>❌ <strong>Subgoal belum terpenuhi:</strong>
                    <?php 
                      $items = array_map(fn($g) => "{$g['kode']} ({$g['nama']})", $t['missing_gejala']);
                      echo htmlspecialchars(implode(", ", $items));
                    ?>
                  </p>
                <?php endif; ?>

                <?php if ($t['full']): ?>
                  <p class="ok">🎉 Semua subgoal terpenuhi — hipotesis <strong>terbukti</strong>.</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="muted">Tidak ada hipotesis yang memiliki kecocokan gejala.</p>
        <?php endif; ?>
      </div>

      <div class="form-actions">
        <a href="konsultasi.php" class="btn btn-primary">🔄 Konsultasi Lagi</a>
        <a href="riwayat.php" class="btn btn-secondary">📋 Lihat Riwayat</a>
        <a href="index.php" class="btn btn-secondary">🏠 Beranda</a>
      </div>
    <?php endif; ?>
  </main>

  <footer>© <?= date('Y') ?> SkinExpert — Sistem Pakar Diagnosis Kulit Wajah Backward Chaining</footer>

  <script>
    // Sidebar toggle
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

    // Filter gejala saat konsultasi
    const filter = document.getElementById('filter');
    if (filter) {
      filter.addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('#gejala-list .checkbox-item').forEach(el => {
          el.style.display = el.dataset.nama.includes(q) ? '' : 'none';
        });
      });
    }

    // Efek ripple saat hover button
    document.querySelectorAll('.btn').forEach(b => {
      b.addEventListener('mousemove', e => {
        const r = b.getBoundingClientRect();
        b.style.setProperty('--x', (e.clientX - r.left) + 'px');
        b.style.setProperty('--y', (e.clientY - r.top) + 'px');
      });
    });

    // Tutup sidebar saat klik link
    document.querySelectorAll('.sidebar a').forEach(a => {
      a.addEventListener('click', () => toggleMenu(false));
    });
  </script>
</body>
</html>
