<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$jenisPesan = "pesan";
$editData = null;
$gejalaTerpilih = [];

function ambilGejala($conn): array
{
    $data = [];
    $result = $conn->query("SELECT id, kode, nama FROM gejala ORDER BY kode");

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function ambilKondisi($conn): array
{
    $data = [];
    $result = $conn->query(
        "SELECT id, kode, nama FROM kondisi_kulit ORDER BY kode"
    );

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

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
        $kondisiId = (int) ($_POST["kondisi_kulit_id"] ?? 0);
        $aktif = isset($_POST["aktif"]) ? 1 : 0;

        $pilihanGejala = $_POST["gejala"] ?? [];
        if (!is_array($pilihanGejala)) {
            $pilihanGejala = [];
        }

        $pilihanGejala = array_values(array_unique(array_filter(
            array_map("intval", $pilihanGejala),
            fn($v) => $v > 0
        )));
        $gejalaTerpilih = $pilihanGejala;

        if ($kode === "" || $nama === "") {
            $pesan = "Kode dan nama aturan wajib diisi.";
            $jenisPesan = "pesan error";
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $kode)) {
            $pesan = "Format kode aturan tidak valid.";
            $jenisPesan = "pesan error";
        } elseif ($kondisiId <= 0 || count($pilihanGejala) === 0) {
            $pesan = "Pilih kondisi hasil dan minimal satu gejala prasyarat.";
            $jenisPesan = "pesan error";
        } else {
            // Pastikan kondisi hasil benar-benar tersedia.
            $stmt = $conn->prepare(
                "SELECT id FROM kondisi_kulit WHERE id=?"
            );
            $stmt->bind_param("i", $kondisiId);
            $stmt->execute();
            $kondisiValid = $stmt->get_result()->num_rows === 1;
            $stmt->close();

            // Pastikan semua gejala yang dipilih ada di database.
            $gejalaValid = [];
            $resultGejala = $conn->query("SELECT id FROM gejala");
            while ($g = $resultGejala->fetch_assoc()) {
                $gejalaValid[] = (int) $g["id"];
            }

            $semuaGejalaValid = count(array_diff(
                $pilihanGejala,
                $gejalaValid
            )) === 0;

            if (!$kondisiValid || !$semuaGejalaValid) {
                $pesan = "Kondisi atau gejala yang dipilih tidak valid.";
                $jenisPesan = "pesan error";
            } else {
                try {
                    $conn->begin_transaction();

                    if ($id > 0) {
                        $stmt = $conn->prepare(
                            "UPDATE rules
                             SET kode=?, nama=?, kondisi_kulit_id=?, aktif=?
                             WHERE id=?"
                        );
                        $stmt->bind_param(
                            "ssiii", $kode, $nama, $kondisiId, $aktif, $id
                        );
                        $stmt->execute();
                        $stmt->close();

                        // Periksa bahwa aturan yang diedit memang ada.
                        $stmt = $conn->prepare(
                            "SELECT id FROM rules WHERE id=?"
                        );
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $aturanAda = $stmt->get_result()->num_rows === 1;
                        $stmt->close();

                        if (!$aturanAda) {
                            throw new RuntimeException("Aturan tidak ditemukan.");
                        }

                        $stmt = $conn->prepare(
                            "DELETE FROM rule_gejala WHERE rule_id=?"
                        );
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $stmt->close();

                        $ruleId = $id;
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO rules
                             (kode, nama, kondisi_kulit_id, aktif)
                             VALUES (?, ?, ?, ?)"
                        );
                        $stmt->bind_param(
                            "ssii", $kode, $nama, $kondisiId, $aktif
                        );
                        $stmt->execute();
                        $ruleId = $conn->insert_id;
                        $stmt->close();
                    }

                    $stmt = $conn->prepare(
                        "INSERT INTO rule_gejala (rule_id, gejala_id)
                         VALUES (?, ?)"
                    );

                    foreach ($pilihanGejala as $gejalaId) {
                        $stmt->bind_param("ii", $ruleId, $gejalaId);
                        $stmt->execute();
                    }

                    $stmt->close();
                    $conn->commit();

                    header("Location: aturan.php?status=sukses");
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();

                    $isUnique = ($e->getCode() == 1062) || (stripos($e->getMessage(), 'UNIQUE') !== false);
                    if ($isUnique) {
                        $pesan = "Kode aturan sudah digunakan.";
                    } else {
                        $pesan = "Aturan gagal disimpan. Periksa data lalu coba lagi: " . $e->getMessage();
                    }
                    $jenisPesan = "pesan error";
                }
            }
        }
    }

    if ($aksi === "hapus") {
        $id = (int) ($_POST["id"] ?? 0);

        try {
            $conn->begin_transaction();

            $stmtRg = $conn->prepare("DELETE FROM rule_gejala WHERE rule_id=?");
            $stmtRg->bind_param("i", $id);
            $stmtRg->execute();
            $stmtRg->close();

            $stmt = $conn->prepare("DELETE FROM rules WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            header("Location: aturan.php?status=hapus");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $pesan = "Aturan gagal dihapus: " . $e->getMessage();
            $jenisPesan = "pesan error";
        }
    }

    if ($aksi === "toggle") {
        $id = (int) ($_POST["id"] ?? 0);

        $stmt = $conn->prepare(
            "UPDATE rules SET aktif = CASE WHEN aktif=1 THEN 0 ELSE 1 END WHERE id=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: aturan.php?status=toggle");
        exit;
    }
}

