# SkinExpert — Sistem Pakar Diagnosis Kulit Wajah

**SkinExpert** adalah aplikasi sistem pakar diagnosis klinis kondisi kulit wajah berbasis web modern menggunakan mesin inferensi **Backward Chaining (Goal-Driven Reasoning)** dengan representasi logika Horn Clause.

Sistem ini membantu pengguna memvalidasi hipotesis kondisi kulit wajah (Kulit Kering Dehidrasi, Kulit Berminyak Rentan Komedo, Acne Vulgaris Radang, Kulit Sensitif Alergi, Penuaan Dini / Aging, dll.) berdasarkan gejala klinis yang dirasakan. Dilengkapi dengan panduan interaktif, pertanyaan verifikasi lanjutan, modul manajemen basis pengetahuan (Admin), dan pencatatan riwayat konsultasi.

---

## Fitur Utama

1. **Mesin Inferensi Backward Chaining (Horn Clauses)**:
   - Evaluasi berbasis kaidah Horn Clause $(P_1 \land P_2 \land \dots \land P_n) \rightarrow Q$.
   - **Interactive Follow-Up Verification**: Jika pengguna memilih gejala parsial, sistem secara otomatis mengajukan pertanyaan verifikasi lanjutan yang relevan untuk membuktikan hipotesis dengan 1 klik.
   - **Reasoning Trace Transparan**: Jejak eksekusi inferensi lengkap (premis terpenuhi vs belum terpenuhi) dapat diperiksa melalui panel trace log teknis.
2. **Desain UI Modern & Vector SVG Icons**:
   - Tampilan bersih, minimalis, dan berfokus pada panduan praktis (tanpa dinding teks teori yang membebani pengguna).
   - Seluruh ikon menggunakan grafis vektor SVG tajam (`includes/icons.php`) menggantikan emoji sistem agar konsisten di semua sistem operasi dan perangkat mobile.
3. **Skenario Latihan Interaktif (`/latihan.php`)**:
   - Demonstrasi visual dan komputasional studi kasus penalaran Backward Chaining berbasis materi akademik UNWAHA (Fakta $F_1, F_2$ $\rightarrow$ Masalah $M_1, M_2$ melalui Aturan $R_6-R_{10}$).
4. **Knowledge Base Admin (`/admin/`)**:
   - **Fakta Gejala (G)**: Manajemen 15 gejala klinis wajah ($G_{01}-G_{15}$).
   - **Kondisi Kulit (H)**: Manajemen 7 target hipotesis kondisi kulit ($H_{01}-H_{07}$) beserta rekomendasi perawatan.
   - **Basis Aturan (R)**: Konfigurasi 14 aturan implikasi ($R_{01}-R_{14}$) lengkap dengan proteksi keamanan CSRF.
5. **Riwayat Pemeriksaan (`/riwayat.php`)**:
   - Pencatatan otomatis waktu konsultasi, gejala yang dipilih, dan status kesimpulan diagnosis.
   - Fitur penghapusan riwayat pemeriksaan per data.
6. **Dual Database Engine (SQLite & MySQL)**:
   - Terpasang database SQLite mandiri (`skinexpert.sqlite`) siap pakai tanpa konfigurasi tambahan.
   - Mendukung integrasi mulus ke database cloud MySQL/MariaDB.

---

## Pengetahuan Klinis (Knowledge Base)

Sistem telah diisi dengan basis pengetahuan klinis yang realistis dan tervalidasi:

