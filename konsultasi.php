<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/proses/backward_chaining.php';
require_once __DIR__ . '/includes/icons.php';

$error = '';
$hasilDiagnosa = null;

$preSelectedGejala = [];
if (isset($_GET['edit']) && !empty($_SESSION['last_selected_gejala'])) {
    $preSelectedGejala = array_map('intval', $_SESSION['last_selected_gejala']);
}
$preSelectedGoal = 0;
if (isset($_GET['edit']) && isset($_SESSION['last_target_goal_id'])) {
    $preSelectedGoal = (int)$_SESSION['last_target_goal_id'];
}

if (isset($_GET['reset'])) {
    unset($_SESSION['last_selected_gejala']);
    unset($_SESSION['last_target_goal_id']);
    header('Location: konsultasi.php');
    exit;
}

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

// Proses formulir konsultasi
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $selected_ids = $_POST['gejala'] ?? [];
    if (!is_array($selected_ids)) {
        $selected_ids = [];
    }

    $targetGoalId = (int)($_POST['target_goal_id'] ?? 0);

    if (empty($selected_ids)) {
        $error = 'Pilih minimal satu gejala klinis yang sedang Anda alami.';
    } else {
        $hasilDiagnosa = evaluasiBackwardChaining($conn, $selected_ids, $targetGoalId);

        $_SESSION['last_selected_gejala'] = $hasilDiagnosa['selected_set'];
        $_SESSION['last_target_goal_id'] = $targetGoalId;

        // Simpan ke riwayat database dengan ringkasan klinis yang informatif
        if (!empty($hasilDiagnosa['goals_terbukti'])) {
            $names = array_map(fn($gt) => "{$gt['kondisi']['nama']} [TERBUKTI 100%]", $hasilDiagnosa['goals_terbukti']);
            $hasil_str = implode(", ", $names);
        } elseif (!empty($hasilDiagnosa['goals_indikasi'])) {
            $topInd = $hasilDiagnosa['goals_indikasi'][0];
            $hasil_str = "Indikasi {$topInd['kondisi']['nama']} ({$topInd['score']}% Bukti)";
        } elseif ($hasilDiagnosa['top'] && $hasilDiagnosa['top']['score'] > 0) {
            $topSc = $hasilDiagnosa['top'];
            $hasil_str = "Observasi Awal {$topSc['kondisi']['nama']} ({$topSc['score']}%)";
        } else {
            $hasil_str = "Gejala Belum Cukup Bukti (Perlu Observasi)";
        }

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
  <title>Konsultasi Pra-Diagnosis Kulit Wajah - SkinExpert</title>
  <link rel="stylesheet" href="static/style.css">
</head>
<body>

  <!-- Tombol Navigasi Menu -->
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
    <?php if (!empty($error)): ?>
      <div class="alert alert-error" style="margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">
        <?= icon('alert', '', 18) ?> <?= htmlspecialchars($error) ?>
      </div>
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
        <h1 style="display:flex;align-items:center;gap:0.6rem;">
          <span style="color:var(--primary);"><?= icon('chat', '', 28) ?></span> Konsultasi Pra-Diagnosis Kondisi Kulit
        </h1>
        <p class="muted" style="margin-bottom:1.5rem;">
          Pilih keluhan klinis yang Anda rasakan di wajah. Sistem pakar akan menelusuri mundur (Backward Chaining) untuk membuktikan dugaan kondisi kulit Anda.
        </p>

        <form method="post" action="konsultasi.php">
          
          <!-- LANGKAH 1: TARGET HIPOTESIS -->
          <div style="margin-bottom:1.6rem;">
            <label style="font-size:1rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;margin-bottom:0.4rem;">
              <span style="color:#ea580c;"><?= icon('target', '', 18) ?></span> 1. Pilih Dugaan Kondisi Kulit (Opsional)
            </label>
            <p class="muted" style="font-size:0.86rem;margin-bottom:0.8rem;">
              Pilih satu dugaan kondisi jika ingin fokus mengujinya, atau biarkan "Evaluasi Semua Kondisi" agar sistem menguji seluruh kemungkinan.
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:0.65rem;">
              <label class="checkbox-item" style="cursor:pointer;">
                <input type="radio" name="target_goal_id" value="0" <?= $preSelectedGoal === 0 ? 'checked' : '' ?> style="accent-color:var(--primary);width:18px;height:18px;">
                <div>
                  <strong>Evaluasi Semua Kondisi</strong>
                  <div class="muted" style="font-size:0.78rem;">Uji seluruh hipotesis H01 s.d. H07</div>
                </div>
              </label>

              <?php foreach ($daftar_kondisi as $k): 
                $isCheckG = ($preSelectedGoal === (int)$k['id']);
              ?>
                <label class="checkbox-item" style="cursor:pointer;">
                  <input type="radio" name="target_goal_id" value="<?= $k['id'] ?>" <?= $isCheckG ? 'checked' : '' ?> style="accent-color:var(--primary);width:18px;height:18px;">
                  <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span class="code-badge badge-h"><?= htmlspecialchars($k['kode']) ?></span>
                    <strong style="font-size:0.92rem;"><?= htmlspecialchars($k['nama']) ?></strong>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <hr style="border:0;border-top:1px solid var(--border);margin:1.5rem 0;">

          <!-- LANGKAH 2: PILIH GEJALA -->
          <div style="margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
              <label style="font-size:1rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;">
                <span style="color:var(--primary);"><?= icon('search', '', 18) ?></span> 2. Centang Gejala yang Sedang Anda Rasakan
              </label>
              <span class="muted" style="font-size:0.85rem;" id="selected-counter">0 gejala dipilih</span>
            </div>
            
            <input type="text" id="filter" placeholder="Ketik untuk mencari gejala..." class="search-input" autocomplete="off">
            
            <div class="checkbox-grid" id="gejala-list">
              <?php foreach ($daftar_gejala as $g): 
                $isCheckS = in_array((int)$g['id'], $preSelectedGejala, true);
              ?>
                <label class="checkbox-item" data-nama="<?= htmlspecialchars(strtolower($g['nama'])) ?>">
                  <input type="checkbox" name="gejala[]" value="<?= $g['id'] ?>" <?= $isCheckS ? 'checked' : '' ?> onchange="updateCounter()">
                  <div style="display:flex;align-items:center;gap:0.6rem;width:100%;">
                    <span class="code-badge badge-g"><?= htmlspecialchars($g['kode']) ?></span>
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
              <?= icon('rocket', '', 18) ?> Jalankan Analisis Backward Chaining
            </button>
            <a href="index.php" class="btn btn-secondary"><?= icon('home', '', 18) ?> Batal</a>
          </div>
        </form>
      </div>

    <?php else: ?>
      <!-- TAMPILAN HASIL PRA-DIAGNOSIS MENYELURUH -->
      <?php 
        $semuaBukti = $hasilDiagnosa['semua_bukti'];
        $goalsTerbukti = $hasilDiagnosa['goals_terbukti'];
        $goalsIndikasi = $hasilDiagnosa['goals_indikasi'];
        $verifikasiOpsi = $hasilDiagnosa['verifikasi_opsi'];
        $targetKondisi = $hasilDiagnosa['target_kondisi'];
        $targetEval = $hasilDiagnosa['target_eval'];
        $top = $hasilDiagnosa['top'];
      ?>

      <!-- 1. KARTU HASIL UTAMA -->
      <?php if (!empty($goalsTerbukti)): ?>
        <div class="result-box success" style="margin-bottom:1.8rem;">
          <div style="display:flex;align-items:flex-start;gap:0.9rem;">
            <div style="color:#10b981;"><?= icon('check-circle', '', 36) ?></div>
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;margin-bottom:0.4rem;">
                <span class="badge" style="background:#15803d;color:white;font-weight:700;font-size:0.85rem;padding:0.25rem 0.75rem;">
                  DIAGNOSIS TERBUKTI (100% BUKTI KLINIS)
                </span>
                <span class="muted" style="font-size:0.85rem;">Inferensi: Modus Ponens Backward Chaining</span>
              </div>
              <h2 style="color:#065f46;font-size:1.45rem;margin:0 0 0.5rem;">
                <?= htmlspecialchars(implode(", ", array_map(fn($gt) => "{$gt['kondisi']['nama']} ({$gt['kondisi']['kode']})", $goalsTerbukti))) ?>
              </h2>
              <p style="color:#047857;font-size:0.92rem;margin:0;">
                Seluruh premis gejala prasyarat telah terpenuhi secara lengkap pada basis pengetahuan.
              </p>
            </div>
          </div>

          <?php foreach ($goalsTerbukti as $gt): ?>
            <div style="background:white;border-radius:12px;padding:1.2rem;margin-top:1rem;border:1px solid #86efac;">
              <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem;">
                <span class="code-badge badge-h"><?= htmlspecialchars($gt['kondisi']['kode']) ?></span>
                <h3 style="color:#065f46;font-size:1.15rem;margin:0;">
                  <?= htmlspecialchars($gt['kondisi']['nama']) ?>
                </h3>
              </div>
              <p style="color:#334155;margin-bottom:0.8rem;font-size:0.95rem;">
                <?= htmlspecialchars($gt['kondisi']['deskripsi']) ?>
              </p>
              
              <div style="background:#f0fdf4;padding:0.9rem 1.1rem;border-radius:10px;border-left:4px solid #10b981;">
                <strong style="color:#166534;display:flex;align-items:center;gap:0.4rem;margin-bottom:0.4rem;font-size:0.92rem;">
                  <?= icon('stethoscope', '', 18) ?> Panduan & Rekomendasi Perawatan Klinis:
                </strong>
                <p style="color:#14532d;margin:0;font-size:0.92rem;line-height:1.5;">
                  <?= htmlspecialchars($gt['kondisi']['saran']) ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif (!empty($goalsIndikasi)): ?>
        <div class="result-box" style="margin-bottom:1.8rem;background:linear-gradient(145deg, #f0fdfa 0%, #e0f2fe 100%);border:2px solid #38bdf8;">
          <div style="display:flex;align-items:flex-start;gap:0.9rem;">
            <div style="color:#0284c7;"><?= icon('search', '', 36) ?></div>
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;margin-bottom:0.4rem;">
                <span class="badge" style="background:#0284c7;color:white;font-weight:700;font-size:0.85rem;padding:0.25rem 0.75rem;">
                  INDIKASI KLINIS TERDETEKSI
                </span>
                <span class="muted" style="font-size:0.85rem;">Kecocokan Parsial Signifikan (&ge; 50%)</span>
              </div>
              <h2 style="color:#0369a1;font-size:1.35rem;margin:0 0 0.4rem;">
                <?= htmlspecialchars(implode(", ", array_map(fn($gi) => "{$gi['kondisi']['nama']} ({$gi['score']}%)", $goalsIndikasi))) ?>
              </h2>
              <p style="color:#075985;font-size:0.92rem;margin:0;">
                Gejala yang Anda laporkan memiliki kecocokan kuat dengan pola klinis di atas. Anda dapat langsung mengonfirmasi gejala pelengkap di bawah untuk melengkapi bukti menjadi 100%.
              </p>
            </div>
          </div>

          <?php foreach ($goalsIndikasi as $gi): ?>
            <div style="background:white;border-radius:12px;padding:1.1rem;margin-top:1rem;border:1px solid #bae6fd;">
              <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.4rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                  <span class="code-badge badge-h"><?= htmlspecialchars($gi['kondisi']['kode']) ?></span>
                  <strong style="color:#0f172a;font-size:1.05rem;"><?= htmlspecialchars($gi['kondisi']['nama']) ?></strong>
                </div>
                <span class="badge" style="background:#0284c7;color:white;font-weight:700;font-size:0.8rem;">
                  <?= $gi['score'] ?>% Bukti Terpenuhi
                </span>
              </div>
              <p style="color:#475569;margin-bottom:0.8rem;font-size:0.92rem;">
                <?= htmlspecialchars($gi['kondisi']['deskripsi']) ?>
              </p>
              <div style="background:#f8fafc;padding:0.8rem 1rem;border-radius:8px;border-left:3px solid #0284c7;">
                <strong style="color:#0369a1;display:flex;align-items:center;gap:0.4rem;font-size:0.88rem;margin-bottom:0.25rem;">
                  <?= icon('stethoscope', '', 16) ?> Perawatan Awal yang Aman:
                </strong>
                <p style="color:#334155;margin:0;font-size:0.88rem;line-height:1.45;">
                  <?= htmlspecialchars($gi['kondisi']['saran']) ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div class="result-box" style="margin-bottom:1.8rem;background:#f8fafc;border:2px solid #cbd5e1;">
          <div style="display:flex;align-items:flex-start;gap:0.9rem;">
            <div style="color:#64748b;"><?= icon('pin', '', 36) ?></div>
            <div>
              <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;margin-bottom:0.4rem;">
                <span class="badge" style="background:#64748b;color:white;font-weight:700;font-size:0.85rem;padding:0.25rem 0.75rem;">
                  OBSERVASI AWAL KONDISI KULIT
                </span>
                <span class="muted" style="font-size:0.85rem;">Gejala Masih Terbatas (&lt; 50%)</span>
              </div>
              <h2 style="color:#1e293b;font-size:1.3rem;margin:0;">
                Pola Gejala Belum Mengarah Pasti ke Kondisi Spesifik
              </h2>
              <p style="color:#475569;margin-top:0.4rem;font-size:0.92rem;">
                Gejala yang dilaporkan belum mencapai ambang indikasi klinis kuat. Simak matriks bukti lengkap di bawah untuk melihat potensi kondisi yang paling relevan.
              </p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- 2. STATUS TARGET HIPOTESIS SPESIFIK (JIKA DIPILIH) -->
      <?php if ($targetKondisi && $targetEval): ?>
        <div class="card" style="margin-bottom:1.5rem;border:2px solid #0d9488;background:#f0fdfa;">
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem;">
            <span style="color:var(--primary);"><?= icon('target', '', 20) ?></span>
            <strong style="font-size:1rem;color:#0f766e;">
              Hasil Uji Dugaan Awal Anda: <?= htmlspecialchars($targetKondisi['nama']) ?> (<?= htmlspecialchars($targetKondisi['kode']) ?>)
            </strong>
          </div>
          <div style="display:flex;align-items:center;gap:0.8rem;flex-wrap:wrap;margin-top:0.4rem;font-size:0.9rem;">
            <span class="badge" style="background:<?= $targetEval['full'] ? '#10b981' : ($targetEval['score'] >= 50 ? '#0284c7' : '#f59e0b') ?>;color:white;font-weight:700;">
              <?= $targetEval['status_label'] ?>
            </span>
            <code style="background:#ffffff;padding:0.25rem 0.6rem;border-radius:6px;border:1px solid #99f6e4;color:#0f766e;font-size:0.82rem;">
              <?= htmlspecialchars($targetEval['formula']) ?>
            </code>
            <span style="color:#134e4a;font-size:0.88rem;">
              <?= htmlspecialchars($targetEval['modus_ponens']) ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

      <!-- 3. VERIFIKASI & LENGKAPI BUKTI (1-KLIK TANPA MULAI DARI NOL) -->
      <?php if (!empty($verifikasiOpsi)): ?>
        <div class="verify-section">
          <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.4rem;">
            <span style="color:#0284c7;"><?= icon('lightbulb', '', 24) ?></span>
            <h3 style="color:#0369a1;margin:0;font-size:1.2rem;">
              Lengkapi Bukti Diagnosis (1-Klik Cepat)
            </h3>
          </div>
          <p style="color:#334155;margin:0 0 1rem;font-size:0.92rem;line-height:1.5;">
            Mesin inferensi Backward Chaining menemukan gejala prasyarat yang belum terkonfirmasi. Jika Anda juga merasakan gejala di bawah ini, klik tombol untuk langsung membuktikannya ke 100%:
          </p>

          <div class="verify-action-grid">
            <?php foreach ($verifikasiOpsi as $v): ?>
              <div class="verify-card-item">
                <div>
                  <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;">
                    <span class="code-badge badge-g"><?= htmlspecialchars($v['gejala']['kode']) ?></span>
                    <strong style="color:#0f172a;font-size:0.95rem;"><?= htmlspecialchars($v['gejala']['nama']) ?></strong>
                  </div>
                  <p class="muted" style="font-size:0.82rem;margin:0 0 0.6rem;line-height:1.4;">
                    <?= htmlspecialchars($v['gejala']['deskripsi']) ?>
                  </p>
                  <div style="font-size:0.8rem;color:#0369a1;background:#f0f9ff;padding:0.35rem 0.65rem;border-radius:6px;border:1px solid #bae6fd;">
                    Melengkapi: <strong><?= htmlspecialchars($v['kondisi']['nama']) ?></strong> (<?= $v['score_sekarang'] ?>% → 100%)
                  </div>
                </div>

                <form method="post" action="konsultasi.php" style="margin:0;">
                  <?php foreach ($hasilDiagnosa['selected_set'] as $sid): ?>
                    <input type="hidden" name="gejala[]" value="<?= $sid ?>">
                  <?php endforeach; ?>
                  <input type="hidden" name="gejala[]" value="<?= $v['gejala']['id'] ?>">
                  <input type="hidden" name="target_goal_id" value="<?= $v['kondisi']['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;padding:0.6rem 0.8rem;">
                    <?= icon('check', '', 15) ?> + Konfirmasi Gejala Ini (Buktikan 100%)
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- 4. TABEL MATRIKS BUKTI MENYELURUH (7 KONDISI KULIT) -->
      <div class="evidence-matrix-card">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.6rem;margin-bottom:0.8rem;">
          <div>
            <h3 style="font-size:1.15rem;margin:0 0 0.2rem;color:#1e293b;display:flex;align-items:center;gap:0.5rem;">
              <span style="color:var(--primary);"><?= icon('clipboard', '', 20) ?></span> Laporan Bukti Diagnosis Menyeluruh (7 Kondisi Kulit)
            </h3>
            <p class="muted" style="font-size:0.85rem;margin:0;">
              Hasil pengujian penalaran Backward Chaining terhadap seluruh hipotesis pada basis pengetahuan.
            </p>
          </div>
          <span class="muted" style="font-size:0.82rem;">
            <?= count($hasilDiagnosa['selected_set']) ?> Gejala Dilaporkan
          </span>
        </div>

        <div class="table-wrap">
          <table class="evidence-table">
            <thead>
              <tr>
                <th style="min-width:210px;">Kondisi Kulit (Hipotesis)</th>
                <th style="min-width:140px;">Tingkat Bukti</th>
                <th style="min-width:130px;">Status Inferensi</th>
                <th style="min-width:180px;">Gejala Terpenuhi</th>
                <th style="min-width:180px;">Gejala Prasyarat Belum Ada</th>
                <th style="min-width:170px;">Klausa Horn</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($semuaBukti as $b): 
                $rowClass = $b['full'] ? 'row-proven' : ($b['is_target'] ? 'row-target' : '');
              ?>
                <tr class="<?= $rowClass ?>">
                  <td>
                    <div style="display:flex;align-items:center;gap:0.45rem;">
                      <span class="code-badge badge-h"><?= htmlspecialchars($b['kondisi']['kode']) ?></span>
                      <div>
                        <strong style="color:#0f172a;"><?= htmlspecialchars($b['kondisi']['nama']) ?></strong>
                        <?php if ($b['is_target']): ?>
                          <span class="badge" style="background:#0d9488;color:white;font-size:0.68rem;padding:0.15rem 0.45rem;margin-left:0.3rem;">Pilihan Anda</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="evidence-bar-wrap">
                      <div class="evidence-bar-fill <?= $b['full'] ? 'fill-proven' : ($b['score'] >= 50 ? 'fill-indikasi' : ($b['score'] > 0 ? 'fill-warning' : 'fill-empty')) ?>" style="width:<?= max(5, $b['score']) ?>%;"></div>
                    </div>
                    <strong><?= $b['score'] ?>%</strong>
                  </td>
                  <td>
                    <?php if ($b['full']): ?>
                      <span class="badge" style="background:#10b981;color:white;font-weight:700;font-size:0.75rem;">
                        <?= icon('check', '', 12) ?> TERBUKTI 100%
                      </span>
                    <?php elseif ($b['score'] >= 50): ?>
                      <span class="badge" style="background:#0284c7;color:white;font-weight:700;font-size:0.75rem;">
                        INDIKASI KUAT
                      </span>
                    <?php elseif ($b['score'] > 0): ?>
                      <span class="badge" style="background:#f59e0b;color:white;font-weight:700;font-size:0.75rem;">
                        OBSERVASI AWAL
                      </span>
                    <?php else: ?>
                      <span class="badge" style="background:#94a3b8;color:white;font-size:0.75rem;">
                        BELUM ADA BUKTI
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($b['matched_gejala'])): ?>
                      <div style="display:flex;flex-wrap:wrap;gap:0.3rem;">
                        <?php foreach ($b['matched_gejala'] as $mg): ?>
                          <span style="font-size:0.78rem;padding:0.2rem 0.5rem;background:#dcfce7;color:#15803d;border:1px solid #86efac;border-radius:12px;font-weight:600;">
                            <?= htmlspecialchars($mg['kode']) ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <span class="muted" style="font-size:0.82rem;">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($b['full']): ?>
                      <span style="color:#10b981;font-weight:700;font-size:0.82rem;display:inline-flex;align-items:center;gap:0.25rem;">
                        <?= icon('check-circle', '', 14) ?> Lengkap Terpenuhi
                      </span>
                    <?php elseif (!empty($b['missing_gejala'])): ?>
                      <div style="display:flex;flex-wrap:wrap;gap:0.3rem;">
                        <?php foreach ($b['missing_gejala'] as $mg): ?>
                          <span style="font-size:0.78rem;padding:0.2rem 0.5rem;background:#fef3c7;color:#b45309;border:1px solid #fde68a;border-radius:12px;" title="<?= htmlspecialchars($mg['nama']) ?>">
                            <?= htmlspecialchars($mg['kode']) ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <span class="muted" style="font-size:0.82rem;">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <code style="font-size:0.78rem;background:#f1f5f9;color:#2563eb;padding:0.25rem 0.5rem;border-radius:5px;display:inline-block;">
                      <?= htmlspecialchars($b['formula']) ?>
                    </code>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 5. RINGKASAN GEJALA YANG DILAPORKAN -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem;">
          <h3 style="font-size:1.05rem;margin:0;color:#334155;display:flex;align-items:center;gap:0.4rem;">
            <span style="color:var(--primary);"><?= icon('pin', '', 18) ?></span> Gejala yang Anda Laporkan:
          </h3>
          <a href="konsultasi.php?edit=1" class="btn btn-sm btn-secondary" style="font-size:0.82rem;padding:0.35rem 0.75rem;">
            <?= icon('settings', '', 14) ?> Sesuaikan Pilihan Gejala
          </a>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
          <?php foreach ($hasilDiagnosa['selected_set'] as $sid): 
            $gData = $hasilDiagnosa['gejala_map'][$sid] ?? null;
            if (!$gData) continue;
          ?>
            <span style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.75rem;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:20px;font-size:0.84rem;color:#065f46;font-weight:600;">
              <?= icon('check', 'svg-icon-sm', 13) ?> <?= htmlspecialchars($gData['kode']) ?>: <?= htmlspecialchars($gData['nama']) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 6. TOMBOL AKSI UTAMA -->
      <div class="form-actions" style="margin-top:1.5rem;">
        <a href="konsultasi.php?edit=1" class="btn btn-secondary">
          <?= icon('search', '', 16) ?> Sesuaikan Gejala Terpilih
        </a>
        <a href="konsultasi.php?reset=1" class="btn btn-primary">
          <?= icon('refresh', '', 16) ?> Konsultasi Baru (Reset)
        </a>
        <a href="riwayat.php" class="btn btn-secondary">
          <?= icon('clipboard', '', 16) ?> Lihat Riwayat Pemeriksaan
        </a>
        <a href="index.php" class="btn btn-secondary">
          <?= icon('home', '', 16) ?> Beranda
        </a>
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
    updateCounter();

    document.querySelectorAll('.sidebar a').forEach(a => {
      a.addEventListener('click', () => toggleMenu(false));
    });
  </script>
</body>
</html>
