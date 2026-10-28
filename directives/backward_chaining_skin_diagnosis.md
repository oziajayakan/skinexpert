# SOP - Sistem Pakar Pra-Diagnosis Kondisi Kulit Wajah (Backward Chaining)

## Tujuan
Mengembangkan sistem pakar pra-diagnosis dan identifikasi kondisi kulit wajah berbasis web dengan metode **Backward Chaining (Goal-Driven Reasoning)**, berlandaskan literatur ilmiah diagnosa berbasis gejala.

## Basis Pengetahuan Klinis (Knowledge Base)
1. **Fakta Gejala ($G_{01} - G_{15}$)**:
   - $G_{01}$: Kulit terasa kering / kasar
   - $G_{02}$: Kulit mengelupas / bersisik halus
   - $G_{03}$: Sensasi kencang setelah cuci muka
   - $G_{04}$: Wajah tampak mengilap / berminyak
   - $G_{05}$: Pori-pori tampak membesar
   - $G_{06}$: Riasan / bedak cepat luntur
   - $G_{07}$: Minyak berlebih di area T-Zone
   - $G_{08}$: Area pipi terasa normal atau kering
   - $G_{09}$: Tampak komedo hitam / putih
   - $G_{10}$: Pori-pori tersumbat / bruntusan
   - $G_{11}$: Benjolan jerawat tampak merah
   - $G_{12}$: Jerawat terasa nyeri atau bengkak
   - $G_{13}$: Kulit mudah memerah
   - $G_{14}$: Sensasi perih, gatal, atau menyengat
   - $G_{15}$: Kulit kusam dan tampak garis halus

2. **Target Hipotesis ($H_{01} - H_{07}$)**:
   - $H_{01}$: Kulit Kering (Dry Skin / Xerosis)
   - $H_{02}$: Kulit Berminyak (Oily Skin / Seborrhea)
   - $H_{03}$: Kulit Kombinasi (Combination Skin)
   - $H_{04}$: Indikasi Komedo & Pori Tersumbat (Comedonal Acne)
   - $H_{05}$: Indikasi Jerawat Meradang (Inflammatory Acne)
   - $H_{06}$: Kulit Sensitif & Iritasi Ringan (Sensitive Skin)
   - $H_{07}$: Kulit Dehidrasi (Dehydrated Skin)

3. **Aturan Inferensi ($R_{01} - R_{14}$)**:
   - Setiap kondisi didukung oleh aturan utama dan aturan alternatif berbasis Horn clauses: $(P_1 \land P_2) \rightarrow Q$.
   - Pasangan gejala natural langsung membuktikan hipotesis (TERBUKTI 100%).
   - Jika pengguna memilih 1 gejala, sistem secara cerdas meminta verifikasi 1 premis tambahan melalui mekanisme penalaran mundur interaktif.

## Prinsip Desain UI/UX
- **Ringkas & Berorientasi Panduan**: Menghindari paragraf panjang atau teks akademis yang membingungkan.
- **Hasil Aksi Nyata**: Menampilkan kesimpulan pra-diagnosis yang jelas, panduan perawatan yang dapat segera diterapkan, dan opsi verifikasi 1-klik.
- **Trace Teknis Tersembunyi Rapi**: Formula Modus Ponens dan detail premis disimpan dalam accordion collapsible (`<details>`) agar layar tetap bersih.