if (($_GET["status"] ?? "") === "sukses") {
    $pesan = "Aturan berhasil disimpan.";
} elseif (($_GET["status"] ?? "") === "hapus") {
    $pesan = "Aturan berhasil dihapus.";
} elseif (($_GET["status"] ?? "") === "toggle") {
    $pesan = "Status aturan berhasil diubah.";
}

if (isset($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];

    $stmt = $conn->prepare("SELECT * FROM rules WHERE id=?");
    $stmt->bind_param("i", $idEdit);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editData) {
        $stmt = $conn->prepare(
            "SELECT gejala_id FROM rule_gejala WHERE rule_id=?"
        );
        $stmt->bind_param("i", $idEdit);
        $stmt->execute();
        $resultPilihan = $stmt->get_result();

        while ($row = $resultPilihan->fetch_assoc()) {
            $gejalaTerpilih[] = (int) $row["gejala_id"];
        }
        $stmt->close();
    }
}

$daftarGejala = ambilGejala($conn);
$daftarKondisi = ambilKondisi($conn);

$sqlDaftar = "
    SELECT r.id, r.kode, r.nama, r.aktif,
           k.kode AS kode_kondisi, k.nama AS nama_kondisi
    FROM rules r
    JOIN kondisi_kulit k ON k.id = r.kondisi_kulit_id
    ORDER BY r.id ASC
";
$resultAturan = $conn->query($sqlDaftar);

$judulHalaman = "Data Aturan";
require __DIR__ . "/header.php";
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
    <div style="color:var(--primary);"><?= icon('rule', '', 28) ?></div>
    <h1 style="margin:0;">Data Basis Aturan (R)</h1>
</div>
<p style="color:var(--text-muted);margin-bottom:24px;">
    Basis aturan inferensi Backward Chaining: membuktikan apakah target hipotesis (Goal $Q$) terbukti melalui pemenuhan premis prasyarat $(P_1 \land P_2 \dots) \rightarrow Q$.
</p>

<?php if ($pesan !== ""): ?>
    <div class="<?= htmlspecialchars($jenisPesan) ?>">
        <?= htmlspecialchars($pesan) ?>
    </div>
<?php endif; ?>

<?php if (count($daftarGejala) === 0 || count($daftarKondisi) === 0): ?>
    <div class="pesan error">
        Tambahkan setidaknya satu gejala dan satu kondisi kulit sebelum
        membuat aturan.
    </div>
<?php else: ?>

