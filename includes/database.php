<?php
/**
 * Database Configuration
 * Sesuaikan dengan konfigurasi MySQL Anda
 */

// Set timezone to Indonesia Western Time (WIB)
date_default_timezone_set('Asia/Jakarta');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'kanwil_banten');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika tidak ada password di XAMPP

// First, connect without database to create it if not exists
try {
    $pdoTemp = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    
    // Create database if not exists
    $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdoTemp = null; // Close connection
} catch (PDOException $e) {
    die("Gagal membuat database: " . $e->getMessage());
}

// Create PDO connection to the database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    // Set MySQL session timezone to WIB (UTC+7)
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

/**
 * Create tables if not exists
 * Strategy: Create tables without foreign keys first, then add constraints
 */
function initDatabase($pdo) {
    
    // ============================================
    // STEP 1: Create all tables WITHOUT foreign keys
    // ============================================
    
    // Table Instansi
    $pdo->exec("CREATE TABLE IF NOT EXISTS instansi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_instansi VARCHAR(100) NOT NULL,
        alamat TEXT,
        logo VARCHAR(255),
        running_text TEXT,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Table UPT
    $pdo->exec("CREATE TABLE IF NOT EXISTS upt (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_upt VARCHAR(100) NOT NULL,
        alamat TEXT,
        kapasitas INT DEFAULT 0,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Table Users (without foreign key initially)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('admin', 'operator', 'viewer') DEFAULT 'viewer',
        upt_id INT NULL,
        akses_dashboard TINYINT(1) DEFAULT 0,
        akses_penghuni TINYINT(1) DEFAULT 0,
        akses_overstaying TINYINT(1) DEFAULT 0,
        akses_integrasi TINYINT(1) DEFAULT 0,
        akses_upt TINYINT(1) DEFAULT 0,
        akses_user TINYINT(1) DEFAULT 0,
        akses_instansi TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Table Data Penghuni (without foreign key initially)
    $pdo->exec("CREATE TABLE IF NOT EXISTS data_penghuni (
        id INT AUTO_INCREMENT PRIMARY KEY,
        upt_id INT NOT NULL,
        klasifikasi_pidana ENUM('Pidana Umum', 'Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida', 'WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'UU ITE') NOT NULL,
        tahanan_dewasa_laki INT DEFAULT 0,
        tahanan_dewasa_perempuan INT DEFAULT 0,
        tahanan_anak_laki INT DEFAULT 0,
        tahanan_anak_perempuan INT DEFAULT 0,
        narapidana_dewasa_laki INT DEFAULT 0,
        narapidana_dewasa_perempuan INT DEFAULT 0,
        narapidana_anak_laki INT DEFAULT 0,
        narapidana_anak_perempuan INT DEFAULT 0,
        tanggal DATE NOT NULL,
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Table Data Overstaying (without foreign key initially)
    $pdo->exec("CREATE TABLE IF NOT EXISTS data_overstaying (
        id INT AUTO_INCREMENT PRIMARY KEY,
        upt_id INT NOT NULL,
        tahanan_ai INT DEFAULT 0,
        tahanan_aii INT DEFAULT 0,
        tahanan_aiii INT DEFAULT 0,
        tahanan_aiv INT DEFAULT 0,
        tahanan_av INT DEFAULT 0,
        overstaying_ai INT DEFAULT 0,
        overstaying_aii INT DEFAULT 0,
        overstaying_aiii INT DEFAULT 0,
        overstaying_aiv INT DEFAULT 0,
        overstaying_av INT DEFAULT 0,
        tanggal DATE NOT NULL,
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Table Data Integrasi (without foreign key initially)
    $pdo->exec("CREATE TABLE IF NOT EXISTS data_integrasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        upt_id INT NOT NULL,
        usulan_pb INT DEFAULT 0,
        usulan_cb INT DEFAULT 0,
        usulan_cmb INT DEFAULT 0,
        usulan_cmk INT DEFAULT 0,
        usulan_asimilasi INT DEFAULT 0,
        sk_terbit_pb INT DEFAULT 0,
        sk_terbit_cb INT DEFAULT 0,
        sk_terbit_cmb INT DEFAULT 0,
        sk_terbit_cmk INT DEFAULT 0,
        sk_terbit_asimilasi INT DEFAULT 0,
        tanggal DATE NOT NULL,
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ============================================
    // STEP 2: Add foreign key constraints (if not exist)
    // ============================================
    
    // Foreign key for users -> upt
    try {
        $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_upt'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE SET NULL");
        }
    } catch (Exception $e) {
        // Constraint might already exist or other issue, ignore
    }
    
    // Foreign key for data_penghuni -> upt
    try {
        $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'data_penghuni' AND CONSTRAINT_NAME = 'fk_penghuni_upt'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE data_penghuni ADD CONSTRAINT fk_penghuni_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE");
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    // Foreign key for data_overstaying -> upt
    try {
        $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'data_overstaying' AND CONSTRAINT_NAME = 'fk_overstaying_upt'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE data_overstaying ADD CONSTRAINT fk_overstaying_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE");
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    // Foreign key for data_integrasi -> upt
    try {
        $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'data_integrasi' AND CONSTRAINT_NAME = 'fk_integrasi_upt'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE data_integrasi ADD CONSTRAINT fk_integrasi_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE");
        }
    } catch (Exception $e) {
        // Ignore
    }

    // ============================================
    // STEP 3: Insert default data
    // ============================================
    
    // Insert default admin user if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role, upt_id, akses_dashboard, akses_penghuni, akses_overstaying, akses_integrasi, akses_upt, akses_user, akses_instansi) 
            VALUES ('admin', '$hashedPassword', 'Administrator', 'admin', NULL, 1, 1, 1, 1, 1, 1, 1)");
    }

    // Insert default instansi if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM instansi");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO instansi (nama_instansi, alamat) VALUES ('Kantor Wilayah Kementerian Hukum dan HAM', 'Jakarta')");
    }
    
    // ============================================
    // STEP 4: Handle migrations for old table structures
    // ============================================
    
    // Check if we need to alter existing tables for data structure
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM data_penghuni LIKE 'jumlah_laki_laki'");
        if ($stmt->fetch()) {
            // Drop foreign key first
            try {
                $pdo->exec("ALTER TABLE data_penghuni DROP FOREIGN KEY fk_penghuni_upt");
            } catch (Exception $e) {}
            
            // Old structure exists, need to migrate
            $pdo->exec("DROP TABLE IF EXISTS data_penghuni");
            $pdo->exec("CREATE TABLE data_penghuni (
                id INT AUTO_INCREMENT PRIMARY KEY,
                upt_id INT NOT NULL,
                klasifikasi_pidana ENUM('Pidana Umum', 'Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida', 'WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'UU ITE') NOT NULL,
                tahanan_dewasa_laki INT DEFAULT 0,
                tahanan_dewasa_perempuan INT DEFAULT 0,
                tahanan_anak_laki INT DEFAULT 0,
                tahanan_anak_perempuan INT DEFAULT 0,
                narapidana_dewasa_laki INT DEFAULT 0,
                narapidana_dewasa_perempuan INT DEFAULT 0,
                narapidana_anak_laki INT DEFAULT 0,
                narapidana_anak_perempuan INT DEFAULT 0,
                tanggal DATE NOT NULL,
                keterangan TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Re-add foreign key
            $pdo->exec("ALTER TABLE data_penghuni ADD CONSTRAINT fk_penghuni_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE");
        }
    } catch (Exception $e) {
        // Table doesn't exist yet, ignore
    }
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM data_overstaying LIKE 'jumlah_laki_laki'");
        if ($stmt->fetch()) {
            try {
                $pdo->exec("ALTER TABLE data_overstaying DROP FOREIGN KEY fk_overstaying_upt");
            } catch (Exception $e) {}
            
            $pdo->exec("DROP TABLE IF EXISTS data_overstaying");
            $pdo->exec("CREATE TABLE data_overstaying (
                id INT AUTO_INCREMENT PRIMARY KEY,
                upt_id INT NOT NULL,
                tahanan_ai INT DEFAULT 0,
                tahanan_aii INT DEFAULT 0,
                tahanan_aiii INT DEFAULT 0,
                tahanan_aiv INT DEFAULT 0,
                tahanan_av INT DEFAULT 0,
                overstaying_ai INT DEFAULT 0,
                overstaying_aii INT DEFAULT 0,
                overstaying_aiii INT DEFAULT 0,
                overstaying_aiv INT DEFAULT 0,
                overstaying_av INT DEFAULT 0,
                tanggal DATE NOT NULL,
                keterangan TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $pdo->exec("ALTER TABLE data_overstaying ADD CONSTRAINT fk_overstaying_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE");
        }
    } catch (Exception $e) {
        // Table doesn't exist yet, ignore
    }
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM data_integrasi LIKE 'jumlah_laki_laki'");
        if ($stmt->fetch()) {
            try {
                $pdo->exec("ALTER TABLE data_integrasi DROP FOREIGN KEY fk_integrasi_upt");
            } catch (Exception $e) {}
            
            $pdo->exec("DROP TABLE IF EXISTS data_integrasi");
            $pdo->exec("CREATE TABLE data_integrasi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                upt_id INT NOT NULL,
                usulan_pb INT DEFAULT 0,
                usulan_cb INT DEFAULT 0,
                usulan_cmb INT DEFAULT 0,
                usulan_cmk INT DEFAULT 0,
                usulan_asimilasi INT DEFAULT 0,
                sk_terbit_pb INT DEFAULT 0,
                sk_terbit_cb INT DEFAULT 0,
                sk_terbit_cmb INT DEFAULT 0,
                sk_terbit_cmk INT DEFAULT 0,
                sk_terbit_asimilasi INT DEFAULT 0,
                tanggal DATE NOT NULL,
                keterangan TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $pdo->exec("ALTER TABLE data_integrasi ADD CONSTRAINT fk_integrasi_upt FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE");
        }
    } catch (Exception $e) {
        // Table doesn't exist yet, ignore
    }
    
    // Add foto column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'foto'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER nama_lengkap");
        }
    } catch (Exception $e) {}
    
    // Add akses_laporan column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'akses_laporan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN akses_laporan TINYINT(1) DEFAULT 0 AFTER akses_instansi");
            // Give admin access to laporan
            $pdo->exec("UPDATE users SET akses_laporan = 1 WHERE role = 'admin'");
        }
    } catch (Exception $e) {}
    
    // Add new columns to instansi table for kop surat
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'telepon'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN telepon VARCHAR(50) AFTER alamat");
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'email'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN email VARCHAR(100) AFTER telepon");
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'website'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN website VARCHAR(100) AFTER email");
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'kop_image'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN kop_image VARCHAR(255) AFTER logo");
        }
    } catch (Exception $e) {}
    
    // Add time restriction columns to instansi table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'batas_jam_aktif'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN batas_jam_aktif TINYINT(1) DEFAULT 0 AFTER website");
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'jam_mulai'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN jam_mulai TIME DEFAULT '08:00:00' AFTER batas_jam_aktif");
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'jam_selesai'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN jam_selesai TIME DEFAULT '11:00:00' AFTER jam_mulai");
        }
    } catch (Exception $e) {}
    
    // Create pengumuman table for announcements/news
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pengumuman (
            id INT AUTO_INCREMENT PRIMARY KEY,
            judul VARCHAR(255) NOT NULL,
            isi TEXT NOT NULL,
            kategori ENUM('Pengumuman', 'Berita', 'Urgent', 'Info') DEFAULT 'Pengumuman',
            tipe ENUM('Global', 'UPT') DEFAULT 'Global',
            upt_id INT NULL,
            gambar VARCHAR(255) DEFAULT NULL,
            aktif TINYINT(1) DEFAULT 1,
            tampilkan_marquee TINYINT(1) DEFAULT 0,
            tanggal_mulai DATE DEFAULT NULL,
            tanggal_selesai DATE DEFAULT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (upt_id) REFERENCES upt(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Create pengumuman_dibaca table to track read status
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pengumuman_dibaca (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pengumuman_id INT NOT NULL,
            user_id INT NOT NULL,
            dibaca_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_read (pengumuman_id, user_id),
            FOREIGN KEY (pengumuman_id) REFERENCES pengumuman(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Add akses_pengumuman column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'akses_pengumuman'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN akses_pengumuman TINYINT(1) DEFAULT 0 AFTER akses_laporan");
            // Give admin access to pengumuman
            $pdo->exec("UPDATE users SET akses_pengumuman = 1 WHERE role = 'admin'");
        }
    } catch (Exception $e) {}
    
    // Add no_wa column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'no_wa'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER foto");
        }
    } catch (Exception $e) {}
    
    // Add akses_wa_settings column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'akses_wa_settings'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN akses_wa_settings TINYINT(1) DEFAULT 0 AFTER akses_pengumuman");
            // Give admin access to wa settings
            $pdo->exec("UPDATE users SET akses_wa_settings = 1 WHERE role = 'admin'");
        }
    } catch (Exception $e) {}
    
    // Create wa_settings table for WhatsApp gateway configuration
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS wa_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gateway_name VARCHAR(50) DEFAULT 'fonnte',
            api_key VARCHAR(255) DEFAULT NULL,
            api_url VARCHAR(255) DEFAULT 'https://api.fonnte.com/send',
            group_id VARCHAR(50) DEFAULT NULL,
            jam_reminder_pagi TIME DEFAULT '07:30:00',
            jam_reminder_siang TIME DEFAULT '12:00:00',
            aktif TINYINT(1) DEFAULT 0,
            template_pagi TEXT DEFAULT NULL,
            template_siang TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Insert default settings if not exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM wa_settings");
        if ($stmt->fetchColumn() == 0) {
            $templatePagi = "🌅 *PENGINGAT INPUT DATA*\n\nYth. {nama}\nUPT: {upt}\n\nMohon segera input data penghuni hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_";
            $templateSiang = "📋 *LAPORAN INPUT DATA HARIAN*\n📅 Tanggal: {tanggal}\n\n{list_belum_input}\n\n_Sultan Banten Ditjenpas_";
            $stmt = $pdo->prepare("INSERT INTO wa_settings (gateway_name, aktif, template_pagi, template_siang) VALUES ('fonnte', 0, ?, ?)");
            $stmt->execute([$templatePagi, $templateSiang]);
        }
    } catch (Exception $e) {}
    
    // Add template columns if not exist
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM wa_settings LIKE 'template_pagi'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE wa_settings ADD COLUMN template_pagi TEXT DEFAULT NULL AFTER aktif");
            $pdo->exec("ALTER TABLE wa_settings ADD COLUMN template_siang TEXT DEFAULT NULL AFTER template_pagi");
            
            // Update with default templates
            $templatePagi = "🌅 *PENGINGAT INPUT DATA*\n\nYth. {nama}\nUPT: {upt}\n\nMohon segera input data penghuni hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_";
            $templateSiang = "📋 *LAPORAN INPUT DATA HARIAN*\n📅 Tanggal: {tanggal}\n\n{list_belum_input}\n\n_Sultan Banten Ditjenpas_";
            $pdo->prepare("UPDATE wa_settings SET template_pagi = ?, template_siang = ?")->execute([$templatePagi, $templateSiang]);
        }
    } catch (Exception $e) {}
    
    // Create wa_notification_logs table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS wa_notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipe ENUM('reminder_pagi', 'reminder_siang', 'test') NOT NULL,
            target VARCHAR(50) NOT NULL,
            pesan TEXT NOT NULL,
            status ENUM('success', 'failed') DEFAULT 'success',
            response TEXT,
            jumlah_penerima INT DEFAULT 0,
            tanggal_kirim TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Add Twilio columns to wa_settings table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM wa_settings LIKE 'account_sid'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE wa_settings ADD COLUMN account_sid VARCHAR(255) DEFAULT NULL AFTER api_url");
        }
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM wa_settings LIKE 'sender_number'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE wa_settings ADD COLUMN sender_number VARCHAR(50) DEFAULT NULL AFTER account_sid");
        }
    } catch (Exception $e) {}
    
    // Create activity_logs table for user activity tracking
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_name VARCHAR(100) NOT NULL,
            aksi ENUM('login', 'logout', 'tambah', 'edit', 'hapus', 'view', 'export', 'import', 'setting', 'error') NOT NULL,
            modul ENUM('auth', 'penghuni', 'overstaying', 'integrasi', 'user', 'upt', 'instansi', 'wa', 'laporan', 'pengumuman') NOT NULL,
            deskripsi TEXT NOT NULL,
            detail TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_aksi (aksi),
            INDEX idx_modul (modul),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Add akses_activity_log column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'akses_activity_log'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN akses_activity_log TINYINT(1) DEFAULT 0 AFTER akses_wa_settings");
            // Give admin access to activity log
            $pdo->exec("UPDATE users SET akses_activity_log = 1 WHERE role = 'admin'");
        }
    } catch (Exception $e) {}
    
    // Add akses_wa_schedule column to users table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'akses_wa_schedule'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN akses_wa_schedule TINYINT(1) DEFAULT 0 AFTER akses_activity_log");
            // Give admin access to wa schedule
            $pdo->exec("UPDATE users SET akses_wa_schedule = 1 WHERE role = 'admin'");
        }
    } catch (Exception $e) {}
    
    // Create wa_scheduled_reports table for scheduled WA reports
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS wa_scheduled_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_laporan VARCHAR(100) NOT NULL,
            group_id VARCHAR(100) NOT NULL,
            jam_kirim TIME NOT NULL,
            hari VARCHAR(20) DEFAULT 'daily',
            template_pesan TEXT,
            kirim_ke_pimpinan TINYINT(1) DEFAULT 0,
            aktif TINYINT(1) DEFAULT 1,
            last_sent DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Create wa_scheduled_report_logs table for scheduled WA report logs
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS wa_scheduled_report_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            status ENUM('success', 'failed') DEFAULT 'success',
            response TEXT,
            tanggal_kirim TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_report_id (report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Create wa_pimpinan table for WA recipients (pimpinan)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS wa_pimpinan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            jabatan VARCHAR(100),
            no_wa VARCHAR(20) NOT NULL,
            urutan INT DEFAULT 0,
            aktif TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    
    // Add group_id_laporan column to wa_settings table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM wa_settings LIKE 'group_id_laporan'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE wa_settings ADD COLUMN group_id_laporan VARCHAR(50) DEFAULT NULL AFTER group_id");
        }
    } catch (Exception $e) {}
    
    // Add favicon column to instansi table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'favicon'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN favicon VARCHAR(255) DEFAULT NULL AFTER logo");
        }
    } catch (Exception $e) {}

    // Add running_text column to instansi table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'running_text'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN running_text TEXT DEFAULT NULL AFTER logo");
        }
    } catch (Exception $e) {}

    // Add welcome_banner column to instansi table if not exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM instansi LIKE 'welcome_banner'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE instansi ADD COLUMN welcome_banner VARCHAR(255) DEFAULT NULL AFTER running_text");
        }
    } catch (Exception $e) {}
}

