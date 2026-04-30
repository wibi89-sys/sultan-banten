<?php
/**
 * Activity Log Page - View user activity logs
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('activity_log');

$user = getCurrentUser();
$instansi = getInstansi();

// Get filters from URL
$filterAksi = $_GET['aksi'] ?? '';
$filterModul = $_GET['modul'] ?? '';
$filterUserId = $_GET['user_id'] ?? '';
$filterTanggalMulai = $_GET['tanggal_mulai'] ?? '';
$filterTanggalAkhir = $_GET['tanggal_akhir'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build filters array
$filters = [];
if ($filterAksi) $filters['aksi'] = $filterAksi;
if ($filterModul) $filters['modul'] = $filterModul;
if ($filterUserId) $filters['user_id'] = $filterUserId;
if ($filterTanggalMulai) $filters['tanggal_mulai'] = $filterTanggalMulai;
if ($filterTanggalAkhir) $filters['tanggal_akhir'] = $filterTanggalAkhir;
if ($filterSearch) $filters['search'] = $filterSearch;

// Get activity logs
$logs = getActivityLogs($filters, $perPage, $offset);
$totalLogs = countActivityLogs($filters);
$totalPages = ceil($totalLogs / $perPage);

// Get statistics
$stats = getActivityStats();

// Get all users for filter dropdown
$allUsers = $pdo->query("SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap")->fetchAll();

// Action labels with icons
$aksiLabels = [
    'login' => ['label' => 'Login', 'icon' => 'fa-sign-in-alt', 'color' => 'success'],
    'logout' => ['label' => 'Logout', 'icon' => 'fa-sign-out-alt', 'color' => 'secondary'],
    'tambah' => ['label' => 'Tambah', 'icon' => 'fa-plus', 'color' => 'primary'],
    'edit' => ['label' => 'Edit', 'icon' => 'fa-edit', 'color' => 'warning'],
    'hapus' => ['label' => 'Hapus', 'icon' => 'fa-trash', 'color' => 'danger'],
    'view' => ['label' => 'Lihat', 'icon' => 'fa-eye', 'color' => 'info'],
    'export' => ['label' => 'Export', 'icon' => 'fa-download', 'color' => 'success'],
    'import' => ['label' => 'Import', 'icon' => 'fa-upload', 'color' => 'info'],
    'setting' => ['label' => 'Setting', 'icon' => 'fa-cog', 'color' => 'secondary'],
    'error' => ['label' => 'Error', 'icon' => 'fa-exclamation-triangle', 'color' => 'danger'],
];

// Module labels
$modulLabels = [
    'auth' => 'Autentikasi',
    'penghuni' => 'Data Penghuni',
    'overstaying' => 'Data Overstaying',
    'integrasi' => 'Data Integrasi',
    'user' => 'Manajemen User',
    'upt' => 'Data UPT',
    'instansi' => 'Pengaturan Instansi',
    'wa' => 'Pengaturan WA',
    'laporan' => 'Cetak Laporan',
    'pengumuman' => 'Pengumuman',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Activity Log - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Activity Log */
        :root {
            --bg-body: #f5f7fa;
            --bg-card: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
        }
        
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }
        
        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            transition: background-color 0.3s ease;
        }
        
        .content-card {
            background: var(--bg-card);
            transition: background-color 0.3s ease;
        }
        
        /* Stats Cards */
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 15px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-base);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            color: white;
        }
        
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .stat-card .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        /* Log detail popover */
        .detail-popover {
            max-width: 400px;
            font-size: 11px;
        }
        
        .detail-json {
            background: var(--gray-100);
            padding: 10px;
            border-radius: var(--border-radius-sm);
            font-family: monospace;
            font-size: 11px;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        /* Action badges */
        .badge-aksi {
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
            <h5>Sultan Banten</h5>
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
                    <a href="pengumuman.php"><i class="fas fa-bullhorn"></i><span>Pengumuman</span></a>
                    <a href="activity-log.php" class="active"><i class="fas fa-history"></i><span>Activity Log</span></a>
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
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Activity Log</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Aktivitas</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['today']) ?></div>
                    <div class="stat-label">Aktivitas Hari Ini</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['this_week']) ?></div>
                    <div class="stat-label">Aktivitas Minggu Ini</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= count($stats['top_users']) ?></div>
                    <div class="stat-label">User Aktif</div>
                </div>
            </div>
        </div>
        
        <!-- Top Users & Actions -->
        <div class="row mb-3">
            <div class="col-md-6 mb-3">
                <div class="content-card">
                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Aktivitas per Aksi</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($stats['by_action'] as $item): ?>
                            <?php $label = $aksiLabels[$item['aksi']] ?? ['label' => $item['aksi'], 'icon' => 'fa-circle', 'color' => 'secondary']; ?>
                            <span class="badge bg-<?= $label['color'] ?> badge-aksi">
                                <i class="fas <?= $label['icon'] ?> me-1"></i>
                                <?= $label['label'] ?>: <?= number_format($item['total']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="content-card">
                    <h6 class="mb-3"><i class="fas fa-users me-2"></i>Top User Aktif</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($stats['top_users'] as $i => $u): ?>
                                <tr>
                                    <td style="width: 30px;"><?= $i + 1 ?>.</td>
                                    <td><?= htmlspecialchars($u['user_name']) ?></td>
                                    <td class="text-end"><strong><?= number_format($u['total']) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="content-card">
            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Aktivitas</h6>
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small">Aksi</label>
                    <select name="aksi" class="form-select form-select-sm">
                        <option value="">Semua Aksi</option>
                        <?php foreach ($aksiLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filterAksi == $key ? 'selected' : '' ?>><?= $label['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Modul</label>
                    <select name="modul" class="form-select form-select-sm">
                        <option value="">Semua Modul</option>
                        <?php foreach ($modulLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filterModul == $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Semua User</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filterUserId == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($filterTanggalMulai) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="<?= htmlspecialchars($filterTanggalAkhir) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="activity-log.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Activity Log Table -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Log Aktivitas</h6>
                <span class="badge bg-secondary"><?= number_format($totalLogs) ?> data</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Aksi</th>
                            <th>Modul</th>
                            <th>Deskripsi</th>
                            <th>IP Address</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Tidak ada data aktivitas
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $no = $offset + 1; foreach ($logs as $log): ?>
                        <?php $label = $aksiLabels[$log['aksi']] ?? ['label' => $log['aksi'], 'icon' => 'fa-circle', 'color' => 'secondary']; ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div style="font-size: 11px;">
                                    <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                                    <strong><?= date('H:i:s', strtotime($log['created_at'])) ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($log['user_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $label['color'] ?> badge-aksi">
                                    <i class="fas <?= $label['icon'] ?> me-1"></i>
                                    <?= $label['label'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?= $modulLabels[$log['modul']] ?? $log['modul'] ?>
                                </span>
                            </td>
                            <td style="max-width: 300px; white-space: normal;">
                                <small><?= htmlspecialchars($log['deskripsi']) ?></small>
                            </td>
                            <td>
                                <code style="font-size: 10px;"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code>
                            </td>
                            <td>
                                <?php if ($log['detail']): ?>
                                <button type="button" class="btn btn-outline-info btn-sm py-0 px-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-placement="left"
                                    data-bs-html="true"
                                    data-bs-content="<div class='detail-json'><?= htmlspecialchars(json_encode(json_decode($log['detail'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>"
                                    title="Detail Data">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + $perPage, $totalLogs) ?> dari <?= number_format($totalLogs) ?> data
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten 2026
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        });
        
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
            this.classList.remove('show');
        });
        
        // Initialize popovers
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl, {
            customClass: 'detail-popover'
        }));
    </script>
</body>
</html>
