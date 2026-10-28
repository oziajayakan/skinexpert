-- Database: `skinexpert_db` - Knowledge Base Skin Diagnosis (Backward Chaining)
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS `rule_gejala`;
DROP TABLE IF EXISTS `pemeriksaan_gejala`;
DROP TABLE IF EXISTS `rules`;
DROP TABLE IF EXISTS `pemeriksaan`;
DROP TABLE IF EXISTS `kondisi_kulit`;
DROP TABLE IF EXISTS `gejala`;

CREATE TABLE `gejala` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(10) NOT NULL UNIQUE,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gejala` (`id`, `kode`, `nama`, `deskripsi`) VALUES
(1, 'G01', 'Kulit terasa kering atau kasar', 'Permukaan kulit wajah terasa kasar, bersisik halus, atau kehilangan kelembapan alami.'),
(2, 'G02', 'Kulit mengelupas atau bersisik', 'Tampak serpihan kulit halus yang mengelupas terutama saat tersentuh atau terkena air.'),
(3, 'G03', 'Sensasi kencang setelah cuci muka', 'Kulit terasa tertarik kencang dan kaku beberapa menit setelah mencuci wajah.'),
(4, 'G04', 'Wajah tampak mengilap / berminyak', 'Tampak lapisan sebum/minyak berlebih di seluruh area wajah dalam hitungan jam.'),
(5, 'G05', 'Pori-pori tampak membesar', 'Ukuran pori-pori terlihat jelas dan terbuka pada area hidung dan pipi.'),
(6, 'G06', 'Riasan / bedak cepat luntur', 'Make-up atau tabir surya mudah luntur atau bergeser akibat produksi sebum.'),
(7, 'G07', 'Minyak berlebih di area T-Zone', 'Minyak hanya terkonsentrasi di dahi, hidung, dan dagu (zona T).'),
(8, 'G08', 'Area pipi terasa normal atau kering', 'Kulit di kedua pipi tidak berminyak, cenderung normal atau terasa kering.'),
(9, 'G09', 'Tampak komedo hitam atau putih', 'Terdapat bintik blackhead atau whitehead di hidung, dahi, atau dagu.'),
(10, 'G10', 'Pori-pori tersumbat / bruntusan', 'Tekstur kulit bergerigi kasar atau bruntusan akibat penyumbatan sebum.'),
(11, 'G11', 'Benjolan jerawat tampak merah', 'Terdapat papula jerawat yang meradang kemerahan di area wajah.'),
(12, 'G12', 'Jerawat terasa nyeri atau bengkak', 'Jerawat terasa berdenyut, bengkak, atau mulai timbul nanah (pustula).'),
(13, 'G13', 'Kulit mudah memerah', 'Kulit wajah cepat merah saat terkena matahari, udara panas, atau gesekan.'),
(14, 'G14', 'Sensasi perih, gatal, atau menyengat', 'Kulit mudah terasa perih, gatal, atau rasa panas saat memakai produk baru.'),
(15, 'G15', 'Kulit kusam dan tampak garis halus', 'Wajah kehilangan kilau sehat dan tampak garis-garis halus akibat dehidrasi.');

CREATE TABLE `kondisi_kulit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(10) NOT NULL UNIQUE,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `saran` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kondisi_kulit` (`id`, `kode`, `nama`, `deskripsi`, `saran`) VALUES
(1, 'H01', 'Kulit Kering (Dry Skin)', 'Kondisi kulit yang kekurangan kadar lipid dan minyak alami pelindung (skin barrier).', 'Gunakan pembersih wajah bertekstur krim (gentle low-pH), pelembap berkandungan ceramide atau asam lemak esensial, dan hindari eksfoliasi fisik yang kasar.'),
(2, 'H02', 'Kulit Berminyak (Oily Skin)', 'Kondisi kelenjar sebasea memproduksi sebum berlebih di seluruh area wajah.', 'Gunakan pembersih berbasis gel dengan Salicylic Acid (BHA), pelembap ringan berbahan dasar air (oil-free/gel), dan tabir surya bertipe matte.'),
(3, 'H03', 'Kulit Kombinasi (Combination Skin)', 'Kondisi produksi minyak tinggi di zona-T tetapi normal atau kering di area pipi.', 'Gunakan pembersih lembut seimbang, hidrasi ringan untuk seluruh wajah, dan pelembap ekstra khusus area pipi yang kering.'),
(4, 'H04', 'Indikasi Komedo (Comedonal Acne)', 'Penyumbatan folikel pori oleh penumpukan sel kulit mati dan sebum tanpa peradangan aktif.', 'Gunakan bahan aktif eksfoliasi kimia BHA (Salicylic Acid 1-2%), lakukan double cleansing di malam hari, dan pilih produk berlabel non-comedogenic.'),
(5, 'H05', 'Indikasi Jerawat Meradang (Inflammatory Acne)', 'Peradangan aktif pada folikel akibat infeksi bakteri C. acnes yang memicu benjolan merah dan nyeri.', 'Gunakan spot treatment berformula Benzoyl Peroxide atau Centella/Tea Tree, hindari memencet jerawat, dan konsultasikan ke dokter kulit jika bertambah banyak.'),
(6, 'H06', 'Kulit Sensitif & Iritasi Ringan (Sensitive Skin)', 'Skin barrier yang rapuh dan reaktif terhadap rangsangan lingkungan atau formula kosmetik.', 'Gunakan produk berlabel hypoallergenic, bebas pewangi (fragrance-free) dan bebas alkohol denat. Gunakan pelembap penenang berbahan Centella Asiatica atau Panthenol.'),
(7, 'H07', 'Kulit Dehidrasi (Dehydrated Skin)', 'Kondisi kulit kekurangan kadar air, seringkali terjadi bahkan pada permukaan kulit yang berminyak.', 'Tingkatkan hidrasi kulit dengan serum Hyaluronic Acid atau Glycerin, gunakan pelembap pengunci hidrasi, dan cukupi asupan cairan harian minimal 2 liter.');

CREATE TABLE `rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(10) NOT NULL UNIQUE,
  `nama` varchar(150) NOT NULL,
  `kondisi_kulit_id` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_rules_kondisi` (`kondisi_kulit_id`),
  CONSTRAINT `fk_rules_kondisi` FOREIGN KEY (`kondisi_kulit_id`) REFERENCES `kondisi_kulit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rules` (`id`, `kode`, `nama`, `kondisi_kulit_id`, `aktif`) VALUES
(1, 'R01', 'Aturan Kulit Kering 1', 1, 1),
(2, 'R02', 'Aturan Kulit Kering 2', 1, 1),
(3, 'R03', 'Aturan Kulit Berminyak 1', 2, 1),
(4, 'R04', 'Aturan Kulit Berminyak 2', 2, 1),
(5, 'R05', 'Aturan Kulit Kombinasi 1', 3, 1),
(6, 'R06', 'Aturan Indikasi Komedo 1', 4, 1),
(7, 'R07', 'Aturan Indikasi Komedo 2', 4, 1),
(8, 'R08', 'Aturan Jerawat Meradang 1', 5, 1),
(9, 'R09', 'Aturan Jerawat Meradang 2', 5, 1),
(10, 'R10', 'Aturan Kulit Sensitif 1', 6, 1),
(11, 'R11', 'Aturan Kulit Sensitif 2', 6, 1),
(12, 'R12', 'Aturan Kulit Dehidrasi 1', 7, 1),
(13, 'R13', 'Aturan Kulit Dehidrasi 2', 7, 1),
(14, 'R14', 'Aturan Kulit Kombinasi 2', 3, 1);

CREATE TABLE `rule_gejala` (
  `rule_id` int(11) NOT NULL,
  `gejala_id` int(11) NOT NULL,
  PRIMARY KEY (`rule_id`, `gejala_id`),
  KEY `fk_rg_gejala` (`gejala_id`),
  CONSTRAINT `fk_rg_rule` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rg_gejala` FOREIGN KEY (`gejala_id`) REFERENCES `gejala` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rule_gejala` (`rule_id`, `gejala_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 3),
(3, 4),
(3, 5),
(4, 4),
(4, 6),
(5, 7),
(5, 8),
(6, 9),
(6, 10),
(7, 4),
(7, 9),
(8, 11),
(8, 12),
(9, 10),
(9, 11),
(10, 13),
(10, 14),
(11, 3),
(11, 14),
(12, 1),
(12, 15),
(13, 3),
(13, 4),
(14, 6),
(14, 7);

CREATE TABLE `pemeriksaan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hasil` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pemeriksaan_gejala` (
  `pemeriksaan_id` int(11) NOT NULL,
  `gejala_id` int(11) NOT NULL,
  PRIMARY KEY (`pemeriksaan_id`, `gejala_id`),
  KEY `fk_pg_gejala` (`gejala_id`),
  CONSTRAINT `fk_pg_pemeriksaan` FOREIGN KEY (`pemeriksaan_id`) REFERENCES `pemeriksaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pg_gejala` FOREIGN KEY (`gejala_id`) REFERENCES `gejala` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
