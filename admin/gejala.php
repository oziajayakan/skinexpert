<?php
session_start();
require_once __DIR__ . "/../config/database.php";

$pesan = "";
$jenisPesan = "pesan";
$editData = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "simpan") {
        $id = (int) ($_POST["id"] ?? 0);
        $kode = strtoupper(trim($_POST["kode"] ?? ""));
        $nama = trim($_POST["nama"] ?? "");
        $deskripsi = trim($_POST["deskripsi"] ?? "");

        if ($kode === "" || $nama === "") {
            $pesan = "Kode dan nama gejala wajib diisi.";
            $jenisPesan = "pesan error";
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $kode)) {
            $pesan = "Kode hanya boleh berisi huruf, angka, tanda minus, atau garis bawah.";
            $jenisPesan = "pesan error";
        } else {
            try {
                if ($id > 0) {
                    $stmt = $conn->prepare(
                        "UPDATE gejala SET kode=?, nama=?, deskripsi=? WHERE id=?"
                    );
                    $stmt->bind_param("sssi", $kode, $nama, $deskripsi, $id);
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO gejala (kode, nama, deskripsi) VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param("sss", $kode, $nama, $deskripsi);
                }

                $stmt->execute();
                $stmt->close();

                header("Location: gejala.php?status=sukses");
                exit;
            } catch (Throwable $e) {
                $isUnique = ($e->getCode() == 1062) || (stripos($e->getMessage(), 'UNIQUE') !== false);
                $pesan = $isUnique
                    ? "Kode gejala sudah digunakan. Gunakan kode lain."
                    : "Data gagal disimpan. Periksa kembali data yang dimasukkan.";
                $jenisPesan = "pesan error";
            }
        }
    }

    if ($aksi === "hapus") {
        $id = (int) ($_POST["id"] ?? 0);

        try {
            $conn->begin_transaction();

            // 1. Hapus relasi di rule_gejala
            $stmtRg = $conn->prepare("DELETE FROM rule_gejala WHERE gejala_id=?");
            $stmtRg->bind_param("i", $id);
            $stmtRg->execute();
            $stmtRg->close();

            // 2. Hapus relasi di pemeriksaan_gejala
            $stmtPg = $conn->prepare("DELETE FROM pemeriksaan_gejala WHERE gejala_id=?");
            $stmtPg->bind_param("i", $id);
            $stmtPg->execute();
            $stmtPg->close();

            // 3. Hapus data gejala
            $stmt = $conn->prepare("DELETE FROM gejala WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            header("Location: gejala.php?status=hapus");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $pesan = "Gejala gagal dihapus: " . $e->getMessage();
            $jenisPesan = "pesan error";
        }
    }
}

if (isset($_GET["status"])) {
    if ($_GET["status"] === "sukses") $pesan = "Data gejala berhasil disimpan.";
    if ($_GET["status"] === "hapus") $pesan = "Data gejala berhasil dihapus.";
}

if (isset($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM gejala WHERE id=?");
    $stmt->bind_param("i", $idEdit);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM gejala ORDER BY id ASC");

$judulHalaman = "Data Gejala";
require __DIR__ . "/header.php";
?>

<h1>Data Gejala</h1>
<p>Kelola gejala yang dapat dipilih pengguna saat melakukan pemeriksaan.</p>

<?php if ($pesan !== ""): ?>
    <div class="<?= htmlspecialchars($jenisPesan) ?>">
        <?= htmlspecialchars($pesan) ?>
    </div>
<?php endif; ?>

<section class="card">
    <h3><?= $editData ? "Edit Gejala" : "Tambah Gejala" ?></h3>

    <form method="post">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id"
               value="<?= (int) ($editData["id"] ?? 0) ?>">

        <label for="kode">Kode Gejala</label>
        <input type="text" id="kode" name="kode" required
               placeholder="Contoh: G08"
               value="<?= htmlspecialchars($editData["kode"] ?? "") ?>">

        <label for="nama">Nama Gejala</label>
        <input type="text" id="nama" name="nama" required
               maxlength="150"
               placeholder="Contoh: Kulit terasa kering"
               value="<?= htmlspecialchars($editData["nama"] ?? "") ?>">

        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi"
                  placeholder="Penjelasan singkat gejala"><?= htmlspecialchars($editData["deskripsi"] ?? "") ?></textarea>

        <br><br>
        <button type="submit">Simpan Gejala</button>

        <?php if ($editData): ?>
            <a class="btn btn-secondary" href="gejala.php">Batal Edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h3>Daftar Gejala</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode</th>
                    <th>Nama Gejala</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row["kode"]) ?></td>
                        <td><?= htmlspecialchars($row["nama"]) ?></td>
                        <td><?= htmlspecialchars($row["deskripsi"] ?? "") ?></td>
                        <td>
                            <a class="btn btn-warning"
                               href="gejala.php?edit=<?= (int) $row["id"] ?>">
                                Edit
                            </a>

                            <form method="post"
                                  onsubmit="return confirm('Yakin ingin menghapus gejala ini?');"
                                  style="display:inline-block">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id"
                                       value="<?= (int) $row["id"] ?>">
                                <button class="btn-danger" type="submit">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Belum ada data gejala.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . "/footer.php"; ?>
