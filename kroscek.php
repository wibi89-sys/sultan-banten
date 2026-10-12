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
             WHERE do.upt_id = u.id AND do.tanggal = :tanggal2) as jml_overstaying,
            (SELECT COUNT(*) 
             FROM data_bebas_harian dbh 
             WHERE dbh.upt_id = u.id AND dbh.tanggal = :tanggal3) as input_bebas_count,
            (SELECT SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya) 
             FROM data_bebas_harian dbh 
             WHERE dbh.upt_id = u.id AND dbh.tanggal = :tanggal4) as total_bebas
        FROM upt u 
        ORDER BY u.nama_upt ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'tanggal1' => $filterTanggal, 
    'tanggal2' => $filterTanggal,
    'tanggal3' => $filterTanggal,
    'tanggal4' => $filterTanggal
]);
$kroscekData = $stmt->fetchAll();

// Hitung Statistik Ringkasan
$totalUpt = count($kroscekData);
$totalSesuai = 0;
$totalTidakSesuai = 0;
$totalBebasSudah = 0;
$totalBebasBelum = 0;
$sumTotalBebas = 0;

foreach ($kroscekData as $row) {
    $jmlP = $row['jml_penghuni'] !== null ? (int)$row['jml_penghuni'] : null;
    $jmlO = $row['jml_overstaying'] !== null ? (int)$row['jml_overstaying'] : null;
    
    if ($jmlP !== null && $jmlO !== null && $jmlP === $jmlO) {
        $totalSesuai++;
    } else {
        $totalTidakSesuai++;
    }

    if ((int)$row['input_bebas_count'] > 0) {
        $totalBebasSudah++;
        $sumTotalBebas += (int)($row['total_bebas'] ?? 0);
    } else {
        $totalBebasBelum++;
    }
}

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
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .status-sesuai {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .status-tidak-sesuai {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .status-kosong {
            background-color: #e2e3e5;
            color: #41464b;
            border: 1px solid #d3d6d8;
        }
        .summary-card {
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            transition: all 0.2s ease;
            background: #fff;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }
        .filter-btn {
            font-size: 13px;
            font-weight: 500;
            border-radius: 20px;
            padding: 4px 12px;
        }
        .filter-btn.active {
            background-color: #0d6efd;
            color: #fff !important;
            border-color: #0d6efd;
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
                    <?php if (hasAccess('bebas_harian')): ?>
                    <a href="bebas_harian.php"><i class="fas fa-door-open"></i><span>Bebas Harian</span></a>
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
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <h5 class="mb-1 fw-bold"><i class="fas fa-check-double text-primary me-2"></i>Kroscek Data Tahanan & Cek Bebas Harian</h5>
                    <p class="text-muted small mb-0">Verifikasi kecocokan data tahanan dan kepatuhan input data bebas harian seluruh UPT</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal: <strong><?= formatDate($filterTanggal) ?></strong>
                    </span>
                </div>
            </div>

            <!-- Ringkasan Statistik KPI -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 summary-card h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Total UPT</span>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-building small"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 fw-bold"><?= $totalUpt ?></h4>
                        <small class="text-muted">UPT Terdaftar</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 summary-card h-100 border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Kroscek Sesuai</span>
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-check small"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 fw-bold text-success"><?= $totalSesuai ?></h4>
                        <small class="text-muted">Tahanan sinkron</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 summary-card h-100 border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Bebas Harian Input</span>
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-check-circle small"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 fw-bold text-success"><?= $totalBebasSudah ?> <span class="fs-6 text-muted fw-normal">/ <?= $totalUpt ?></span></h4>
                        <small class="text-muted"><?= $sumTotalBebas ?> Orang Bebas</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 summary-card h-100 border-start border-4 border-danger">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Belum Bebas Harian</span>
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-times-circle small"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 fw-bold text-danger"><?= $totalBebasBelum ?></h4>
                        <small class="text-muted">Belum ada input</small>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tanggal & Filter Toolbar -->
            <div class="card mb-4 bg-light border-0 shadow-sm">
                <div class="card-body py-3">
                    <form method="GET" class="row align-items-end g-3">
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label fw-bold mb-1 small text-secondary">
                                <i class="fas fa-calendar-day me-1 text-primary"></i>Pilih Tanggal Kroscek
                            </label>
                            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($filterTanggal) ?>" required>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold mb-1 small text-secondary">
                                <i class="fas fa-filter me-1 text-primary"></i>Cari Nama UPT
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="searchUpt" class="form-control" placeholder="Ketik untuk mencari UPT...">
                            </div>
                        </div>
                    </form>

                    <!-- Filter Kategori Buttons -->
                    <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                        <span class="small fw-bold text-muted align-self-center me-1">Filter Tampilan:</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">
                            Semua (<?= $totalUpt ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="bebas-sudah">
                            <i class="fas fa-check-circle me-1"></i>Sudah Bebas Harian (<?= $totalBebasSudah ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger filter-btn" data-filter="bebas-belum">
                            <i class="fas fa-times-circle me-1"></i>Belum Bebas Harian (<?= $totalBebasBelum ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary filter-btn" data-filter="kroscek-sesuai">
                            <i class="fas fa-check-double me-1"></i>Tahanan Sesuai (<?= $totalSesuai ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="kroscek-tidak">
                            <i class="fas fa-exclamation-triangle me-1"></i>Tahanan Selisih (<?= $totalTidakSesuai ?>)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabel Kroscek Data -->
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm mb-0">
                    <thead class="bg-primary bg-opacity-10">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th class="text-start" width="25%">Nama UPT</th>
                            <th class="text-center" width="15%">Tahanan<br><small class="fw-normal text-muted">(Data Penghuni)</small></th>
                            <th class="text-center" width="15%">Tahanan<br><small class="fw-normal text-muted">(Overstaying A1-A5)</small></th>
                            <th class="text-center" width="20%">Kroscek Tahanan</th>
                            <th class="text-center" width="20%">Cek Bebas Harian</th>
                        </tr>
                    </thead>
                    <tbody id="kroscekTableBody">
                        <?php 
                        $no = 1;
                        foreach ($kroscekData as $row): 
                            $jmlPenghuni = $row['jml_penghuni'] !== null ? (int)$row['jml_penghuni'] : null;
                            $jmlOverstaying = $row['jml_overstaying'] !== null ? (int)$row['jml_overstaying'] : null;
                            
                            $statusKroscek = "";
                            $statusKroscekClass = "";
                            $statusKroscekIcon = "";
                            $isKroscekSesuai = false;
                            $selisih = 0;
                            
                            if ($jmlPenghuni === null && $jmlOverstaying === null) {
                                $statusKroscek = "Belum Input Keduanya";
                                $statusKroscekClass = "status-kosong";
                                $statusKroscekIcon = "fa-minus-circle";
                            } elseif ($jmlPenghuni === null) {
                                $statusKroscek = "Penghuni Belum Input";
                                $statusKroscekClass = "status-kosong";
                                $statusKroscekIcon = "fa-exclamation-circle";
                            } elseif ($jmlOverstaying === null) {
                                $statusKroscek = "Overstaying Belum Input";
                                $statusKroscekClass = "status-kosong";
                                $statusKroscekIcon = "fa-exclamation-circle";
                            } elseif ($jmlPenghuni === $jmlOverstaying) {
                                $statusKroscek = "Sesuai";
                                $statusKroscekClass = "status-sesuai";
                                $statusKroscekIcon = "fa-check-circle";
                                $isKroscekSesuai = true;
                            } else {
                                $selisih = abs($jmlPenghuni - $jmlOverstaying);
                                $statusKroscek = "Tidak Sesuai (Selisih: $selisih)";
                                $statusKroscekClass = "status-tidak-sesuai";
                                $statusKroscekIcon = "fa-times-circle";
                            }

                            // Bebas Harian Check
                            $isInputBebas = ((int)$row['input_bebas_count'] > 0);
                            $totalBebas = (int)($row['total_bebas'] ?? 0);
                        ?>
                        <tr data-upt="<?= htmlspecialchars(strtolower($row['nama_upt'])) ?>" 
                            data-kroscek="<?= $isKroscekSesuai ? 'sesuai' : 'tidak' ?>" 
                            data-bebas="<?= $isInputBebas ? 'sudah' : 'belum' ?>">
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-bold text-dark">
                                <i class="fas fa-building text-primary me-2 opacity-50"></i>
                                <?= htmlspecialchars($row['nama_upt']) ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?= $jmlPenghuni !== null ? number_format($jmlPenghuni, 0, ',', '.') : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?= $jmlOverstaying !== null ? number_format($jmlOverstaying, 0, ',', '.') : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?= $statusKroscekClass ?>">
                                    <i class="fas <?= $statusKroscekIcon ?>"></i> <?= $statusKroscek ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($isInputBebas): ?>
                                    <span class="status-badge status-sesuai shadow-none">
                                        <i class="fas fa-check-circle text-success fs-6"></i> Sudah Input
                                    </span>
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-door-open me-1 text-success"></i>
                                        <?= $totalBebas > 0 ? "<strong>{$totalBebas}</strong> Orang Bebas" : "Nihil (0 Bebas)" ?>
                                    </div>
                                <?php else: ?>
                                    <span class="status-badge status-tidak-sesuai shadow-none">
                                        <i class="fas fa-times-circle text-danger fs-6"></i> Belum Input
                                    </span>
                                    <div class="small text-danger mt-1">
                                        <i class="fas fa-clock me-1"></i>Belum ada data
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($kroscekData)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data UPT terdaftar.</td>
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
            sidebar?.classList.toggle('show');
            overlay?.classList.toggle('show');
        }
        
        toggleBtn?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);

        // Filter & Search Interaktif
        const searchInput = document.getElementById('searchUpt');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const tableRows = document.querySelectorAll('#kroscekTableBody tr');

        function applyTableFilter() {
            const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const activeBtn = document.querySelector('.filter-btn.active');
            const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';

            tableRows.forEach(row => {
                const uptName = row.getAttribute('data-upt') || '';
                const kroscek = row.getAttribute('data-kroscek') || '';
                const bebas = row.getAttribute('data-bebas') || '';

                const matchesSearch = uptName.includes(searchVal);
                let matchesCategory = true;

                if (activeFilter === 'bebas-sudah') {
                    matchesCategory = (bebas === 'sudah');
                } else if (activeFilter === 'bebas-belum') {
                    matchesCategory = (bebas === 'belum');
                } else if (activeFilter === 'kroscek-sesuai') {
                    matchesCategory = (kroscek === 'sesuai');
                } else if (activeFilter === 'kroscek-tidak') {
                    matchesCategory = (kroscek === 'tidak');
                }

                if (matchesSearch && matchesCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput?.addEventListener('input', applyTableFilter);

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                applyTableFilter();
            });
        });
    </script>
</body>
</html>
