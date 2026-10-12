<?php
/**
 * Pengumuman/Berita Management Page
 */

require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser();
$instansi = getInstansi();
$message = '';
$error = '';

// Check if user can manage pengumuman (add/edit/delete)
$canManage = ($user['role'] === 'admin') || hasAccess('pengumuman');

// Get all UPT for dropdown
$uptList = getAllUPT();

// Handle Add/Edit/Delete (only for users with manage permission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $judul = sanitize($_POST['judul']);
        $isi = $_POST['isi']; // Don't sanitize to preserve HTML
        $kategori = sanitize($_POST['kategori']);
        $tipe = sanitize($_POST['tipe']);
        $upt_id = !empty($_POST['upt_id']) ? (int)$_POST['upt_id'] : NULL;
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $tampilkan_marquee = isset($_POST['tampilkan_marquee']) ? 1 : 0;
        $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : NULL;
        $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : NULL;
        
        // Handle image upload
        $gambar = NULL;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['gambar']['type'], $allowed)) {
                $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $gambar = 'uploads/pengumuman_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $gambar);
            }
        }
        
        // If tipe is Global, set upt_id to NULL
        if ($tipe === 'Global') {
            $upt_id = NULL;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO pengumuman (judul, isi, kategori, tipe, upt_id, gambar, aktif, tampilkan_marquee, tanggal_mulai, tanggal_selesai, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$judul, $isi, $kategori, $tipe, $upt_id, $gambar, $aktif, $tampilkan_marquee, $tanggal_mulai, $tanggal_selesai, $user['id']]);
            $message = 'Pengumuman berhasil ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan pengumuman: ' . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $judul = sanitize($_POST['judul']);
        $isi = $_POST['isi'];
        $kategori = sanitize($_POST['kategori']);
        $tipe = sanitize($_POST['tipe']);
        $upt_id = !empty($_POST['upt_id']) ? (int)$_POST['upt_id'] : NULL;
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $tampilkan_marquee = isset($_POST['tampilkan_marquee']) ? 1 : 0;
        $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : NULL;
        $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : NULL;
        
        // If tipe is Global, set upt_id to NULL
        if ($tipe === 'Global') {
            $upt_id = NULL;
        }
        
        // Handle image upload
        $gambar = NULL;
        $gambarUpdate = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['gambar']['type'], $allowed)) {
                $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $gambar = 'uploads/pengumuman_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $gambar);
                $gambarUpdate = ', gambar = ?';
            }
        }
        
        // Check if delete image checkbox is checked
        $deleteImage = isset($_POST['delete_gambar']);
        if ($deleteImage) {
            // Get old image to delete
            $stmt = $pdo->prepare("SELECT gambar FROM pengumuman WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if ($old && $old['gambar'] && file_exists($old['gambar'])) {
                unlink($old['gambar']);
            }
            $gambarUpdate = ', gambar = NULL';
            $gambar = NULL;
        }
        
        $sql = "UPDATE pengumuman SET judul=?, isi=?, kategori=?, tipe=?, upt_id=?, aktif=?, tampilkan_marquee=?, tanggal_mulai=?, tanggal_selesai=? $gambarUpdate WHERE id=?";
        $params = [$judul, $isi, $kategori, $tipe, $upt_id, $aktif, $tampilkan_marquee, $tanggal_mulai, $tanggal_selesai];
        
        if ($gambar && !$deleteImage) {
            $params[] = $gambar;
        }
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $message = 'Pengumuman berhasil diperbarui.';
    }
    
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // Get old image to delete
            $stmt = $pdo->prepare("SELECT gambar FROM pengumuman WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if ($old && $old['gambar'] && file_exists($old['gambar'])) {
                unlink($old['gambar']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM pengumuman WHERE id=?");
            $stmt->execute([$id]);
            // die("Deleted ID: " . $id); // Debug
            $message = 'Pengumuman berhasil dihapus.';
        }
    }
}