// Function to add missing indexes for query performance
function optimizeDatabaseIndexes($pdo) {
    try {
        $stmt = $pdo->query("SHOW INDEX FROM data_penghuni WHERE Key_name = 'idx_tanggal'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE data_penghuni ADD INDEX idx_tanggal (tanggal)");
            $pdo->exec("ALTER TABLE data_penghuni ADD INDEX idx_upt_tanggal (upt_id, tanggal)");
            $pdo->exec("ALTER TABLE data_penghuni ADD INDEX idx_klasifikasi (klasifikasi_pidana)");
        }
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SHOW INDEX FROM data_overstaying WHERE Key_name = 'idx_tanggal'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE data_overstaying ADD INDEX idx_tanggal (tanggal)");
            $pdo->exec("ALTER TABLE data_overstaying ADD INDEX idx_upt_tanggal (upt_id, tanggal)");
        }
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SHOW INDEX FROM data_integrasi WHERE Key_name = 'idx_tanggal'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE data_integrasi ADD INDEX idx_tanggal (tanggal)");
            $pdo->exec("ALTER TABLE data_integrasi ADD INDEX idx_upt_tanggal (upt_id, tanggal)");
        }
    } catch (Exception $e) {}
}

// Initialize database (optimized to run only once using lock file)
$lockFile = __DIR__ . '/db_init.lock';
if (!file_exists($lockFile)) {
    initDatabase($pdo);
    optimizeDatabaseIndexes($pdo);
    @file_put_contents($lockFile, 'Database checked and initialized on ' . date('Y-m-d H:i:s'));
}