| Kode Hipotesis | Kondisi Kulit Wajah | Prasyarat Gejala Utama (Horn Premise) |
|---|---|---|
| **H01** | Kulit Kering & Dehidrasi | $G_{01}$ (Wajah terasa tertarik/kering) $\land$ $G_{02}$ (Kulit mengelupas/bersisik) |
| **H02** | Kulit Berminyak & Seborrhea | $G_{04}$ (Kilap berlebih di zona T) $\land$ $G_{05}$ (Pori-pori tampak membesar) |
| **H03** | Acne Vulgaris (Jerawat Inflamasi) | $G_{11}$ (Benjolan jerawat merah/bernanah) $\land$ $G_{12}$ (Nyeri saat jerawat disentuh) |
| **H04** | Komedo & Pori Tersumbat | $G_{04}$ (Kilap berlebih di zona T) $\land$ $G_{07}$ (Komedo hitam/putih di hidung & pipi) |
| **H05** | Kulit Sensitif & Reaktif | $G_{13}$ (Kulit mudah memerah/perih) $\land$ $G_{14}$ (Sensasi terbakar terkena skincare/matahari) |
| **H06** | Penuaan Dini (Premature Aging) | $G_{08}$ (Garis halus di sekitar mata/mulut) $\land$ $G_{09}$ (Kulit kendur & elastisitas berkurang) |
| **H07** | Hiperpigmentasi / Flek Hitam | $G_{10}$ (Bercak kecokelatan/noda hitam pasca jerawat) $\land$ $G_{04}$ atau $G_{01}$ |

---

## Panduan Deployment ke Vercel

Repositori ini siap di-deploy langsung ke Vercel:

1. **Import Repositori**:
   - Buka [dashboard Vercel](https://vercel.com/new).
   - Hubungkan akun GitHub dan pilih repositori `https://github.com/oziajayakan/skinexpert.git`.
2. **Deploy**:
   - Klik **Deploy**. Vercel akan otomatis membaca file `vercel.json` dan mem-build menggunakan runtime `vercel-php@0.9.0`.
3. **Database di Vercel**:
   - **Mode SQLite bawaan**: Aplikasi otomatis menyalin database SQLite ke direktori writable `/tmp/skinexpert.sqlite` sehingga konsultasi dan admin langsung berfungsi.
   - **Mode MySQL (Opsional)**: Jika ingin menggunakan cloud MySQL (Railway, Aiven, PlanetScale, TiDB), atur Environment Variables di project Vercel:
     - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`.

---

## Menjalankan Secara Lokal

Cukup jalankan PHP built-in server:
```bash
php -S 127.0.0.1:8000
```
atau klik dua kali file `jalankan_server.bat` di Windows. Buka peramban di `http://127.0.0.1:8000`.

---

## Struktur Direktori

```text
skinexpert/
├── admin/                      # Manajemen Knowledge Base (CRUD Gejala, Kondisi, Aturan)
│   ├── header.php              # Header admin & navigasi SVG
│   ├── footer.php              # Footer admin
│   ├── index.php               # Dashboard ringkasan KB
│   ├── gejala.php              # Kelola 15 fakta gejala klinis (G)
│   ├── kondisi.php             # Kelola 7 hipotesis kondisi kulit (H)
│   └── aturan.php              # Kelola 14 kaidah aturan Horn (R)
├── api/
│   └── index.php               # Router serverless untuk Vercel
├── config/
│   └── database.php            # Hybrid database connector (SQLite & MySQL)
├── directives/
│   └── backward_chaining_skin_diagnosis.md # SOP & spesifikasi logika inferensi
├── execution/
│   ├── export_products.py      # Ekspor data produk/pengetahuan
│   ├── migrate_cascade_sqlite.py # Migrasi foreign key cascade SQLite
│   └── seed_sqlite.py          # Seeder dataset awal
├── includes/
│   └── icons.php               # Pustaka helper 28 vektor stroke SVG modern
├── static/
│   └── style.css               # Desain UI/UX SkinExpert, token CSS & komponen SVG
├── index.php                   # Beranda & panduan praktis
├── konsultasi.php              # Halaman konsultasi & inferensi Backward Chaining
├── latihan.php                 # Simulasi pengujian logika UNWAHA
├── riwayat.php                 # Riwayat pemeriksaan pengguna
├── skinexpert.sqlite           # Basis data SQLite lokal siap pakai
├── skinexpert_db.sql           # Skema DDL & dataset SQL lengkap
├── vercel.json                 # Konfigurasi routing serverless Vercel
├── jalankan_server.bat         # Launcher server lokal Windows
└── README.md
```

---

## Lisensi
Sistem Pakar Diagnosis Kulit Wajah Backward Chaining. Open source dan siap digunakan untuk riset, skripsi, dan implementasi klinis.
