<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$jenisPesan = "pesan";
$editData = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Permintaan tidak valid. Muat ulang halaman dan coba lagi.");
    }

    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "simpan") {
        $id = (int) ($_POST["id"] ?? 0);
        $kode = strtoupper(trim($_POST["kode"] ?? ""));
        $nama = trim($_POST["nama"] ?? "");
        $deskripsi = trim($_POST["deskripsi"] ?? "");
        $saran = trim($_POST["saran"] ?? "");

        if ($kode === "" || $nama === "") {
            $pesan = "Kode dan nama kondisi wajib diisi.";
            $jenisPesan = "pesan error";
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $kode)) {
            $pesan = "Kode hanya boleh berisi huruf, angka, tanda minus, atau garis bawah.";
            $jenisPesan = "pesan error";
        } else {
            try {
                if ($id > 0) {
                    $stmt = $conn->prepare(
                        "UPDATE kondisi_kulit
                         SET kode=?, nama=?, deskripsi=?, saran=?
                         WHERE id=?"
                    );
                    $stmt->bind_param(
                        "ssssi", $kode, $nama, $deskripsi, $saran, $id
                    );
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO kondisi_kulit (kode, nama, deskripsi, saran)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param(
                        "ssss", $kode, $nama, $deskripsi, $saran
                    );
                }

                $stmt->execute();
                $stmt->close();

                header("Location: kondisi.php?status=sukses");
                exit;
            } catch (Throwable $e) {
                $isUnique = ($e->getCode() == 1062) || (stripos($e->getMessage(), 'UNIQUE') !== false);
                if ($isUnique) {
                    $pesan = "Kode kondisi sudah digunakan. Gunakan kode lain.";
                } else {
                    $pesan = "Data gagal disimpan. Periksa kembali data.";
                }
                $jenisPesan = "pesan error";
            }
        }
    }

    if ($aksi === "hapus") {
        $id = (int) ($_POST["id"] ?? 0);

        try {
            $conn->begin_transaction();

            // 1. Dapatkan aturan-aturan yang merujuk kondisi kulit ini
            $stmtR = $conn->prepare("SELECT id FROM rules WHERE kondisi_kulit_id = ?");
            $stmtR->bind_param("i", $id);
            $stmtR->execute();
            $resR = $stmtR->get_result();
            $ruleIds = [];
            while ($rowR = $resR->fetch_assoc()) {
                $ruleIds[] = (int) $rowR["id"];
            }
            $stmtR->close();

            // 2. Hapus rule_gejala untuk aturan-aturan tersebut
            foreach ($ruleIds as $rId) {
                $stmtRg = $conn->prepare("DELETE FROM rule_gejala WHERE rule_id = ?");
                $stmtRg->bind_param("i", $rId);
                $stmtRg->execute();
                $stmtRg->close();
            }

            // 3. Hapus aturan dari tabel rules
            $stmtDelR = $conn->prepare("DELETE FROM rules WHERE kondisi_kulit_id = ?");
            $stmtDelR->bind_param("i", $id);
            $stmtDelR->execute();
            $stmtDelR->close();

            // 4. Hapus kondisi kulit
            $stmt = $conn->prepare("DELETE FROM kondisi_kulit WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            header("Location: kondisi.php?status=hapus");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $pesan = "Kondisi gagal dihapus: " . $e->getMessage();
            $jenisPesan = "pesan error";
        }
    }
}

if (($_GET["status"] ?? "") === "sukses") {
    $pesan = "Data kondisi berhasil disimpan.";
} elseif (($_GET["status"] ?? "") === "hapus") {
    $pesan = "Data kondisi berhasil dihapus.";
}

if (isset($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM kondisi_kulit WHERE id=?");
    $stmt->bind_param("i", $idEdit);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM kondisi_kulit ORDER BY id ASC");

$judulHalaman = "Data Kondisi Kulit";
require __DIR__ . "/header.php";
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
    <div style="color:var(--primary);"><?= icon('target', '', 28) ?></div>
    <h1 style="margin:0;">Data Kondisi Kulit (H)</h1>
</div>
<p style="color:var(--text-muted);margin-bottom:24px;">Kelola kesimpulan hipotesis dan rekomendasi saran klinis sistem pakar.</p>

<?php if ($pesan !== ""): ?>
    <div class="<?= htmlspecialchars($jenisPesan) ?>">
        <?= htmlspecialchars($pesan) ?>
    </div>
<?php endif; ?>

<section class="card">
    <h3 style="display:flex;align-items:center;gap:8px;margin-top:0;">
        <?= $editData ? icon('edit', '', 18) . " Edit Kondisi" : icon('plus', '', 18) . " Tambah Kondisi" ?>
    </h3>

    <form method="post">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id"
               value="<?= (int) ($editData["id"] ?? 0) ?>">

        <label for="kode">Kode Kondisi</label>
        <input id="kode" type="text" name="kode" required
               placeholder="Contoh: K05"
               value="<?= htmlspecialchars($editData["kode"] ?? "") ?>">

        <label for="nama">Nama Kondisi</label>
        <input id="nama" type="text" name="nama" required maxlength="150"
               placeholder="Contoh: Indikasi kulit kering"
               value="<?= htmlspecialchars($editData["nama"] ?? "") ?>">

        <label for="deskripsi">Deskripsi Kondisi</label>
        <textarea id="deskripsi" name="deskripsi"><?= htmlspecialchars($editData["deskripsi"] ?? "") ?></textarea>

        <label for="saran">Saran Umum</label>
        <textarea id="saran" name="saran"><?= htmlspecialchars($editData["saran"] ?? "") ?></textarea>

        <br><br>
        <button type="submit"><?= icon('check', '', 16) ?> Simpan Kondisi</button>
        <?php if ($editData): ?>
            <a class="btn btn-secondary" href="kondisi.php"><?= icon('close', '', 14) ?> Batal Edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h3 style="margin-top:0;">Daftar Kondisi Kulit</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode</th>
                    <th>Nama Kondisi</th>
                    <th>Deskripsi</th>
                    <th>Saran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row["kode"]) ?></strong></td>
                        <td><?= htmlspecialchars($row["nama"]) ?></td>
                        <td style="color:#64748b;font-size:0.9rem;"><?= htmlspecialchars($row["deskripsi"] ?? "") ?></td>
                        <td style="color:#64748b;font-size:0.9rem;"><?= htmlspecialchars($row["saran"] ?? "") ?></td>
                        <td>
                            <a class="btn btn-warning"
                               href="kondisi.php?edit=<?= (int) $row["id"] ?>">
                                <?= icon('edit', '', 14) ?> Edit
                            </a>

                            <form method="post"
                                  style="display:inline-block"
                                  onsubmit="return confirm('Yakin ingin menghapus kondisi ini?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id"
                                       value="<?= (int) $row["id"] ?>">
                                <button type="submit" class="btn-danger">
                                    <?= icon('trash', '', 14) ?> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">Belum ada data kondisi.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . "/footer.php"; ?>
