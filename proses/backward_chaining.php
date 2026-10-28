<?php

/**
 * Mesin Inferensi Backward Chaining SkinExpert.
 * 
 * Model Verifikasi (Goal-Driven Subgoal Evaluation):
 * - Dimulai dari setiap hipotesis kondisi kulit (Goal).
 * - Menelusuri subgoal (gejala prasyarat) dari aturan yang menghasilkan kondisi tersebut.
 * - Menghitung tingkat kecocokan (score = matched / subgoals * 100%).
 * - Mengidentifikasi kesimpulan utama (Top Match) dan jejak penalaran (subgoal terpenuhi & belum terpenuhi).
 */

/**
 * Class BackwardChainingExpert
 * Engine Backward Chaining interaktif berbasis Goal & Subgoal Verification.
 */
class BackwardChainingExpert {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function ambilSemuaGoals(): array {
        $res = $this->conn->query("SELECT id, kode, nama, deskripsi, saran FROM kondisi_kulit ORDER BY kode ASC");
        $goals = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $goals[] = [
                    'id' => (int)$row['id'],
                    'kode' => $row['kode'],
                    'nama' => $row['nama'],
                    'deskripsi' => $row['deskripsi'],
                    'saran' => $row['saran']
                ];
            }
        }
        return $goals;
    }

    public function evaluasi(array $fakta = [], ?int $targetGoalId = null): array {
        $resGejala = $this->conn->query("SELECT id, kode, nama, deskripsi FROM gejala ORDER BY kode ASC");
        $gejalaMap = [];
        if ($resGejala) {
            while ($g = $resGejala->fetch_assoc()) {
                $gejalaMap[(int)$g['id']] = $g;
            }
        }

        $goals = $this->ambilSemuaGoals();
        if ($targetGoalId !== null) {
            $goals = array_values(array_filter($goals, fn($g) => $g['id'] === $targetGoalId));
        }

        $resRules = $this->conn->query("
            SELECT r.id as rule_id, r.kode as rule_kode, r.nama as rule_nama, r.kondisi_kulit_id, rg.gejala_id
            FROM rules r
            INNER JOIN rule_gejala rg ON rg.rule_id = r.id
            WHERE r.aktif = 1
            ORDER BY r.id ASC, rg.gejala_id ASC
        ");

        $ruleMap = [];
        if ($resRules) {
            while ($r = $resRules->fetch_assoc()) {
                $rid = (int)$r['rule_id'];
                if (!isset($ruleMap[$rid])) {
                    $ruleMap[$rid] = [
                        'id' => $rid,
                        'kode' => $r['rule_kode'],
                        'nama' => $r['rule_nama'],
                        'kondisi_id' => (int)$r['kondisi_kulit_id'],
                        'subgoals' => []
                    ];
                }
                $ruleMap[$rid]['subgoals'][] = (int)$r['gejala_id'];
            }
        }

        $goalsTerbukti = [];
        $pertanyaanBerikutnya = null;
        $jejak = [];

        foreach ($goals as $goal) {
            $goalId = $goal['id'];
            $rulesForGoal = array_filter($ruleMap, fn($r) => $r['kondisi_id'] === $goalId);

            foreach ($rulesForGoal as $rule) {
                $subgoals = $rule['subgoals'];
                $allTrue = true;
                $hasFalse = false;
                $firstUnknown = null;

                foreach ($subgoals as $gid) {
                    if (!array_key_exists($gid, $fakta)) {
                        $allTrue = false;
                        if ($firstUnknown === null) {
                            $firstUnknown = $gid;
                        }
                    } elseif ($fakta[$gid] === false) {
                        $hasFalse = true;
                        $allTrue = false;
                        break;
                    }
                }

                if ($hasFalse) {
                    $jejak[] = [
                        'goal' => $goal,
                        'rule' => $rule,
                        'status' => 'GAGAL_BACKTRACK',
                        'keterangan' => "Aturan {$rule['kode']} gugur karena premis bernilai False."
                    ];
                } elseif ($allTrue && !empty($subgoals)) {
                    $goalsTerbukti[] = [
                        'id' => $goal['id'],
                        'kode' => $goal['kode'],
                        'nama' => $goal['nama'],
                        'aturan' => $rule['kode'],
                        'deskripsi' => $goal['deskripsi'],
                        'saran' => $goal['saran']
                    ];
                    $jejak[] = [
                        'goal' => $goal,
                        'rule' => $rule,
                        'status' => 'TERBUKTI',
                        'keterangan' => "Semua premis aturan {$rule['kode']} bernilai True."
                    ];
                } else {
                    if ($pertanyaanBerikutnya === null && $firstUnknown !== null) {
                        $gData = $gejalaMap[$firstUnknown] ?? ['id' => $firstUnknown, 'kode' => 'G?', 'nama' => 'Gejala'];
                        $pertanyaanBerikutnya = [
                            'gejala' => $gData,
                            'goal' => $goal,
                            'alasan' => "Memverifikasi hipotesis {$goal['nama']} via aturan {$rule['kode']}"
                        ];
                    }
                    $jejak[] = [
                        'goal' => $goal,
                        'rule' => $rule,
                        'status' => 'MENUNGGU_FAKTA',
                        'keterangan' => "Menunggu verifikasi premis belum diketahui."
                    ];
                }
            }
        }

        $selesai = ($pertanyaanBerikutnya === null);

        return [
            'selesai' => $selesai,
            'goals_terbukti' => $goalsTerbukti,
            'pertanyaan_berikutnya' => $pertanyaanBerikutnya,
            'jejak' => $jejak,
            'total_fakta' => count($fakta)
        ];
    }
}

