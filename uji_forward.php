
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/proses/forward_chaining.php';

$gejala = [];
$result = $conn->query(
    "SELECT id, kode, nama FROM gejala ORDER BY kode"
);

while ($row = $result->fetch_assoc()) {
    $gejala[] = $row;
}

$diagnosis = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pilihan = $_POST['gejala'] ?? [];

    if (!is_array($pilihan)) {
        $pilihan = [];
    }

    $diagnosis = forwardChaining($conn, $pilihan);

    if (empty($diagnosis['fakta'])) {
        $error = $diagnosis['pesan'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Forward Chaining - SkinExpert</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 850px;
            margin: 35px auto;
            padding: 0 18px;
            line-height: 1.6;
            background: #f5f7f6;
            color: #26352e;
        }

        .card {
            background: white;
            padding: 22px;
            margin-bottom: 18px;
            border-radius: 12px;
            border: 1px solid #e1e8e3;
        }

        .gejala {
            display: block;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        button {
            background: #176b45;
            color: white;
            border: 0;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
        }

        .hasil {
            border-left: 5px solid #278357;
            padding: 12px;
            background: #f0f8f3;
            margin: 12px 0;
        }

        .error {
            color: #a12626;
        }

        code {
            background: #edf0ee;
            padding: 3px 6px;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <h1>SkinExpert</h1>
    <p>Pengujian algoritma Forward Chaining</p>

    <div class="card">
        <h2>Pilih Gejala</h2>
        <p>Pilih gejala yang ingin dijadikan fakta awal.</p>

        <form method="POST">
            <?php foreach ($gejala as $g): ?>
                <label class="gejala">
                    <input
                        type="checkbox"
                        name="gejala[]"
                        value="<?= (int) $g['id'] ?>"
                        <?= (
                            isset($_POST['gejala']) &&
                            is_array($_POST['gejala']) &&
                            in_array(
                                (string) $g['id'],
                                array_map('strval', $_POST['gejala']),
                                true
                            )
                        ) ? 'checked' : '' ?>
                    >

                    <strong>
                        <?= htmlspecialchars($g['kode']) ?>
                    </strong>
                    -
                    <?= htmlspecialchars($g['nama']) ?>
                </label>
            <?php endforeach; ?>

            <button type="submit">Proses Forward Chaining</button>
        </form>
    </div>

    <?php if ($error !== ''): ?>
        <div class="card error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($diagnosis !== null && empty($error)): ?>

        <div class="card">
            <h2>Hasil Inferensi</h2>

            <?php if (!empty($diagnosis['hasil'])): ?>

                <?php foreach ($diagnosis['hasil'] as $h): ?>
                    <div class="hasil">
                        <h3>
                            <?= htmlspecialchars($h['kode']) ?> -
                            <?= htmlspecialchars($h['nama']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($h['deskripsi'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Aturan terpenuhi:</strong>
                            <?= htmlspecialchars(implode(', ', $h['aturan'])) ?>
                        </p>

                        <p>
                            <strong>Saran:</strong>
                            <?= htmlspecialchars($h['saran'] ?? '') ?>
                        </p>
                    </div>
                <?php endforeach; ?>

                <p>
                    Hasil ini merupakan identifikasi awal berdasarkan
                    aturan contoh, bukan diagnosis medis.
                </p>

            <?php else: ?>

                <p>
                    Belum ada kesimpulan yang didukung aturan.
                    Gejala yang dipilih belum memenuhi seluruh prasyarat
                    aturan aktif yang tersedia.
                </p>

            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Jejak Penalaran</h2>
            <p>Berikut status setiap aturan yang diperiksa sistem.</p>

            <?php foreach ($diagnosis['jejak'] as $j): ?>
                <div>
                    <strong>
                        <?= htmlspecialchars($j['rule']) ?>
                    </strong>:

                    <?= htmlspecialchars($j['status']) ?>

                    <?php if (!empty($j['gejala_belum_terpenuhi'])): ?>
                        <br>
                        <small>
                            Gejala yang belum terpenuhi:
                            <?= htmlspecialchars(
                                implode(', ', $j['gejala_belum_terpenuhi'])
                            ) ?>
                        </small>
                    <?php endif; ?>
                </div>
                <hr>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</body>
</html>
