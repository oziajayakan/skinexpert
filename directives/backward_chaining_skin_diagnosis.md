# SOP - Sistem Pakar Diagnosis Kulit Wajah (Backward Chaining)

## Tujuan
Mengembangkan sistem pakar diagnosis kondisi kulit wajah berbasis web interaktif dengan metode **Backward Chaining (Goal-Driven Reasoning)**, memanfaatkan basis pengetahuan, aturan relasi, dan dataset produk perawatan kulit yang sudah ada di repositori.

## Input & Sumber Bahan
1. `skinexpert_db.sql` : Skema tabel gejala, kondisi kulit, rules, rule_gejala, pemeriksaan, pemeriksaan_gejala.
2. `dataset/DataProduk.xlsx` : Katalog produk rekomendasi (kandungan bahan aktif, target masalah kulit, izin BPOM, kecocokan tipe kulit).
3. `dataset/MasalahKulit/` & `dataset/TipeKulit/` : Kategori masalah dan tipe kulit untuk referensi visual dan taksonomi.
4. Desain UI/UX SkinExpert yang sudah ada (`index.php`, `konsultasi.php`, `riwayat.php`, `admin/`).

## Logika Mesin Inferensi (Backward Chaining)
1. **Inisialisasi Hipotesis (Goal)**:
   - Ambil daftar kondisi kulit aktif sebagai target hipotesis (K01, K02, K03, K04, dst).
2. **Evaluasi Aturan (Rule Match)**:
   - Untuk setiap hipotesis, identifikasi aturan (`rules`) dan gejala prasyarat (`rule_gejala`).
3. **Penelusuran Premis Dinamis (Sub-Goal Evaluation)**:
   - Periksa status fakta pengguna (True / False / Unknown).
   - Ajukan pertanyaan ke pengguna untuk fakta yang berstatus *Unknown*.
   - Jika premis bernilai False, lakukan *backtrack* / eliminasi aturan tersebut.
   - Jika semua premis dalam suatu aturan bernilai True, hipotesis dinyatakan **TERBUKTI (PROVEN)**.
4. **Penjelasan & Rekomendasi Solusi**:
   - Hasilkan jejak inferensi (*reasoning trace*) yang transparan (Goal -> Premis -> Fakta Pengguna -> Status).
   - Hubungkan hasil diagnosis dengan rekomendasi produk dari `DataProduk.xlsx`.
   - Simpan riwayat diagnosis ke tabel pemeriksaan.

## Edge Cases & Penanganan
- **MySQL/MariaDB belum aktif**: Sediakan hybrid data layer (MySQL dengan fallback otomatis SQLite terisi data awal dari `skinexpert_db.sql`) agar server lokal (`php -S localhost:8000`) langsung berjalan tanpa konfigurasi rumit.
- **Foreign Key Constraint pada Operasi CRUD Hapus**:
  - Di SQLite & MySQL, relasi antar-tabel (`rules` -> `rule_gejala`, `kondisi_kulit` -> `rules`, `gejala` -> `rule_gejala` & `pemeriksaan_gejala`) wajib mendukung `ON DELETE CASCADE` dan dibersihkan secara eksplisit di layer aplikasi dalam satu transaksi database (`begin_transaction`).
  - SQLiteStatementWrapper mengonversi `PDOException` menjadi `mysqli_sql_exception` (dengan kode 1062 untuk unik dan 1451 untuk foreign key) agar penanganan error kompatibel penuh di kedua driver.
- **Tidak ada hipotesis terpenuhi**: Tampilkan pesan edukatif dan rekomendasi konsultasi lebih lanjut.
- **Multiple Proven Goals**: Tampilkan seluruh kondisi yang terbukti beserta prioritas penanganannya.
