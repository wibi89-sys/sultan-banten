<?php
/**
 * Kroscek Data Page
 * Membandingkan jumlah tahanan dari Data Penghuni dengan Tahanan A1-A5 dari Data Overstaying
 */

require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser();
$instansi = getInstansi();

// Filter Tanggal
$filterTanggal = isset($_GET['tanggal']) ? sanitize($_GET['tanggal']) : date('Y-m-d');

// Get Data Kroscek
$sql = "SELECT 
            u.id as upt_id,
            u.nama_upt,
            (SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) 
             FROM data_penghuni dp 
             WHERE dp.upt_id = u.id AND dp.tanggal = :tanggal1 AND dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup')) as jml_penghuni,
            (SELECT SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) 
             FROM data_overstaying do 
             WHERE do.upt_id = u.id AND do.tanggal = :tanggal2) as jml_overstaying
        FROM upt u 
        ORDER BY u.nama_upt ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['tanggal1' => $filterTanggal, 'tanggal2' => $filterTanggal]);
$kroscekData = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kroscek Data - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        .status-badge {
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        .status-sesuai {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-tidak-sesuai {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-kosong {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
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
                    <?php if (hasAccess('integrasi')): ?>
                    <a href="integrasi.php"><i class="fas fa-exchange-alt"></i><span>Data Integrasi</span></a>
                    <?php endif; ?>
                    <a href="kroscek.php" class="active"><i class="fas fa-check-double"></i><span>Kroscek Data</span></a>
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
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-check-double me-2"></i>Kroscek Data</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="mb-2 mb-md-0"><i class="fas fa-check-double me-2"></i>Kroscek Data Tahanan</h5>
            </div>
            
            <!-- Filter Tanggal -->
            <div class="card mb-4 bg-light shadow-sm">
                <div class="card-body py-3">
                    <form method="GET" class="row align-items-end g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1">Pilih Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($filterTanggal) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="bg-primary bg-opacity-10">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th class="text-start">UPT</th>
                            <th class="text-center" width="20%">Tahanan<br><small class="fw-normal">(Isi Penghuni)</small></th>
                            <th class="text-center" width="20%">Tahanan<br><small class="fw-normal">(Overstaying A1-A5)</small></th>
                            <th class="text-center" width="20%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($kroscekData as $row): 
                            $jmlPenghuni = $row['jml_penghuni'] !== null ? (int)$row['jml_penghuni'] : null;
                            $jmlOverstaying = $row['jml_overstaying'] !== null ? (int)$row['jml_overstaying'] : null;
                            
                            $status = "";
                            $statusClass = "";
                            $statusIcon = "";
                            
                            if ($jmlPenghuni === null && $jmlOverstaying === null) {
                                $status = "Belum Input Keduanya";
                                $statusClass = "status-kosong";
                                $statusIcon = "fa-minus-circle";
                            } elseif ($jmlPenghuni === null) {
                                $status = "Penghuni Belum Input";
                                $statusClass = "status-kosong";
                                $statusIcon = "fa-exclamation-circle";
                            } elseif ($jmlOverstaying === null) {
                                $status = "Overstaying Belum Input";
                                $statusClass = "status-kosong";
                                $statusIcon = "fa-exclamation-circle";
                            } elseif ($jmlPenghuni === $jmlOverstaying) {
                                $status = "Sesuai";
                                $statusClass = "status-sesuai";
                                $statusIcon = "fa-check-circle";
                            } else {
                                $status = "Tidak Sesuai";
                                $statusClass = "status-tidak-sesuai";
                                $statusIcon = "fa-times-circle";
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['nama_upt']) ?></td>
                            <td class="text-center fw-bold"><?= $jmlPenghuni !== null ? $jmlPenghuni : '-' ?></td>
                            <td class="text-center fw-bold"><?= $jmlOverstaying !== null ? $jmlOverstaying : '-' ?></td>
                            <td class="text-center">
                                <span class="status-badge <?= $statusClass ?> d-inline-block w-100">
                                    <i class="fas <?= $statusIcon ?> me-1"></i> <?= $status ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($kroscekData)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data UPT terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
        
        toggleBtn?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>
