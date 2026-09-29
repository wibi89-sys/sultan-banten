<?php
/**
 * Cetak Laporan Page
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('laporan');

$user = getCurrentUser();
$instansi = getInstansi();
$uptList = getAllUPT();
$userUptFilter = getUserUptFilter();

// Get report type from URL
$reportType = $_GET['type'] ?? 'penghuni';

// Validate report type
$validTypes = ['penghuni', 'overstaying', 'integrasi', 'rekapitulasi'];
if (!in_array($reportType, $validTypes)) {
    $reportType = 'penghuni';
}

// Report titles
$reportTitles = [
    'penghuni' => 'Laporan Data Penghuni',
    'overstaying' => 'Laporan Data Overstaying',
    'integrasi' => 'Laporan Data Integrasi',
    'rekapitulasi' => 'Laporan Rekapitulasi'
];

// Get date range
$defaultStartDate = date('Y-m-01');
$defaultEndDate = date('Y-m-t');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $reportTitles[$reportType] ?> - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles for Laporan */
        .report-type-card {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        
        .report-type-card:hover {
            border-color: var(--primary-500);
            background: var(--primary-50);
        }
        
        .report-type-card.active {
            border-color: var(--primary-500);
            background: var(--bg-gradient);
            color: white;
        }
        
        .report-type-card.active i, .report-type-card.active h6 {
            color: white;
        }
        
        .report-type-card i {
            font-size: 32px;
            color: var(--primary-500);
            margin-bottom: 10px;
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
            <div class="menu-parent" data-bs-toggle="collapse" data-bs-target="#menuLaporan">
                <i class="fas fa-print"></i>
                <span>Cetak Laporan</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse show" id="menuLaporan">
                <div class="sidebar-submenu">
                    <a href="laporan.php?type=penghuni" <?= $reportType == 'penghuni' ? 'class="active"' : '' ?>><i class="fas fa-users"></i><span>Lap. Penghuni</span></a>
                    <a href="laporan.php?type=overstaying" <?= $reportType == 'overstaying' ? 'class="active"' : '' ?>><i class="fas fa-clock"></i><span>Lap. Overstaying</span></a>
                    <a href="laporan.php?type=integrasi" <?= $reportType == 'integrasi' ? 'class="active"' : '' ?>><i class="fas fa-exchange-alt"></i><span>Lap. Integrasi</span></a>
                    <a href="laporan.php?type=rekapitulasi" <?= $reportType == 'rekapitulasi' ? 'class="active"' : '' ?>><i class="fas fa-chart-bar"></i><span>Rekapitulasi</span></a>
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

    <div class="main-content d-flex flex-column">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-print me-2"></i><?= $reportTitles[$reportType] ?></h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content-card">
            <!-- Report Type Selection -->
            <h6 class="mb-3">Pilih Jenis Laporan</h6>
            <div class="row mb-4">
                <div class="col-6 col-md-3 mb-2">
                    <div class="report-type-card <?= $reportType == 'penghuni' ? 'active' : '' ?>" onclick="location.href='laporan.php?type=penghuni'">
                        <i class="fas fa-users"></i>
                        <h6 class="mb-0">Data Penghuni</h6>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="report-type-card <?= $reportType == 'overstaying' ? 'active' : '' ?>" onclick="location.href='laporan.php?type=overstaying'">
                        <i class="fas fa-clock"></i>
                        <h6 class="mb-0">Overstaying</h6>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="report-type-card <?= $reportType == 'integrasi' ? 'active' : '' ?>" onclick="location.href='laporan.php?type=integrasi'">
                        <i class="fas fa-exchange-alt"></i>
                        <h6 class="mb-0">Integrasi</h6>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="report-type-card <?= $reportType == 'rekapitulasi' ? 'active' : '' ?>" onclick="location.href='laporan.php?type=rekapitulasi'">
                        <i class="fas fa-chart-bar"></i>
                        <h6 class="mb-0">Rekapitulasi</h6>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <!-- Filter Form -->
            <form id="formLaporan" method="GET" action="generate_excel.php" target="_blank">
                <input type="hidden" name="type" value="<?= $reportType ?>">
                
                <div class="row">
                    <?php if (!$userUptFilter): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Pilih UPT</label>
                        <select name="upt_id" id="upt_id" class="form-select">
                            <option value="">Semua UPT</option>
                            <?php foreach ($uptList as $upt): ?>
                            <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="upt_id" value="<?= $userUptFilter ?>">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">UPT</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama_upt'] ?? $uptList[0]['nama_upt'] ?? '-') ?>" readonly>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="<?= $defaultStartDate ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?= $defaultEndDate ?>" required>
                    </div>
                </div>
                
                <?php if ($reportType == 'overstaying' || $reportType == 'penghuni'): ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Tempat TTD</label>
                        <input type="text" name="tempat_ttd" id="tempat_ttd" class="form-control" placeholder="Contoh: Serang" value="Serang">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Jabatan Penanda Tangan</label>
                        <input type="text" name="jabatan_ttd" id="jabatan_ttd" class="form-control" placeholder="Contoh: Kepala Bidang Pelayanan dan Pembinaan">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Nama Penanda Tangan</label>
                        <input type="text" name="nama_ttd" id="nama_ttd" class="form-control" placeholder="Contoh: Surya Dharma">
                    </div>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                        <i class="fas fa-redo me-1"></i> Reset
                    </button>
                    <?php if ($reportType == 'overstaying' || $reportType == 'penghuni'): ?>
                    <button type="button" class="btn btn-danger" onclick="submitPdf()">
                        <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                    </button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success" onclick="document.getElementById('formLaporan').action='generate_excel.php';">
                        <i class="fas fa-file-excel me-1"></i> Download Excel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten 2026
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        function resetForm() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            document.getElementById('tanggal_mulai').value = firstDay.toISOString().split('T')[0];
            document.getElementById('tanggal_akhir').value = lastDay.toISOString().split('T')[0];
            document.getElementById('upt_id').value = '';
            
            if (document.getElementById('jabatan_ttd')) {
                document.getElementById('tempat_ttd').value = 'Serang';
                document.getElementById('jabatan_ttd').value = '';
                document.getElementById('nama_ttd').value = '';
            }
        }
        
        function submitPdf() {
            document.getElementById('formLaporan').action = 'generate_pdf.php';
            document.getElementById('formLaporan').submit();
        }
        
        // Auto-save and load signature settings
        window.addEventListener('DOMContentLoaded', function() {
            const tempatTtd = document.getElementById('tempat_ttd');
            const jabatanTtd = document.getElementById('jabatan_ttd');
            const namaTtd = document.getElementById('nama_ttd');
            
            if (tempatTtd && jabatanTtd && namaTtd) {
                // Load saved values
                if (localStorage.getItem('tempat_ttd')) tempatTtd.value = localStorage.getItem('tempat_ttd');
                if (localStorage.getItem('jabatan_ttd')) jabatanTtd.value = localStorage.getItem('jabatan_ttd');
                if (localStorage.getItem('nama_ttd')) namaTtd.value = localStorage.getItem('nama_ttd');
                
                // Add event listeners to save on change
                tempatTtd.addEventListener('input', () => localStorage.setItem('tempat_ttd', tempatTtd.value));
                jabatanTtd.addEventListener('input', () => localStorage.setItem('jabatan_ttd', jabatanTtd.value));
                namaTtd.addEventListener('input', () => localStorage.setItem('nama_ttd', namaTtd.value));
            }
        });
    </script>
    
    <?php include 'includes/profile_settings.php'; ?>
</body>
</html>
