<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/backward_chaining.php';

$engine = new BackwardChainingExpert($conn);

// Inisialisasi state sesi jika belum ada
if (!isset($_SESSION['bc_state'])) {
    $_SESSION['bc_state'] = [
        'fakta' => [],        // [gejala_id => bool]
        'riwayat_jawaban' => [], // [['gejala_id' => ..., 'jawaban' => ...]]
        'target_goal_id' => null,
        'selesai' => false,
        'hasil' => null
    ];
}

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? 'status';

function kirimRespon(bool $sukses, string $pesan, array $data = []): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    echo json_encode([
        'sukses' => $sukses,
        'pesan' => $pesan,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    switch ($aksi) {
        case 'mulai':
        case 'reset':
            $targetGoalId = isset($_POST['target_goal_id']) && (int)$_POST['target_goal_id'] > 0 
                ? (int)$_POST['target_goal_id'] 
                : null;

            $_SESSION['bc_state'] = [
                'fakta' => [],
                'riwayat_jawaban' => [],
                'target_goal_id' => $targetGoalId,
                'selesai' => false,
                'hasil' => null
            ];

            $eval = $engine->evaluasi([], $targetGoalId);
            $_SESSION['bc_state']['hasil'] = $eval;

            kirimRespon(true, "Konsultasi backward chaining berhasil diinisialisasi.", [
                'state' => $eval,
                'goals' => $engine->ambilSemuaGoals(),
                'target_goal_id' => $targetGoalId
            ]);
            break;

        case 'status':
            $state = $_SESSION['bc_state'];
            $eval = $engine->evaluasi($state['fakta'], $state['target_goal_id']);
            $_SESSION['bc_state']['hasil'] = $eval;

            kirimRespon(true, "Status konsultasi terkini.", [
                'state' => $eval,
                'goals' => $engine->ambilSemuaGoals(),
                'target_goal_id' => $state['target_goal_id'],
                'riwayat_jawaban' => $state['riwayat_jawaban']
            ]);
            break;

        case 'jawab':
            $gejalaId = (int)($_POST['gejala_id'] ?? 0);
            $jawaban = isset($_POST['jawaban']) && ((string)$_POST['jawaban'] === '1' || $_POST['jawaban'] === true || $_POST['jawaban'] === 'true');

            if ($gejalaId <= 0) {
                kirimRespon(false, "ID gejala tidak valid.");
            }

            $_SESSION['bc_state']['fakta'][$gejalaId] = $jawaban;
            $_SESSION['bc_state']['riwayat_jawaban'][] = [
                'gejala_id' => $gejalaId,
                'jawaban' => $jawaban,
                'waktu' => date('H:i:s')
            ];

            $targetGoalId = $_SESSION['bc_state']['target_goal_id'];
            $eval = $engine->evaluasi($_SESSION['bc_state']['fakta'], $targetGoalId);
            $_SESSION['bc_state']['hasil'] = $eval;

            // Jika inferensi selesai, simpan otomatis ke tabel riwayat jika belum tersimpan
            $pemeriksaanId = null;
            if ($eval['selesai']) {
                $pemeriksaanId = simpanRiwayat($conn, $eval, $_SESSION['bc_state']['fakta']);
            }

            kirimRespon(true, "Jawaban berhasil diproses.", [
                'state' => $eval,
                'pemeriksaan_id' => $pemeriksaanId,
                'riwayat_jawaban' => $_SESSION['bc_state']['riwayat_jawaban']
            ]);
            break;

        case 'undo':
            if (empty($_SESSION['bc_state']['riwayat_jawaban'])) {
                kirimRespon(false, "Belum ada jawaban untuk dibatalkan.");
            }

            // Hapus jawaban terakhir
            $terakhir = array_pop($_SESSION['bc_state']['riwayat_jawaban']);
            $idHapus = $terakhir['gejala_id'];
            unset($_SESSION['bc_state']['fakta'][$idHapus]);

            $targetGoalId = $_SESSION['bc_state']['target_goal_id'];
            $eval = $engine->evaluasi($_SESSION['bc_state']['fakta'], $targetGoalId);
            $_SESSION['bc_state']['hasil'] = $eval;

            kirimRespon(true, "Jawaban sebelumnya berhasil dibatalkan.", [
                'state' => $eval,
                'riwayat_jawaban' => $_SESSION['bc_state']['riwayat_jawaban']
            ]);
            break;

        default:
            kirimRespon(false, "Aksi tidak dikenali.");
            break;
    }
} catch (Throwable $e) {
    kirimRespon(false, "Terjadi kesalahan pada mesin inferensi: " . $e->getMessage());
}

/**
 * Menyimpan hasil pemeriksaan ke database
 */
function simpanRiwayat($conn, array $eval, array $fakta): ?int {
    try {
        $ringkasan = json_encode($eval['goals_terbukti'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($ringkasan === false) {
            $ringkasan = "[]";
        }

        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO pemeriksaan (hasil) VALUES (?)");
        $stmt->bind_param("s", $ringkasan);
        $stmt->execute();
        $pemeriksaanId = $conn->insert_id;
        $stmt->close();

        // Simpan hanya gejala yang bernilai TRUE
        $gejalaTerpilih = [];
        foreach ($fakta as $id => $val) {
            if ($val === true) {
                $gejalaTerpilih[] = (int)$id;
            }
        }

        if (!empty($gejalaTerpilih)) {
            $stmtGejala = $conn->prepare("INSERT INTO pemeriksaan_gejala (pemeriksaan_id, gejala_id) VALUES (?, ?)");
            foreach ($gejalaTerpilih as $idGejala) {
                $stmtGejala->bind_param("ii", $pemeriksaanId, $idGejala);
                $stmtGejala->execute();
            }
            $stmtGejala->close();
        }

        $conn->commit();
        return $pemeriksaanId;
    } catch (Throwable $err) {
        $conn->rollback();
        error_log("Gagal menyimpan riwayat: " . $err->getMessage());
        return null;
    }
}
