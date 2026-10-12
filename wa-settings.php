<?php
/**
 * WhatsApp Settings Page
 * Manage WhatsApp notification settings
 */

require_once 'includes/functions.php';

// Require login
$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

// Check access
if (!hasAccess('wa_settings')) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$instansi = getInstansi();

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $gateway_name = $_POST['gateway_name'] ?? 'fonnte';
        $api_key = trim($_POST['api_key'] ?? '');
        $api_url = trim($_POST['api_url'] ?? 'https://api.fonnte.com/send');
        $group_id = trim($_POST['group_id'] ?? '');
        $jam_reminder_pagi = $_POST['jam_reminder_pagi'] ?? '07:30';
        $jam_reminder_siang = $_POST['jam_reminder_siang'] ?? '12:00';
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $template_pagi = $_POST['template_pagi'] ?? '';
        $template_siang = $_POST['template_siang'] ?? '';
        
        // Update or insert settings
        $stmt = $pdo->prepare("UPDATE wa_settings SET 
            gateway_name = ?, 
            api_key = ?, 
            api_url = ?, 
            group_id = ?, 
            jam_reminder_pagi = ?, 
            jam_reminder_siang = ?, 
            aktif = ?,
            template_pagi = ?,
            template_siang = ?
            WHERE id = 1");
        
        if ($stmt->execute([$gateway_name, $api_key, $api_url, $group_id, $jam_reminder_pagi, $jam_reminder_siang, $aktif, $template_pagi, $template_siang])) {
            $message = 'Pengaturan WhatsApp berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan pengaturan.';
        }
    }
    
    if ($action === 'test_message') {
        $test_number = trim($_POST['test_number'] ?? '');
        $test_message = trim($_POST['test_message'] ?? 'Test notification dari Sultan Banten');
        
        if ($test_number) {
            $result = sendWAMessage($test_number, $test_message);
            if ($result['success']) {
                $message = '✅ Pesan test berhasil dikirim ke ' . $test_number;
            } else {
                $error = '❌ Gagal mengirim pesan: ' . ($result['message'] ?? 'Unknown error');
            }
        } else {
            $error = 'Nomor tujuan harus diisi.';
        }
    }
    
    if ($action === 'test_group') {
        // Get settings directly for this action
        $settings = getWASettings();
        $group_id = trim($settings['group_id'] ?? '');
        $test_message = '📢 *TEST PESAN GRUP*\n\nIni adalah pesan test dari Sultan Banten.\n\n_Terkirim: ' . date('d/m/Y H:i:s') . '_';
        
        if ($group_id) {
            $result = sendWAMessage($group_id, $test_message);
            if ($result['success']) {
                $message = '✅ Pesan test berhasil dikirim ke grup WhatsApp!';
            } else {
                $error = '❌ Gagal mengirim ke grup: ' . ($result['message'] ?? 'Unknown error');
            }
        } else {
            $error = 'Group ID belum dikonfigurasi di pengaturan.';
        }
    }
    
    if ($action === 'send_morning') {
        $result = sendMorningReminder();
        if ($result['success']) {
            $message = "✅ Reminder pagi berhasil dikirim ke {$result['sent']} operator.";
        } else {
            $error = '❌ Gagal mengirim reminder: ' . $result['message'];
        }
    }
    
    if ($action === 'send_afternoon') {
        $result = sendAfternoonReport();
        if ($result['success']) {
            $message = "✅ Laporan siang berhasil dikirim ke grup WhatsApp!<br>" . 
                "Belum input: Penghuni ({$result['missing_penghuni']}), Overstaying ({$result['missing_overstaying']}), Integrasi ({$result['missing_integrasi']})";
        } else {
            $error = '❌ Gagal mengirim laporan: ' . $result['message'];
        }
    }
}
}

// Get current settings
$waSettings = getWASettings();

// Get notification logs
$stmt = $pdo->query("SELECT * FROM wa_notification_logs ORDER BY tanggal_kirim DESC LIMIT 20");
$logs = $stmt->fetchAll();

