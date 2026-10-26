<?php

/**
 * Mesin inferensi Forward Chaining SkinExpert.
 *
 * Fakta awal  : gejala yang dipilih pengguna.
 * Aturan      : dibaca dari database.
 * Kesimpulan  : kondisi kulit yang dihasilkan aturan.
 *
 * Ketentuan:
 * - Semua prasyarat suatu aturan harus terpenuhi.
 * - Hanya aturan aktif yang diproses.
 * - Aturan tanpa gejala prasyarat tidak menghasilkan kesimpulan.
 * - Beberapa aturan boleh menghasilkan kondisi yang sama.
 * - Semua aturan yang cocok untuk kondisi yang sama akan disimpan.
 */

function forwardChaining($conn, array $faktaGejala): array
{
    // 1. Normalisasi ID gejala.
    $faktaGejala = array_values(array_unique(array_filter(
        array_map('intval', $faktaGejala),
        fn($id) => $id > 0
    )));

    if (empty($faktaGejala)) {
        return [
            'hasil' => [],
            'fakta' => [],
            'jejak' => [],
            'pesan' => 'Pilih minimal satu gejala terlebih dahulu.'
        ];
    }

    // 2. Validasi gejala berdasarkan database.
    $faktaValid = [];
    $idFaktaValid = [];

    $resultGejala = $conn->query(
        "SELECT id, kode, nama FROM gejala ORDER BY id ASC"
    );

    while ($g = $resultGejala->fetch_assoc()) {
        $idGejala = (int) $g['id'];

        if (in_array($idGejala, $faktaGejala, true)) {
            $g['id'] = $idGejala;
            $faktaValid[] = $g;
            $idFaktaValid[] = $idGejala;
        }
    }

    if (empty($faktaValid)) {
        return [
            'hasil' => [],
            'fakta' => [],
            'jejak' => [],
            'pesan' => 'Gejala yang dipilih tidak ditemukan.'
        ];
    }

    // 3. Ambil seluruh aturan aktif beserta kesimpulannya.
    $sqlRules = "
        SELECT
            r.id AS rule_id,
            r.kode AS kode_rule,
            r.nama AS nama_rule,
            k.id AS kondisi_id,
            k.kode AS kode_kondisi,
            k.nama AS nama_kondisi,
            k.deskripsi,
            k.saran
        FROM rules r
        INNER JOIN kondisi_kulit k
            ON k.id = r.kondisi_kulit_id
        WHERE r.aktif = 1
        ORDER BY r.id ASC
    ";

    $resultRules = $conn->query($sqlRules);

    // Kunci hasil menggunakan ID kondisi agar tidak duplikat.
    $hasil = [];
    $jejak = [];

    // Siapkan query prasyarat satu kali.
    $stmtPrasyarat = $conn->prepare("
        SELECT g.id, g.kode, g.nama
        FROM rule_gejala rg
        INNER JOIN gejala g
            ON g.id = rg.gejala_id
        WHERE rg.rule_id = ?
        ORDER BY g.id ASC
    ");

    // 4. Evaluasi setiap aturan aktif.
    while ($rule = $resultRules->fetch_assoc()) {
        $idRule = (int) $rule['rule_id'];
        $idKondisi = (int) $rule['kondisi_id'];

        // Ambil gejala prasyarat aturan.
        $stmtPrasyarat->bind_param("i", $idRule);
        $stmtPrasyarat->execute();

        $resultPrasyarat = $stmtPrasyarat->get_result();
        $prasyarat = [];

        while ($g = $resultPrasyarat->fetch_assoc()) {
            $g['id'] = (int) $g['id'];
            $prasyarat[] = $g;
        }

        // Aturan tanpa prasyarat tidak boleh menghasilkan kesimpulan.
        if (empty($prasyarat)) {
            $jejak[] = [
                'rule' => $rule['kode_rule'],
                'nama' => $rule['nama_rule'],
                'jumlah_prasyarat' => 0,
                'jumlah_cocok' => 0,
                'status' => 'TIDAK DIPROSES',
                'gejala_belum_terpenuhi' => [],
                'keterangan' => 'Aturan belum memiliki gejala prasyarat.'
            ];

            continue;
        }

        // Cocokkan seluruh prasyarat dengan fakta pengguna.
        $jumlahCocok = 0;
        $gejalaBelumTerpenuhi = [];

        foreach ($prasyarat as $g) {
            if (in_array($g['id'], $idFaktaValid, true)) {
                $jumlahCocok++;
            } else {
                $gejalaBelumTerpenuhi[] = $g['kode'];
            }
        }

        $terpenuhi = ($jumlahCocok === count($prasyarat));

        // Simpan jejak inferensi untuk setiap aturan.
        $jejak[] = [
            'rule' => $rule['kode_rule'],
            'nama' => $rule['nama_rule'],
            'jumlah_prasyarat' => count($prasyarat),
            'jumlah_cocok' => $jumlahCocok,
            'status' => $terpenuhi
                ? 'TERPENUHI'
                : 'BELUM TERPENUHI',
            'gejala_belum_terpenuhi' => $gejalaBelumTerpenuhi
        ];

        // 5. Jika aturan terpenuhi, masukkan kesimpulan.
        if ($terpenuhi) {

            // BUG LAMA DIPERBAIKI:
            // Buat kondisi hanya jika belum ada dalam hasil.
            if (!isset($hasil[$idKondisi])) {
                $hasil[$idKondisi] = [
                    'kode' => $rule['kode_kondisi'],
                    'nama' => $rule['nama_kondisi'],
                    'deskripsi' => $rule['deskripsi'],
                    'saran' => $rule['saran'],
                    'aturan' => []
                ];
            }

            // Tambahkan kode aturan tanpa menghapus aturan sebelumnya.
            if (!in_array(
                $rule['kode_rule'],
                $hasil[$idKondisi]['aturan'],
                true
            )) {
                $hasil[$idKondisi]['aturan'][] = $rule['kode_rule'];
            }
        }
    }

    $stmtPrasyarat->close();

    // 6. Kembalikan hasil dengan format yang dipakai halaman konsultasi.
    return [
        'hasil' => array_values($hasil),
        'fakta' => $faktaValid,
        'jejak' => $jejak,
        'pesan' => empty($hasil)
            ? 'Belum ada aturan yang seluruh prasyaratnya terpenuhi.'
            : 'Proses inferensi selesai.'
    ];
}