function evaluasiBackwardChaining($conn, array $selected_ids, ?int $targetGoalId = null): array {
    // 1. Ambil semua gejala (Fakta - F)
    $gejala_res = $conn->query("SELECT id, kode, nama, deskripsi FROM gejala ORDER BY kode ASC");
    $gejala_map = [];
    while ($g = $gejala_res->fetch_assoc()) {
        $gejala_map[(int)$g['id']] = $g;
    }

    // 2. Ambil semua kondisi kulit (Masalah / Goal - M)
    $kondisi_res = $conn->query("SELECT id, kode, nama, deskripsi, saran FROM kondisi_kulit ORDER BY kode ASC");
    $kondisi_all = [];
    while ($k = $kondisi_res->fetch_assoc()) {
        $kondisi_all[] = $k;
    }

    // Filter jika pengguna memilih target Masalah (Goal) spesifik
    if ($targetGoalId !== null && $targetGoalId > 0) {
        $kondisi_all = array_values(array_filter($kondisi_all, fn($k) => (int)$k['id'] === $targetGoalId));
    }

    // 3. Ambil rules dan subgoals
    $rules_res = $conn->query("
        SELECT r.id as rule_id, r.kode as rule_kode, r.nama as rule_nama, r.kondisi_kulit_id, rg.gejala_id
        FROM rules r
        INNER JOIN rule_gejala rg ON rg.rule_id = r.id
        WHERE r.aktif = 1
        ORDER BY r.id ASC, rg.gejala_id ASC
    ");

    $rule_list = [];
    if ($rules_res) {
        while ($r = $rules_res->fetch_assoc()) {
            $rid = (int)$r['rule_id'];
            $kid = (int)$r['kondisi_kulit_id'];
            $gid = (int)$r['gejala_id'];
            if (!isset($rule_list[$rid])) {
                $rule_list[$rid] = [
                    'id' => $rid,
                    'kode' => $r['rule_kode'],
                    'nama' => $r['rule_nama'],
                    'kondisi_id' => $kid,
                    'subgoals' => []
                ];
            }
            $rule_list[$rid]['subgoals'][] = $gid;
        }
    }

    $selected_set = array_values(array_unique(array_filter(
        array_map('intval', $selected_ids),
        fn($id) => $id > 0
    )));

    // ====== BACKWARD CHAINING VERIFIKASI LOGIKA MODUS PONENS ======
    $trace = [];
    $goals_terbukti = [];

    foreach ($kondisi_all as $k) {
        $kid = (int)$k['id'];
        $rulesForGoal = array_values(array_filter($rule_list, fn($r) => $r['kondisi_id'] === $kid));

        if (empty($rulesForGoal)) {
            $trace[] = [
                'kondisi' => $k,
                'rule' => ['kode' => '-', 'nama' => 'Tidak Ada Aturan Aktif'],
                'subgoals' => [],
                'all_gejala' => [],
                'matched' => [],
                'missing' => [],
                'matched_gejala' => [],
                'missing_gejala' => [],
                'score' => 0,
                'full' => false,
                'status' => 'TIDAK_ADA_ATURAN',
                'formula' => '- → ' . $k['kode'],
                'modus_ponens' => "Tidak ada aturan aktif di basis pengetahuan yang dapat membuktikan hipotesis {$k['kode']} ({$k['nama']})."
            ];
            continue;
        }

        foreach ($rulesForGoal as $rule) {
            $subgoals = $rule['subgoals'];
            $matched = array_values(array_intersect($subgoals, $selected_set));
            $missing = array_values(array_diff($subgoals, $selected_set));
            $score = !empty($subgoals) ? round((count($matched) / count($subgoals)) * 100, 1) : 0;
            $full = !empty($subgoals) && empty($missing); // Seluruh premis (P1 ∧ P2 ∧ ... Pn) TRUE

            // Ambil objek gejala untuk tampilan rapi
            $matched_gejala = [];
            foreach ($matched as $mid) {
                if (isset($gejala_map[$mid])) {
                    $matched_gejala[] = $gejala_map[$mid];
                }
            }

            $missing_gejala = [];
            foreach ($missing as $mid) {
                if (isset($gejala_map[$mid])) {
                    $missing_gejala[] = $gejala_map[$mid];
                }
            }

            $all_gejala = [];
            foreach ($subgoals as $sid) {
                if (isset($gejala_map[$sid])) {
                    $all_gejala[] = $gejala_map[$sid];
                }
            }

            $kodeGejala = array_map(fn($g) => $g['kode'], $all_gejala);
            $formula_str = "(" . implode(" ∧ ", $kodeGejala) . ") → " . $k['kode'];

            $traceItem = [
                'kondisi' => $k,
                'rule' => $rule,
                'subgoals' => $subgoals,
                'all_gejala' => $all_gejala,
                'matched' => $matched,
                'missing' => $missing,
                'matched_gejala' => $matched_gejala,
                'missing_gejala' => $missing_gejala,
                'score' => $score,
                'full' => $full,
                'status' => $full ? 'TERBUKTI' : (count($matched) > 0 ? 'GUGUR_BACKTRACK' : 'TIDAK_TERPENUHI'),
                'formula' => $formula_str,
                'modus_ponens' => $full 
                    ? "Semua premis (" . implode(" & ", $kodeGejala) . ") terpenuhi pada fakta pengguna. Berdasarkan Modus Ponens, hipotesis {$k['kode']} ({$k['nama']}) dinyatakan TERBUKTI (BENAR)."
                    : "Premis aturan belum lengkap (" . count($matched) . " dari " . count($subgoals) . " fakta terpenuhi). Aturan gugur dan sistem melakukan backtracking."
            ];

            $trace[] = $traceItem;

            if ($full) {
                $goals_terbukti[] = $traceItem;
            }
        }
    }

    // Urutkan trace: yang TERBUKTI terlebih dahulu, lalu skor tertinggi
    usort($trace, function($a, $b) {
        if ($a['full'] !== $b['full']) {
            return $a['full'] ? -1 : 1;
        }
        if ($b['score'] != $a['score']) {
            return $b['score'] <=> $a['score'];
        }
        return strcmp($a['kondisi']['kode'], $b['kondisi']['kode']);
    });

    // Cari pertanyaan verifikasi lanjutan (gejala yang belum terjawab pada aturan paling mendekati)
    $pertanyaan_lanjutan = null;
    if (empty($goals_terbukti) && !empty($trace)) {
        foreach ($trace as $t) {
            if (!empty($t['missing_gejala']) && !empty($t['matched_gejala'])) {
                $pertanyaan_lanjutan = [
                    'kondisi' => $t['kondisi'],
                    'rule' => $t['rule'],
                    'gejala' => $t['missing_gejala'][0],
                    'score' => $t['score']
                ];
                break;
            }
        }
    }

    $top = !empty($goals_terbukti) ? $goals_terbukti[0] : (!empty($trace) ? $trace[0] : null);

    return [
        'trace' => $trace,
        'goals_terbukti' => $goals_terbukti,
        'top' => $top,
        'pertanyaan_lanjutan' => $pertanyaan_lanjutan,
        'n_selected' => count($selected_set),
        'n_gejala' => count($gejala_map),
        'selected_set' => $selected_set,
        'gejala_map' => $gejala_map,
        'is_single_goal' => ($targetGoalId !== null && $targetGoalId > 0)
    ];
}


