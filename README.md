# SkinExpert 🌿

**SkinExpert** adalah aplikasi sistem pakar diagnosis kondisi kulit wajah berbasis web interaktif menggunakan metode penalaran **Backward Chaining (Goal-Driven Reasoning)**.

Sistem ini membantu pengguna mengidentifikasi kondisi kulit (misal: Jerawat Vulgaris, Dermatitis Kontak, Kulit Kering Sensitif, dll.) berdasarkan gejala klinis yang dirasakan, serta menyertakan saran perawatan, modul administrasi basis pengetahuan, dan pencatatan riwayat konsultasi.

---

## ✨ Fitur Utama

1. **Konsultasi Interaktif & Visual Monitor (Backward Chaining)**:
   - Pemilihan **Fakta Klinis (F)** dan penentuan target **Masalah / Goal (M)** yang ingin diuji.
   - Evaluasi inferensi **Goal-Driven Reasoning** untuk memverifikasi prasyarat aturan $(P_1 \land P_2 \dots) \rightarrow Q$.
   - Tampilan visual diagram aturan bergaya **Slide Monitor** (badge chevron Fakta, Masalah, dan Aturan horizontal).
   - Visualisasi transparan jejak penalaran (*reasoning trace*) lengkap dengan status `TERBUKTI` vs `GUGUR (BACKTRACK)`.
2. **Skenario Latihan Interaktif (`/latihan.php`)**:
   - Replikasi visual dan komputasional studi kasus latihan kuliah **Universitas KH. A. Wahab Hasbullah (UNWAHA)**:
     - Fakta: `F1 (Hari Senin)`, `F2 (Yani Kuliah)`.
     - Masalah: `M1 (Apakah Ali Masuk Kuliah?)`, `M2 (Apakah Ani Bolos Kuliah?)`.
     - Aturan: `R6` s.d. `R10`.
   - Menampilkan penelusuran berantai dan derivasi Modus Ponens secara interaktif dan real-time.
3. **Panel Admin Lengkap (`/admin/`)**:
   - **Dashboard Ringkasan**: Statistik data Fakta (F), Masalah (M), dan Aturan (R).
   - **Kelola Fakta (Gejala)**: Tambah, ubah, dan hapus data fakta klinis.
   - **Kelola Masalah (Kondisi Kulit)**: Manajemen jenis diagnosis dan rekomendasi saran penanganan.
   - **Basis Aturan (Rules Engine)**: Pemetaan aturan relasi antara kondisi kulit dan fakta prasyarat dengan proteksi CSRF.
4. **Riwayat Pemeriksaan (`/riwayat.php`)**:
   - Pencatatan otomatis hasil diagnosis, waktu pemeriksaan, dan gejala yang dipilih pengguna.
   - Fitur penghapusan riwayat pemeriksaan secara terisolasi.
5. **Hybrid Database Layer**:
   - Mendukung **MySQL / MariaDB** (untuk server produksi atau cloud).
   - Fallback otomatis ke **SQLite (`skinexpert.sqlite`)** terisi data awal lengkap, sehingga siap berjalan tanpa konfigurasi server database tambahan.

---

## 🚀 Panduan Deployment ke Vercel

Aplikasi ini telah dikonfigurasi untuk langsung di-deploy ke Vercel menggunakan runtime `vercel-php`.

### Langkah Deployment Cepat via Vercel Dashboard:

1. **Import Repositori**:
   - Buka [dashboard Vercel](https://vercel.com/new).
   - Hubungkan akun GitHub Anda dan pilih repositori `skinexpert`.
2. **Deploy**:
   - Klik **Deploy**.
   - Vercel akan otomatis mendeteksi berkas `vercel.json` dan mem-build aplikasi menggunakan runtime `vercel-php@0.9.0`.
3. **Konfigurasi Database di Vercel**:
   - **Secara Default**: Aplikasi otomatis menggunakan database SQLite ter-bundle yang disalin ke `/tmp/skinexpert.sqlite` (writable serverless cache).
   - **Opsional (Database Cloud MySQL)**: Jika ingin menggunakan database MySQL eksternal (misal: PlanetScale, TiDB Cloud, Aiven, Railway), cukup tambahkan Environment Variables di pengaturan Project Vercel:
     - `DB_HOST` = host database Anda
     - `DB_USER` = username database
     - `DB_PASS` = password database
     - `DB_NAME` = nama database
     - `DB_PORT` = port (default: 3306)

---

## 💻 Menjalankan Secara Lokal

### Menggunakan PHP Built-in Server (Rekomendasi):
Cukup klik ganda berkas `jalankan_server.bat` atau jalankan perintah:
```bash
php -S localhost:8000
```
Buka peramban di `http://localhost:8000`.

### Menggunakan Flask (Alternatif Python):
Tersedia juga implementasi alternatif Python Flask di `app.py`:
```bash
pip install flask
python app.py
```
Buka peramban di `http://127.0.0.1:5000`.

---

## 📂 Struktur Direktori

```text
skinexpert/
├── admin/                  # Panel manajemen sistem pakar (CRUD Gejala, Kondisi, Aturan)
│   ├── index.php           # Dashboard admin
│   ├── gejala.php          # Manajemen gejala
│   ├── kondisi.php         # Manajemen kondisi kulit
│   └── aturan.php          # Manajemen basis aturan
├── api/                    # Entry point serverless untuk Vercel
│   └── index.php           # Router serverless vercel-php
├── config/
│   └── database.php        # Konfigurasi koneksi MySQL & SQLite adapter
├── proses/
│   ├── backward_chaining.php # Mesin inferensi Backward Chaining
│   ├── forward_chaining.php  # Pengujian Forward Chaining
│   └── api_backward.php      # Endpoint API Backward Chaining
├── static/
│   └── style.css           # Desain UI/UX & tema modern SkinExpert
├── index.php               # Halaman beranda
├── konsultasi.php          # Formulir konsultasi & visualisasi hasil
├── riwayat.php             # Riwayat diagnosis pengguna
├── skinexpert.sqlite       # Database SQLite lokal terisi data awal
├── skinexpert_db.sql       # Skema DDL & dataset SQL
├── vercel.json             # Konfigurasi routing & runtime Vercel
└── README.md
```

---

## 📝 Lisensi & Hak Cipta
Dibuat untuk sistem pakar diagnosis kondisi kulit wajah menggunakan metode Dynamic Backward Chaining.
