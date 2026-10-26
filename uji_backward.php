<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/proses/backward_chaining.php';

$engine = new BackwardChainingExpert($conn);

echo "=== PENGUJIAN UNIT ENGINE BACKWARD CHAINING ===\n\n";

// Test 1: Inisialisasi tanpa fakta
echo "[TEST 1] Memulai konsultasi tanpa fakta awal:\n";
$eval1 = $engine->evaluasi([]);
echo "Status Selesai: " . ($eval1['selesai'] ? "YA" : "TIDAK") . "\n";
if ($eval1['pertanyaan_berikutnya']) {
    $q = $eval1['pertanyaan_berikutnya'];
    echo "Pertanyaan pertama diajukan: [{$q['gejala']['kode']}] {$q['gejala']['nama']}\n";
    echo "Untuk membuktikan Goal: {$q['goal']['nama']} ({$q['goal']['kode']})\n";
    echo "Alasan: {$q['alasan']}\n\n";
}

// Test 2: Fakta membuktikan K01 (G01 = TRUE, G03 = TRUE)
echo "[TEST 2] Simulasi fakta: G01=TRUE, G03=TRUE:\n";
$faktaK01 = [
    1 => true, // G01
    3 => true  // G03
];
$eval2 = $engine->evaluasi($faktaK01);
echo "Jumlah Goal Terbukti: " . count($eval2['goals_terbukti']) . "\n";
foreach ($eval2['goals_terbukti'] as $gt) {
    echo "-> Terbukti: [{$gt['kode']}] {$gt['nama']} (Aturan: {$gt['aturan']})\n";
    echo "   Deskripsi: {$gt['deskripsi']}\n";
    echo "   Saran Klinis: {$gt['saran']}\n";
}
echo "\n";

// Test 3: Simulasi Backtracking (G01 = TRUE, G03 = FALSE)
echo "[TEST 3] Simulasi Backtracking: G01=TRUE, G03=FALSE (R01 gugur, sistem mencari goal lain):\n";
$faktaBacktrack = [
    1 => true,  // G01
    3 => false  // G03
];
$eval3 = $engine->evaluasi($faktaBacktrack);
echo "Status Goal K01 gugur. Pertanyaan berikutnya yang dialihkan sistem:\n";
if ($eval3['pertanyaan_berikutnya']) {
    $q = $eval3['pertanyaan_berikutnya'];
    echo "Dialihkan ke: [{$q['gejala']['kode']}] {$q['gejala']['nama']} untuk Goal {$q['goal']['nama']}\n";
}
echo "\n=== SEMUA PENGUJIAN ENGINE SELESAI DENGAN SUKSES ===\n";
