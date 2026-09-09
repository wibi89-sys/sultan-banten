<?php
/**
 * WhatsApp Scheduled Reports Page
 * Manage scheduled WhatsApp reports to groups with custom templates
 */

require_once 'includes/functions.php';

// Require login
$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

// Check access
if (!hasAccess('wa_schedule')) {
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
    
    if ($action === 'add_schedule') {
        $nama_laporan = trim($_POST['nama_laporan'] ?? '');
        $group_id = trim($_POST['group_id'] ?? '');
        $jam_kirim = $_POST['jam_kirim'] ?? '12:00';
        $hari = $_POST['hari'] ?? 'daily';
        $template_pesan = $_POST['template_pesan'] ?? '';
        $kirim_ke_pimpinan = isset($_POST['kirim_ke_pimpinan']) ? 1 : 0;
        
        if ($nama_laporan && $group_id && $jam_kirim) {
            // Use default template if not provided
            if (empty($template_pesan)) {
                $template_pesan = getDefaultTemplate();
            }
            
            $stmt = $pdo->prepare("INSERT INTO wa_scheduled_reports (nama_laporan, group_id, jam_kirim, hari, template_pesan, kirim_ke_pimpinan, aktif) VALUES (?, ?, ?, ?, ?, ?, 1)");
            if ($stmt->execute([$nama_laporan, $group_id, $jam_kirim, $hari, $template_pesan, $kirim_ke_pimpinan])) {
                $message = 'Jadwal laporan berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan jadwal.';
            }
        } else {
            $error = 'Semua field harus diisi.';
        }
    }
    
    if ($action === 'update_schedule') {
        $id = (int)($_POST['schedule_id'] ?? 0);
        $nama_laporan = trim($_POST['nama_laporan'] ?? '');
        $group_id = trim($_POST['group_id'] ?? '');
        $jam_kirim = $_POST['jam_kirim'] ?? '12:00';
        $hari = $_POST['hari'] ?? 'daily';
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $template_pesan = $_POST['template_pesan'] ?? '';
        $kirim_ke_pimpinan = isset($_POST['kirim_ke_pimpinan_edit']) ? 1 : 0;
        
        if ($id && $nama_laporan && $group_id && $jam_kirim) {
            // If template is empty, use the existing one or default
            if (empty($template_pesan)) {
                $stmt = $pdo->prepare("SELECT template_pesan FROM wa_scheduled_reports WHERE id = ?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                $template_pesan = $existing['template_pesan'] ?? getDefaultTemplate();
            }
            
            $stmt = $pdo->prepare("UPDATE wa_scheduled_reports SET nama_laporan = ?, group_id = ?, jam_kirim = ?, hari = ?, template_pesan = ?, kirim_ke_pimpinan = ?, aktif = ? WHERE id = ?");
            if ($stmt->execute([$nama_laporan, $group_id, $jam_kirim, $hari, $template_pesan, $kirim_ke_pimpinan, $aktif, $id])) {
                $message = 'Jadwal laporan berhasil diperbarui!';
            } else {
                $error = 'Gagal memperbarui jadwal.';
            }
        } else {
            $error = 'Semua field harus diisi.';
        }
    }
    
    if ($action === 'delete_schedule') {
        $id = (int)($_POST['schedule_id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM wa_scheduled_reports WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Jadwal laporan berhasil dihapus!';
            } else {
                $error = 'Gagal menghapus jadwal.';
            }
        }
    }
    
    if ($action === 'test_send') {
        $id = (int)($_POST['schedule_id'] ?? 0);
        if ($id) {
            $result = sendScheduledReport($id);
            if ($result['success']) {
                $message = '✅ Laporan test berhasil dikirim ke grup!';
            } else {
                $error = '❌ Gagal mengirim laporan: ' . ($result['message'] ?? 'Unknown error');
            }
        }
    }
    
    if ($action === 'preview_template') {
        $template_pesan = $_POST['template_pesan'] ?? '';
        if (empty($template_pesan)) {
            $template_pesan = getDefaultTemplate();
        }
        $reportData = generateComprehensiveReport();
        $previewMessage = formatTemplateMessage($template_pesan, $reportData);
    }
    
    if ($action === 'reset_template') {
        $resetTemplate = getDefaultTemplate();
    }
    }
}