// Get operators with WA number
$stmt = $pdo->query("SELECT u.id, u.nama_lengkap, u.no_wa, u.upt_id, up.nama_upt 
    FROM users u 
    LEFT JOIN upt up ON u.upt_id = up.id 
    WHERE u.role = 'operator'
    ORDER BY up.nama_upt, u.nama_lengkap");
$operators = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Pengaturan WhatsApp - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for WA Settings */
        .wa-icon {
            color: #25D366;
        }
        
        .form-switch .form-check-input {
            width: 3em;
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
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
                    <?php if ($user['role'] === 'admin'): ?>
                    <a href="activity-log.php"><i class="fas fa-history"></i><span>Activity Log</span></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (hasAccess('wa_settings') || hasAccess('wa_schedule')): ?>
            <div class="menu-parent" data-bs-toggle="collapse" data-bs-target="#menuWA">
                <i class="fab fa-whatsapp"></i>
                <span>Fitur WhatsApp</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse show" id="menuWA">
                <div class="sidebar-submenu">
                    <a href="wa-settings.php" class="active"><i class="fas fa-cog"></i><span>Pengaturan WA</span></a>
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
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fab fa-whatsapp me-2"></i>Pengaturan WhatsApp</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>
        
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
        
        <div class="row">
            <!-- Settings Form -->
            <div class="col-lg-6 mb-3">
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-cog me-2"></i>Konfigurasi Gateway</h5>
                    <form method="POST">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Gateway</label>
                            <div class="col-sm-8">
                                <select name="gateway_name" class="form-select">
                                    <option value="fonnte" <?= ($waSettings['gateway_name'] ?? '') == 'fonnte' ? 'selected' : '' ?>>Fonnte</option>
                                    <option value="wablas" <?= ($waSettings['gateway_name'] ?? '') == 'wablas' ? 'selected' : '' ?>>Wablas</option>
                                    <option value="starsender" <?= ($waSettings['gateway_name'] ?? '') == 'starsender' ? 'selected' : '' ?>>Starsender</option>
                                    <option value="other" <?= ($waSettings['gateway_name'] ?? '') == 'other' ? 'selected' : '' ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">API Key</label>
                            <div class="col-sm-8">
                                <input type="text" name="api_key" class="form-control" 
                                    value="<?= htmlspecialchars($waSettings['api_key'] ?? '') ?>" 
                                    placeholder="Masukkan API Key dari gateway">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">API URL</label>
                            <div class="col-sm-8">
                                <input type="text" name="api_url" class="form-control" 
                                    value="<?= htmlspecialchars($waSettings['api_url'] ?? 'https://api.fonnte.com/send') ?>" 
                                    placeholder="URL endpoint API">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Group ID</label>
                            <div class="col-sm-8">
                                <input type="text" name="group_id" class="form-control" 
                                    value="<?= htmlspecialchars($waSettings['group_id'] ?? '') ?>" 
                                    placeholder="ID Grup WhatsApp untuk laporan siang">
                                <small class="text-muted">
                                    <strong>Fonnte:</strong> Kode undangan grup (contoh: <code>HxYz123AbCDEF</code>)<br>
                                    Atau Group JID: <code>120363xxx@g.us</code>
                                </small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Reminder Pagi</label>
                            <div class="col-sm-8">
                                <input type="time" name="jam_reminder_pagi" class="form-control" 
                                    value="<?= substr($waSettings['jam_reminder_pagi'] ?? '07:30:00', 0, 5) ?>">
                                <small class="text-muted">Waktu mengirim reminder ke operator</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Laporan Siang</label>
                            <div class="col-sm-8">
                                <input type="time" name="jam_reminder_siang" class="form-control" 
                                    value="<?= substr($waSettings['jam_reminder_siang'] ?? '12:00:00', 0, 5) ?>">
                                <small class="text-muted">Waktu mengirim laporan ke grup</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label">Status</label>
                            <div class="col-sm-8">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="aktif" id="aktif" 
                                        <?= !empty($waSettings['aktif']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="aktif">
                                        <span class="badge bg-success" id="statusBadge">
                                            <?= !empty($waSettings['aktif']) ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h6 class="mb-3"><i class="fas fa-file-alt me-2"></i>Template Pesan</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Template Pesan Pagi (Reminder Input Data)</label>
                            <textarea name="template_pagi" class="form-control" rows="6" 
                                placeholder="Template pesan untuk reminder pagi..."><?= htmlspecialchars($waSettings['template_pagi'] ?? '') ?></textarea>
                            <small class="text-muted">
                                <strong>Variabel tersedia:</strong> {nama}, {upt}, {jam_mulai}, {jam_selesai}, {list_missing}
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Template Pesan Siang (Laporan Belum Input)</label>
                            <textarea name="template_siang" class="form-control" rows="6" 
                                placeholder="Template pesan untuk laporan siang..."><?= htmlspecialchars($waSettings['template_siang'] ?? '') ?></textarea>
                            <small class="text-muted">
                                <strong>Variabel tersedia:</strong> {tanggal}, {tanggal_indo}, {list_belum_input}
                            </small>
                        </div>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Contoh Template Pagi:</strong><br>
                            <code>🌅 *PENGINGAT INPUT DATA*\n\nYth. {nama}\nUPT: {upt}\n\nMohon segera input data hari ini.\n⏰ Batas waktu: {jam_mulai} - {jam_selesai} WIB</code>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Test & Manual Send -->
            <div class="col-lg-6 mb-3">
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Test & Kirim Manual</h5>
                    
                    <!-- Test Message -->
                    <form method="POST" class="mb-4">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="test_message">
                        <h6 class="mb-2">Test Kirim Pesan (Nomor Pribadi)</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <input type="text" name="test_number" class="form-control" 
                                    placeholder="Nomor WA (08xxx)" required>
                            </div>
                            <div class="col-7">
                                <input type="text" name="test_message" class="form-control" 
                                    placeholder="Pesan test" value="Test dari Sultan Banten">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> Test ke Nomor
                        </button>
                    </form>
                    
                    <!-- Test Group -->
                    <form method="POST" class="mb-4">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="test_group">
                        <h6 class="mb-2">Test Kirim ke Grup</h6>
                        <div class="mb-2">
                            <small class="text-muted">Group ID: <code><?= htmlspecialchars($waSettings['group_id'] ?? '-') ?></code></small>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-users me-1"></i> Test ke Grup
                        </button>
                    </form>
                    
                    <!-- Manual Triggers -->
                    <h6 class="mb-2">Kirim Notifikasi Manual</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" class="d-inline">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="send_morning">
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-sun me-1"></i> Reminder Pagi
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="send_afternoon">
                            <button type="submit" class="btn btn-info btn-sm">
                                <i class="fas fa-moon me-1"></i> Laporan Siang
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Operators List -->
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-users me-2"></i>Operator dengan WhatsApp</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>UPT</th>
                                    <th>No. WA</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($operators as $op): ?>
                                <tr>
                                    <td><?= htmlspecialchars($op['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($op['nama_upt'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($op['no_wa']): ?>
                                            <span class="text-success"><?= htmlspecialchars($op['no_wa']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Belum diisi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($op['no_wa']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Edit nomor WA di halaman Manajemen User</small>
                </div>
            </div>
        </div>
        
        <!-- Notification Logs -->
        <div class="content-card">
            <h5 class="mb-3"><i class="fas fa-history me-2"></i>Riwayat Notifikasi</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Tipe</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Penerima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada log notifikasi</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($log['tanggal_kirim'])) ?></td>
                            <td>
                                <?php 
                                $tipeLabels = [
                                    'reminder_pagi' => '<span class="badge bg-warning text-dark"><i class="fas fa-sun me-1"></i>Reminder Pagi</span>',
                                    'reminder_siang' => '<span class="badge bg-info"><i class="fas fa-moon me-1"></i>Laporan Siang</span>',
                                    'test' => '<span class="badge bg-secondary"><i class="fas fa-flask me-1"></i>Test</span>'
                                ];
                                echo $tipeLabels[$log['tipe']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($log['tipe']) . '</span>';
                                ?>
                            </td>
                            <td><small><?= htmlspecialchars(substr($log['target'], 0, 25)) ?><?= strlen($log['target']) > 25 ? '...' : '' ?></small></td>
                            <td>
                                <?php if ($log['status'] == 'success'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Terkirim</span>
                                <?php elseif ($log['status'] == 'partial'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Sebagian</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Gagal</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $log['jumlah_penerima'] ?? 1 ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten 2026
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
        }
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        // Update status badge
        const aktifCheckbox = document.getElementById('aktif');
        const statusBadge = document.getElementById('statusBadge');
        
        if (aktifCheckbox && statusBadge) {
            aktifCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    statusBadge.textContent = 'Aktif';
                    statusBadge.className = 'badge bg-success';
                } else {
                    statusBadge.textContent = 'Nonaktif';
                    statusBadge.className = 'badge bg-secondary';
                }
            });
        }
    </script>
</body>
</html>
