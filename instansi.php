<?php
/**
 * Institution Settings Page
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('instansi');

$user = getCurrentUser();
$instansi = getInstansi();
$message = '';
$error = '';

// Check and create logo_kop, ttd_image, stempel_image columns if they don't exist
try {
    $pdo->query("ALTER TABLE instansi ADD COLUMN logo_kop VARCHAR(255) NULL");
} catch(PDOException $e) {}

try {
    $pdo->query("ALTER TABLE instansi ADD COLUMN ttd_image VARCHAR(255) NULL");
} catch(PDOException $e) {}

try {
    $pdo->query("ALTER TABLE instansi ADD COLUMN stempel_image VARCHAR(255) NULL");
} catch(PDOException $e) {}

try {
    $instansi = getInstansi();
} catch(PDOException $e) {}

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        // Handle Delete Logo
        if (isset($_POST['delete_logo']) && !empty($instansi['logo'])) {
    $logoPath = __DIR__ . '/' . $instansi['logo'];
    if (file_exists($logoPath)) {
        unlink($logoPath);
    }
    $stmt = $pdo->prepare("UPDATE instansi SET logo = NULL WHERE id = ?");
    $stmt->execute([$instansi['id']]);
    $instansi = getInstansi();
    $message = 'Logo berhasil dihapus.';
}

// Handle Delete Logo Kop Surat
if (isset($_POST['delete_logo_kop']) && !empty($instansi['logo_kop'])) {
    $logoKopPath = __DIR__ . '/' . $instansi['logo_kop'];
    if (file_exists($logoKopPath)) unlink($logoKopPath);
    $stmt = $pdo->prepare("UPDATE instansi SET logo_kop = NULL WHERE id = ?");
    $stmt->execute([$instansi['id']]);
    $instansi = getInstansi();
    $message = 'Logo Kop Surat berhasil dihapus.';
}

// Handle Delete Tanda Tangan
if (isset($_POST['delete_ttd_image']) && !empty($instansi['ttd_image'])) {
    $ttdPath = __DIR__ . '/' . $instansi['ttd_image'];
    if (file_exists($ttdPath)) unlink($ttdPath);
    $stmt = $pdo->prepare("UPDATE instansi SET ttd_image = NULL WHERE id = ?");
    $stmt->execute([$instansi['id']]);
    $instansi = getInstansi();
    $message = 'Gambar Tanda Tangan berhasil dihapus.';
}

// Handle Delete Stempel
if (isset($_POST['delete_stempel_image']) && !empty($instansi['stempel_image'])) {
    $stempelPath = __DIR__ . '/' . $instansi['stempel_image'];
    if (file_exists($stempelPath)) unlink($stempelPath);
    $stmt = $pdo->prepare("UPDATE instansi SET stempel_image = NULL WHERE id = ?");
    $stmt->execute([$instansi['id']]);
    $instansi = getInstansi();
    $message = 'Gambar Stempel berhasil dihapus.';
}

// Handle Delete Favicon
if (isset($_POST['delete_favicon']) && !empty($instansi['favicon'])) {
    $faviconPath = __DIR__ . '/' . $instansi['favicon'];
    if (file_exists($faviconPath)) {
        unlink($faviconPath);
    }
    $stmt = $pdo->prepare("UPDATE instansi SET favicon = NULL WHERE id = ?");
    $stmt->execute([$instansi['id']]);
    $instansi = getInstansi();
    $message = 'Favicon berhasil dihapus.';
}

// Handle Delete Welcome Banner
if (isset($_POST['delete_banner']) && !empty($instansi['welcome_banner'])) {
    $bannerPath = __DIR__ . '/' . $instansi['welcome_banner'];
    if (file_exists($bannerPath)) {
        unlink($bannerPath);
    }
    $stmt = $pdo->prepare("UPDATE instansi SET welcome_banner = NULL WHERE id = ?");
    $stmt->execute([$instansi['id']]);
    $instansi = getInstansi();
    $message = 'Banner selamat datang berhasil dihapus.';
}

// Handle Favicon Upload (separate form)
if (isset($_POST['save_favicon'])) {
    $favicon = $instansi['favicon'] ?? '';
    
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml', 'image/jpeg'];
        $fileType = $_FILES['favicon']['type'];
        $extension = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        
        // Also check by extension for better compatibility
        $allowedExtensions = ['ico', 'png', 'svg', 'jpg', 'jpeg'];
        
        if (in_array($fileType, $allowedTypes) || in_array($extension, $allowedExtensions)) {
            $uploadDir = 'uploads/';
            $uploadPath = __DIR__ . '/' . $uploadDir;
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $fileName = 'favicon_' . time() . '.' . $extension;
            $targetFile = $uploadPath . $fileName;
            
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $targetFile)) {
                // Delete old favicon if exists
                if (!empty($favicon) && file_exists(__DIR__ . '/' . $favicon)) {
                    unlink(__DIR__ . '/' . $favicon);
                }
                $favicon = $uploadDir . $fileName;
                
                $stmt = $pdo->prepare("UPDATE instansi SET favicon = ? WHERE id = ?");
                $stmt->execute([$favicon, $instansi['id']]);
                $instansi = getInstansi();
                $message = 'Favicon berhasil diunggah.';
            }
        } else {
            $error = 'Format file favicon tidak didukung. Gunakan ICO, PNG, SVG, atau JPG.';
        }
    } else {
        $error = 'Pilih file favicon untuk diunggah.';
    }
}

// Handle Time Restriction Settings (separate form)
if (isset($_POST['save_time_settings'])) {
    $batas_jam_aktif = isset($_POST['batas_jam_aktif']) ? 1 : 0;
    $jam_mulai = sanitize($_POST['jam_mulai'] ?? '08:00');
    $jam_selesai = sanitize($_POST['jam_selesai'] ?? '11:00');
    
    try {
        $stmt = $pdo->prepare("UPDATE instansi SET batas_jam_aktif=?, jam_mulai=?, jam_selesai=? WHERE id=?");
        $stmt->execute([$batas_jam_aktif, $jam_mulai . ':00', $jam_selesai . ':00', $instansi['id']]);
        $message = 'Pengaturan jam berhasil disimpan.';
        
        // Refresh instansi data
        $instansi = getInstansi();
    } catch (PDOException $e) {
        $error = 'Gagal menyimpan pengaturan jam: ' . $e->getMessage();
    }
}

// Handle Update General Settings
if (isset($_POST['save_settings'])) {
    $nama_instansi = sanitize($_POST['nama_instansi'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $website = sanitize($_POST['website'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $running_text = $_POST['running_text'] ?? '';
    
    // Handle logo upload
    $logo = $instansi['logo'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['logo']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = 'uploads/';
            $uploadPath = __DIR__ . '/' . $uploadDir;
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_' . time() . '.' . $extension;
            $targetFile = $uploadPath . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                // Delete old logo if exists
                if (!empty($logo) && file_exists(__DIR__ . '/' . $logo)) {
                    unlink(__DIR__ . '/' . $logo);
                }
                $logo = $uploadDir . $fileName;
            }
        } else {
            $error = 'Format file logo tidak didukung. Gunakan JPG, PNG, atau GIF.';
        }
    }
    
    // Handle logo kop surat upload
    $logo_kop = $instansi['logo_kop'] ?? '';
    if (isset($_FILES['logo_kop']) && $_FILES['logo_kop']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['logo_kop']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = 'uploads/';
            $uploadPath = __DIR__ . '/' . $uploadDir;
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $extension = pathinfo($_FILES['logo_kop']['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_kop_' . time() . '.' . $extension;
            $targetFile = $uploadPath . $fileName;
            
            if (move_uploaded_file($_FILES['logo_kop']['tmp_name'], $targetFile)) {
                // Delete old logo kop if exists
                if (!empty($logo_kop) && file_exists(__DIR__ . '/' . $logo_kop)) {
                    unlink(__DIR__ . '/' . $logo_kop);
                }
                $logo_kop = $uploadDir . $fileName;
            }
        } else {
            $error = 'Format file logo kop surat tidak didukung. Gunakan JPG, PNG, atau GIF.';
        }
    }
    
    // Handle Tanda Tangan upload
    $ttd_image = $instansi['ttd_image'] ?? '';
    if (isset($_FILES['ttd_image']) && $_FILES['ttd_image']['error'] === UPLOAD_ERR_OK) {
        $fileType = $_FILES['ttd_image']['type'];
        if (in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $uploadPath = __DIR__ . '/uploads/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fileName = 'ttd_' . time() . '.' . pathinfo($_FILES['ttd_image']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['ttd_image']['tmp_name'], $uploadPath . $fileName)) {
                if (!empty($ttd_image) && file_exists(__DIR__ . '/' . $ttd_image)) unlink(__DIR__ . '/' . $ttd_image);
                $ttd_image = 'uploads/' . $fileName;
            }
        }
    }
    
    // Handle Stempel upload
    $stempel_image = $instansi['stempel_image'] ?? '';
    if (isset($_FILES['stempel_image']) && $_FILES['stempel_image']['error'] === UPLOAD_ERR_OK) {
        $fileType = $_FILES['stempel_image']['type'];
        if (in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $uploadPath = __DIR__ . '/uploads/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fileName = 'stempel_' . time() . '.' . pathinfo($_FILES['stempel_image']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['stempel_image']['tmp_name'], $uploadPath . $fileName)) {
                if (!empty($stempel_image) && file_exists(__DIR__ . '/' . $stempel_image)) unlink(__DIR__ . '/' . $stempel_image);
                $stempel_image = 'uploads/' . $fileName;
            }
        }
    }
    
    if (!$error && !empty($nama_instansi)) {
        try {
            $stmt = $pdo->prepare("UPDATE instansi SET nama_instansi=?, alamat=?, telepon=?, email=?, website=?, logo=?, logo_kop=?, ttd_image=?, stempel_image=?, running_text=?, latitude=?, longitude=? WHERE id=?");
            $stmt->execute([$nama_instansi, $alamat, $telepon, $email, $website, $logo, $logo_kop, $ttd_image, $stempel_image, $running_text, $latitude, $longitude, $instansi['id']]);
            $message = 'Pengaturan instansi berhasil disimpan.';
            
            // Refresh instansi data
            $instansi = getInstansi();
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
        }
    }
    }
    
    // Handle Update Welcome Banner
    if (isset($_POST['save_banner'])) {
        $banner = $instansi['welcome_banner'] ?? '';
        
        if (isset($_FILES['welcome_banner']) && $_FILES['welcome_banner']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['welcome_banner']['type'];
            $extension = strtolower(pathinfo($_FILES['welcome_banner']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadDir = 'uploads/';
                $uploadPath = __DIR__ . '/' . $uploadDir;
                
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                $fileName = 'banner_' . time() . '.' . $extension;
                $targetFile = $uploadPath . $fileName;
                
                if (move_uploaded_file($_FILES['welcome_banner']['tmp_name'], $targetFile)) {
                    // Delete old banner if exists
                    if (!empty($banner) && file_exists(__DIR__ . '/' . $banner)) {
                        unlink(__DIR__ . '/' . $banner);
                    }
                    $banner = $uploadDir . $fileName;
                    
                    $stmt = $pdo->prepare("UPDATE instansi SET welcome_banner = ? WHERE id = ?");
                    $stmt->execute([$banner, $instansi['id']]);
                    $instansi = getInstansi();
                    $message = 'Banner selamat datang berhasil diunggah.';
                }
            } else {
                $error = 'Format file banner tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            }
        } else {
            $error = 'Pilih file gambar untuk diunggah.';
        }
    }
}
}

// Get favicon URL
$faviconUrl = getFavicon();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Instansi - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Instansi */
        .logo-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--gray-50);
            overflow: hidden;
        }
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .logo-preview i {
            font-size: 48px;
            color: var(--gray-300);
        }
        .favicon-preview {
            width: 64px;
            height: 64px;
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--gray-50);
            overflow: hidden;
        }
        .favicon-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .favicon-preview i {
            font-size: 24px;
            color: var(--gray-300);
        }
        .favicon-demo {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 15px;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            margin-top: 10px;
        }
        .favicon-demo-browser {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid var(--gray-200);
        }
        .favicon-demo-browser img {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>
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
                    <a href="instansi.php" class="active"><i class="fas fa-cog"></i><span>Pengaturan Instansi</span></a>
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

    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Pengaturan Instansi</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="content-card">
            <h5 class="mb-4"><i class="fas fa-cog me-2"></i>Pengaturan Instansi</h5>
            
            <form method="POST" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Nama Instansi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_instansi" class="form-control" value="<?= htmlspecialchars($instansi['nama_instansi'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($instansi['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Running Text Sapaan / Ucapan <small class="text-muted">(Akan muncul di bagian bawah layar)</small></label>
                            <textarea name="running_text" class="form-control" rows="2" placeholder="Contoh: Selamat Hari Dharma Karyadhika 2026..."><?= htmlspecialchars($instansi['running_text'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($instansi['telepon'] ?? '') ?>" placeholder="021-1234567">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($instansi['email'] ?? '') ?>" placeholder="info@instansi.go.id">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($instansi['website'] ?? '') ?>" placeholder="www.instansi.go.id">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" name="latitude" class="form-control" value="<?= $instansi['latitude'] ?? '' ?>" placeholder="-6.2088">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" name="longitude" class="form-control" value="<?= $instansi['longitude'] ?? '' ?>" placeholder="106.8456">
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($instansi['latitude']) && !empty($instansi['longitude'])): ?>
                        <div class="mb-3">
                            <a href="https://maps.google.com/?q=<?= $instansi['latitude'] ?>,<?= $instansi['longitude'] ?>" target="_blank" class="btn btn-outline-info">
                                <i class="fas fa-map-marker-alt me-1"></i> Lihat Lokasi di Google Maps
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logo Instansi</label>
                        <div class="logo-preview mb-3" id="logoPreview">
                            <?php if (!empty($instansi['logo'])): ?>
                                <img src="<?= htmlspecialchars($instansi['logo']) ?>" alt="Logo" id="logoImage">
                            <?php else: ?>
                                <i class="fas fa-image" id="logoPlaceholder"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="logo" class="form-control mb-2" accept="image/*" onchange="previewLogo(this)">
                        
                        <?php if (!empty($instansi['logo'])): ?>
                        <button type="submit" name="delete_logo" value="1" class="btn btn-outline-danger btn-sm mb-3" onclick="return confirm('Hapus logo ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus Logo
                        </button>
                        <?php endif; ?>

                        <hr>

                        <label class="form-label">Logo Kop Surat <small>(Untuk Laporan PDF)</small></label>
                        <div class="logo-preview mb-3" id="logoKopPreview" style="height: 100px;">
                            <?php if (!empty($instansi['logo_kop'])): ?>
                                <img src="<?= htmlspecialchars($instansi['logo_kop']) ?>" alt="Logo Kop" id="logoKopImage">
                            <?php else: ?>
                                <i class="fas fa-file-invoice" id="logoKopPlaceholder"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="logo_kop" class="form-control" accept="image/*" onchange="previewLogoKop(this)">
                        <small class="text-muted d-block mb-2">Format: JPG, PNG. Maks: 2MB</small>
                        <?php if (!empty($instansi['logo_kop'])): ?>
                        <button type="submit" name="delete_logo_kop" value="1" class="btn btn-outline-danger btn-sm mb-2" onclick="return confirm('Hapus logo kop surat ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus Logo Kop
                        </button>
                        <?php endif; ?>

                        <hr>

                        <label class="form-label">Tanda Tangan <small>(Transparan disarankan)</small></label>
                        <div class="logo-preview mb-3" style="height: 80px;">
                            <?php if (!empty($instansi['ttd_image'])): ?>
                                <img src="<?= htmlspecialchars($instansi['ttd_image']) ?>" alt="Tanda Tangan" style="max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <i class="fas fa-signature text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="ttd_image" class="form-control mb-2" accept="image/*">
                        <?php if (!empty($instansi['ttd_image'])): ?>
                        <button type="submit" name="delete_ttd_image" value="1" class="btn btn-outline-danger btn-sm mb-2" onclick="return confirm('Hapus Tanda Tangan ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus TTD
                        </button>
                        <?php endif; ?>

                        <hr>

                        <label class="form-label">Stempel Instansi <small>(Transparan disarankan)</small></label>
                        <div class="logo-preview mb-3" style="height: 80px;">
                            <?php if (!empty($instansi['stempel_image'])): ?>
                                <img src="<?= htmlspecialchars($instansi['stempel_image']) ?>" alt="Stempel" style="max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <i class="fas fa-stamp text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="stempel_image" class="form-control mb-2" accept="image/*">
                        <?php if (!empty($instansi['stempel_image'])): ?>
                        <button type="submit" name="delete_stempel_image" value="1" class="btn btn-outline-danger btn-sm mb-2" onclick="return confirm('Hapus Stempel ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus Stempel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="save_settings" value="1" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Favicon Settings -->
        <div class="content-card mt-4">
            <h5 class="mb-4"><i class="fas fa-image me-2"></i>Pengaturan Favicon</h5>
            <p class="text-muted mb-4">Favicon adalah ikon kecil yang muncul di tab browser dan bookmark. Upload favicon untuk branding aplikasi Anda.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Favicon Saat Ini</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="favicon-preview" id="faviconPreview">
                                <?php if (!empty($instansi['favicon'])): ?>
                                    <img src="<?= htmlspecialchars($instansi['favicon']) ?>" alt="Favicon" id="faviconImage">
                                <?php else: ?>
                                    <i class="fas fa-globe" id="faviconPlaceholder"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="mb-1"><strong>Preview di Browser:</strong></p>
                                <div class="favicon-demo-browser">
                                    <?php if (!empty($instansi['favicon'])): ?>
                                        <img src="<?= htmlspecialchars($instansi['favicon']) ?>" alt="Favicon">
                                    <?php else: ?>
                                        <i class="fas fa-globe" style="font-size: 16px; color: var(--gray-400);"></i>
                                    <?php endif; ?>
                                    <span style="font-size: 12px;">Sultan Banten - Dashboard</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Favicon Baru</label>
                            <input type="file" name="favicon" class="form-control" accept=".ico,.png,.svg,.jpg,.jpeg" onchange="previewFavicon(this)">
                            <small class="text-muted">Format: ICO, PNG, SVG, JPG. Ukuran optimal: 32x32 atau 64x64 pixel. Maks: 500KB</small>
                        </div>
                        
                        <?php if (!empty($instansi['favicon'])): ?>
                        <button type="submit" name="delete_favicon" value="1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus favicon ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus Favicon
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-1"></i> Tips Favicon</h6>
                            <ul class="mb-0 small">
                                <li>Gunakan format <strong>ICO</strong> untuk kompatibilitas terbaik</li>
                                <li>Format <strong>PNG</strong> dan <strong>SVG</strong> juga didukung</li>
                                <li>Ukuran ideal adalah <strong>32x32</strong> atau <strong>64x64</strong> pixel</li>
                                <li>Desain harus simpel dan recognizable pada ukuran kecil</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <p class="text-muted small mb-2">Jika tidak ada favicon yang diupload, sistem akan menggunakan favicon default:</p>
                            <div class="favicon-demo">
                                <div class="favicon-preview" style="width: 32px; height: 32px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="32" height="32">
                                        <rect fill="#667eea" width="100" height="100" rx="20"/>
                                        <text x="50" y="68" text-anchor="middle" font-size="50" font-weight="bold" fill="white">SB</text>
                                    </svg>
                                </div>
                                <span class="small">Favicon Default "SB"</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="save_favicon" value="1" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Favicon
                    </button>
                </div>
            </form>
        </div>

        <!-- Welcome Banner Settings -->
        <div class="content-card mt-4">
            <h5 class="mb-4"><i class="fas fa-bullhorn me-2"></i>Banner Selamat Datang (Popup)</h5>
            <p class="text-muted mb-4">Upload banner yang akan muncul secara otomatis saat dashboard pertama kali dibuka oleh pengunjung.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <div class="row">
                    <div class="col-md-7">
                        <label class="form-label">Banner Saat Ini</label>
                        <div class="mb-3">
                            <div class="banner-preview" id="bannerPreview" style="width: 100%; height: 200px; border: 2px dashed var(--gray-300); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; background-color: var(--gray-50); overflow: hidden;">
                                <?php if (!empty($instansi['welcome_banner'])): ?>
                                    <img src="<?= htmlspecialchars($instansi['welcome_banner']) ?>" alt="Banner" id="bannerImage" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size: 48px; color: var(--gray-300);"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Banner Baru</label>
                            <input type="file" name="welcome_banner" class="form-control" accept="image/*" onchange="previewBanner(this)">
                            <small class="text-muted">Format: JPG, PNG, WEBP. Ukuran optimal: 800x600 pixel atau landscape. Maks: 2MB</small>
                        </div>
                        
                        <?php if (!empty($instansi['welcome_banner'])): ?>
                        <button type="submit" name="delete_banner" value="1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus banner selamat datang ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus Banner
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-1"></i> Info Banner Popup</h6>
                            <p class="small mb-0">Banner ini dirancang untuk memberikan kesan pertama yang profesional bagi pengunjung dashboard. Sangat cocok untuk:</p>
                            <ul class="mb-0 small mt-2">
                                <li>Ucapan selamat datang resmi</li>
                                <li>Informasi layanan utama</li>
                                <li>Pengumuman penting yang mendesak</li>
                                <li>Branding instansi</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="save_banner" value="1" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Banner
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Time Restriction Settings -->
        <div class="content-card mt-4">
            <h5 class="mb-4"><i class="fas fa-clock me-2"></i>Pembatasan Jam Input Data</h5>
            <p class="text-muted mb-4">Atur jam untuk membatasi input data. Operator hanya dapat menginput data pada jam yang ditentukan. Admin tidak dibatasi.</p>
            
            <form method="POST">
                <?php csrf_field(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="batas_jam_aktif" id="batas_jam_aktif" <?= !empty($instansi['batas_jam_aktif']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="batas_jam_aktif">
                                    Aktifkan Pembatasan Jam Input
                                </label>
                            </div>
                            <small class="text-muted">Jika diaktifkan, operator hanya dapat menginput data pada jam yang ditentukan</small>
                        </div>
                    </div>
                </div>
                
                <div class="row" id="timeSettings" style="<?= empty($instansi['batas_jam_aktif']) ? 'display: none;' : '' ?>">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" value="<?= substr($instansi['jam_mulai'] ?? '08:00:00', 0, 5) ?>">
                            <small class="text-muted">Contoh: 08:00</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" value="<?= substr($instansi['jam_selesai'] ?? '11:00:00', 0, 5) ?>">
                            <small class="text-muted">Contoh: 11:00</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info py-2 mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Info:</strong> Pembatasan jam tidak berlaku untuk Admin.
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="save_time_settings" value="1" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan Jam
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
        © Ditjenpas Banten 2026
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        function previewLogo(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('logoPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo Preview" style="max-width: 100%; max-height: 150px; object-fit: contain;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previewFavicon(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('faviconPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Favicon Preview" style="max-width: 100%; max-height: 64px; object-fit: contain;">';
                    
                    // Also update browser demo
                    var demo = document.querySelector('.favicon-demo-browser');
                    if (demo) {
                        var img = demo.querySelector('img');
                        if (img) {
                            img.src = e.target.result;
                        } else {
                            var icon = demo.querySelector('i');
                            if (icon) {
                                icon.outerHTML = '<img src="' + e.target.result + '" alt="Favicon">';
                            }
                        }
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle time settings visibility
        document.getElementById('batas_jam_aktif').addEventListener('change', function() {
            var timeSettings = document.getElementById('timeSettings');
            timeSettings.style.display = this.checked ? '' : 'none';
        });

        function previewBanner(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('bannerPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Banner Preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previewLogoKop(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('logoKopPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo Kop Preview" style="max-width: 100%; max-height: 100px; object-fit: contain;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
