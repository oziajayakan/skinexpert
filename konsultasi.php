<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/proses/backward_chaining.php';

$error = '';
$hasilDiagnosa = null;

// Ambil semua gejala (Fakta - F)
$gejala_res = $conn->query("SELECT id, kode, nama, deskripsi FROM gejala ORDER BY kode ASC");
$daftar_gejala = [];
while ($g = $gejala_res->fetch_assoc()) {
    $daftar_gejala[] = $g;
}

// Ambil semua kondisi kulit (Masalah / Goal - M)
$kondisi_res = $conn->query("SELECT id, kode, nama FROM kondisi_kulit ORDER BY kode ASC");
$daftar_kondisi = [];
while ($k = $kondisi_res->fetch_assoc()) {
    $daftar_kondisi[] = $k;
}

$fColors = ['c-green', 'c-orange', 'c-blue', 'c-yellow', 'c-purple', 'c-red', 'c-teal'];
$badgeColors = ['c-green', 'c-orange', 'c-purple', 'c-teal', 'c-blue', 'c-red'];

// Proses formulir konsultasi
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $selected_ids = $_POST['gejala'] ?? [];
    if (!is_array($selected_ids)) {
        $selected_ids = [];
    }

    $targetGoalId = (int)($_POST['target_goal_id'] ?? 0);

    if (empty($selected_ids)) {
        $error = 'Pilih minimal satu fakta gejala klinis terlebih dahulu.';
    } else {
        $hasilDiagnosa = evaluasiBackwardChaining($conn, $selected_ids, $targetGoalId);

        // Simpan ke riwayat database
        $top = $hasilDiagnosa['top'];
        $hasil_str = $top && $top['full']
            ? "{$top['kondisi']['nama']} [TERBUKTI]"
            : ($top ? "{$top['kondisi']['nama']} ({$top['score']}%)" : "Tidak Teridentifikasi");

        try {
            $conn->begin_transaction();
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO pemeriksaan (tanggal, hasil) VALUES (?, ?)");
            $stmt->bind_param("ss", $now, $hasil_str);
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
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konsultasi Backward Chaining - SkinExpert 🌿</title>
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
    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- STEP WIZARD INDICATOR -->
    <div class="step-pill-wizard">
      <div class="step-pill <?= $hasilDiagnosa === null ? 'active' : '' ?>">
        <span class="step-num">1</span>
        <span>Pilih Fakta (F) & Masalah (M)</span>
      </div>
      <div class="step-pill <?= $hasilDiagnosa !== null ? 'active' : '' ?>">
        <span class="step-num">2</span>
        <span>Inferensi Backward Chaining</span>
      </div>
    </div>

    <?php if ($hasilDiagnosa === null): ?>
      <!-- ==================== TAMPILAN FORMULIR SELEKSI FAKTA & MASALAH ==================== -->
      <div class="card">
        <h1>💬 Konsultasi Sistem Pakar Kulit Wajah</h1>
        <p class="muted" style="margin-bottom:1.5rem">
          Metode <strong>Backward Chaining</strong> membuktikan kebenaran hipotesis kondisi kulit (<strong>Masalah / Goal M</strong>) 
          berdasarkan prasyarat aturan (<strong>Rule R</strong>) dan fakta klinis (<strong>Fakta F</strong>) yang Anda alami.
        </p>

        <form method="post" action="konsultasi.php">
          
          <!-- BAGIAN 1: TARGET MASALAH / GOAL (M) -->
          <div style="margin-bottom:1.8rem;">
            <label style="font-size:1rem;font-weight:700;display:block;margin-bottom:0.6rem;">
              🎯 Langkah 1: Pilih Target Masalah / Hipotesis yang Ingin Diuji (Goal - M)
            </label>
            <p class="muted" style="font-size:0.88rem;margin-bottom:0.8rem;">
              Pilih masalah spesifik yang ingin diverifikasi, atau pilih "Uji Seluruh Masalah" untuk evaluasi komprehensif.
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:0.75rem;">
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="radio" name="target_goal_id" value="0" checked style="accent-color:var(--primary);width:18px;height:18px;margin-top:0.2rem;">
                <div>
                  <strong>🌟 Evaluasi Seluruh Masalah</strong>
                  <div class="muted" style="font-size:0.78rem;">Sistem akan menguji M1 s.d. M4 secara otomatis</div>
                </div>
              </label>

              <?php 
                $badgeColors = ['c-green', 'c-orange', 'c-purple', 'c-teal'];
                $idx = 0;
                foreach ($daftar_kondisi as $k): 
                  $bColor = $badgeColors[$idx % count($badgeColors)];
                  $idx++;
              ?>
                <label class="checkbox-item" style="cursor:pointer;">
                  <input type="radio" name="target_goal_id" value="<?= $k['id'] ?>" style="accent-color:var(--primary);width:18px;height:18px;margin-top:0.2rem;">
                  <div>
                    <span class="chevron-badge <?= $bColor ?>" style="font-size:0.75rem;padding:0.2rem 0.8rem 0.2rem 0.5rem;min-width:45px;">M<?= $idx ?></span>
                    <strong style="margin-left:0.3rem;"><?= htmlspecialchars($k['nama']) ?></strong>
                    <div class="muted" style="font-size:0.78rem;">Uji hipotesis <?= htmlspecialchars($k['kode']) ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <hr style="border:0;border-top:1px solid var(--border);margin:1.8rem 0;">

          <!-- BAGIAN 2: PILIH FAKTA (F) -->
          <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.6rem;margin-bottom:0.6rem;">
              <label style="font-size:1rem;font-weight:700;">
                🔍 Langkah 2: Pilih Fakta Gejala Klinis yang Sedang Dialami (Fakta - F)
              </label>
              <span class="muted" style="font-size:0.85rem;" id="selected-counter">0 fakta dipilih</span>
            </div>
            
            <input type="text" id="filter" placeholder="🔎 Cari fakta gejala kulit..." class="search-input" autocomplete="off">
            
            <div class="checkbox-grid" id="gejala-list">
              <?php 
                $fColors = ['c-green', 'c-orange', 'c-blue', 'c-yellow', 'c-purple', 'c-red', 'c-teal'];
                $fIdx = 0;
                foreach ($daftar_gejala as $g): 
                  $cColor = $fColors[$fIdx % count($fColors)];
                  $fIdx++;
              ?>
                <label class="checkbox-item" data-nama="<?= htmlspecialchars(strtolower($g['nama'])) ?>">
                  <input type="checkbox" name="gejala[]" value="<?= $g['id'] ?>" onchange="updateCounter()">
                  <div style="display:flex;align-items:center;gap:0.6rem;width:100%;">
                    <span class="chevron-badge <?= $cColor ?>" style="font-size:0.78rem;padding:0.25rem 0.9rem 0.25rem 0.6rem;min-width:55px;">F<?= $fIdx ?></span>
                    <div>
                      <strong><?= htmlspecialchars($g['nama']) ?></strong>
                      <div class="muted" style="font-size:0.78rem;"><?= htmlspecialchars($g['kode']) ?>: <?= htmlspecialchars($g['deskripsi']) ?></div>
                    </div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-actions" style="margin-top:1.8rem;">
            <button type="submit" class="btn btn-primary btn-lg">
              🚀 Jalankan Inferensi Backward Chaining
            </button>
            <a href="index.php" class="btn btn-secondary">🏠 Batal</a>
            <a href="latihan.php" class="btn btn-secondary" style="margin-left:auto;">
              🧪 Lihat Contoh Latihan UNWAHA
            </a>
          </div>
        </form>
      </div>

    <?php else: ?>
      <!-- ==================== TAMPILAN HASIL & VISUAL MONITOR ==================== -->
      <?php 
        $top = $hasilDiagnosa['top'];
        $trace = $hasilDiagnosa['trace'];
        $goalsTerbukti = $hasilDiagnosa['goals_terbukti'];
      ?>

      <!-- MONITOR SLIDE VISUAL (SEPERTI SLIDE KELAS) -->
      <div class="slide-monitor">
        <div class="slide-screen-header">
          <div class="slide-screen-title">
            <span>🌿 Skenario Diagnosis Kulit Wajah (Fakta & Masalah)</span>
          </div>
          <div class="slide-rainbow-bar"></div>
        </div>

        <!-- FAKTA AKTIF -->
        <p style="font-size:0.92rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.8rem;">
          📌 Fakta Masukan Pengguna (Working Memory):
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:0.75rem;margin-bottom:1.5rem;">
          <?php 
            $fIdx = 0;
            foreach ($hasilDiagnosa['selected_set'] as $sid): 
              $gData = $hasilDiagnosa['gejala_map'][$sid] ?? null;
              if (!$gData) continue;
              $fIdx++;
              $cColor = $fColors[$fIdx % count($fColors)];
          ?>
            <div class="visual-arrow-row">
              <span class="chevron-badge <?= $cColor ?>">F<?= $fIdx ?></span>
              <div class="arrow-text-box box-blue">
                <span><?= htmlspecialchars($gData['nama']) ?></span>
                <span style="font-size:0.78rem;opacity:0.75;"><?= htmlspecialchars($gData['kode']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- MASALAH / GOAL YANG DIUJI -->
        <p style="font-size:0.92rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.8rem;">
          🎯 Status Pembuktian Masalah (M):
        </p>
        <div class="problem-grid" style="margin-top:0;">
          <?php 
            $mIdx = 0;
            foreach ($trace as $t): 
              $mIdx++;
              $isProven = $t['full'];
              $boxClass = $isProven ? 'pb-green' : 'pb-orange';
          ?>
            <div class="problem-card-box <?= $boxClass ?>">
              <div class="problem-badge-title">
                M<?= $mIdx ?> <?= $isProven ? '✅ TERBUKTI' : '❌ GUGUR' ?>
              </div>
              <div class="problem-question">
                Apakah mengalami <?= htmlspecialchars($t['kondisi']['nama']) ?> (<?= htmlspecialchars($t['kondisi']['kode']) ?>)?
              </div>
              <div style="margin-top:0.8rem;font-size:0.88rem;opacity:0.9;border-top:1px dashed rgba(255,255,255,0.3);padding-top:0.6rem;">
                Tingkat Bukti Premis: <strong><?= $t['score'] ?>%</strong>
                (<?= count($t['matched']) ?> / <?= count($t['subgoals']) ?> fakta sesuai)
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- KESIMPULAN UTAMA DOKTER / PAKAR -->
      <?php if (!empty($goalsTerbukti)): ?>
        <div class="result-box success">
          <h2>🎯 Kesimpulan Diagnosa: Hipotesis Berhasil Dibuktikan!</h2>
          <?php foreach ($goalsTerbukti as $gt): ?>
            <div style="background:white;border-radius:14px;padding:1.2rem;margin-top:0.9rem;border:1px solid #86efac;box-shadow:0 3px 12px rgba(16,185,129,0.1);">
              <h3 style="color:#065f46;font-size:1.25rem;">
                <?= htmlspecialchars($gt['kondisi']['kode']) ?> — <?= htmlspecialchars($gt['kondisi']['nama']) ?>
              </h3>
              <p style="margin:0.5rem 0;color:#334155;">
                <?= htmlspecialchars($gt['kondisi']['deskripsi']) ?>
              </p>
              <h4>🩺 Solusi & Saran Perawatan Klinis:</h4>
              <p style="background:#f0fdf4;padding:0.8rem;border-radius:8px;border-left:4px solid #10b981;margin-top:0.4rem;color:#14532d;">
                <?= htmlspecialchars($gt['kondisi']['saran']) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="result-box warning">
          <h2>⚠️ Tidak Ada Hipotesis yang Terpenuhi Penuh</h2>
          <p>Berdasarkan fakta gejala yang dipilih, tidak ada aturan yang seluruh premis prasyaratnya terpenuhi. Semua aturan mengalami <em>backtracking</em>.</p>
        </div>
      <?php endif; ?>

      <!-- DETAIL JEJAK INFERENSI LOGIKA (AND-OR GRAPH & MODUS PONENS) -->
      <div class="card">
        <h2>🧠 Rincian Penelusuran Mundur (Reasoning Trace)</h2>
        <p class="muted" style="margin-bottom:1.2rem;">
          Berikut rincian bagaimana mesin Backward Chaining mengevaluasi aturan implikasi $(P_1 \land P_2 \dots) \rightarrow Q$ untuk setiap target Masalah.
        </p>

        <?php foreach ($trace as $t): ?>
          <div class="proof-step-card <?= $t['full'] ? 'proven' : 'gugur' ?>">
            <div class="proof-header">
              <div>
                <span class="chevron-badge <?= $t['full'] ? 'c-green' : 'c-red' ?>" style="font-size:0.75rem;padding:0.25rem 0.8rem;min-width:50px;">
                  <?= htmlspecialchars($t['rule']['kode']) ?>
                </span>
                <strong style="margin-left:0.5rem;font-size:1.05rem;">
                  <?= htmlspecialchars($t['kondisi']['nama']) ?> (<?= htmlspecialchars($t['kondisi']['kode']) ?>)
                </strong>
              </div>
              <span class="proof-status-tag <?= $t['full'] ? 'tag-proven' : 'tag-gugur' ?>">
                <?= $t['full'] ? '🟢 TERBUKTI (PROVEN)' : '🔴 GUGUR (BACKTRACK)' ?>
              </span>
            </div>

            <!-- TAMPILAN ATURAN SEPERTI DI SLIDE KELAS -->
            <div class="rule-visual-row">
              <span class="chevron-badge c-blue"><?= htmlspecialchars($t['rule']['kode']) ?></span>
              <div class="rule-if-box">
                <span>JIKA</span>
                <span>
                  <?php 
                    $pNames = array_map(fn($g) => $g['kode'] . ' (' . $g['nama'] . ')', $t['all_gejala']);
                    echo htmlspecialchars(implode(" & ", $pNames));
                  ?>
                </span>
              </div>
              <div class="rule-then-box">
                <span>MAKA: <?= htmlspecialchars($t['kondisi']['nama']) ?> (<?= htmlspecialchars($t['kondisi']['kode']) ?>)</span>
              </div>
            </div>

            <!-- FORMULA MODUS PONENS -->
            <div class="formula-box">
              Rumus Logika: <span class="highlight"><?= htmlspecialchars($t['formula']) ?></span><br>
              Status Premis: <?= $t['full'] ? '<span style="color:#4ade80;">Seluruh Premis = TRUE ∴ Kesimpulan VALID</span>' : '<span style="color:#f87171;">Ada Premis = FALSE ∴ Aturan Gugur (Backtracking)</span>' ?>
            </div>

            <!-- EVALUASI SUBGOAL -->
            <div style="font-size:0.9rem;line-height:1.6;">
              <p><strong>Status Evaluasi:</strong> <?= htmlspecialchars($t['modus_ponens']) ?></p>
              
              <?php if (!empty($t['matched_gejala'])): ?>
                <p style="color:#15803d;margin-top:0.4rem;">
                  ✅ <strong>Fakta Terpenuhi:</strong> 
                  <?= htmlspecialchars(implode(", ", array_map(fn($g) => "{$g['kode']} - {$g['nama']}", $t['matched_gejala']))) ?>
                </p>
              <?php endif; ?>

              <?php if (!empty($t['missing_gejala'])): ?>
                <p style="color:#b91c1c;margin-top:0.3rem;">
                  ❌ <strong>Premis Tidak Ditemukan pada Fakta Pengguna:</strong> 
                  <?= htmlspecialchars(implode(", ", array_map(fn($g) => "{$g['kode']} - {$g['nama']}", $t['missing_gejala']))) ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="form-actions" style="margin-top:1.5rem;">
          <a href="konsultasi.php" class="btn btn-primary">🔄 Konsultasi Lagi</a>
          <a href="latihan.php" class="btn btn-secondary">🧪 Coba Skenario Latihan UNWAHA</a>
          <a href="riwayat.php" class="btn btn-secondary">📋 Lihat Riwayat</a>
          <a href="index.php" class="btn btn-secondary">🏠 Beranda</a>
        </div>
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

    function updateCounter() {
      const count = document.querySelectorAll('input[name="gejala[]"]:checked').length;
      const el = document.getElementById('selected-counter');
      if (el) {
        el.innerText = count + ' fakta dipilih';
      }
    }

    // Efek ripple saat hover button
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
