<?php
/**
 * Data Integrasi Page
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('integrasi');

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
        $usulan_pb = (int)$_POST['usulan_pb'];
        $usulan_cb = (int)$_POST['usulan_cb'];
        $usulan_cmb = (int)$_POST['usulan_cmb'];
        $usulan_cmk = (int)$_POST['usulan_cmk'];
        $usulan_asimilasi = (int)$_POST['usulan_asimilasi'];
        $sk_terbit_pb = (int)$_POST['sk_terbit_pb'];
        $sk_terbit_cb = (int)$_POST['sk_terbit_cb'];
        $sk_terbit_cmb = (int)$_POST['sk_terbit_cmb'];
        $sk_terbit_cmk = (int)$_POST['sk_terbit_cmk'];
        $sk_terbit_asimilasi = (int)$_POST['sk_terbit_asimilasi'];
        $tanggal = sanitize($_POST['tanggal']);
        $keterangan = sanitize($_POST['keterangan']);
        
        try {
            // Cek apakah data sudah ada untuk UPT dan tanggal tersebut (mencegah duplikasi)
            $stmtCheckDuplicate = $pdo->prepare("SELECT COUNT(*) FROM data_integrasi WHERE upt_id = ? AND tanggal = ?");
            $stmtCheckDuplicate->execute([$upt_id, $tanggal]);
            if ($stmtCheckDuplicate->fetchColumn() > 0) {
                throw new Exception("Data integrasi untuk UPT ini pada tanggal " . formatDate($tanggal) . " sudah ada. Silakan edit data yang sudah ada daripada membuat data baru.");
            }

            $stmt = $pdo->prepare("INSERT INTO data_integrasi (upt_id, usulan_pb, usulan_cb, usulan_cmb, usulan_cmk, usulan_asimilasi, sk_terbit_pb, sk_terbit_cb, sk_terbit_cmb, sk_terbit_cmk, sk_terbit_asimilasi, tanggal, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$upt_id, $usulan_pb, $usulan_cb, $usulan_cmb, $usulan_cmk, $usulan_asimilasi, $sk_terbit_pb, $sk_terbit_cb, $sk_terbit_cmb, $sk_terbit_cmk, $sk_terbit_asimilasi, $tanggal, $keterangan]);
            $message = 'Data integrasi berhasil ditambahkan.';
            // Get UPT name for logging
            $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
            $stmtUpt->execute([$upt_id]);
            $namaUpt = $stmtUpt->fetchColumn();
            logActivity('tambah', 'integrasi', "Menambahkan data integrasi UPT $namaUpt tanggal $tanggal", ['upt_id' => $upt_id, 'tanggal' => $tanggal]);
        } catch (Exception $e) {
            $error = 'Gagal menambahkan data: ' . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $upt_id = (int)$_POST['upt_id'];
        $usulan_pb = (int)$_POST['usulan_pb'];
        $usulan_cb = (int)$_POST['usulan_cb'];
        $usulan_cmb = (int)$_POST['usulan_cmb'];
        $usulan_cmk = (int)$_POST['usulan_cmk'];
        $usulan_asimilasi = (int)$_POST['usulan_asimilasi'];
        $sk_terbit_pb = (int)$_POST['sk_terbit_pb'];
        $sk_terbit_cb = (int)$_POST['sk_terbit_cb'];
        $sk_terbit_cmb = (int)$_POST['sk_terbit_cmb'];
        $sk_terbit_cmk = (int)$_POST['sk_terbit_cmk'];
        $sk_terbit_asimilasi = (int)$_POST['sk_terbit_asimilasi'];
        $tanggal = sanitize($_POST['tanggal']);
        $keterangan = sanitize($_POST['keterangan']);
        
        try {
            $stmt = $pdo->prepare("UPDATE data_integrasi SET upt_id=?, usulan_pb=?, usulan_cb=?, usulan_cmb=?, usulan_cmk=?, usulan_asimilasi=?, sk_terbit_pb=?, sk_terbit_cb=?, sk_terbit_cmb=?, sk_terbit_cmk=?, sk_terbit_asimilasi=?, tanggal=?, keterangan=? WHERE id=?");
            $stmt->execute([$upt_id, $usulan_pb, $usulan_cb, $usulan_cmb, $usulan_cmk, $usulan_asimilasi, $sk_terbit_pb, $sk_terbit_cb, $sk_terbit_cmb, $sk_terbit_cmk, $sk_terbit_asimilasi, $tanggal, $keterangan, $id]);
            $message = 'Data integrasi berhasil diperbarui.';
            // Get UPT name for logging
            $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
            $stmtUpt->execute([$upt_id]);
            $namaUpt = $stmtUpt->fetchColumn();
            logActivity('edit', 'integrasi', "Mengubah data integrasi UPT $namaUpt tanggal $tanggal", ['id' => $id, 'upt_id' => $upt_id, 'tanggal' => $tanggal]);
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM data_integrasi WHERE id=?");
            $stmt->execute([$id]);
            $message = 'Data integrasi berhasil dihapus.';
            logActivity('hapus', 'integrasi', "Menghapus data integrasi ID: $id", ['id' => $id]);
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
    $whereClause = "WHERE di.tanggal BETWEEN ? AND ?";
    $params = [$tanggalMulai, $tanggalSampai];
} elseif ($tanggalMulai) {
    $whereClause = "WHERE di.tanggal >= ?";
    $params = [$tanggalMulai];
} elseif ($tanggalSampai) {
    $whereClause = "WHERE di.tanggal <= ?";
    $params = [$tanggalSampai];
}

// Add UPT filter
if ($filterUpt > 0) {
    if ($whereClause) {
        $whereClause .= " AND di.upt_id = ?";
    } else {
        $whereClause = "WHERE di.upt_id = ?";
    }
    $params[] = $filterUpt;
}

// Pagination
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM data_integrasi di $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Get data integrasi with UPT info (with Limit)
$sql = "SELECT di.*, u.nama_upt FROM data_integrasi di JOIN upt u ON di.upt_id = u.id $whereClause ORDER BY di.tanggal DESC, di.id DESC LIMIT $itemsPerPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dataIntegrasi = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Data Integrasi - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Integrasi */
        .filter-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            box-shadow: var(--shadow-md);
            margin-bottom: 15px;
        }
        
        /* Category Header */
        .category-header {
            color: white;
            padding: 10px 15px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .usulan-header { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .sk-header { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); }
        
        /* Table Header Colors */
        .table-usulan-header { background: #fef3c7 !important; }
        .table-sk-header { background: #d1fae5 !important; }
        
        /* Jumlah Box */
        .jumlah-box {
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            padding: 10px;
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .jumlah-box.usulan { background: #fef3c7; color: #92400e; }
        .jumlah-box.sk { background: #d1fae5; color: #065f46; }
        
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
                    <?php if (hasAccess('overstaying')): ?>
                    <a href="overstaying.php"><i class="fas fa-clock"></i><span>Data Overstaying</span></a>
                    <?php endif; ?>
                    <a href="integrasi.php" class="active"><i class="fas fa-exchange-alt"></i><span>Data Integrasi</span></a>
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
                <h4 class="mb-0">Data Integrasi</h4>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="integrasi.php" class="btn btn-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="mb-2 mb-md-0"><i class="fas fa-exchange-alt me-2"></i>Data Integrasi UPT</h5>
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
                            <th colspan="6" class="text-center table-usulan-header">Usulan</th>
                            <th colspan="6" class="text-center table-sk-header">SK Terbit</th>
                            <th rowspan="2" class="text-center">Keterangan</th>
                            <th rowspan="2" class="text-center">Aksi</th>
                        </tr>
                        <tr>
                            <th class="text-center small table-usulan-header">PB</th>
                            <th class="text-center small table-usulan-header">CB</th>
                            <th class="text-center small table-usulan-header">CMB</th>
                            <th class="text-center small table-usulan-header">CMK</th>
                            <th class="text-center small table-usulan-header">Asim.</th>
                            <th class="text-center small table-usulan-header">Jml</th>
                            <th class="text-center small table-sk-header">PB</th>
                            <th class="text-center small table-sk-header">CB</th>
                            <th class="text-center small table-sk-header">CMB</th>
                            <th class="text-center small table-sk-header">CMK</th>
                            <th class="text-center small table-sk-header">Asim.</th>
                            <th class="text-center small table-sk-header">Jml</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($dataIntegrasi as $data): 
                            $totalUsulan = $data['usulan_pb'] + $data['usulan_cb'] + $data['usulan_cmb'] + $data['usulan_cmk'] + $data['usulan_asimilasi'];
                            $totalSK = $data['sk_terbit_pb'] + $data['sk_terbit_cb'] + $data['sk_terbit_cmb'] + $data['sk_terbit_cmk'] + $data['sk_terbit_asimilasi'];
                            $sisa = $totalUsulan - $totalSK;
                            $keteranganText = $sisa > 0 ? "$sisa usulan belum terbit SK" : 'Semua usulan telah terbit SK';
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/y', strtotime($data['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($data['nama_upt']) ?></td>
                            <td class="text-center"><?= $data['usulan_pb'] ?></td>
                            <td class="text-center"><?= $data['usulan_cb'] ?></td>
                            <td class="text-center"><?= $data['usulan_cmb'] ?></td>
                            <td class="text-center"><?= $data['usulan_cmk'] ?></td>
                            <td class="text-center"><?= $data['usulan_asimilasi'] ?></td>
                            <td class="text-center fw-bold" style="background:#fef3c7;"><?= $totalUsulan ?></td>
                            <td class="text-center"><?= $data['sk_terbit_pb'] ?></td>
                            <td class="text-center"><?= $data['sk_terbit_cb'] ?></td>
                            <td class="text-center"><?= $data['sk_terbit_cmb'] ?></td>
                            <td class="text-center"><?= $data['sk_terbit_cmk'] ?></td>
                            <td class="text-center"><?= $data['sk_terbit_asimilasi'] ?></td>
                            <td class="text-center fw-bold" style="background:#d1fae5;"><?= $totalSK ?></td>
                            <td>
                                <span class="badge <?= $sisa > 0 ? 'bg-warning text-dark' : 'bg-success' ?> badge-keterangan">
                                    <?= $keteranganText ?>
                                </span>
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
                        <?php if (empty($dataIntegrasi)): ?>
                        <tr><td colspan="16" class="text-center text-muted py-3">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
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
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Data Integrasi</h5>
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
                                <div class="category-header usulan-header">
                                    <i class="fas fa-file-alt me-2"></i>USULAN
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">PB</label>
                                        <input type="number" name="usulan_pb" id="add_u_pb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CB</label>
                                        <input type="number" name="usulan_cb" id="add_u_cb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMB</label>
                                        <input type="number" name="usulan_cmb" id="add_u_cmb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMK</label>
                                        <input type="number" name="usulan_cmk" id="add_u_cmk" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">Asimilasi</label>
                                        <input type="number" name="usulan_asimilasi" id="add_u_asim" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                </div>
                                <div class="jumlah-box usulan">
                                    Jumlah: <span id="add_jumlah_usulan">0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="category-header sk-header">
                                    <i class="fas fa-file-signature me-2"></i>SK TERBIT
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">PB</label>
                                        <input type="number" name="sk_terbit_pb" id="add_s_pb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CB</label>
                                        <input type="number" name="sk_terbit_cb" id="add_s_cb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMB</label>
                                        <input type="number" name="sk_terbit_cmb" id="add_s_cmb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMK</label>
                                        <input type="number" name="sk_terbit_cmk" id="add_s_cmk" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                    <div>
                                        <label class="form-label small">Asimilasi</label>
                                        <input type="number" name="sk_terbit_asimilasi" id="add_s_asim" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                    </div>
                                </div>
                                <div class="jumlah-box sk">
                                    Jumlah: <span id="add_jumlah_sk">0</span>
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
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Data Integrasi</h5>
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
                                <div class="category-header usulan-header">
                                    <i class="fas fa-file-alt me-2"></i>USULAN
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">PB</label>
                                        <input type="number" name="usulan_pb" id="edit_u_pb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CB</label>
                                        <input type="number" name="usulan_cb" id="edit_u_cb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMB</label>
                                        <input type="number" name="usulan_cmb" id="edit_u_cmb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMK</label>
                                        <input type="number" name="usulan_cmk" id="edit_u_cmk" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">Asimilasi</label>
                                        <input type="number" name="usulan_asimilasi" id="edit_u_asim" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                </div>
                                <div class="jumlah-box usulan">
                                    Jumlah: <span id="edit_jumlah_usulan">0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="category-header sk-header">
                                    <i class="fas fa-file-signature me-2"></i>SK TERBIT
                                </div>
                                <div class="input-grid">
                                    <div>
                                        <label class="form-label small">PB</label>
                                        <input type="number" name="sk_terbit_pb" id="edit_s_pb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CB</label>
                                        <input type="number" name="sk_terbit_cb" id="edit_s_cb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMB</label>
                                        <input type="number" name="sk_terbit_cmb" id="edit_s_cmb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">CMK</label>
                                        <input type="number" name="sk_terbit_cmk" id="edit_s_cmk" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                    <div>
                                        <label class="form-label small">Asimilasi</label>
                                        <input type="number" name="sk_terbit_asimilasi" id="edit_s_asim" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                    </div>
                                </div>
                                <div class="jumlah-box sk">
                                    Jumlah: <span id="edit_jumlah_sk">0</span>
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
            const uPb = parseInt(document.getElementById(prefix + '_u_pb').value) || 0;
            const uCb = parseInt(document.getElementById(prefix + '_u_cb').value) || 0;
            const uCmb = parseInt(document.getElementById(prefix + '_u_cmb').value) || 0;
            const uCmk = parseInt(document.getElementById(prefix + '_u_cmk').value) || 0;
            const uAsim = parseInt(document.getElementById(prefix + '_u_asim').value) || 0;
            
            const sPb = parseInt(document.getElementById(prefix + '_s_pb').value) || 0;
            const sCb = parseInt(document.getElementById(prefix + '_s_cb').value) || 0;
            const sCmb = parseInt(document.getElementById(prefix + '_s_cmb').value) || 0;
            const sCmk = parseInt(document.getElementById(prefix + '_s_cmk').value) || 0;
            const sAsim = parseInt(document.getElementById(prefix + '_s_asim').value) || 0;
            
            document.getElementById(prefix + '_jumlah_usulan').textContent = uPb + uCb + uCmb + uCmk + uAsim;
            document.getElementById(prefix + '_jumlah_sk').textContent = sPb + sCb + sCmb + sCmk + sAsim;
        }

        function editData(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_upt_id').value = data.upt_id;
            document.getElementById('edit_tanggal').value = data.tanggal;
            document.getElementById('edit_u_pb').value = data.usulan_pb;
            document.getElementById('edit_u_cb').value = data.usulan_cb;
            document.getElementById('edit_u_cmb').value = data.usulan_cmb;
            document.getElementById('edit_u_cmk').value = data.usulan_cmk;
            document.getElementById('edit_u_asim').value = data.usulan_asimilasi;
            document.getElementById('edit_s_pb').value = data.sk_terbit_pb;
            document.getElementById('edit_s_cb').value = data.sk_terbit_cb;
            document.getElementById('edit_s_cmb').value = data.sk_terbit_cmb;
            document.getElementById('edit_s_cmk').value = data.sk_terbit_cmk;
            document.getElementById('edit_s_asim').value = data.sk_terbit_asimilasi;
            document.getElementById('edit_keterangan').value = data.keterangan || '';
            updateJumlah('edit');
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        }
    </script>
    <?php include 'includes/marquee.php'; ?>
</body>
</html>
