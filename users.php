<?php
/**
 * User Management Page
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('user');

$user = getCurrentUser();
$instansi = getInstansi();
$message = '';
$error = '';

// Get all UPT for dropdown
$uptList = getAllUPT();

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $no_wa = sanitize($_POST['no_wa'] ?? '');
        $role = sanitize($_POST['role']);
        $upt_id = !empty($_POST['upt_id']) ? (int)$_POST['upt_id'] : NULL;
        
        $akses_dashboard = isset($_POST['akses_dashboard']) ? 1 : 0;
        $akses_penghuni = isset($_POST['akses_penghuni']) ? 1 : 0;
        $akses_overstaying = isset($_POST['akses_overstaying']) ? 1 : 0;
        $akses_integrasi = isset($_POST['akses_integrasi']) ? 1 : 0;
        $akses_bebas_harian = isset($_POST['akses_bebas_harian']) ? 1 : 0;
        $akses_upt = isset($_POST['akses_upt']) ? 1 : 0;
        $akses_user = isset($_POST['akses_user']) ? 1 : 0;
        $akses_instansi = isset($_POST['akses_instansi']) ? 1 : 0;
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, no_wa, role, upt_id, akses_dashboard, akses_penghuni, akses_overstaying, akses_integrasi, akses_bebas_harian, akses_upt, akses_user, akses_instansi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $nama_lengkap, $no_wa, $role, $upt_id, $akses_dashboard, $akses_penghuni, $akses_overstaying, $akses_integrasi, $akses_bebas_harian, $akses_upt, $akses_user, $akses_instansi]);
            $message = 'User berhasil ditambahkan.';
            logActivity('tambah', 'user', "Menambahkan user baru: $nama_lengkap ($username)", ['username' => $username, 'nama_lengkap' => $nama_lengkap, 'role' => $role]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Username sudah digunakan.';
            } else {
                $error = 'Gagal menambahkan user: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $no_wa = sanitize($_POST['no_wa'] ?? '');
        $role = sanitize($_POST['role']);
        $upt_id = !empty($_POST['upt_id']) ? (int)$_POST['upt_id'] : NULL;
        
        $akses_dashboard = isset($_POST['akses_dashboard']) ? 1 : 0;
        $akses_penghuni = isset($_POST['akses_penghuni']) ? 1 : 0;
        $akses_overstaying = isset($_POST['akses_overstaying']) ? 1 : 0;
        $akses_integrasi = isset($_POST['akses_integrasi']) ? 1 : 0;
        $akses_bebas_harian = isset($_POST['akses_bebas_harian']) ? 1 : 0;
        $akses_upt = isset($_POST['akses_upt']) ? 1 : 0;
        $akses_user = isset($_POST['akses_user']) ? 1 : 0;
        $akses_instansi = isset($_POST['akses_instansi']) ? 1 : 0;
        
        // Check if password should be updated
        if (!empty($_POST['password'])) {
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap=?, no_wa=?, password=?, role=?, upt_id=?, akses_dashboard=?, akses_penghuni=?, akses_overstaying=?, akses_integrasi=?, akses_bebas_harian=?, akses_upt=?, akses_user=?, akses_instansi=? WHERE id=?");
            $stmt->execute([$nama_lengkap, $no_wa, $hashedPassword, $role, $upt_id, $akses_dashboard, $akses_penghuni, $akses_overstaying, $akses_integrasi, $akses_bebas_harian, $akses_upt, $akses_user, $akses_instansi, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap=?, no_wa=?, role=?, upt_id=?, akses_dashboard=?, akses_penghuni=?, akses_overstaying=?, akses_integrasi=?, akses_bebas_harian=?, akses_upt=?, akses_user=?, akses_instansi=? WHERE id=?");
            $stmt->execute([$nama_lengkap, $no_wa, $role, $upt_id, $akses_dashboard, $akses_penghuni, $akses_overstaying, $akses_integrasi, $akses_bebas_harian, $akses_upt, $akses_user, $akses_instansi, $id]);
        }
        
        $message = 'User berhasil diperbarui.';
        logActivity('edit', 'user', "Mengubah data user: $nama_lengkap", ['id' => $id, 'nama_lengkap' => $nama_lengkap, 'role' => $role]);
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Prevent deleting yourself
        if ($id == $user['id']) {
            $error = 'Tidak dapat menghapus akun yang sedang digunakan.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
                $stmt->execute([$id]);
                $message = 'User berhasil dihapus.';
                logActivity('hapus', 'user', "Menghapus user ID: $id", ['id' => $id]);
            } catch (PDOException $e) {
                $error = 'Gagal menghapus user: ' . $e->getMessage();
            }
        }
    }
    }
}

// Get all users with UPT info
$stmt = $pdo->query("SELECT u.*, upt.nama_upt FROM users u LEFT JOIN upt ON u.upt_id = upt.id ORDER BY u.username");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Manajemen User - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Users */
        .access-checkbox {
            margin-bottom: 6px;
        }
        
        .access-checkbox .form-check-input {
            margin-right: 6px;
        }
        
        .access-checkbox .form-check-label {
            font-size: 12px;
        }
        
        /* Role Badge */
        .role-admin { background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%); }
        .role-operator { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .role-viewer { background: #6c757d; }
        
        .search-box {
            min-width: 250px;
        }
        
        .search-box .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .search-box {
                width: 100%;
                margin-top: 10px;
                order: 3;
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
            
            <?php if (hasAccess('penghuni') || hasAccess('overstaying') || hasAccess('integrasi') || hasAccess('bebas_harian')): ?>
            <!-- Input Data Parent -->
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
            <!-- Cetak Laporan Parent -->
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
                    <a href="users.php" class="active"><i class="fas fa-user-cog"></i><span>Manajemen User</span></a>
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
                <h4 class="mb-0">Manajemen User</h4>
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
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <h5 class="mb-0 me-3"><i class="fas fa-user-cog me-2"></i>Daftar User</h5>
                    <div class="search-box">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="userSearch" class="form-control border-start-0 ps-0" placeholder="Cari user..." aria-label="Cari user">
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
                    <i class="fas fa-plus me-1"></i> Tambah User
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>No. WA</th>
                            <th>Role</th>
                            <th>UPT</th>
                            <th>Akses Menu</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td>
                                <?php if ($u['no_wa']): ?>
                                    <span class="text-success"><i class="fab fa-whatsapp me-1"></i><?= htmlspecialchars($u['no_wa']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge role-<?= str_replace('_', '-', $u['role']) ?>"><?= ucfirst($u['role']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['upt_id']): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($u['nama_upt']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Semua UPT</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $akses = [];
                                if ($u['akses_dashboard']) $akses[] = 'Dashboard';
                                if ($u['akses_penghuni']) $akses[] = 'Penghuni';
                                if ($u['akses_overstaying']) $akses[] = 'Overstaying';
                                if ($u['akses_integrasi']) $akses[] = 'Integrasi';
                                if (!empty($u['akses_bebas_harian'])) $akses[] = 'Bebas Harian';
                                if ($u['akses_upt']) $akses[] = 'UPT';
                                if ($u['akses_user']) $akses[] = 'User';
                                if ($u['akses_instansi']) $akses[] = 'Instansi';
                                echo !empty($akses) ? implode(', ', $akses) : '-';
                                ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning" onclick="editUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Add -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_lengkap" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. WhatsApp</label>
                                <input type="text" name="no_wa" class="form-control" placeholder="08xxxxxxxxxx">
                                <small class="text-muted">Untuk notifikasi reminder input data</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="viewer">Viewer</option>
                                    <option value="operator">Operator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">UPT</label>
                                <select name="upt_id" class="form-select">
                                    <option value="">-- Semua UPT (Admin Level) --</option>
                                    <?php foreach ($uptList as $upt): ?>
                                    <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pilih UPT jika user hanya boleh mengakses data UPT tertentu</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Akses Menu</label>
                            <div class="row">
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_dashboard" value="1" class="form-check-input" id="add_dashboard">
                                        <label class="form-check-label" for="add_dashboard">Dashboard</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_penghuni" value="1" class="form-check-input" id="add_penghuni">
                                        <label class="form-check-label" for="add_penghuni">Data Penghuni</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_overstaying" value="1" class="form-check-input" id="add_overstaying">
                                        <label class="form-check-label" for="add_overstaying">Data Overstaying</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_integrasi" value="1" class="form-check-input" id="add_integrasi">
                                        <label class="form-check-label" for="add_integrasi">Data Integrasi</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_bebas_harian" value="1" class="form-check-input" id="add_bebas_harian">
                                        <label class="form-check-label" for="add_bebas_harian">Bebas Harian</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_upt" value="1" class="form-check-input" id="add_upt">
                                        <label class="form-check-label" for="add_upt">Data UPT</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_user" value="1" class="form-check-input" id="add_user">
                                        <label class="form-check-label" for="add_user">Manajemen User</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_instansi" value="1" class="form-check-input" id="add_instansi">
                                        <label class="form-check-label" for="add_instansi">Pengaturan Instansi</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
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
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEdit">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" id="edit_username" class="form-control" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                <input type="password" name="password" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. WhatsApp</label>
                                <input type="text" name="no_wa" id="edit_no_wa" class="form-control" placeholder="08xxxxxxxxxx">
                                <small class="text-muted">Untuk notifikasi reminder input data</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="edit_role" class="form-select" required>
                                    <option value="viewer">Viewer</option>
                                    <option value="operator">Operator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">UPT</label>
                                <select name="upt_id" id="edit_upt_id" class="form-select">
                                    <option value="">-- Semua UPT (Admin Level) --</option>
                                    <?php foreach ($uptList as $upt): ?>
                                    <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pilih UPT jika user hanya boleh mengakses data UPT tertentu</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Akses Menu</label>
                            <div class="row">
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_dashboard" value="1" class="form-check-input" id="edit_dashboard">
                                        <label class="form-check-label" for="edit_dashboard">Dashboard</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_penghuni" value="1" class="form-check-input" id="edit_penghuni">
                                        <label class="form-check-label" for="edit_penghuni">Data Penghuni</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_overstaying" value="1" class="form-check-input" id="edit_overstaying">
                                        <label class="form-check-label" for="edit_overstaying">Data Overstaying</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_integrasi" value="1" class="form-check-input" id="edit_integrasi">
                                        <label class="form-check-label" for="edit_integrasi">Data Integrasi</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_bebas_harian" value="1" class="form-check-input" id="edit_bebas_harian">
                                        <label class="form-check-label" for="edit_bebas_harian">Bebas Harian</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_upt" value="1" class="form-check-input" id="edit_upt_menu">
                                        <label class="form-check-label" for="edit_upt_menu">Data UPT</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_user" value="1" class="form-check-input" id="edit_user">
                                        <label class="form-check-label" for="edit_user">Manajemen User</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 access-checkbox">
                                    <div class="form-check">
                                        <input type="checkbox" name="akses_instansi" value="1" class="form-check-input" id="edit_instansi">
                                        <label class="form-check-label" for="edit_instansi">Pengaturan Instansi</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
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

        function editUser(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_username').value = data.username;
            document.getElementById('edit_nama').value = data.nama_lengkap;
            document.getElementById('edit_no_wa').value = data.no_wa || '';
            document.getElementById('edit_role').value = data.role;
            document.getElementById('edit_upt_id').value = data.upt_id || '';
            
            document.getElementById('edit_dashboard').checked = data.akses_dashboard == 1;
            document.getElementById('edit_penghuni').checked = data.akses_penghuni == 1;
            document.getElementById('edit_overstaying').checked = data.akses_overstaying == 1;
            document.getElementById('edit_integrasi').checked = data.akses_integrasi == 1;
            document.getElementById('edit_bebas_harian').checked = data.akses_bebas_harian == 1;
            document.getElementById('edit_upt_menu').checked = data.akses_upt == 1;
            document.getElementById('edit_user').checked = data.akses_user == 1;
            document.getElementById('edit_instansi').checked = data.akses_instansi == 1;
            
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        }

        // Search functionality
        document.getElementById('userSearch')?.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#userTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
