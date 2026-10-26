<?php
session_start();
require_once __DIR__ . "/config/database.php";

$pesan = "";
$error = "";

// Proses Hapus Riwayat
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "hapus") {
    $idHapus = (int)($_POST["id"] ?? 0);
    if ($idHapus > 0) {
        try {
            $conn->begin_transaction();
            $stmt1 = $conn->prepare("DELETE FROM pemeriksaan_gejala WHERE pemeriksaan_id = ?");
            $stmt1->bind_param("i", $idHapus);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("DELETE FROM pemeriksaan WHERE id = ?");
            $stmt2->bind_param("i", $idHapus);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            header("Location: riwayat.php?status=hapus&id={$idHapus}");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = "Gagal menghapus riwayat: " . $e->getMessage();
        }
    }
}

if (($_GET["status"] ?? "") === "hapus") {
    $idHapus = (int)($_GET["id"] ?? 0);
    $pesan = $idHapus > 0
        ? "Riwayat pemeriksaan #{$idHapus} berhasil dihapus."
        : "Riwayat pemeriksaan berhasil dihapus.";
}

// Ambil seluruh riwayat pemeriksaan
$riwayat = [];
$query = $conn->query("SELECT id, tanggal, hasil FROM pemeriksaan ORDER BY id DESC");

while ($row = $query->fetch_assoc()) {
    $idPemeriksaan = (int)$row["id"];

    // Ambil kode-kode gejala yang dipilih
    $stmt = $conn->prepare("
        SELECT g.kode, g.nama
        FROM pemeriksaan_gejala pg
        JOIN gejala g ON g.id = pg.gejala_id
        WHERE pg.pemeriksaan_id = ?
        ORDER BY g.kode ASC
    ");
    $stmt->bind_param("i", $idPemeriksaan);
    $stmt->execute();
    $resGejala = $stmt->get_result();

    $gejalaList = [];
    while ($g = $resGejala->fetch_assoc()) {
        $gejalaList[] = $g["kode"];
    }
    $stmt->close();

    $row["gejala_str"] = !empty($gejalaList) ? implode(", ", $gejalaList) : "-";

    // Format tampilan hasil
    $hasilRaw = $row["hasil"];
    $decoded = json_decode($hasilRaw, true);
    if (is_array($decoded)) {
        if (!empty($decoded)) {
            $row["hasil_tampil"] = $decoded[0]["nama"] ?? "Teridentifikasi";
        } else {
            $row["hasil_tampil"] = "Tidak teridentifikasi";
        }
    } else {
        $row["hasil_tampil"] = $hasilRaw ?: "Tidak teridentifikasi";
    }

    $riwayat[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Konsultasi - SkinExpert 🌿</title>
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
    <h1>📋 Riwayat Konsultasi</h1>
    <p class="muted" style="margin-bottom:1.2rem">Daftar pemeriksaan kulit wajah yang pernah dilakukan.</p>

    <?php if (!empty($pesan)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Tanggal</th>
              <th>Gejala Terpilih</th>
              <th>Hasil Diagnosa</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($riwayat)): ?>
              <?php foreach ($riwayat as $idx => $r): ?>
                <tr>
                  <td><?= $idx + 1 ?></td>
                  <td><?= htmlspecialchars($r["tanggal"]) ?></td>
                  <td class="muted"><?= htmlspecialchars($r["gejala_str"]) ?></td>
                  <td><strong><?= htmlspecialchars($r["hasil_tampil"]) ?></strong></td>
                  <td>
                    <form method="post" action="riwayat.php" onsubmit="return confirm('Hapus riwayat pemeriksaan ini?')" style="display:inline">
                      <input type="hidden" name="aksi" value="hapus">
                      <input type="hidden" name="id" value="<?= $r["id"] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="muted">Belum ada riwayat konsultasi yang tersimpan.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="form-actions">
      <a href="konsultasi.php" class="btn btn-primary">💬 Mulai Konsultasi Baru</a>
      <a href="index.php" class="btn btn-secondary">🏠 Beranda</a>
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