// Handle non-manage actions (like mark_read)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $token = $_POST['csrf_token'] ?? '';
        if (verifyCSRFToken($token)) {
            $pengumuman_id = (int)$_POST['pengumuman_id'];
            $stmt = $pdo->prepare("INSERT IGNORE INTO pengumuman_dibaca (pengumuman_id, user_id) VALUES (?, ?)");
            $stmt->execute([$pengumuman_id, $user['id']]);
        }
        exit; // Exit regardless of token valid for AJAX
    }
}

// Get all pengumuman with UPT info
$uptFilter = getUserUptFilter();
$whereClause = "";
$params = [];

if ($uptFilter) {
    $whereClause = "WHERE (p.tipe = 'Global' OR p.upt_id = ?)";
    $params[] = $uptFilter;
}

$sql = "SELECT p.*, u.nama_upt, creator.nama_lengkap as created_by_name,
        (SELECT COUNT(*) FROM pengumuman_dibaca pd WHERE pd.pengumuman_id = p.id) as read_count
        FROM pengumuman p 
        LEFT JOIN upt u ON p.upt_id = u.id 
        LEFT JOIN users creator ON p.created_by = creator.id 
        $whereClause 
        ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pengumumanList = $stmt->fetchAll();

// Get unread count for current user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pengumuman p 
    LEFT JOIN pengumuman_dibaca pd ON p.id = pd.pengumuman_id AND pd.user_id = ?
    WHERE p.aktif = 1 AND pd.id IS NULL");
$stmt->execute([$user['id']]);
$unreadCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Pengumuman - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Pengumuman */
        .content-wrapper {
            padding: 25px;
        }
        
        /* Pengumuman Cards */
        .pengumuman-card {
            border-left: 4px solid var(--primary-500);
            transition: transform var(--transition-base), box-shadow var(--transition-base);
        }
        
        .pengumuman-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .pengumuman-card.urgent {
            border-left-color: var(--danger-500);
        }
        
        .pengumuman-card.berita {
            border-left-color: var(--success-500);
        }
        
        .pengumuman-card.info {
            border-left-color: var(--accent-500);
        }
        
        .pengumuman-card .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .pengumuman-card .card-text {
            color: var(--gray-500);
            font-size: 14px;
            line-height: 1.6;
            max-height: 80px;
            overflow: hidden;
            position: relative;
        }
        
        .pengumuman-card .card-text::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, white);
        }
        
        .pengumuman-meta {
            font-size: 12px;
            color: var(--gray-400);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .pengumuman-meta i {
            margin-right: 5px;
        }
        
        .kategori-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .kategori-pengumuman { background: var(--primary-500); color: white; }
        .kategori-berita { background: var(--success-500); color: white; }
        .kategori-urgent { background: var(--danger-500); color: white; }
        .kategori-info { background: var(--accent-500); color: #000; }
        
        .tipe-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
        }
        
        .marquee-badge {
            background: var(--warning-500);
            color: #000;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        /* Image preview */
        .img-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--border-radius-sm);
            margin-top: 10px;
            object-fit: cover;
        }
        
        /* Toggle button */
        .toggle-btn {
            background: var(--primary-500);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
        }
    </style>
