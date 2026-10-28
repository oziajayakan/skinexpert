<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/proses/backward_chaining.php';

$error = '';
$hasilDiagnosa = null;

// Ambil semua gejala (Fakta - F / G)
$gejala_res = $conn->query("SELECT id, kode, nama, deskripsi FROM gejala ORDER BY id ASC");
$daftar_gejala = [];
if ($gejala_res) {
    while ($g = $gejala_res->fetch_assoc()) {
        $daftar_gejala[] = $g;
    }
}

// Ambil semua kondisi kulit (Masalah / Hipotesis - M / H)
$kondisi_res = $conn->query("SELECT id, kode, nama, deskripsi, saran FROM kondisi_kulit ORDER BY id ASC");
$daftar_kondisi = [];
if ($kondisi_res) {
    while ($k = $kondisi_res->fetch_assoc()) {
        $daftar_kondisi[] = $k;
    }
}

$fColors = ['c-green', 'c-orange', 'c-blue', 'c-yellow', 'c-purple', 'c-red', 'c-teal'];

// Proses formulir konsultasi
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $selected_ids = $_POST['gejala'] ?? [];
    if (!is_array($selected_ids)) {
        $selected_ids = [];
    }

    $targetGoalId = (int)($_POST['target_goal_id'] ?? 0);

    if (empty($selected_ids)) {
        $error = 'Pilih minimal satu gejala yang sedang Anda alami.';
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
  <title>Konsultasi Pra-Diagnosis Kulit Wajah - SkinExpert 🌿</title>
  <link rel="stylesheet" href="static/style.css">
</head>
<body>

  <!-- Tombol Navigasi Menu -->
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
    <?php if (!empty($error)): ?>
      <div class="alert alert-error" style="margin-bottom:1.5rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- WIZARD STEP -->
    <div class="step-pill-wizard">
      <div class="step-pill <?= $hasilDiagnosa === null ? 'active' : '' ?>">
        <span class="step-num">1</span>
        <span>Pilih Gejala & Hipotesis</span>
      </div>
      <div class="step-pill <?= $hasilDiagnosa !== null ? 'active' : '' ?>">
        <span class="step-num">2</span>
        <span>Hasil Pra-Diagnosis</span>
      </div>
    </div>

    <?php if ($hasilDiagnosa === null): ?>
      <!-- FORMULIR INPUT KONSULTASI -->
      <div class="card">
        <h1>💬 Konsultasi Pra-Diagnosis Kondisi Kulit</h1>
        <p class="muted" style="margin-bottom:1.5rem;">
          Pilih gejala klinis yang Anda rasakan. Sistem pakar akan menelusuri mundur (Backward Chaining) untuk membuktikan dugaan kondisi kulit wajah Anda.
        </p>

        <form method="post" action="konsultasi.php">
          
          <!-- LANGKAH 1: TARGET HIPOTESIS -->
          <div style="margin-bottom:1.6rem;">
            <label style="font-size:1rem;font-weight:700;display:block;margin-bottom:0.4rem;">
              🎯 1. Pilih Dugaan Kondisi Kulit (Opsional)
            </label>
            <p class="muted" style="font-size:0.86rem;margin-bottom:0.8rem;">
              Pilih satu dugaan kondisi jika ingin fokus mengujinya, atau biarkan "Evaluasi Semua Kondisi" agar sistem menguji semua kemungkinan.
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:0.65rem;">
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="radio" name="target_goal_id" value="0" checked style="accent-color:var(--primary);width:18px;height:18px;">
                <div>
                  <strong>🌟 Evaluasi Semua Kondisi</strong>
                  <div class="muted" style="font-size:0.78rem;">Uji seluruh hipotesis H01 s.d. H07</div>
                </div>
              </label>

              <?php 
                $badgeColors = ['c-green', 'c-orange', 'c-purple', 'c-teal', 'c-blue', 'c-red', 'c-yellow'];
                $idx = 0;
                foreach ($daftar_kondisi as $k): 
                  $bColor = $badgeColors[$idx % count($badgeColors)];
                  $idx++;
              ?>
                <label class="checkbox-item" style="cursor:pointer;">
                  <input type="radio" name="target_goal_id" value="<?= $k['id'] ?>" style="accent-color:var(--primary);width:18px;height:18px;">
                  <div>
                    <span class="chevron-badge <?= $bColor ?>" style="font-size:0.75rem;padding:0.2rem 0.7rem;min-width:48px;"><?= htmlspecialchars($k['kode']) ?></span>
                    <strong style="margin-left:0.2rem;font-size:0.92rem;"><?= htmlspecialchars($k['nama']) ?></strong>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <hr style="border:0;border-top:1px solid var(--border);margin:1.5rem 0;">

          <!-- LANGKAH 2: PILIH GEJALA -->
          <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
              <label style="font-size:1rem;font-weight:700;">
                🔍 2. Centang Gejala yang Sedang Anda Rasakan
              </label>
              <span class="muted" style="font-size:0.85rem;" id="selected-counter">0 gejala dipilih</span>
            </div>
            
            <input type="text" id="filter" placeholder="🔎 Ketik untuk mencari gejala..." class="search-input" autocomplete="off">
            
            <div class="checkbox-grid" id="gejala-list">
              <?php 
                $fIdx = 0;
                foreach ($daftar_gejala as $g): 
                  $cColor = $fColors[$fIdx % count($fColors)];
                  $fIdx++;
              ?>
                <label class="checkbox-item" data-nama="<?= htmlspecialchars(strtolower($g['nama'])) ?>">
                  <input type="checkbox" name="gejala[]" value="<?= $g['id'] ?>" onchange="updateCounter()">
                  <div style="display:flex;align-items:center;gap:0.6rem;width:100%;">
                    <span class="chevron-badge <?= $cColor ?>" style="font-size:0.78rem;padding:0.25rem 0.8rem;min-width:52px;"><?= htmlspecialchars($g['kode']) ?></span>
                    <div>
                      <strong><?= htmlspecialchars($g['nama']) ?></strong>
                      <div class="muted" style="font-size:0.78rem;"><?= htmlspecialchars($g['deskripsi']) ?></div>
                    </div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-actions" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary btn-lg">
              🚀 Jalankan Analisis Backward Chaining
            </button>
            <a href="index.php" class="btn btn-secondary">🏠 Batal</a>
          </div>
        </form>
      </div>

    <?php else: ?>
      <!-- TAMPILAN HASIL PRA-DIAGNOSIS (FOKUS & PANDUAN RINGKAS) -->
      <?php 
        $top = $hasilDiagnosa['top'];
        $trace = $hasilDiagnosa['trace'];
        $goalsTerbukti = $hasilDiagnosa['goals_terbukti'];
        $pertanyaanLanjutan = $hasilDiagnosa['pertanyaan_lanjutan'] ?? null;
      ?>

      <!-- KARTU HASIL UTAMA -->
      <?php if (!empty($goalsTerbukti)): ?>
        <div class="result-box success" style="margin-bottom:1.8rem;">
          <div style="display:flex;align-items:flex-start;gap:0.8rem;">
            <span style="font-size:2.2rem;line-height:1;">✅</span>
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;margin-bottom:0.4rem;">
                <span class="badge" style="background:#15803d;color:white;font-weight:700;font-size:0.85rem;padding:0.25rem 0.75rem;">
                  HIPOTESIS TERBUKTI (PROVEN)
                </span>
                <span class="muted" style="font-size:0.85rem;">Metode: Modus Ponens Backward Chaining</span>
              </div>
              <h2 style="color:#065f46;font-size:1.45rem;margin:0 0 0.5rem;">
                <?= htmlspecialchars(implode(", ", array_map(fn($gt) => "{$gt['kondisi']['nama']} ({$gt['kondisi']['kode']})", $goalsTerbukti))) ?>
              </h2>
            </div>
          </div>

          <?php foreach ($goalsTerbukti as $gt): ?>
            <div style="background:white;border-radius:12px;padding:1.2rem;margin-top:1rem;border:1px solid #86efac;">
              <h3 style="color:#065f46;font-size:1.15rem;margin-bottom:0.4rem;">
                <?= htmlspecialchars($gt['kondisi']['nama']) ?>
              </h3>
              <p style="color:#334155;margin-bottom:0.8rem;font-size:0.95rem;">
                <?= htmlspecialchars($gt['kondisi']['deskripsi']) ?>
              </p>
              
              <div style="background:#f0fdf4;padding:0.9rem 1.1rem;border-radius:10px;border-left:4px solid #10b981;">
                <strong style="color:#166534;display:block;margin-bottom:0.4rem;font-size:0.92rem;">
                  🩺 Panduan & Langkah Perawatan:
                </strong>
                <p style="color:#14532d;margin:0;font-size:0.92rem;line-height:1.5;">
                  <?= htmlspecialchars($gt['kondisi']['saran']) ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div class="result-box warning" style="margin-bottom:1.8rem;">
          <div style="display:flex;align-items:flex-start;gap:0.8rem;">
            <span style="font-size:2.2rem;line-height:1;">🔍</span>
            <div>
              <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;margin-bottom:0.4rem;">
                <span class="badge" style="background:#b45309;color:white;font-weight:700;font-size:0.85rem;padding:0.25rem 0.75rem;">
                  BELUM CUKUP BUKTI
                </span>
                <span class="muted" style="font-size:0.85rem;">Status: Aturan Mengalami Backtracking</span>
              </div>
              <h2 style="color:#92400e;font-size:1.35rem;margin:0;">
                Kombinasi Gejala Belum Memenuhi 100% Premis Aturan
              </h2>
              <p style="color:#78350f;margin-top:0.4rem;font-size:0.92rem;">
                Dugaan kondisi kulit belum dapat dibuktikan secara pasti dengan gejala yang Anda pilih saat ini.
              </p>
            </div>
          </div>
        </div>

        <!-- PANDUAN VERIFIKASI LANJUTAN (BACKWARD CHAINING INTERAKTIF) -->
        <?php if ($pertanyaanLanjutan): ?>
          <div class="card" style="border: 2px solid #0284c7; background: #f0f9ff; margin-bottom: 1.8rem;">
            <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.5rem;">
              <span style="font-size:1.4rem;">💡</span>
              <h3 style="color:#0369a1;margin:0;font-size:1.15rem;">
                Verifikasi Gejala Tambahan (Backward Chaining)
              </h3>
            </div>
            <p style="color:#334155;margin-bottom:0.9rem;font-size:0.92rem;line-height:1.5;">
              Sistem menelusuri mundur aturan <strong><?= htmlspecialchars($pertanyaanLanjutan['rule']['kode']) ?></strong> untuk hipotesis 
              <strong><?= htmlspecialchars($pertanyaanLanjutan['kondisi']['nama']) ?></strong> (kecocokan <?= $pertanyaanLanjutan['score'] ?>%). 
              Untuk membuktikan hipotesis ini, apakah Anda juga mengalami gejala berikut?
            </p>

            <div style="display:flex;align-items:center;gap:0.8rem;background:white;padding:0.9rem 1.1rem;border-radius:10px;border:1px solid #bae6fd;margin-bottom:1rem;">
              <span class="chevron-badge c-blue" style="font-size:0.8rem;min-width:55px;">
                <?= htmlspecialchars($pertanyaanLanjutan['gejala']['kode']) ?>
              </span>
              <div>
                <strong style="color:#0f172a;font-size:0.95rem;"><?= htmlspecialchars($pertanyaanLanjutan['gejala']['nama']) ?></strong>
                <div class="muted" style="font-size:0.82rem;"><?= htmlspecialchars($pertanyaanLanjutan['gejala']['deskripsi']) ?></div>
              </div>
            </div>

            <!-- Form One-Click Verifikasi -->
            <form method="post" action="konsultasi.php" style="display:inline-flex;gap:0.8rem;flex-wrap:wrap;">
              <?php foreach ($hasilDiagnosa['selected_set'] as $sid): ?>
                <input type="hidden" name="gejala[]" value="<?= $sid ?>">
              <?php endforeach; ?>
              <input type="hidden" name="gejala[]" value="<?= $pertanyaanLanjutan['gejala']['id'] ?>">
              <input type="hidden" name="target_goal_id" value="<?= $pertanyaanLanjutan['kondisi']['id'] ?>">
              <button type="submit" class="btn btn-primary">
                ✅ Ya, Saya Mengalami Gejala Ini (Buktikan)
              </button>
              <a href="konsultasi.php" class="btn btn-secondary">
                ❌ Tidak, Pilih Ulang Gejala
              </a>
            </form>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- RINGKASAN GEJALA YANG ANDA PILIH -->
      <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="font-size:1.05rem;margin-bottom:0.8rem;color:#334155;">
          📌 Gejala yang Anda Laporkan:
        </h3>
        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
          <?php foreach ($hasilDiagnosa['selected_set'] as $sid): 
            $gData = $hasilDiagnosa['gejala_map'][$sid] ?? null;
            if (!$gData) continue;
          ?>
            <span style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.4rem 0.8rem;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:20px;font-size:0.85rem;color:#065f46;font-weight:600;">
              ✓ <?= htmlspecialchars($gData['kode']) ?>: <?= htmlspecialchars($gData['nama']) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- RINCIAN TEKNIS ATURAN INFERENSI (COLLAPSIBLE / ACCORDION) -->
      <details class="card" style="margin-bottom:1.8rem;background:#f8fafc;border:1px solid #e2e8f0;">
        <summary style="font-weight:700;font-size:0.95rem;color:#475569;cursor:pointer;padding:0.3rem 0;">
          ⚙️ Klik di sini untuk melihat Rincian Aturan Logika Inferensi (Technical Trace)
        </summary>
        
        <div style="margin-top:1.2rem;display:flex;flex-direction:column;gap:1rem;">
          <?php foreach ($trace as $t): ?>
            <div style="background:white;border-radius:10px;padding:1rem;border:1px solid <?= $t['full'] ? '#86efac' : '#e2e8f0' ?>;">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                <div>
                  <span class="chevron-badge <?= $t['full'] ? 'c-green' : 'c-blue' ?>" style="font-size:0.75rem;padding:0.2rem 0.6rem;min-width:48px;">
                    <?= htmlspecialchars($t['rule']['kode']) ?>
                  </span>
                  <strong style="margin-left:0.4rem;font-size:0.92rem;">
                    <?= htmlspecialchars($t['kondisi']['nama']) ?> (<?= htmlspecialchars($t['kondisi']['kode']) ?>)
                  </strong>
                </div>
                <span class="badge" style="background:<?= $t['full'] ? '#10b981' : '#64748b' ?>;color:white;font-weight:700;font-size:0.75rem;">
                  <?= $t['full'] ? 'TERBUKTI (100%)' : 'Kecocokan ' . $t['score'] . '%' ?>
                </span>
              </div>
              <div style="font-family:monospace;font-size:0.82rem;color:#2563eb;background:#eff6ff;padding:0.4rem 0.8rem;border-radius:6px;margin-bottom:0.4rem;">
                <?= htmlspecialchars($t['formula']) ?>
              </div>
              <div style="font-size:0.82rem;color:#64748b;">
                Fakta sesuai: <?= count($t['matched']) ?> dari <?= count($t['subgoals']) ?> gejala prasyarat.
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </details>

      <!-- TOMBOL AKSI -->
      <div class="form-actions">
        <a href="konsultasi.php" class="btn btn-primary">🔄 Konsultasi Baru</a>
        <a href="riwayat.php" class="btn btn-secondary">📋 Lihat Riwayat</a>
        <a href="index.php" class="btn btn-secondary">🏠 Beranda</a>
      </div>
    <?php endif; ?>
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
        el.innerText = count + ' gejala dipilih';
      }
    }

    document.querySelectorAll('.sidebar a').forEach(a => {
      a.addEventListener('click', () => toggleMenu(false));
    });
  </script>
</body>
</html>