// Get all schedules
$stmt = $pdo->query("SELECT * FROM wa_scheduled_reports ORDER BY jam_kirim ASC");
$schedules = $stmt->fetchAll();

// Get WA settings for default group
$waSettings = getWASettings();
$defaultGroup = $waSettings['group_id_laporan'] ?? ($waSettings['group_id'] ?? '');

// Get pimpinan list for checkbox
$stmt = $pdo->query("SELECT * FROM wa_pimpinan WHERE aktif = 1 ORDER BY urutan ASC, nama ASC");
$pimpinanList = $stmt->fetchAll();

// Get recent logs
$stmt = $pdo->query("SELECT l.*, s.nama_laporan FROM wa_scheduled_report_logs l JOIN wa_scheduled_reports s ON l.report_id = s.id ORDER BY l.tanggal_kirim DESC LIMIT 20");
$logs = $stmt->fetchAll();

// Get available template variables
$templateVariables = getTemplateVariables();

// Days options
$hariOptions = [
    'daily' => 'Setiap Hari',
    'weekdays' => 'Hari Kerja (Senin-Jumat)',
    '0' => 'Minggu',
    '1' => 'Senin',
    '2' => 'Selasa',
    '3' => 'Rabu',
    '4' => 'Kamis',
    '5' => 'Jumat',
    '6' => 'Sabtu'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Jadwal Laporan WA - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        .wa-icon { color: #25D366; }
        .schedule-card {
            border-left: 4px solid var(--primary-500);
            transition: all 0.2s ease;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .schedule-card.inactive {
            border-left-color: var(--gray-500);
            opacity: 0.7;
        }
        .preview-box {
            background: #075e54;
            color: white;
            border-radius: 8px;
            padding: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .template-editor {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            min-height: 300px;
            resize: vertical;
        }
        .variable-list {
            background: var(--gray-100);
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        [data-theme="dark"] .variable-list { background: var(--gray-800); }
        .variable-item {
            padding: 6px 10px;
            margin-bottom: 4px;
            background: var(--card-bg);
            border-radius: 4px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }
        .variable-item:hover {
            background: var(--primary-500);
            color: white;
            border-color: var(--primary-500);
        }
        .variable-category {
            font-weight: 600;
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
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
            
            <?php if (hasAccess('penghuni') || hasAccess('overstaying') || hasAccess('integrasi')): ?>
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
                    <a href="wa-settings.php"><i class="fas fa-cog"></i><span>Pengaturan WA</span></a>
                    <a href="wa-schedule.php" class="active"><i class="fas fa-clock"></i><span>Jadwal Laporan</span></a>
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
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Jadwal Laporan WhatsApp</h4>
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
        
        <!-- Template Variables Info -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Variabel Template yang Tersedia</h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#variablesCollapse">
                    <i class="fas fa-eye me-1"></i> Tampilkan/Sembunyikan
                </button>
            </div>
            <div class="collapse" id="variablesCollapse">
                <div class="variable-list">
                    <p class="text-muted small mb-3">
                        <i class="fas fa-lightbulb text-warning me-1"></i>
                        Klik variabel untuk menyalin ke clipboard, lalu paste di editor template. Gunakan format <code>{nama_variabel}</code>
                    </p>
                    
                    <?php 
                    $categories = [
                        'Umum' => ['tanggal', 'tanggal_indo', 'waktu_kirim'],
                        'Data Penghuni' => ['narapidana', 'tahanan', 'isi_penghuni', 'kapasitas', 'persen_overkapasitas'],
                        'Kategori Umur' => ['anak', 'dewasa'],
                        'Jenis Kelamin' => ['laki_laki', 'perempuan'],
                        'Jenis Pidana - Kelompok' => ['pidum_ite', 'pidsus'],
                        'Jenis Pidana - Detail' => ['tipikor', 'narkoba_bandar', 'narkoba_pengguna', 'teroris', 'ilegal_logging', 'trafficking', 'money_laundering', 'genosida'],
                        'Kategori Lainnya' => ['wna', 'lansia', 'sakit', 'uu_ite', 'pidana_mati', 'seumur_hidup'],
                        'Overstaying' => ['overstaying_ai', 'overstaying_aii', 'overstaying_aiii', 'overstaying_aiv', 'overstaying_av', 'total_overstaying'],
                        'Integrasi' => ['sk_pb', 'sk_cb', 'sk_cmb', 'sk_asimilasi', 'total_sk'],
                    ];
                    
                    foreach ($categories as $catName => $vars):
                    ?>
                    <div class="variable-category"><?= $catName ?></div>
                    <?php foreach ($vars as $var): ?>
                    <div class="variable-item" onclick="copyVariable('<?= $var ?>')" title="Klik untuk menyalin">
                        <code>{<?= $var ?>}</code> - <?= $templateVariables[$var] ?? '' ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Add/Edit Schedule Form -->
            <div class="col-lg-6 mb-3">
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Tambah Jadwal Baru</h5>
                    <form method="POST">
                    <?php csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_schedule">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Laporan</label>
                                <input type="text" name="nama_laporan" class="form-control" 
                                    placeholder="Contoh: Laporan Harian Pagi" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Group ID WhatsApp</label>
                                <input type="text" name="group_id" class="form-control" 
                                    value="<?= htmlspecialchars($defaultGroup) ?>"
                                    placeholder="Contoh: HxYz123AbCDEF atau 120363xxx@g.us" required>
                                <small class="text-muted">
                                    <?php if ($defaultGroup): ?>
                                    <i class="fas fa-info-circle"></i> Terisi dari pengaturan WA
                                    <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-warning"></i> Group ID belum dikonfigurasi
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jam Kirim</label>
                                <input type="time" name="jam_kirim" class="form-control" value="12:00" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hari</label>
                                <select name="hari" class="form-select">
                                    <option value="daily">Setiap Hari</option>
                                    <option value="weekdays">Hari Kerja (Senin-Jumat)</option>
                                    <option value="1">Senin</option>
                                    <option value="2">Selasa</option>
                                    <option value="3">Rabu</option>
                                    <option value="4">Kamis</option>
                                    <option value="5">Jumat</option>
                                    <option value="6">Sabtu</option>
                                    <option value="0">Minggu</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                Template Pesan 
                                <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="resetTemplate()">
                                    <i class="fas fa-undo"></i> Reset ke Default
                                </button>
                            </label>
                            <textarea name="template_pesan" id="templateEditor" class="form-control template-editor" 
                                placeholder="Kosongkan untuk menggunakan template default..."><?= htmlspecialchars($resetTemplate ?? '') ?></textarea>
                        </div>
                        
                        <?php if (!empty($pimpinanList)): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="form-check">
                                <input type="checkbox" name="kirim_ke_pimpinan" id="kirimKePimpinan" class="form-check-input" value="1">
                                <label class="form-check-label" for="kirimKePimpinan">
                                    <i class="fas fa-user-tie text-primary me-1"></i>
                                    <strong>Kirim juga ke Pimpinan</strong>
                                </label>
                            </div>
                            <div class="mt-2 small text-muted">
                                Laporan akan dikirim ke:
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($pimpinanList as $p): ?>
                                    <li><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['no_wa']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Jadwal
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="previewTemplate()">
                                <i class="fas fa-eye me-1"></i> Preview
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
                
                <!-- Preview Report -->
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-eye me-2"></i>Preview Template</h5>
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="action" value="preview_template">
                        <input type="hidden" name="template_pesan" id="previewTemplateInput" value="">
                        <button type="submit" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('previewTemplateInput').value = document.getElementById('templateEditor').value">
                            <i class="fas fa-sync me-1"></i> Generate Preview
                        </button>
                    </form>
                    
                    <?php if (isset($previewMessage)): ?>
                    <div class="preview-box"><?= htmlspecialchars($previewMessage) ?></div>
                    <?php else: ?>
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Klik "Generate Preview" untuk melihat format laporan yang akan dikirim.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Schedule List -->
            <div class="col-lg-6 mb-3">
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>Daftar Jadwal</h5>
                    
                    <?php if (empty($schedules)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                        <p>Belum ada jadwal laporan.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                    <div class="schedule-card content-card mb-3 <?= $schedule['aktif'] ? '' : 'inactive' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <?= htmlspecialchars($schedule['nama_laporan']) ?>
                                    <?php if (!$schedule['aktif']): ?>
                                    <span class="badge bg-secondary ms-2">Nonaktif</span>
                                    <?php endif; ?>
                                </h6>
                                <div class="small text-muted">
                                    <i class="fas fa-users me-1"></i> 
                                    <code><?= htmlspecialchars($schedule['group_id']) ?></code>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-clock me-1"></i> 
                                    <?= substr($schedule['jam_kirim'], 0, 5) ?> WIB
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= $hariOptions[$schedule['hari']] ?? $schedule['hari'] ?>
                                </div>
                                <?php if ($schedule['last_sent']): ?>
                                <div class="small text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Terakhir dikirim: <?= date('d/m/Y H:i', strtotime($schedule['last_sent'])) ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($schedule['template_pesan'])): ?>
                                <div class="small text-info">
                                    <i class="fas fa-file-alt me-1"></i>
                                    Template kustom
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($schedule['kirim_ke_pimpinan'])): ?>
                                <div class="small text-primary">
                                    <i class="fas fa-user-tie me-1"></i>
                                    Kirim ke Pimpinan
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-1 ms-2">
                                <!-- Edit Template -->
                                <button type="button" class="btn btn-outline-primary btn-sm" title="Edit Template"
                                    onclick="editSchedule(<?= htmlspecialchars(json_encode($schedule), ENT_QUOTES) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Test Send -->
                                <form method="POST" class="d-inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="test_send">
                                    <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Kirim Test">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                                
                                <!-- Toggle Active -->
                                <form method="POST" class="d-inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_schedule">
                                    <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                    <input type="hidden" name="nama_laporan" value="<?= htmlspecialchars($schedule['nama_laporan']) ?>">
                                    <input type="hidden" name="group_id" value="<?= htmlspecialchars($schedule['group_id']) ?>">
                                    <input type="hidden" name="jam_kirim" value="<?= substr($schedule['jam_kirim'], 0, 5) ?>">
                                    <input type="hidden" name="hari" value="<?= $schedule['hari'] ?>">
                                    <input type="hidden" name="aktif" value="<?= $schedule['aktif'] ? '' : '1' ?>">
                                    <button type="submit" class="btn btn-outline-<?= $schedule['aktif'] ? 'warning' : 'success' ?> btn-sm" 
                                        title="<?= $schedule['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <i class="fas fa-<?= $schedule['aktif'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                
                                <!-- Delete -->
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Logs -->
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-history me-2"></i>Riwayat Pengiriman</h5>
                    
                    <?php if (empty($logs)): ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Belum ada riwayat pengiriman.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Laporan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($log['tanggal_kirim'])) ?></td>
                                    <td><?= htmlspecialchars($log['nama_laporan']) ?></td>
                                    <td>
                                        <?php if ($log['status'] == 'success'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Terkirim</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Gagal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten 2026
        </footer>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Jadwal Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_schedule">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Laporan</label>
                                <input type="text" name="nama_laporan" id="edit_nama_laporan" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Group ID WhatsApp</label>
                                <input type="text" name="group_id" id="edit_group_id" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jam Kirim</label>
                                <input type="time" name="jam_kirim" id="edit_jam_kirim" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Hari</label>
                                <select name="hari" id="edit_hari" class="form-select">
                                    <option value="daily">Setiap Hari</option>
                                    <option value="weekdays">Hari Kerja (Senin-Jumat)</option>
                                    <option value="1">Senin</option>
                                    <option value="2">Selasa</option>
                                    <option value="3">Rabu</option>
                                    <option value="4">Kamis</option>
                                    <option value="5">Jumat</option>
                                    <option value="6">Sabtu</option>
                                    <option value="0">Minggu</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="aktif" id="edit_aktif" class="form-check-input" value="1" checked>
                                    <label class="form-check-label" for="edit_aktif">Aktif</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                Template Pesan
                                <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="resetEditTemplate()">
                                    <i class="fas fa-undo"></i> Reset ke Default
                                </button>
                            </label>
                            <textarea name="template_pesan" id="edit_template" class="form-control template-editor"></textarea>
                        </div>
                        
                        <?php if (!empty($pimpinanList)): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="form-check">
                                <input type="checkbox" name="kirim_ke_pimpinan_edit" id="edit_kirim_ke_pimpinan" class="form-check-input" value="1">
                                <label class="form-check-label" for="edit_kirim_ke_pimpinan">
                                    <i class="fas fa-user-tie text-primary me-1"></i>
                                    <strong>Kirim juga ke Pimpinan</strong>
                                </label>
                            </div>
                            <div class="mt-2 small text-muted">
                                Laporan akan dikirim ke:
                                <ul class="mb-0 mt-1 ps-3">
                                    <?php foreach ($pimpinanList as $p): ?>
                                    <li><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['no_wa']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast for copy notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>Variabel disalin ke clipboard!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
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
        
        // Copy variable to clipboard
        function copyVariable(varName) {
            const text = '{' + varName + '}';
            navigator.clipboard.writeText(text).then(() => {
                const toastEl = document.getElementById('copyToast');
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }).catch(err => {
                alert('Gagal menyalin: ' + err);
            });
        }
        
        // Reset template to default
        function resetTemplate() {
            document.getElementById('templateEditor').value = `📊 *LAPORAN HARIAN UPT*
📅 Tanggal: {tanggal}
───────────────────────

👥 *DATA PENGHUNI*
• Narapidana: {narapidana}
• Tahanan: {tahanan}
• Isi Penghuni: {isi_penghuni}
• Kapasitas: {kapasitas}
• Overkapasitas: {persen_overkapasitas}

⚖️ *JENIS PIDANA*
• Pidum + UU ITE: {pidum_ite}
• Total Pidsus: {pidsus}
  - Tipikor: {tipikor}
  - Narkoba Bandar: {narkoba_bandar}
  - Narkoba Pengguna: {narkoba_pengguna}
  - Teroris: {teroris}
  - Ilegal Logging: {ilegal_logging}
  - Trafficking: {trafficking}
  - Money Laundering: {money_laundering}
  - Genosida: {genosida}

📋 *LAINNYA*
• WNA: {wna}
• Anak: {anak}

👫 *JENIS KELAMIN*
• Laki-laki: {laki_laki}
• Perempuan: {perempuan}

⏰ *OVERSTAYING*
• AI: {overstaying_ai}
• AII: {overstaying_aii}
• AIII: {overstaying_aiii}
• AIV: {overstaying_aiv}
• AV: {overstaying_av}
• Total: {total_overstaying}

📜 *SK INTEGRASI*
• SK PB: {sk_pb}
• SK CB: {sk_cb}
• SK CMB: {sk_cmb}
• SK Asimilasi: {sk_asimilasi}
• Total SK: {total_sk}

───────────────────────
_Sistem Monitoring UPT Ditjenpas Banten_
_Dikirim pada {waktu_kirim}_`;
        }
        
        function resetEditTemplate() {
            resetTemplate();
            document.getElementById('edit_template').value = document.getElementById('templateEditor').value;
        }
        
        // Edit schedule
        function editSchedule(schedule) {
            document.getElementById('edit_schedule_id').value = schedule.id;
            document.getElementById('edit_nama_laporan').value = schedule.nama_laporan;
            document.getElementById('edit_group_id').value = schedule.group_id;
            document.getElementById('edit_jam_kirim').value = schedule.jam_kirim.substring(0, 5);
            document.getElementById('edit_hari').value = schedule.hari;
            document.getElementById('edit_aktif').checked = schedule.aktif == 1;
            document.getElementById('edit_template').value = schedule.template_pesan || '';
            
            // Set kirim_ke_pimpinan checkbox if exists
            const kirimPimpinanCheckbox = document.getElementById('edit_kirim_ke_pimpinan');
            if (kirimPimpinanCheckbox) {
                kirimPimpinanCheckbox.checked = schedule.kirim_ke_pimpinan == 1;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
        
        // Preview template
        function previewTemplate() {
            document.getElementById('previewTemplateInput').value = document.getElementById('templateEditor').value;
            document.querySelector('button[type="submit"][formmethod="POST"]')?.click();
        }
    </script>
</body>
</html>