</head>
<body>
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
            
            <?php if (hasAccess('penghuni') || hasAccess('overstaying') || hasAccess('integrasi') || hasAccess('bebas_harian')): ?>
            <div class="menu-parent collapsed" data-bs-toggle="collapse" data-bs-target="#menuInputData">
                <i class="fas fa-edit"></i>
                <span>Input Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse" id="menuInputData">
                <div class="sidebar-submenu">
                    <?php if (hasAccess('penghuni')): ?>
                    <a href="penghuni.php"><i class="fas fa-users"></i><span>Data Penghuni</span></a>
                    <?php endif; ?>
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

            <div class="menu-parent" data-bs-toggle="collapse" data-bs-target="#menuMaster">
                <i class="fas fa-database"></i>
                <span>Master Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse show" id="menuMaster">
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
                    <a href="pengumuman.php" class="active"><i class="fas fa-bullhorn"></i><span>Pengumuman</span></a>
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
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Pengumuman & Berita</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-wrapper">
            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="badge bg-primary"><?= count($pengumumanList) ?> pengumuman</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $unreadCount ?> belum dibaca</span>
                    <?php endif; ?>
                </div>
                <?php if ($canManage): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                    <i class="fas fa-plus me-1"></i> Tambah Pengumuman
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Pengumuman List -->
            <?php if (empty($pengumumanList)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada pengumuman</h5>
                    <?php if ($canManage): ?>
                    <p class="text-muted">Klik tombol "Tambah Pengumuman" untuk membuat pengumuman baru.</p>
                    <?php else: ?>
                    <p class="text-muted">Pengumuman akan muncul di sini setelah admin menambahkannya.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($pengumumanList as $p): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card pengumuman-card <?= strtolower($p['kategori']) ?> h-100">
                        <?php if ($p['gambar'] && file_exists($p['gambar'])): ?>
                        <img src="<?= htmlspecialchars($p['gambar']) ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="kategori-badge kategori-<?= strtolower($p['kategori']) ?>"><?= $p['kategori'] ?></span>
                                <div>
                                    <?php if ($p['tampilkan_marquee']): ?>
                                    <span class="marquee-badge"><i class="fas fa-tv"></i> Marquee</span>
                                    <?php endif; ?>
                                    <?php if (!$p['aktif']): ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h5 class="card-title"><?= htmlspecialchars($p['judul']) ?></h5>
                            <p class="card-text flex-grow-1"><?= strip_tags(substr($p['isi'], 0, 150)) ?>...</p>
                            <div class="pengumuman-meta mt-3">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($p['created_by_name']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></span>
                            </div>
                            <div class="pengumuman-meta mt-1">
                                <span class="tipe-badge"><?= $p['tipe'] ?><?= $p['nama_upt'] ? ' - ' . $p['nama_upt'] : '' ?></span>
                                <span><i class="fas fa-eye"></i> <?= $p['read_count'] ?> dibaca</span>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewPengumuman(<?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="fas fa-eye"></i> Lihat
                                </button>
                                <?php if ($canManage): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="editPengumuman(<?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="background: transparent; color: #6c757d; font-size: 12px;">
            © Ditjenpas Banten 2026
        </footer>
    </div>
    
    <?php if ($canManage): ?>
    <!-- Modal Add -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Judul <span class="text-danger">*</span></label>
                                <input type="text" name="judul" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="Pengumuman">Pengumuman</option>
                                    <option value="Berita">Berita</option>
                                    <option value="Urgent">Urgent</option>
                                    <option value="Info">Info</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Isi Pengumuman <span class="text-danger">*</span></label>
                            <textarea name="isi" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipe</label>
                                <select name="tipe" class="form-select" id="add_tipe" onchange="toggleUptSelect(this, 'add_upt_container')">
                                    <option value="Global">Global (Semua UPT)</option>
                                    <option value="UPT">Khusus UPT</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3" id="add_upt_container" style="display: none;">
                                <label class="form-label">Pilih UPT</label>
                                <select name="upt_id" class="form-select">
                                    <option value="">-- Pilih UPT --</option>
                                    <?php foreach ($uptList as $upt): ?>
                                    <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gambar (Opsional)</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tampilkan Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tampilkan Sampai</label>
                                <input type="date" name="tanggal_selesai" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3 pt-4">
                                <div class="form-check">
                                    <input type="checkbox" name="aktif" class="form-check-input" id="add_aktif" checked>
                                    <label class="form-check-label" for="add_aktif">Aktif</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="tampilkan_marquee" class="form-check-input" id="add_marquee">
                                    <label class="form-check-label" for="add_marquee">Tampilkan di Marquee</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEdit" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Judul <span class="text-danger">*</span></label>
                                <input type="text" name="judul" id="edit_judul" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" id="edit_kategori" class="form-select">
                                    <option value="Pengumuman">Pengumuman</option>
                                    <option value="Berita">Berita</option>
                                    <option value="Urgent">Urgent</option>
                                    <option value="Info">Info</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Isi Pengumuman <span class="text-danger">*</span></label>
                            <textarea name="isi" id="edit_isi" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipe</label>
                                <select name="tipe" class="form-select" id="edit_tipe" onchange="toggleUptSelect(this, 'edit_upt_container')">
                                    <option value="Global">Global (Semua UPT)</option>
                                    <option value="UPT">Khusus UPT</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3" id="edit_upt_container" style="display: none;">
                                <label class="form-label">Pilih UPT</label>
                                <select name="upt_id" id="edit_upt_id" class="form-select">
                                    <option value="">-- Pilih UPT --</option>
                                    <?php foreach ($uptList as $upt): ?>
                                    <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gambar (Opsional)</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                                <div id="edit_current_image" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tampilkan Mulai</label>
                                <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tampilkan Sampai</label>
                                <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3 pt-4">
                                <div class="form-check">
                                    <input type="checkbox" name="aktif" class="form-check-input" id="edit_aktif" value="1">
                                    <label class="form-check-label" for="edit_aktif">Aktif</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="tampilkan_marquee" class="form-check-input" id="edit_marquee" value="1">
                                    <label class="form-check-label" for="edit_marquee">Tampilkan di Marquee</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="delete_gambar" class="form-check-input" id="edit_delete_gambar" value="1">
                                    <label class="form-check-label" for="edit_delete_gambar">Hapus Gambar</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Modal View -->
    <div class="modal fade" id="modalView" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="view_title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="view_kategori" class="mb-2"></div>
                    <div id="view_image" class="mb-3"></div>
                    <div id="view_isi" style="white-space: pre-wrap;"></div>
                    <hr>
                    <div class="d-flex justify-content-between text-muted small">
                        <span id="view_meta"></span>
                        <span id="view_tipe"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

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
        
        // Toggle UPT select based on tipe
        function toggleUptSelect(select, containerId) {
            const container = document.getElementById(containerId);
            if (select.value === 'UPT') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }
        
        // View pengumuman
        function viewPengumuman(data) {
            document.getElementById('view_title').textContent = data.judul;
            document.getElementById('view_kategori').innerHTML = `<span class="kategori-badge kategori-${data.kategori.toLowerCase()}">${data.kategori}</span>`;
            
            const imageDiv = document.getElementById('view_image');
            if (data.gambar) {
                imageDiv.innerHTML = `<img src="${data.gambar}" class="img-fluid rounded" style="max-height: 300px;">`;
            } else {
                imageDiv.innerHTML = '';
            }
            
            document.getElementById('view_isi').textContent = data.isi;
            document.getElementById('view_meta').textContent = `Oleh: ${data.created_by_name} | ${new Date(data.created_at).toLocaleDateString('id-ID')}`;
            document.getElementById('view_tipe').textContent = `${data.tipe}${data.nama_upt ? ' - ' + data.nama_upt : ''}`;
            
            // Mark as read via AJAX
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('pengumuman_id', data.id);
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            fetch('pengumuman.php', {
                method: 'POST',
                body: formData
            });
            
            new bootstrap.Modal(document.getElementById('modalView')).show();
        }
        
        // Edit pengumuman
        function editPengumuman(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_judul').value = data.judul;
            document.getElementById('edit_isi').value = data.isi;
            document.getElementById('edit_kategori').value = data.kategori;
            document.getElementById('edit_tipe').value = data.tipe;
            document.getElementById('edit_upt_id').value = data.upt_id || '';
            document.getElementById('edit_tanggal_mulai').value = data.tanggal_mulai || '';
            document.getElementById('edit_tanggal_selesai').value = data.tanggal_selesai || '';
            
            document.getElementById('edit_aktif').checked = data.aktif == 1;
            document.getElementById('edit_marquee').checked = data.tampilkan_marquee == 1;
            document.getElementById('edit_delete_gambar').checked = false;
            
            // Show/hide UPT select
            toggleUptSelect(document.getElementById('edit_tipe'), 'edit_upt_container');
            
            // Show current image
            const imageDiv = document.getElementById('edit_current_image');
            if (data.gambar) {
                imageDiv.innerHTML = `<img src="${data.gambar}" class="img-thumbnail" style="max-height: 100px;">`;
            } else {
                imageDiv.innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        }
    </script>
</body>
</html>
