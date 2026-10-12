<?php
/**
 * Profile Page - User can change password and upload photo
 */

require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser();
$instansi = getInstansi();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $message = 'Sesi tidak valid. Silakan coba lagi.';
        $messageType = 'danger';
    } else {
        // Handle password change
        if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Semua field password harus diisi!';
        $messageType = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password baru minimal 6 karakter!';
        $messageType = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Konfirmasi password tidak cocok!';
        $messageType = 'danger';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if ($userData && password_verify($current_password, $userData['password'])) {
            // Update password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            $message = 'Password berhasil diubah!';
            $messageType = 'success';
        } else {
            $message = 'Password saat ini tidak valid!';
            $messageType = 'danger';
        }
    }
}

        // Handle photo upload
        if (isset($_POST['upload_photo'])) {
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
                    $message = 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WebP.';
                    $messageType = 'danger';
                } elseif ($_FILES['photo']['size'] > $maxSize) {
                    $message = 'Ukuran file maksimal 2MB!';
                    $messageType = 'danger';
                } else {
                    $uploadDir = 'uploads/profile/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'user_' . $user['id'] . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (!empty($user['foto']) && file_exists($user['foto'])) {
                        unlink($user['foto']);
                    }
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                        $stmt = $pdo->prepare("UPDATE users SET foto = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$filepath, $user['id']]);
                        $user = getCurrentUser();
                        $message = 'Foto profil berhasil diupload!';
                        $messageType = 'success';
                    } else {
                        $message = 'Gagal mengupload foto!';
                        $messageType = 'danger';
                    }
                }
            } else {
                $message = 'Pilih file foto terlebih dahulu!';
                $messageType = 'danger';
            }
        }

        // Handle profile info update
        if (isset($_POST['update_profile'])) {
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            
            if (empty($nama_lengkap)) {
                $message = 'Nama lengkap tidak boleh kosong!';
                $messageType = 'danger';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nama_lengkap, $user['id']]);
                $user = getCurrentUser();
                $message = 'Profil berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
}

// Handle photo delete (GET)
if (isset($_GET['delete_photo'])) {
    if (!empty($user['foto']) && file_exists($user['foto'])) {
        unlink($user['foto']);
    }
    
    $stmt = $pdo->prepare("UPDATE users SET foto = NULL, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    $user = getCurrentUser();
    $message = 'Foto profil berhasil dihapus!';
    $messageType = 'success';
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
    <title>Profil Saya - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Profile */
        :root {
            --bg-body: #f5f7fa;
            --bg-card: #ffffff;
            --bg-topbar: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --shadow-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-topbar: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --shadow-color: rgba(0,0,0,0.3);
        }
        
        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .content-card {
            background: var(--bg-card);
            transition: background-color 0.3s ease;
        }
        
        .content-card h5 {
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .profile-photo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-500);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 4px solid var(--primary-500);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .profile-photo-placeholder i {
            font-size: 60px;
            color: white;
        }
        
        .profile-name {
            font-size: 20px;
            font-weight: 600;
            margin-top: 15px;
            color: var(--text-primary);
        }
        
        .profile-role {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .info-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 15px;
        }
        
        /* Dark Mode Toggle Switch */
        .dark-mode-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 15px;
        }

        .dark-mode-switch .switch-label {
            font-size: 12px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .dark-mode-switch .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .dark-mode-switch .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .dark-mode-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 26px;
        }

        .dark-mode-switch .slider:before {
            position: absolute;
            content: "\f185";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 12px;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .dark-mode-switch input:checked + .slider {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .dark-mode-switch input:checked + .slider:before {
            transform: translateX(24px);
            content: "\f186";
            color: #a78bfa;
        }
        
        /* Dark mode specific styles */
        [data-theme="dark"] .content-card {
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--bg-card);
            border-color: var(--primary-500);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        [data-theme="dark"] .form-label {
            color: var(--text-secondary);
        }

        [data-theme="dark"] .text-muted {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] h5 {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] .dark-mode-switch .switch-label {
            color: var(--text-secondary);
        }

        @media (max-width: 767.98px) {
            .profile-photo, .profile-photo-placeholder {
                width: 120px;
                height: 120px;
            }
            .profile-photo-placeholder i {
                font-size: 48px;
            }
            .dark-mode-switch { margin-right: 8px; }
            .dark-mode-switch .switch-label { display: none; }
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

            <a href="profile.php" class="active"><i class="fas fa-user-circle"></i><span>Profil Saya</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0">Profil Saya</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <!-- Dark Mode Toggle -->
                <div class="dark-mode-switch">
                    <span class="switch-label d-none d-md-inline"><i class="fas fa-sun"></i></span>
                    <label class="switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider"></span>
                    </label>
                    <span class="switch-label d-none d-md-inline"><i class="fas fa-moon"></i></span>
                </div>
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Photo & Info -->
            <div class="col-md-4">
                <div class="content-card">
                    <div class="profile-photo-container">
                        <?php if (!empty($user['foto']) && file_exists($user['foto'])): ?>
                            <img src="<?= htmlspecialchars($user['foto']) ?>" alt="Foto Profil" class="profile-photo">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="profile-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div class="profile-role">
                            <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Photo Upload -->
                    <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="upload_photo" value="1">
                        <div class="mb-3">
                            <label for="photo" class="form-label small">Upload Foto Profil</label>
                            <input type="file" class="form-control form-control-sm" id="photo" name="photo" accept="image/*">
                            <div class="form-text small">Format: JPG, PNG, GIF, WebP. Maks: 2MB</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="fas fa-upload me-1"></i> Upload
                            </button>
                            <?php if (!empty($user['foto']) && file_exists($user['foto'])): ?>
                            <a href="?delete_photo=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus foto profil?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- User Info -->
                <div class="content-card">
                    <h5><i class="fas fa-info-circle me-2"></i>Informasi Akun</h5>
                    <div class="info-label">Username</div>
                    <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                    
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    
                    <div class="info-label">Role</div>
                    <div class="info-value">
                        <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                    </div>
                    
                    <?php if (!empty($user['nama_upt'])): ?>
                    <div class="info-label">UPT</div>
                    <div class="info-value"><?= htmlspecialchars($user['nama_upt']) ?></div>
                    <?php endif; ?>
                    
                    <div class="info-label">Terdaftar Sejak</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                </div>
            </div>
            
            <!-- Edit Forms -->
            <div class="col-md-8">
                <!-- Edit Profile -->
                <div class="content-card">
                    <h5><i class="fas fa-edit me-2"></i>Edit Profil</h5>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="content-card">
                    <h5><i class="fas fa-key me-2"></i>Ubah Password</h5>
                    <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Minimal 6 karakter</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
        © Ditjenpas Banten 2026
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        
        // Check saved preference
        if (localStorage.getItem('darkMode') === 'true') {
            body.setAttribute('data-theme', 'dark');
            darkModeToggle.checked = true;
        }
        
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                body.removeAttribute('data-theme');
                localStorage.setItem('darkMode', 'false');
            }
        });
        
        // Sidebar toggle for mobile
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    </script>
</body>
</html>