<section class="card">
    <h3 style="display:flex;align-items:center;gap:8px;margin-top:0;">
        <?= $editData ? icon('edit', '', 18) . " Edit Aturan" : icon('plus', '', 18) . " Tambah Aturan" ?>
    </h3>

    <form method="post">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id"
               value="<?= (int) ($editData["id"] ?? 0) ?>">

        <label for="kode">Kode Aturan</label>
        <input id="kode" type="text" name="kode" required
               placeholder="Contoh: R05"
               value="<?= htmlspecialchars($editData["kode"] ?? "") ?>">

        <label for="nama">Nama Aturan</label>
        <input id="nama" type="text" name="nama" required maxlength="150"
               placeholder="Contoh: Identifikasi indikasi kulit kering"
               value="<?= htmlspecialchars($editData["nama"] ?? "") ?>">

        <label for="kondisi">Kondisi Hasil (Goal / Hipotesis)</label>
        <select id="kondisi" name="kondisi_kulit_id" required>
            <option value="">-- Pilih kondisi hasil --</option>
            <?php foreach ($daftarKondisi as $k): ?>
                <option value="<?= (int) $k["id"] ?>"
                    <?= (int) ($editData["kondisi_kulit_id"] ?? 0) === (int) $k["id"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($k["kode"] . " — " . $k["nama"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Gejala Prasyarat (Premis P)</label>
        <p style="color:#64748b;font-size:0.9rem;margin:0 0 10px;">Pilih semua gejala yang wajib terpenuhi untuk aturan ini:</p>

        <div class="grid">
            <?php foreach ($daftarGejala as $g): ?>
                <label style="display:flex;gap:8px;align-items:flex-start;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;">
                    <input type="checkbox" name="gejala[]"
                           value="<?= (int) $g["id"] ?>"
                           <?= in_array((int) $g["id"], $gejalaTerpilih, true) ? "checked" : "" ?>>
                    <span>
                        <strong><?= htmlspecialchars($g["kode"]) ?></strong><br>
                        <span style="font-size:0.9rem;color:#334155;"><?= htmlspecialchars($g["nama"]) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <br>
        <label style="display:flex;gap:8px;align-items:center;cursor:pointer;">
            <input type="checkbox" name="aktif" value="1"
                <?= !$editData || (int) $editData["aktif"] === 1 ? "checked" : "" ?>>
            <span><strong>Aturan aktif</strong></span>
        </label>

        <br>
        <button type="submit"><?= icon('check', '', 16) ?> Simpan Aturan</button>

        <?php if ($editData): ?>
            <a class="btn btn-secondary" href="aturan.php"><?= icon('close', '', 14) ?> Batal Edit</a>
        <?php endif; ?>
    </form>
</section>

<?php endif; ?>

<section class="card">
    <h3 style="margin-top:0;">Daftar Aturan</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Aturan</th>
                    <th>Gejala Prasyarat</th>
                    <th>Kondisi Hasil</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($resultAturan->num_rows > 0): ?>
                <?php while ($r = $resultAturan->fetch_assoc()): ?>
                    <?php
                    $stmtG = $conn->prepare(
                        "SELECT g.kode, g.nama
                         FROM rule_gejala rg
                         JOIN gejala g ON g.id=rg.gejala_id
                         WHERE rg.rule_id=?
                         ORDER BY g.kode"
                    );
                    $stmtG->bind_param("i", $r["id"]);
                    $stmtG->execute();
                    $resultG = $stmtG->get_result();
                    $namaGejala = [];

                    while ($g = $resultG->fetch_assoc()) {
                        $namaGejala[] = $g["kode"] . " — " . $g["nama"];
                    }
                    $stmtG->close();
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r["kode"]) ?></strong></td>
                        <td><?= htmlspecialchars($r["nama"]) ?></td>
                        <td style="font-size:0.9rem;">
                            <?php if ($namaGejala): ?>
                                <?= htmlspecialchars(implode(", ", $namaGejala)) ?>
                            <?php else: ?>
                                <span class="error">Belum ada prasyarat</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($r["kode_kondisi"] . " — " . $r["nama_kondisi"]) ?></strong>
                        </td>
                        <td>
                            <span style="font-weight:600;color:<?= (int) $r["aktif"] === 1 ? '#059669' : '#dc2626' ?>;">
                                <?= (int) $r["aktif"] === 1 ? "Aktif" : "Nonaktif" ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-warning"
                               href="aturan.php?edit=<?= (int) $r["id"] ?>">
                                <?= icon('edit', '', 14) ?> Edit
                            </a>

                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                <input type="hidden" name="aksi" value="toggle">
                                <input type="hidden" name="id"
                                       value="<?= (int) $r["id"] ?>">
                                <button type="submit" class="btn-secondary">
                                    <?= icon('refresh', '', 14) ?> <?= (int) $r["aktif"] === 1 ? "Nonaktifkan" : "Aktifkan" ?>
                                </button>
                            </form>

                            <form method="post" style="display:inline-block"
                                  onsubmit="return confirm('Yakin ingin menghapus aturan ini?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id"
                                       value="<?= (int) $r["id"] ?>">
                                <button type="submit" class="btn-danger">
                                    <?= icon('trash', '', 14) ?> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">Belum ada aturan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . "/footer.php"; ?>
