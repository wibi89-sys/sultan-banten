<?php
/**
 * Data Penghuni Page
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('penghuni');

$user = getCurrentUser();
$instansi = getInstansi();
$message = '';
$error = '';

// Check time restriction
$timeRestriction = getTimeRestriction();
$timeCheck = isInputTimeAllowed();
$canInput = $timeCheck['allowed'];
$timeRestrictionMessage = $timeCheck['message'];

// Klasifikasi options grouped
$klasifikasiUmum = ['Pidana Umum'];
$klasifikasiKhusus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];
$klasifikasiUUITE = ['UU ITE'];
$klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

// All klasifikasi for calculations (exclude Lainnya)
$klasifikasiUtama = array_merge($klasifikasiUmum, $klasifikasiKhusus, $klasifikasiUUITE);
$allKlasifikasi = array_merge($klasifikasiUtama, $klasifikasiLainnya);

// Klasifikasi key mapping for form field names
$klasifikasiKeys = [
    'Pidana Umum' => 'pidana_umum',
    'Tipikor' => 'tipikor',
    'Narkoba Bandar' => 'narkoba_bandar',
    'Narkoba Pengguna' => 'narkoba_pengguna',
    'Teroris' => 'teroris',
    'Trafficking' => 'trafficking',
    'Ilegal Logging' => 'ilegal_logging',
    'Money Laundering' => 'money_laundering',
    'Genosida' => 'genosida',
    'UU ITE' => 'uu_ite',
    'WNA' => 'wna',
    'Sakit Berkepanjangan' => 'sakit_berkepanjangan',
    'Lansia >70 tahun' => 'lansia_70_tahun',
    'Pidana Mati' => 'pidana_mati',
    'Seumur Hidup' => 'seumur_hidup'
];

// Handle AJAX get previous data
if (isset($_GET['action']) && $_GET['action'] === 'get_previous_data') {
    header('Content-Type: application/json');
    $uptId = (int)($_GET['upt_id'] ?? 0);
    $tanggal = sanitize($_GET['tanggal'] ?? date('Y-m-d'));

    if (!$uptId) {
        echo json_encode(['success' => false, 'has_data' => false, 'message' => 'Pilih UPT terlebih dahulu.']);
        exit;
    }

    // Input data tidak ada hari libur: ambil tepat data hari kemarin (H-1)
    $previousDate = date('Y-m-d', strtotime($tanggal . ' - 1 day'));

    $stmtData = $pdo->prepare("SELECT * FROM data_penghuni WHERE upt_id = ? AND tanggal = ?");
    $stmtData->execute([$uptId, $previousDate]);
    $records = $stmtData->fetchAll();

    if (empty($records)) {
        echo json_encode([
            'success' => true,
            'has_data' => false,
            'previous_date' => $previousDate,
            'formatted_yesterday' => formatIndonesianDate($previousDate),
            'message' => 'Data hari kemarin (' . formatIndonesianDate($previousDate) . ') belum diinput untuk UPT ini. Silakan input data hari kemarin terlebih dahulu atau input manual dari 0.'
        ]);
        exit;
    }

    $data = [];
    foreach ($records as $r) {
        $key = $klasifikasiKeys[$r['klasifikasi_pidana']] ?? null;
        if ($key) {
            $data[$key] = [
                'tahanan_dl' => (int)$r['tahanan_dewasa_laki'],
                'tahanan_dp' => (int)$r['tahanan_dewasa_perempuan'],
                'tahanan_al' => (int)$r['tahanan_anak_laki'],
                'tahanan_ap' => (int)$r['tahanan_anak_perempuan'],
                'narapidana_dl' => (int)$r['narapidana_dewasa_laki'],
                'narapidana_dp' => (int)$r['narapidana_dewasa_perempuan'],
                'narapidana_al' => (int)$r['narapidana_anak_laki'],
                'narapidana_ap' => (int)$r['narapidana_anak_perempuan'],
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'has_data' => count($data) > 0,
        'previous_date' => $previousDate,
        'formatted_date' => formatIndonesianDate($previousDate),
        'data' => $data
    ]);
    exit;
}

// Handle Save All Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';
    
    // Check time restriction for add/edit action
    if ($action === 'add_all' || $action === 'edit') {
        $timeCheck = isInputTimeAllowed();
        if (!$timeCheck['allowed']) {
            $error = $timeCheck['message'];
        }
    }
    
    if (!$error && $action === 'add_all') {
        $upt_id = (int)$_POST['upt_id'];
        $tanggal = sanitize($_POST['tanggal']);
        
        try {
            // Cek apakah data sudah ada untuk UPT dan tanggal tersebut (mencegah duplikasi)
            $stmtCheckDuplicate = $pdo->prepare("SELECT COUNT(*) FROM data_penghuni WHERE upt_id = ? AND tanggal = ?");
            $stmtCheckDuplicate->execute([$upt_id, $tanggal]);
            if ($stmtCheckDuplicate->fetchColumn() > 0) {
                throw new Exception("Data untuk UPT ini pada tanggal " . formatDate($tanggal) . " sudah ada. Silakan edit data yang sudah ada daripada menginput baru.");
            }

            $pdo->beginTransaction();
            
            $insertCount = 0;
            
            foreach ($allKlasifikasi as $klasifikasi) {
                // Lewati jika klasifikasi kosong
                if (empty($klasifikasi)) continue;

                $key = $klasifikasiKeys[$klasifikasi];
                
                $tahanan_dl = (int)($_POST['tahanan_dl_' . $key] ?? 0);
                $tahanan_dp = (int)($_POST['tahanan_dp_' . $key] ?? 0);
                $tahanan_al = (int)($_POST['tahanan_al_' . $key] ?? 0);
                $tahanan_ap = (int)($_POST['tahanan_ap_' . $key] ?? 0);
                $narapidana_dl = (int)($_POST['narapidana_dl_' . $key] ?? 0);
                $narapidana_dp = (int)($_POST['narapidana_dp_' . $key] ?? 0);
                $narapidana_al = (int)($_POST['narapidana_al_' . $key] ?? 0);
                $narapidana_ap = (int)($_POST['narapidana_ap_' . $key] ?? 0);
                
                // Insert data baru (tidak lagi melakukan update otomatis di sini)
                $stmt = $pdo->prepare("INSERT INTO data_penghuni (upt_id, klasifikasi_pidana, tahanan_dewasa_laki, tahanan_dewasa_perempuan, tahanan_anak_laki, tahanan_anak_perempuan, narapidana_dewasa_laki, narapidana_dewasa_perempuan, narapidana_anak_laki, narapidana_anak_perempuan, tanggal, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$upt_id, $klasifikasi, $tahanan_dl, $tahanan_dp, $tahanan_al, $tahanan_ap, $narapidana_dl, $narapidana_dp, $narapidana_al, $narapidana_ap, $tanggal, '']);
                $insertCount++;
            }
            
            $pdo->commit();
            
            if ($insertCount > 0) {
                $message = "Berhasil menyimpan $insertCount data penghuni (termasuk data dengan nilai 0).";
                // Get UPT name for logging
                $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
                $stmtUpt->execute([$upt_id]);
                $namaUpt = $stmtUpt->fetchColumn();
                logActivity('tambah', 'penghuni', "Menambahkan $insertCount data penghuni untuk UPT $namaUpt tanggal $tanggal", ['upt_id' => $upt_id, 'tanggal' => $tanggal, 'jumlah' => $insertCount]);
            } else {
                $error = 'Pilih UPT dan tanggal terlebih dahulu.';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $upt_id = (int)$_POST['upt_id'];
        $klasifikasi_pidana = sanitize($_POST['klasifikasi_pidana']);
        $tahanan_dewasa_laki = (int)$_POST['tahanan_dewasa_laki'];
        $tahanan_dewasa_perempuan = (int)$_POST['tahanan_dewasa_perempuan'];
        $tahanan_anak_laki = (int)$_POST['tahanan_anak_laki'];
        $tahanan_anak_perempuan = (int)$_POST['tahanan_anak_perempuan'];
        $narapidana_dewasa_laki = (int)$_POST['narapidana_dewasa_laki'];
        $narapidana_dewasa_perempuan = (int)$_POST['narapidana_dewasa_perempuan'];
        $narapidana_anak_laki = (int)$_POST['narapidana_anak_laki'];
        $narapidana_anak_perempuan = (int)$_POST['narapidana_anak_perempuan'];
        $tanggal = sanitize($_POST['tanggal']);
        $keterangan = sanitize($_POST['keterangan']);
        
        if (empty($klasifikasi_pidana)) {
            $error = 'Klasifikasi pidana tidak boleh kosong.';
        }
        
        if (!$error) {
            try {
            $stmt = $pdo->prepare("UPDATE data_penghuni SET upt_id=?, klasifikasi_pidana=?, tahanan_dewasa_laki=?, tahanan_dewasa_perempuan=?, tahanan_anak_laki=?, tahanan_anak_perempuan=?, narapidana_dewasa_laki=?, narapidana_dewasa_perempuan=?, narapidana_anak_laki=?, narapidana_anak_perempuan=?, tanggal=?, keterangan=? WHERE id=?");
            $stmt->execute([$upt_id, $klasifikasi_pidana, $tahanan_dewasa_laki, $tahanan_dewasa_perempuan, $tahanan_anak_laki, $tahanan_anak_perempuan, $narapidana_dewasa_laki, $narapidana_dewasa_perempuan, $narapidana_anak_laki, $narapidana_anak_perempuan, $tanggal, $keterangan, $id]);
            $message = 'Data penghuni berhasil diperbarui.';
            // Get UPT name for logging
            $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
            $stmtUpt->execute([$upt_id]);
            $namaUpt = $stmtUpt->fetchColumn();
            logActivity('edit', 'penghuni', "Mengubah data penghuni UPT $namaUpt - $klasifikasi_pidana", ['id' => $id, 'upt_id' => $upt_id, 'klasifikasi' => $klasifikasi_pidana]);
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM data_penghuni WHERE id=?");
            $stmt->execute([$id]);
            $message = 'Data penghuni berhasil dihapus.';
            logActivity('hapus', 'penghuni', "Menghapus data penghuni ID: $id", ['id' => $id]);
        } catch (PDOException $e) {
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    }
    }
}

// Get all UPT for dropdown
$uptList = getAllUPT();

// Get selected klasifikasi tab for data list
$selectedKlasifikasi = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Date range filter
$tanggalMulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggalSampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : '';

// UPT filter
$filterUpt = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : 0;

// Build query based on filter
$whereClause = "";
$params = [];

if ($selectedKlasifikasi === 'utama') {
    $placeholders = implode(',', array_fill(0, count($klasifikasiUtama), '?'));
    $whereClause = "WHERE dp.klasifikasi_pidana IN ($placeholders)";
    $params = $klasifikasiUtama;
} elseif ($selectedKlasifikasi === 'lainnya') {
    $placeholders = implode(',', array_fill(0, count($klasifikasiLainnya), '?'));
    $whereClause = "WHERE dp.klasifikasi_pidana IN ($placeholders)";
    $params = $klasifikasiLainnya;
} elseif ($selectedKlasifikasi !== 'all' && in_array($selectedKlasifikasi, $allKlasifikasi)) {
    $whereClause = "WHERE dp.klasifikasi_pidana = ?";
    $params[] = $selectedKlasifikasi;
}

// Add date filter
if ($tanggalMulai && $tanggalSampai) {
    if ($whereClause) {
        $whereClause .= " AND dp.tanggal BETWEEN ? AND ?";
    } else {
        $whereClause = "WHERE dp.tanggal BETWEEN ? AND ?";
    }
    $params[] = $tanggalMulai;
    $params[] = $tanggalSampai;
} elseif ($tanggalMulai) {
    if ($whereClause) {
        $whereClause .= " AND dp.tanggal >= ?";
    } else {
        $whereClause = "WHERE dp.tanggal >= ?";
    }
    $params[] = $tanggalMulai;
} elseif ($tanggalSampai) {
    if ($whereClause) {
        $whereClause .= " AND dp.tanggal <= ?";
    } else {
        $whereClause = "WHERE dp.tanggal <= ?";
    }
    $params[] = $tanggalSampai;
}

// Add UPT filter
if ($filterUpt > 0) {
    if ($whereClause) {
        $whereClause .= " AND dp.upt_id = ?";
    } else {
        $whereClause = "WHERE dp.upt_id = ?";
    }
    $params[] = $filterUpt;
}

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM data_penghuni dp $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Get data penghuni with UPT info (with Limit)
$sql = "SELECT dp.*, u.nama_upt FROM data_penghuni dp JOIN upt u ON dp.upt_id = u.id $whereClause ORDER BY dp.tanggal DESC, dp.id DESC LIMIT $itemsPerPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dataPenghuni = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Data Penghuni - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for penghuni.php */
        
        /* Compact Modal */
        .modal-xl { max-width: 900px; }
        .modal-body { padding: 16px; }
        .modal-header { padding: 10px 15px; }
        .modal-footer { padding: 10px 15px; }
        
        /* Input Table Styles */
        .input-container {
            max-height: 450px;
            overflow: auto;
            position: relative;
            background: var(--card-bg);
        }
        
        .input-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .input-table th, .input-table td {
            padding: 2px 3px !important;
        }
        
        .input-table .group-header {
            padding: 2px 8px !important;
        }
        
        .input-table thead th {
            position: sticky;
            z-index: 10;
            background: var(--table-header-bg);
            border-bottom: 2px solid var(--border-color) !important;
        }
        
        .input-table thead th.tahanan-header {
            background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%) !important;
            color: white !important;
        }
        
        .input-table thead th.narapidana-header {
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%) !important;
            color: white !important;
        }
        
        .input-table thead tr:nth-child(1) th {
            top: 0;
            background: var(--table-header-bg);
            z-index: 12;
            border-bottom: none !important; /* Remove gap */
        }
        
        .input-table thead tr:nth-child(2) th {
            top: 26px; /* Adjusted from 31px to match new smaller row height */
            background: var(--table-header-bg);
            z-index: 11;
        }
        
        /* Ensure second row headers under Tahanan/Narapidana also look good */
        .input-table thead tr:nth-child(2) th {
            background: var(--gray-50);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .input-table thead tr:nth-child(2) th {
            background: var(--gray-100);
            color: var(--text-primary);
        }
        
        .input-table input {
            width: 45px !important;
            padding: 2px 2px !important;
            border: 1px solid var(--input-border);
            border-radius: 4px;
            text-align: center;
            font-size: 12px;
            transition: all 0.2s;
            background: var(--input-bg);
            color: var(--text-primary);
        }
        
        .input-table input:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            z-index: 1;
            position: relative;
        }
        
        /* Badge styles for klasifikasi */
        .badge-klasifikasi {
            font-size: 10px;
            padding: 3px 6px;
        }
        
        .badge-lainnya {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            color: white !important;
        }
        
        .row-lainnya {
            background-color: #fff8e1 !important;
        }
        
        .row-lainnya td {
            color: #856404 !important;
        }
        
        [data-theme="dark"] .row-lainnya {
            background-color: rgba(251, 146, 60, 0.15) !important;
        }
        
        [data-theme="dark"] .row-lainnya td {
            color: #fb923c !important;
        }
        
        /* Smaller inputs on mobile */
        @media (max-width: 767.98px) {
            .input-table input {
                width: 32px !important;
                font-size: 11px;
                padding: 2px 1px !important;
            }
            .input-table th, .input-table td {
                padding: 4px 2px !important;
            }
            .input-table .klasifikasi-name {
                font-size: 10px;
                min-width: 100px;
            }
            .input-container {
                max-height: 350px;
            }
            .modal-xl {
                max-width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php if (!empty($instansi['logo'])): ?>
                <img src="<?= htmlspecialchars($instansi['logo']) ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-building fa-3x text-white mb-2"></i>
            <?php endif; ?>
            <h5><?= htmlspecialchars($instansi['nama_instansi'] ?? 'Sultan Banten') ?></h5>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            
            <?php if (hasAccess('penghuni') || hasAccess('overstaying') || hasAccess('integrasi')): ?>
            <div class="menu-parent" data-bs-toggle="collapse" data-bs-target="#menuInputData">
                <i class="fas fa-edit"></i>
                <span>Input Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse show" id="menuInputData">
                <div class="sidebar-submenu">
                    <a href="penghuni.php" class="active"><i class="fas fa-users"></i><span>Data Penghuni</span></a>
                    <?php if (hasAccess('overstaying')): ?>
                    <a href="overstaying.php"><i class="fas fa-clock"></i><span>Data Overstaying</span></a>
                    <?php endif; ?>
                    <?php if (hasAccess('integrasi')): ?>
                    <a href="integrasi.php"><i class="fas fa-exchange-alt"></i><span>Data Integrasi</span></a>
                    <?php endif; ?>
                    <?php if (hasAccess('bebas_harian')): ?>
                    <a href="bebas_harian.php"><i class="fas fa-door-open"></i><span>Bebas Harian</span></a>
                    <?php endif; ?>
                    <a href="kroscek.php"><i class="fas fa-check-double"></i><span>Kroscek Data</span></a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (hasAccess('laporan')): ?>
            <div class="menu-parent collapsed" data-bs-toggle="collapse" data-bs-target="#menuLaporan">
                <i class="fas fa-print"></i>
                <span>Cetak Laporan</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse" id="menuLaporan">
                <div class="sidebar-submenu">
                    <a href="laporan.php?type=penghuni"><i class="fas fa-users"></i><span>Lap. Penghuni</span></a>
                    <a href="laporan.php?type=overstaying"><i class="fas fa-clock"></i><span>Lap. Overstaying</span></a>
                    <a href="laporan.php?type=integrasi"><i class="fas fa-exchange-alt"></i><span>Lap. Integrasi</span></a>
                    <a href="laporan.php?type=bebas_harian"><i class="fas fa-door-open"></i><span>Lap. Bebas Harian</span></a>
                    <a href="laporan.php?type=rekapitulasi"><i class="fas fa-chart-bar"></i><span>Rekapitulasi</span></a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Master Data Parent -->
            <div class="menu-parent collapsed" data-bs-toggle="collapse" data-bs-target="#menuMaster">
                <i class="fas fa-database"></i>
                <span>Master Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse" id="menuMaster">
                <div class="sidebar-submenu">
                    <?php if (hasAccess('upt')): ?>
                    <a href="upt.php"><i class="fas fa-building"></i><span>Data UPT</span></a>
                    <?php endif; ?>
                    <?php if (hasAccess('user')): ?>
                    <a href="users.php"><i class="fas fa-user-cog"></i><span>Manajemen User</span></a>
                    <?php endif; ?>
                    <?php if (hasAccess('instansi')): ?>
                    <a href="instansi.php"><i class="fas fa-cog"></i><span>Pengaturan Instansi</span></a>
                    <?php endif; ?>
                    <a href="pengumuman.php"><i class="fas fa-bullhorn"></i><span>Pengumuman</span></a>
                    <?php if (hasAccess('activity_log')): ?>
                    <a href="activity-log.php"><i class="fas fa-history"></i><span>Activity Log</span></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (hasAccess('wa_settings') || hasAccess('wa_schedule')): ?>
            <div class="menu-parent collapsed" data-bs-toggle="collapse" data-bs-target="#menuWA">
                <i class="fab fa-whatsapp"></i>
                <span>Fitur WhatsApp</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse" id="menuWA">
                <div class="sidebar-submenu">
                    <a href="wa-settings.php"><i class="fas fa-cog"></i><span>Pengaturan WA</span></a>
                    <a href="wa-schedule.php"><i class="fas fa-clock"></i><span>Jadwal Laporan</span></a>
                    <a href="wa-pimpinan.php"><i class="fas fa-user-tie"></i><span>Daftar Pimpinan</span></a>
                </div>
            </div>
            <?php endif; ?>

            <a href="profile.php"><i class="fas fa-user-circle"></i><span>Profil Saya</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0">Data Penghuni</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="mb-2 mb-md-0"><i class="fas fa-users me-2"></i>Data Penghuni UPT</h5>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($timeRestriction['active'] && !$canInput): ?>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-clock me-1"></i>
                        Input: <?= $timeRestriction['jam_mulai'] ?> - <?= $timeRestriction['jam_selesai'] ?>
                    </span>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd" <?= !$canInput ? 'disabled' : '' ?>>
                        <i class="fas fa-plus me-1"></i> Input Data
                    </button>
                </div>
            </div>
            
            <?php if ($timeRestriction['active'] && !$canInput && $timeRestrictionMessage): ?>
            <div class="alert alert-warning py-2 mb-3">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <?= $timeRestrictionMessage ?>
            </div>
            <?php endif; ?>

            <!-- Data Filter Tabs -->
            <div class="data-filter-tabs">
                <a href="?tab=all" class="filter-tab <?= $selectedKlasifikasi === 'all' ? 'active' : '' ?>">Semua</a>
                <a href="?tab=utama" class="filter-tab group-tab <?= $selectedKlasifikasi === 'utama' ? 'active' : '' ?>">
                    <i class="fas fa-gavel me-1"></i>Data Penghuni
                </a>
                <?php foreach ($klasifikasiUmum as $k): ?>
                <a href="?tab=<?= urlencode($k) ?>" class="filter-tab <?= $selectedKlasifikasi === $k ? 'active' : '' ?>"><?= $k ?></a>
                <?php endforeach; ?>
                <?php foreach ($klasifikasiKhusus as $k): ?>
                <a href="?tab=<?= urlencode($k) ?>" class="filter-tab <?= $selectedKlasifikasi === $k ? 'active' : '' ?>"><?= $k ?></a>
                <?php endforeach; ?>
                <a href="?tab=UU+ITE" class="filter-tab <?= $selectedKlasifikasi === 'UU ITE' ? 'active' : '' ?>">UU ITE</a>
                <a href="?tab=Pidana+Mati" class="filter-tab <?= $selectedKlasifikasi === 'Pidana Mati' ? 'active' : '' ?>">Pidana Mati</a>
                <a href="?tab=Seumur+Hidup" class="filter-tab <?= $selectedKlasifikasi === 'Seumur Hidup' ? 'active' : '' ?>">Seumur Hidup</a>
                <a href="?tab=Lansia+%3E70+tahun" class="filter-tab <?= $selectedKlasifikasi === 'Lansia >70 tahun' ? 'active' : '' ?>">Lansia</a>
                <a href="?tab=Sakit+Berkepanjangan" class="filter-tab <?= $selectedKlasifikasi === 'Sakit Berkepanjangan' ? 'active' : '' ?>">Sakit</a>
                <a href="?tab=WNA" class="filter-tab <?= $selectedKlasifikasi === 'WNA' ? 'active' : '' ?>">WNA</a>
                <a href="?tab=lainnya" class="filter-tab lainnya-tab <?= $selectedKlasifikasi === 'lainnya' ? 'active' : '' ?>">
                    <i class="fas fa-ellipsis-h me-1"></i>Lainnya
                </a>
            </div>

            <!-- Filter Tanggal -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($selectedKlasifikasi) ?>">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tanggalMulai) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tanggal Sampai</label>
                            <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="<?= htmlspecialchars($tanggalSampai) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">UPT</label>
                            <select name="upt_id" class="form-select form-select-sm">
                                <option value="0">-- Semua UPT --</option>
                                <?php foreach ($uptList as $upt): ?>
                                <option value="<?= $upt['id'] ?>" <?= $filterUpt == $upt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="?tab=<?= htmlspecialchars($selectedKlasifikasi) ?>" class="btn btn-secondary btn-sm flex-fill">Reset</a>
                            <?php
                                $excelParams = [
                                    'type' => 'penghuni',
                                    'tanggal_mulai' => $tanggalMulai,
                                    'tanggal_akhir' => $tanggalSampai,
                                    'upt_id' => $filterUpt
                                ];
                                $excelUrl = 'generate_excel.php?' . http_build_query(array_filter($excelParams));
                            ?>
                            <a href="<?= $excelUrl ?>" target="_blank" class="btn btn-success btn-sm flex-fill">
                                <i class="fas fa-file-excel me-1"></i>Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>UPT</th>
                            <th>Klasifikasi</th>
                            <th class="text-center" style="background:#fce4ec;" colspan="4">Tahanan</th>
                            <th class="text-center" style="background:#e3f2fd;" colspan="4">Narapidana</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                        <tr>
                            <th colspan="4"></th>
                            <th class="text-center small" style="background:#fce4ec;">DL</th>
                            <th class="text-center small" style="background:#fce4ec;">DP</th>
                            <th class="text-center small" style="background:#fce4ec;">AL</th>
                            <th class="text-center small" style="background:#fce4ec;">AP</th>
                            <th class="text-center small" style="background:#e3f2fd;">DL</th>
                            <th class="text-center small" style="background:#e3f2fd;">DP</th>
                            <th class="text-center small" style="background:#e3f2fd;">AL</th>
                            <th class="text-center small" style="background:#e3f2fd;">AP</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        $grandTotal = 0;
                        $totalTahananDL = 0; $totalTahananDP = 0; $totalTahananAL = 0; $totalTahananAP = 0;
                        $totalNarapidanaDL = 0; $totalNarapidanaDP = 0; $totalNarapidanaAL = 0; $totalNarapidanaAP = 0;
                        
                        foreach ($dataPenghuni as $data): 
                            $total = $data['tahanan_dewasa_laki'] + $data['tahanan_dewasa_perempuan'] + 
                                     $data['tahanan_anak_laki'] + $data['tahanan_anak_perempuan'] +
                                     $data['narapidana_dewasa_laki'] + $data['narapidana_dewasa_perempuan'] +
                                     $data['narapidana_anak_laki'] + $data['narapidana_anak_perempuan'];
                            
                            // Accumulate totals
                            $grandTotal += $total;
                            $totalTahananDL += $data['tahanan_dewasa_laki'];
                            $totalTahananDP += $data['tahanan_dewasa_perempuan'];
                            $totalTahananAL += $data['tahanan_anak_laki'];
                            $totalTahananAP += $data['tahanan_anak_perempuan'];
                            $totalNarapidanaDL += $data['narapidana_dewasa_laki'];
                            $totalNarapidanaDP += $data['narapidana_dewasa_perempuan'];
                            $totalNarapidanaAL += $data['narapidana_anak_laki'];
                            $totalNarapidanaAP += $data['narapidana_anak_perempuan'];
                            
                            $isLainnya = in_array($data['klasifikasi_pidana'], $klasifikasiLainnya);
                        ?>
                        <tr class="<?= $isLainnya ? 'row-lainnya' : '' ?>">
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/y', strtotime($data['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($data['nama_upt']) ?></td>
                            <td>
                                <span class="badge <?= $isLainnya ? 'badge-lainnya' : 'bg-secondary' ?> badge-klasifikasi">
                                    <?= htmlspecialchars($data['klasifikasi_pidana']) ?>
                                </span>
                            </td>
                            <td class="text-center"><?= $data['tahanan_dewasa_laki'] ?></td>
                            <td class="text-center"><?= $data['tahanan_dewasa_perempuan'] ?></td>
                            <td class="text-center"><?= $data['tahanan_anak_laki'] ?></td>
                            <td class="text-center"><?= $data['tahanan_anak_perempuan'] ?></td>
                            <td class="text-center"><?= $data['narapidana_dewasa_laki'] ?></td>
                            <td class="text-center"><?= $data['narapidana_dewasa_perempuan'] ?></td>
                            <td class="text-center"><?= $data['narapidana_anak_laki'] ?></td>
                            <td class="text-center"><?= $data['narapidana_anak_perempuan'] ?></td>
                            <td class="text-center"><strong><?= $total ?></strong></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm py-0 px-1" onclick="editData(<?= htmlspecialchars(json_encode($data), ENT_QUOTES) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $data['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm py-0 px-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dataPenghuni) || $no == 1): ?>
                        <tr><td colspan="14" class="text-center text-muted py-3">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($dataPenghuni)): ?>
                    <tfoot>
                        <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;">
                            <td colspan="4" class="text-center">JUMLAH TOTAL</td>
                            <td class="text-center"><?= $totalTahananDL ?></td>
                            <td class="text-center"><?= $totalTahananDP ?></td>
                            <td class="text-center"><?= $totalTahananAL ?></td>
                            <td class="text-center"><?= $totalTahananAP ?></td>
                            <td class="text-center"><?= $totalNarapidanaDL ?></td>
                            <td class="text-center"><?= $totalNarapidanaDP ?></td>
                            <td class="text-center"><?= $totalNarapidanaAL ?></td>
                            <td class="text-center"><?= $totalNarapidanaAP ?></td>
                            <td class="text-center"><strong><?= $grandTotal ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="text-muted small mb-2 mb-md-0">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + $itemsPerPage, $totalItems) ?> dari <?= $totalItems ?> data
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php 
                        $queryParams = $_GET;
                        
                        // Previous Page
                        $queryParams['page'] = $currentPage - 1;
                        $prevUrl = '?' . http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $prevUrl ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>

                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            $queryParams['page'] = 1;
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">1</a></li>';
                            if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }

                        for ($i = $startPage; $i <= $endPage; $i++):
                            $queryParams['page'] = $i;
                        ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            $queryParams['page'] = $totalPages;
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $totalPages . '</a></li>';
                        }
                        
                        // Next Page
                        $queryParams['page'] = $currentPage + 1;
                        $nextUrl = '?' . http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $nextUrl ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Add - Input All at Once -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Input Data Penghuni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formInputMassal" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="add_all">
                    
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="form-label small fw-semibold">UPT <span class="text-danger">*</span></label>
                                <select name="upt_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih UPT --</option>
                                    <?php foreach ($uptList as $upt): ?>
                                    <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        
                        <!-- Banner Informasi Auto-Copy Data Kemarin -->
                        <div id="previousDataNotice" class="alert alert-info py-2 px-3 mb-2 small d-none d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <i class="fas fa-magic text-primary me-2"></i>
                                <span id="previousDataText">Memeriksa data hari kemarin...</span>
                            </div>
                            <div class="d-flex gap-1 ms-auto">
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" id="btnResetZero" title="Kosongkan semua input ke 0">
                                    <i class="fas fa-eraser me-1"></i>Reset ke 0
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="btnReloadPrevious" title="Muat ulang data hari kemarin">
                                    <i class="fas fa-sync-alt me-1"></i>Muat Ulang
                                </button>
                            </div>
                        </div>
                        
                        <div class="input-container" style="overflow: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                            <table class="input-table mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th rowspan="2" style="width: 140px; vertical-align: middle;">Klasifikasi</th>
                                        <th colspan="4" class="tahanan-header">TAHANAN</th>
                                        <th colspan="4" class="narapidana-header">NARAPIDANA</th>
                                        <th rowspan="2" style="width: 50px; vertical-align: middle;">Total</th>
                                    </tr>
                                    <tr>
                                        <th>DL</th><th>DP</th><th>AL</th><th>AP</th>
                                        <th>DL</th><th>DP</th><th>AL</th><th>AP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Pidana Umum -->
                                    <tr>
                                        <td colspan="10" class="group-header umum text-uppercase py-1" style="font-size: 9px; letter-spacing: 0.5px;"><i class="fas fa-user me-1"></i>Pidana Umum</td>
                                    </tr>
                                    <?php foreach ($klasifikasiUmum as $k): 
                                        $key = $klasifikasiKeys[$k];
                                    ?>
                                    <tr>
                                        <td class="klasifikasi-name"><?= $k ?></td>
                                        <td><input type="number" name="tahanan_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td class="row-total" id="total_<?= $key ?>">0</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pidana Khusus -->
                                    <tr>
                                        <td colspan="10" class="group-header khusus text-uppercase py-1" style="font-size: 9px; letter-spacing: 0.5px;"><i class="fas fa-gavel me-1"></i>Pidana Khusus</td>
                                    </tr>
                                    <?php foreach ($klasifikasiKhusus as $k): 
                                        $key = $klasifikasiKeys[$k];
                                    ?>
                                    <tr>
                                        <td class="klasifikasi-name"><?= $k ?></td>
                                        <td><input type="number" name="tahanan_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td class="row-total" id="total_<?= $key ?>">0</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- UU ITE -->
                                    <tr>
                                        <td colspan="10" class="group-header uu-ite text-uppercase py-1" style="font-size: 9px; letter-spacing: 0.5px;"><i class="fas fa-laptop me-1"></i>UU ITE</td>
                                    </tr>
                                    <?php foreach ($klasifikasiUUITE as $k): 
                                        $key = $klasifikasiKeys[$k];
                                    ?>
                                    <tr>
                                        <td class="klasifikasi-name"><?= $k ?></td>
                                        <td><input type="number" name="tahanan_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td class="row-total" id="total_<?= $key ?>">0</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Lainnya -->
                                    <tr>
                                        <td colspan="10" class="group-header lainnya text-uppercase py-1" style="font-size: 9px; letter-spacing: 0.5px;"><i class="fas fa-exclamation-triangle me-1"></i>Lainnya</td>
                                    </tr>
                                    <?php foreach ($klasifikasiLainnya as $k): 
                                        $key = $klasifikasiKeys[$k];
                                    ?>
                                    <tr class="row-lainnya">
                                        <td class="klasifikasi-name"><?= $k ?></td>
                                        <td><input type="number" name="tahanan_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="tahanan_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dl_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_dp_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_al_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td><input type="number" name="narapidana_ap_<?= $key ?>" value="0" min="0" data-row="<?= $key ?>" oninput="updateRowTotal('<?= $key ?>')"></td>
                                        <td class="row-total" id="total_<?= $key ?>">0</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Grand Total -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="total-box utama p-2 rounded shadow-sm border" style="background: rgba(99, 102, 241, 0.05);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-primary">TOTAL DATA PENGHUNI:</span>
                                        <span class="badge bg-primary fs-6 px-3 py-1" id="grandTotalUtama">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2 mt-md-0">
                                <div class="total-box lainnya p-2 rounded shadow-sm border" style="background: rgba(245, 158, 11, 0.05);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-warning">TOTAL LAINNYA:</span>
                                        <span class="badge bg-warning text-dark fs-6 px-3 py-1" id="grandTotalLainnya">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save me-1"></i> Simpan Semua
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Data Penghuni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEdit">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="klasifikasi_pidana" id="edit_klasifikasi" value="">
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="form-label small fw-semibold">UPT <span class="text-danger">*</span></label>
                                <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm" required>
                                    <?php foreach ($uptList as $upt): ?>
                                    <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" id="edit_tanggal" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info py-2 mb-3" id="editKlasifikasiAlert">
                            <i class="fas fa-info-circle me-1"></i>
                            Klasifikasi: <strong id="edit_klasifikasi_display"></strong>
                        </div>
                        
                        <!-- Tahanan Input -->
                        <div class="category-section">
                            <div class="category-section-header tahanan">
                                <i class="fas fa-user-lock me-2"></i>TAHANAN
                            </div>
                            <div class="category-section-body">
                                <div class="row">
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Dewasa Laki-laki</label>
                                        <input type="number" name="tahanan_dewasa_laki" id="edit_tahanan_dl" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Dewasa Perempuan</label>
                                        <input type="number" name="tahanan_dewasa_perempuan" id="edit_tahanan_dp" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Anak Laki-laki</label>
                                        <input type="number" name="tahanan_anak_laki" id="edit_tahanan_al" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Anak Perempuan</label>
                                        <input type="number" name="tahanan_anak_perempuan" id="edit_tahanan_ap" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Narapidana Input -->
                        <div class="category-section">
                            <div class="category-section-header narapidana">
                                <i class="fas fa-user-times me-2"></i>NARAPIDANA
                            </div>
                            <div class="category-section-body">
                                <div class="row">
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Dewasa Laki-laki</label>
                                        <input type="number" name="narapidana_dewasa_laki" id="edit_narapidana_dl" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Dewasa Perempuan</label>
                                        <input type="number" name="narapidana_dewasa_perempuan" id="edit_narapidana_dp" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Anak Laki-laki</label>
                                        <input type="number" name="narapidana_anak_laki" id="edit_narapidana_al" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <label class="form-label small">Anak Perempuan</label>
                                        <input type="number" name="narapidana_anak_perempuan" id="edit_narapidana_ap" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label class="form-label small">Keterangan</label>
                            <textarea name="keterangan" id="edit_keterangan" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
        © Ditjenpas Banten 2026
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Klasifikasi keys for JS
        const klasifikasiUtama = <?= json_encode(array_map(function($k) use ($klasifikasiKeys) { return $klasifikasiKeys[$k]; }, $klasifikasiUtama)) ?>;
        const klasifikasiLainnya = <?= json_encode(array_map(function($k) use ($klasifikasiKeys) { return $klasifikasiKeys[$k]; }, $klasifikasiLainnya)) ?>;
        
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
        
        toggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
        
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) toggleSidebar();
            });
        });
        
        // Update row total
        function updateRowTotal(key) {
            const inputs = document.querySelectorAll('[data-row="' + key + '"]');
            let total = 0;
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            document.getElementById('total_' + key).textContent = total;
            updateGrandTotals();
        }
        
        // Update grand totals separately
        function updateGrandTotals() {
            // Total Utama
            let grandTotalUtama = 0;
            klasifikasiUtama.forEach(key => {
                const el = document.getElementById('total_' + key);
                if (el) grandTotalUtama += parseInt(el.textContent) || 0;
            });
            document.getElementById('grandTotalUtama').textContent = grandTotalUtama;
            
            // Total Lainnya
            let grandTotalLainnya = 0;
            klasifikasiLainnya.forEach(key => {
                const el = document.getElementById('total_' + key);
                if (el) grandTotalLainnya += parseInt(el.textContent) || 0;
            });
            document.getElementById('grandTotalLainnya').textContent = grandTotalLainnya;
        }
        
        // Edit data
        function editData(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_upt_id').value = data.upt_id;
            document.getElementById('edit_tanggal').value = data.tanggal;
            document.getElementById('edit_klasifikasi').value = data.klasifikasi_pidana;
            document.getElementById('edit_klasifikasi_display').textContent = data.klasifikasi_pidana;
            
            // Check if lainnya
            const lainnyaList = <?= json_encode($klasifikasiLainnya) ?>;
            const alertBox = document.getElementById('editKlasifikasiAlert');
            if (lainnyaList.includes(data.klasifikasi_pidana)) {
                alertBox.className = 'alert alert-warning py-2 mb-3';
                alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Klasifikasi: <strong>' + data.klasifikasi_pidana + '</strong> <small class="text-muted">(Tidak termasuk perhitungan statistik)</small>';
            } else {
                alertBox.className = 'alert alert-info py-2 mb-3';
                alertBox.innerHTML = '<i class="fas fa-info-circle me-1"></i> Klasifikasi: <strong>' + data.klasifikasi_pidana + '</strong>';
            }
            
            document.getElementById('edit_tahanan_dl').value = data.tahanan_dewasa_laki;
            document.getElementById('edit_tahanan_dp').value = data.tahanan_dewasa_perempuan;
            document.getElementById('edit_tahanan_al').value = data.tahanan_anak_laki;
            document.getElementById('edit_tahanan_ap').value = data.tahanan_anak_perempuan;
            
            document.getElementById('edit_narapidana_dl').value = data.narapidana_dewasa_laki;
            document.getElementById('edit_narapidana_dp').value = data.narapidana_dewasa_perempuan;
            document.getElementById('edit_narapidana_al').value = data.narapidana_anak_laki;
            document.getElementById('edit_narapidana_ap').value = data.narapidana_anak_perempuan;
            
            document.getElementById('edit_keterangan').value = data.keterangan || '';
            
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        }

        // Auto-Fetch & Pre-Fill Data Hari Kemarin
        const modalAddEl = document.getElementById('modalAdd');
        const selectUpt = modalAddEl ? modalAddEl.querySelector('select[name="upt_id"]') : null;
        const inputTanggal = modalAddEl ? modalAddEl.querySelector('input[name="tanggal"]') : null;
        const noticeBox = document.getElementById('previousDataNotice');
        const noticeText = document.getElementById('previousDataText');
        const btnResetZero = document.getElementById('btnResetZero');
        const btnReloadPrevious = document.getElementById('btnReloadPrevious');

        function loadPreviousData() {
            if (!selectUpt || !inputTanggal) return;
            const uptId = selectUpt.value;
            const tanggal = inputTanggal.value;
            if (!uptId || !tanggal) {
                if (noticeBox) noticeBox.classList.add('d-none');
                return;
            }

            if (noticeBox) {
                noticeBox.classList.remove('d-none', 'alert-warning', 'alert-success');
                noticeBox.classList.add('alert-info');
                noticeText.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memeriksa data hari kemarin untuk UPT ini...';
            }

            fetch(`penghuni.php?action=get_previous_data&upt_id=${uptId}&tanggal=${tanggal}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.has_data && res.data) {
                        // Populate inputs
                        Object.keys(res.data).forEach(key => {
                            const row = res.data[key];
                            ['tahanan_dl', 'tahanan_dp', 'tahanan_al', 'tahanan_ap', 'narapidana_dl', 'narapidana_dp', 'narapidana_al', 'narapidana_ap'].forEach(col => {
                                const input = modalAddEl.querySelector(`input[name="${col}_${key}"]`);
                                if (input) {
                                    input.value = row[col] ?? 0;
                                }
                            });
                            updateRowTotal(key);
                        });
                        updateGrandTotals();

                        if (noticeBox) {
                            noticeBox.classList.remove('d-none', 'alert-info', 'alert-warning');
                            noticeBox.classList.add('alert-success');
                            noticeText.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> Data berhasil <strong>disalin otomatis dari hari kemarin (${res.formatted_date})</strong>. Silakan sesuaikan angka yang berubah saja.`;
                        }
                    } else {
                        // Jika data hari kemarin belum diinput, kosongkan input ke 0
                        const inputs = modalAddEl.querySelectorAll('.input-table input[type="number"]');
                        inputs.forEach(input => { input.value = 0; });
                        klasifikasiUtama.concat(klasifikasiLainnya).forEach(key => { updateRowTotal(key); });
                        updateGrandTotals();

                        if (noticeBox) {
                            noticeBox.classList.remove('d-none', 'alert-info', 'alert-success');
                            noticeBox.classList.add('alert-warning');
                            noticeText.innerHTML = `<i class="fas fa-exclamation-triangle text-warning me-1"></i> ${res.message || 'Data hari kemarin belum diinput untuk UPT ini. Silakan input dari 0.'}`;
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading previous data:', err);
                    if (noticeBox) noticeBox.classList.add('d-none');
                });
        }

        function resetFormToZero() {
            if (!modalAddEl) return;
            const inputs = modalAddEl.querySelectorAll('.input-table input[type="number"]');
            inputs.forEach(input => {
                input.value = 0;
            });
            klasifikasiUtama.concat(klasifikasiLainnya).forEach(key => {
                updateRowTotal(key);
            });
            updateGrandTotals();

            if (noticeBox) {
                noticeBox.classList.remove('d-none', 'alert-info', 'alert-success');
                noticeBox.classList.add('alert-warning');
                noticeText.innerHTML = '<i class="fas fa-eraser me-1"></i> Semua input telah di-reset ke nilai 0.';
            }
        }

        if (selectUpt) {
            selectUpt.addEventListener('change', loadPreviousData);
        }
        if (inputTanggal) {
            inputTanggal.addEventListener('change', loadPreviousData);
        }
        if (btnResetZero) {
            btnResetZero.addEventListener('click', resetFormToZero);
        }
        if (btnReloadPrevious) {
            btnReloadPrevious.addEventListener('click', loadPreviousData);
        }

        if (modalAddEl) {
            modalAddEl.addEventListener('show.bs.modal', function() {
                if (selectUpt && selectUpt.value) {
                    loadPreviousData();
                }
            });
        }
    </script>
    <?php include 'includes/marquee.php'; ?>
</body>
</html>
