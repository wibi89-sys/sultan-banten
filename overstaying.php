<?php
/**
 * Data Overstaying Page
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('overstaying');

$user = getCurrentUser();
$instansi = getInstansi();
$message = '';
$error = '';

// Check time restriction
$timeRestriction = getTimeRestriction();
$timeCheck = isInputTimeAllowed();
$canInput = $timeCheck['allowed'];
$timeRestrictionMessage = $timeCheck['message'];

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';
    
    // Check time restriction for add/edit action
    if ($action === 'add' || $action === 'edit') {
        $timeCheck = isInputTimeAllowed();
        if (!$timeCheck['allowed']) {
            $error = $timeCheck['message'];
        }
    }
    
    if (!$error && $action === 'add') {
        $upt_id = (int)$_POST['upt_id'];
        $tahanan_ai = (int)$_POST['tahanan_ai'];
        $tahanan_aii = (int)$_POST['tahanan_aii'];
        $tahanan_aiii = (int)$_POST['tahanan_aiii'];
        $tahanan_aiv = (int)$_POST['tahanan_aiv'];
        $tahanan_av = (int)$_POST['tahanan_av'];
        $overstaying_ai = (int)$_POST['overstaying_ai'];
        $overstaying_aii = (int)$_POST['overstaying_aii'];
        $overstaying_aiii = (int)$_POST['overstaying_aiii'];
        $overstaying_aiv = (int)$_POST['overstaying_aiv'];
        $overstaying_av = (int)$_POST['overstaying_av'];
        $tanggal = sanitize($_POST['tanggal']);
        $keterangan = sanitize($_POST['keterangan']);
        
        try {
            // Cek apakah data sudah ada untuk UPT dan tanggal tersebut (mencegah duplikasi)
            $stmtCheckDuplicate = $pdo->prepare("SELECT COUNT(*) FROM data_overstaying WHERE upt_id = ? AND tanggal = ?");
            $stmtCheckDuplicate->execute([$upt_id, $tanggal]);
            if ($stmtCheckDuplicate->fetchColumn() > 0) {
                throw new Exception("Data overstaying untuk UPT ini pada tanggal " . formatDate($tanggal) . " sudah ada. Silakan edit data yang sudah ada daripada membuat data baru.");
            }

            $stmt = $pdo->prepare("INSERT INTO data_overstaying (upt_id, tahanan_ai, tahanan_aii, tahanan_aiii, tahanan_aiv, tahanan_av, overstaying_ai, overstaying_aii, overstaying_aiii, overstaying_aiv, overstaying_av, tanggal, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$upt_id, $tahanan_ai, $tahanan_aii, $tahanan_aiii, $tahanan_aiv, $tahanan_av, $overstaying_ai, $overstaying_aii, $overstaying_aiii, $overstaying_aiv, $overstaying_av, $tanggal, $keterangan]);
            $message = 'Data overstaying berhasil ditambahkan.';
            // Get UPT name for logging
            $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
            $stmtUpt->execute([$upt_id]);
            $namaUpt = $stmtUpt->fetchColumn();
            logActivity('tambah', 'overstaying', "Menambahkan data overstaying UPT $namaUpt tanggal $tanggal", ['upt_id' => $upt_id, 'tanggal' => $tanggal]);
        } catch (Exception $e) {
            $error = 'Gagal menambahkan data: ' . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $upt_id = (int)$_POST['upt_id'];
        $tahanan_ai = (int)$_POST['tahanan_ai'];
        $tahanan_aii = (int)$_POST['tahanan_aii'];
        $tahanan_aiii = (int)$_POST['tahanan_aiii'];
        $tahanan_aiv = (int)$_POST['tahanan_aiv'];
        $tahanan_av = (int)$_POST['tahanan_av'];
        $overstaying_ai = (int)$_POST['overstaying_ai'];
        $overstaying_aii = (int)$_POST['overstaying_aii'];
        $overstaying_aiii = (int)$_POST['overstaying_aiii'];
        $overstaying_aiv = (int)$_POST['overstaying_aiv'];
        $overstaying_av = (int)$_POST['overstaying_av'];
        $tanggal = sanitize($_POST['tanggal']);
        $keterangan = sanitize($_POST['keterangan']);
        
        try {
            $stmt = $pdo->prepare("UPDATE data_overstaying SET upt_id=?, tahanan_ai=?, tahanan_aii=?, tahanan_aiii=?, tahanan_aiv=?, tahanan_av=?, overstaying_ai=?, overstaying_aii=?, overstaying_aiii=?, overstaying_aiv=?, overstaying_av=?, tanggal=?, keterangan=? WHERE id=?");
            $stmt->execute([$upt_id, $tahanan_ai, $tahanan_aii, $tahanan_aiii, $tahanan_aiv, $tahanan_av, $overstaying_ai, $overstaying_aii, $overstaying_aiii, $overstaying_aiv, $overstaying_av, $tanggal, $keterangan, $id]);
            $message = 'Data overstaying berhasil diperbarui.';
            // Get UPT name for logging
            $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
            $stmtUpt->execute([$upt_id]);
            $namaUpt = $stmtUpt->fetchColumn();
            logActivity('edit', 'overstaying', "Mengubah data overstaying UPT $namaUpt tanggal $tanggal", ['id' => $id, 'upt_id' => $upt_id, 'tanggal' => $tanggal]);
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM data_overstaying WHERE id=?");
            $stmt->execute([$id]);
            $message = 'Data overstaying berhasil dihapus.';
            logActivity('hapus', 'overstaying', "Menghapus data overstaying ID: $id", ['id' => $id]);
        } catch (PDOException $e) {
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    }
}
}

$uptList = getAllUPT();

// Date range filter
$tanggalMulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggalSampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : '';

// UPT filter
$filterUpt = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : 0;

$whereClause = "";
$params = [];

if ($tanggalMulai && $tanggalSampai) {
    $whereClause = "WHERE do.tanggal BETWEEN ? AND ?";
    $params = [$tanggalMulai, $tanggalSampai];
} elseif ($tanggalMulai) {
    $whereClause = "WHERE do.tanggal >= ?";
    $params = [$tanggalMulai];
} elseif ($tanggalSampai) {
    $whereClause = "WHERE do.tanggal <= ?";
    $params = [$tanggalSampai];
}

// Add UPT filter
if ($filterUpt > 0) {
    if ($whereClause) {
        $whereClause .= " AND do.upt_id = ?";
    } else {
        $whereClause = "WHERE do.upt_id = ?";
    }
    $params[] = $filterUpt;
}

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM data_overstaying do $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Get data overstaying with UPT info (with Limit)
$sql = "SELECT do.*, u.nama_upt FROM data_overstaying do JOIN upt u ON do.upt_id = u.id $whereClause ORDER BY do.tanggal DESC, do.id DESC LIMIT $itemsPerPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dataOverstaying = $stmt->fetchAll();

// Get totals
$sumSql = "SELECT 
    SUM(tahanan_ai) as t_ai, SUM(tahanan_aii) as t_aii, SUM(tahanan_aiii) as t_aiii, SUM(tahanan_aiv) as t_aiv, SUM(tahanan_av) as t_av,
    SUM(overstaying_ai) as o_ai, SUM(overstaying_aii) as o_aii, SUM(overstaying_aiii) as o_aiii, SUM(overstaying_aiv) as o_aiv, SUM(overstaying_av) as o_av
    FROM data_overstaying do $whereClause";
$sumStmt = $pdo->prepare($sumSql);
$sumStmt->execute($params);
$totals = $sumStmt->fetch();
if (!$totals) {
    $totals = array_fill_keys(['t_ai', 't_aii', 't_aiii', 't_aiv', 't_av', 'o_ai', 'o_aii', 'o_aiii', 'o_aiv', 'o_av'], 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Data Overstaying - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Overstaying */
        .filter-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            box-shadow: var(--shadow-md);
            margin-bottom: 15px;
        }
        
        /* Category Header - Biru Muda untuk Tahanan, Merah untuk Overstaying */
        .category-header {
            color: white;
            padding: 10px 15px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .tahanan-header { background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%); }
        .overstaying-header { background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); }
        
        /* Table Header Colors */
        .table-tahanan-header { background: #e0f2fe !important; }
        .table-overstaying-header { background: #fee2e2 !important; }
        
        /* Jumlah Box */
        .jumlah-box {
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            padding: 10px;
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .jumlah-box.tahanan { background: #e0f2fe; color: #0369a1; }
        .jumlah-box.overstaying { background: #fee2e2; color: #dc2626; }
        
        /* Input Grid */
        .input-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }
        
        @media (max-width: 767px) {
            .input-grid { grid-template-columns: repeat(3, 1fr); }
        }
        
        @media (max-width: 575px) {
            .input-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        /* Badge */
        .badge-keterangan {
            font-size: 10px;
            padding: 4px 8px;
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
                    <?php if (hasAccess('penghuni')): ?>
                    <a href="penghuni.php"><i class="fas fa-users"></i><span>Data Penghuni</span></a>
                    <?php endif; ?>
                    <a href="overstaying.php" class="active"><i class="fas fa-clock"></i><span>Data Overstaying</span></a>
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
                <h4 class="mb-0">Data Overstaying</h4>
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

        <!-- Filter Tanggal -->
        <div class="filter-card">
            <form method="GET" class="row g-2 align-items-end">
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
                    <a href="overstaying.php" class="btn btn-secondary btn-sm flex-fill">Reset</a>
                    <?php
                        $excelParams = [
                            'type' => 'overstaying',
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

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="mb-2 mb-md-0"><i class="fas fa-clock me-2"></i>Data Overstaying UPT</h5>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($timeRestriction['active'] && !$canInput): ?>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-clock me-1"></i>
                        Input: <?= $timeRestriction['jam_mulai'] ?> - <?= $timeRestriction['jam_selesai'] ?>
                    </span>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd" <?= !$canInput ? 'disabled' : '' ?>>
                        <i class="fas fa-plus me-1"></i> Tambah Data
                    </button>
                </div>
            </div>
            
            <?php if ($timeRestriction['active'] && !$canInput && $timeRestrictionMessage): ?>
            <div class="alert alert-warning py-2 mb-3">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <?= $timeRestrictionMessage ?>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Tanggal</th>
                            <th rowspan="2">UPT</th>
                            <th colspan="6" class="text-center table-tahanan-header">Tahanan</th>
                            <th colspan="6" class="text-center table-overstaying-header">Overstaying</th>
                            <th rowspan="2" class="text-center">Keterangan</th>
                            <th rowspan="2" class="text-center">Aksi</th>
                        </tr>
                        <tr>
                            <th class="text-center small table-tahanan-header">AI</th>
                            <th class="text-center small table-tahanan-header">AII</th>
                            <th class="text-center small table-tahanan-header">AIII</th>
                            <th class="text-center small table-tahanan-header">AIV</th>
                            <th class="text-center small table-tahanan-header">AV</th>
                            <th class="text-center small table-tahanan-header">Jml</th>
                            <th class="text-center small table-overstaying-header">AI</th>
                            <th class="text-center small table-overstaying-header">AII</th>
                            <th class="text-center small table-overstaying-header">AIII</th>
                            <th class="text-center small table-overstaying-header">AIV</th>
                            <th class="text-center small table-overstaying-header">AV</th>
                            <th class="text-center small table-overstaying-header">Jml</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($dataOverstaying as $data): 
                            $totalTahanan = $data['tahanan_ai'] + $data['tahanan_aii'] + $data['tahanan_aiii'] + $data['tahanan_aiv'] + $data['tahanan_av'];
                            $totalOverstay = $data['overstaying_ai'] + $data['overstaying_aii'] + $data['overstaying_aiii'] + $data['overstaying_aiv'] + $data['overstaying_av'];
                            $keteranganText = !empty($data['keterangan']) ? $data['keterangan'] : '-';
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/y', strtotime($data['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($data['nama_upt']) ?></td>
                            <td class="text-center"><?= $data['tahanan_ai'] ?></td>
                            <td class="text-center"><?= $data['tahanan_aii'] ?></td>
                            <td class="text-center"><?= $data['tahanan_aiii'] ?></td>
                            <td class="text-center"><?= $data['tahanan_aiv'] ?></td>
                            <td class="text-center"><?= $data['tahanan_av'] ?></td>
                            <td class="text-center fw-bold" style="background:#e0f2fe;"><?= $totalTahanan ?></td>
                            <td class="text-center"><?= $data['overstaying_ai'] ?></td>
                            <td class="text-center"><?= $data['overstaying_aii'] ?></td>
                            <td class="text-center"><?= $data['overstaying_aiii'] ?></td>
                            <td class="text-center"><?= $data['overstaying_aiv'] ?></td>
                            <td class="text-center"><?= $data['overstaying_av'] ?></td>
                            <td class="text-center fw-bold" style="background:#fee2e2;"><?= $totalOverstay ?></td>
                            <td class="small" style="max-width: 200px; white-space: normal;">
                                <?= nl2br(htmlspecialchars($keteranganText)) ?>
                            </td>
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
                        <?php if (empty($dataOverstaying)): ?>
                        <tr><td colspan="17" class="text-center text-muted py-3">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($dataOverstaying)): 
                        $totalTahananAll = $totals['t_ai'] + $totals['t_aii'] + $totals['t_aiii'] + $totals['t_aiv'] + $totals['t_av'];
                        $totalOverstayAll = $totals['o_ai'] + $totals['o_aii'] + $totals['o_aiii'] + $totals['o_aiv'] + $totals['o_av'];
                    ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">TOTAL KESELURUHAN</td>
                            <td class="text-center"><?= number_format((int)$totals['t_ai']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['t_aii']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['t_aiii']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['t_aiv']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['t_av']) ?></td>
                            <td class="text-center" style="background:#e0f2fe;"><?= number_format($totalTahananAll) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['o_ai']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['o_aii']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['o_aiii']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['o_aiv']) ?></td>
                            <td class="text-center"><?= number_format((int)$totals['o_av']) ?></td>
                            <td class="text-center" style="background:#fee2e2;"><?= number_format($totalOverstayAll) ?></td>
                            <td colspan="2"></td>
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

    <!-- Modal Add -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Data Overstaying</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
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
                                <input type="date" name="tanggal" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="category-header tahanan-header">
                                    <i class="fas fa-user-lock me-2"></i>TAHANAN (Kategori A)
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">AI</label>
                                        <input type="number" name="tahanan_ai" id="add_t_ai" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AII</label>
                                        <input type="number" name="tahanan_aii" id="add_t_aii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIII</label>
                                        <input type="number" name="tahanan_aiii" id="add_t_aiii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIV</label>
                                        <input type="number" name="tahanan_aiv" id="add_t_aiv" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AV</label>
                                        <input type="number" name="tahanan_av" id="add_t_av" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                </div>
                                <div class="jumlah-box tahanan">
                                    Jumlah: <span id="add_jumlah_tahanan">0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="category-header overstaying-header">
                                    <i class="fas fa-clock me-2"></i>OVERSTAYING
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">AI</label>
                                        <input type="number" name="overstaying_ai" id="add_o_ai" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AII</label>
                                        <input type="number" name="overstaying_aii" id="add_o_aii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIII</label>
                                        <input type="number" name="overstaying_aiii" id="add_o_aiii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIV</label>
                                        <input type="number" name="overstaying_aiv" id="add_o_aiv" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AV</label>
                                        <input type="number" name="overstaying_av" id="add_o_av" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                </div>
                                <div class="jumlah-box overstaying">
                                    Jumlah: <span id="add_jumlah_overstay">0</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label class="form-label small">Keterangan</label>
                            <textarea name="keterangan" class="form-control form-control-sm" rows="2"></textarea>
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

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Data Overstaying</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEdit">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
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
                        
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="category-header tahanan-header">
                                    <i class="fas fa-user-lock me-2"></i>TAHANAN (Kategori A)
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">AI</label>
                                        <input type="number" name="tahanan_ai" id="edit_t_ai" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AII</label>
                                        <input type="number" name="tahanan_aii" id="edit_t_aii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIII</label>
                                        <input type="number" name="tahanan_aiii" id="edit_t_aiii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIV</label>
                                        <input type="number" name="tahanan_aiv" id="edit_t_aiv" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AV</label>
                                        <input type="number" name="tahanan_av" id="edit_t_av" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                </div>
                                <div class="jumlah-box tahanan">
                                    Jumlah: <span id="edit_jumlah_tahanan">0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="category-header overstaying-header">
                                    <i class="fas fa-clock me-2"></i>OVERSTAYING
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">AI</label>
                                        <input type="number" name="overstaying_ai" id="edit_o_ai" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AII</label>
                                        <input type="number" name="overstaying_aii" id="edit_o_aii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIII</label>
                                        <input type="number" name="overstaying_aiii" id="edit_o_aiii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AIV</label>
                                        <input type="number" name="overstaying_aiv" id="edit_o_aiv" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">AV</label>
                                        <input type="number" name="overstaying_av" id="edit_o_av" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                </div>
                                <div class="jumlah-box overstaying">
                                    Jumlah: <span id="edit_jumlah_overstay">0</span>
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
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
        
        toggleBtn?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);
        
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) toggleSidebar();
            });
        });

        // Update jumlah function
        function updateJumlah(prefix) {
            const tAi = parseInt(document.getElementById(prefix + '_t_ai').value) || 0;
            const tAii = parseInt(document.getElementById(prefix + '_t_aii').value) || 0;
            const tAiii = parseInt(document.getElementById(prefix + '_t_aiii').value) || 0;
            const tAiv = parseInt(document.getElementById(prefix + '_t_aiv').value) || 0;
            const tAv = parseInt(document.getElementById(prefix + '_t_av').value) || 0;
            
            const oAi = parseInt(document.getElementById(prefix + '_o_ai').value) || 0;
            const oAii = parseInt(document.getElementById(prefix + '_o_aii').value) || 0;
            const oAiii = parseInt(document.getElementById(prefix + '_o_aiii').value) || 0;
            const oAiv = parseInt(document.getElementById(prefix + '_o_aiv').value) || 0;
            const oAv = parseInt(document.getElementById(prefix + '_o_av').value) || 0;
            
            document.getElementById(prefix + '_jumlah_tahanan').textContent = tAi + tAii + tAiii + tAiv + tAv;
            document.getElementById(prefix + '_jumlah_overstay').textContent = oAi + oAii + oAiii + oAiv + oAv;
        }

        function editData(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_upt_id').value = data.upt_id;
            document.getElementById('edit_tanggal').value = data.tanggal;
            document.getElementById('edit_t_ai').value = data.tahanan_ai;
            document.getElementById('edit_t_aii').value = data.tahanan_aii;
            document.getElementById('edit_t_aiii').value = data.tahanan_aiii;
            document.getElementById('edit_t_aiv').value = data.tahanan_aiv;
            document.getElementById('edit_t_av').value = data.tahanan_av;
            document.getElementById('edit_o_ai').value = data.overstaying_ai;
            document.getElementById('edit_o_aii').value = data.overstaying_aii;
            document.getElementById('edit_o_aiii').value = data.overstaying_aiii;
            document.getElementById('edit_o_aiv').value = data.overstaying_aiv;
            document.getElementById('edit_o_av').value = data.overstaying_av;
            document.getElementById('edit_keterangan').value = data.keterangan || '';
            updateJumlah('edit');
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        }
    </script>
    <?php include 'includes/marquee.php'; ?>
</body>
</html>
