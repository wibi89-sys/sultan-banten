CREATE TABLE `data_asesmen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `no_register` varchar(100) NOT NULL,
  `upt` varchar(255) DEFAULT NULL,
  `ttl` varchar(255) DEFAULT NULL,
  `usia` int(11) DEFAULT NULL,
  `tgl_asesmen` date DEFAULT NULL,
  
  `modul0_1` varchar(20) DEFAULT NULL,
  `modul0_2` varchar(20) DEFAULT NULL,
  `modul0_3` varchar(20) DEFAULT NULL,
  `modul0_4` varchar(20) DEFAULT NULL,
  `modul0_5` varchar(20) DEFAULT NULL,
  `modul0_6` varchar(20) DEFAULT NULL,
  `hasil_modul0` varchar(50) DEFAULT NULL,
  
  `zat_utama` varchar(100) DEFAULT NULL,
  `usia_pertama_memakai` int(11) DEFAULT NULL,
  `lama_pemakaian` int(11) DEFAULT NULL,
  `gejala_json` text DEFAULT NULL,
  `kategori_modul1` varchar(50) DEFAULT NULL,
  
  `td` varchar(50) DEFAULT NULL,
  `nadi` varchar(50) DEFAULT NULL,
  `suhu` varchar(50) DEFAULT NULL,
  `bb` varchar(50) DEFAULT NULL,
  `tb` varchar(50) DEFAULT NULL,
  `kesimpulan_modul2` varchar(50) DEFAULT NULL,
  
  `modul3_1` int(11) DEFAULT NULL,
  `modul3_2` int(11) DEFAULT NULL,
  `modul3_3` int(11) DEFAULT NULL,
  `modul3_4` int(11) DEFAULT NULL,
  `modul3_5` int(11) DEFAULT NULL,
  
  `klaster_4a` varchar(100) DEFAULT NULL,
  `skor_4a` int(11) DEFAULT NULL,
  `skor_4b` int(11) DEFAULT NULL,
  `skor_4c` int(11) DEFAULT NULL,
  `skor_4d` int(11) DEFAULT NULL,
  
  `total_skor` int(11) DEFAULT NULL,
  `kategori_hasil` varchar(50) DEFAULT NULL,
  `keputusan_tpp` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
