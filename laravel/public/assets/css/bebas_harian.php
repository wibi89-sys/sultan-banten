<?php
/**
 * Data Bebas Harian Page
 * Input data WBP/Tahanan bebas harian berdasarkan klasifikasi pidana
 */

require_once 'includes/functions.php';
requireLogin();
requireAccess('bebas_harian');

$user = getCurrentUser();
$instansi = getInstansi();
$message = '';
$error = '';

// Check time restriction
$timeRestriction = getTimeRestriction();
$timeCheck = isInputTimeAllowed();
$canInput = $timeCheck['allowed'];
$timeRestrictionMessage = $timeCheck['message'];

// Klasifikasi Tindak Pidana sesuai Database
$klasifikasiList = [
    'Pidana Umum'       => 'pidana_umum',
    'Tipikor'           => 'tipikor',
    'Narkoba Bandar'    => 'narkoba_bandar',
    'Narkoba Pengguna'  => 'narkoba_pengguna',
    'Teroris'           => 'teroris',
    'Trafficking'       => 'trafficking',
    'Ilegal Logging'    => 'ilegal_logging',
    'Money Laundering'  => 'money_laundering',
    'Genosida'          => 'genosida',
    'UU ITE'            => 'uu_ite'
];

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';

        // Check time restriction for add/edit action
        if ($action === 'add_all' || $action === 'edit_all' || $action === 'edit_single') {
            $timeCheck = isInputTimeAllowed();
            if (!$timeCheck['allowed']) {
                $error = $timeCheck['message'];
            }
        }

        // 1. ADD ALL (Batch input per UPT & Tanggal)
        if (!$error && $action === 'add_all') {
            $upt_id = (int)$_POST['upt_id'];
            $tanggal = sanitize($_POST['tanggal']);
            $keterangan_global = sanitize($_POST['keterangan_global'] ?? '');

            try {
                // Cegah duplikasi UPT & Tanggal
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM data_bebas_harian WHERE upt_id = ? AND tanggal = ?");
                $stmtCheck->execute([$upt_id, $tanggal]);
                if ($stmtCheck->fetchColumn() > 0) {
                    throw new Exception("Data Bebas Harian untuk UPT ini pada tanggal " . formatDate($tanggal) . " sudah ada. Silakan gunakan tombol edit pada data yang sudah ada.");
                }

                $pdo->beginTransaction();
                $insertedRows = 0;

                $stmtInsert = $pdo->prepare("INSERT INTO data_bebas_harian 
                    (upt_id, tanggal, klasifikasi_pidana, bebas_murni, pb, cb, cmb, asimilasi, bdh, lainnya, ket_lainnya, keterangan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($klasifikasiList as $klasifikasi => $key) {
                    $bebas_murni = (int)($_POST['bebas_murni_' . $key] ?? 0);
                    $pb = (int)($_POST['pb_' . $key] ?? 0);
                    $cb = (int)($_POST['cb_' . $key] ?? 0);
                    $cmb = (int)($_POST['cmb_' . $key] ?? 0);
                    $asimilasi = (int)($_POST['asimilasi_' . $key] ?? 0);
                    $bdh = (int)($_POST['bdh_' . $key] ?? 0);
                    $lainnya = (int)($_POST['lainnya_' . $key] ?? 0);
                    $ket_lainnya = sanitize($_POST['ket_lainnya_' . $key] ?? '');

                    $stmtInsert->execute([
                        $upt_id, $tanggal, $klasifikasi,
                        $bebas_murni, $pb, $cb, $cmb, $asimilasi, $bdh, $lainnya,
                        $ket_lainnya, $keterangan_global
                    ]);
                    $insertedRows++;
                }

                $pdo->commit();
                $message = "Berhasil menyimpan data bebas harian untuk tanggal " . formatDate($tanggal) . ".";

                $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
                $stmtUpt->execute([$upt_id]);
                $namaUpt = $stmtUpt->fetchColumn();
                logActivity('tambah', 'bebas_harian', "Menambahkan data bebas harian UPT $namaUpt tanggal $tanggal", ['upt_id' => $upt_id, 'tanggal' => $tanggal]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }

        // 2. EDIT ALL (Simpan update untuk semua klasifikasi di tanggal & UPT tersebut)
        if (!$error && $action === 'edit_all') {
            $upt_id = (int)$_POST['edit_upt_id'];
            $tanggal = sanitize($_POST['edit_tanggal']);
            $keterangan_global = sanitize($_POST['edit_keterangan_global'] ?? '');

            try {
                $pdo->beginTransaction();

                // Hapus data lama UPT & Tanggal tersebut, lalu re-insert agar selalu konsisten
                $stmtDel = $pdo->prepare("DELETE FROM data_bebas_harian WHERE upt_id = ? AND tanggal = ?");
                $stmtDel->execute([$upt_id, $tanggal]);

                $stmtInsert = $pdo->prepare("INSERT INTO data_bebas_harian 
                    (upt_id, tanggal, klasifikasi_pidana, bebas_murni, pb, cb, cmb, asimilasi, bdh, lainnya, ket_lainnya, keterangan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($klasifikasiList as $klasifikasi => $key) {
                    $bebas_murni = (int)($_POST['edit_bebas_murni_' . $key] ?? 0);
                    $pb = (int)($_POST['edit_pb_' . $key] ?? 0);
                    $cb = (int)($_POST['edit_cb_' . $key] ?? 0);
                    $cmb = (int)($_POST['edit_cmb_' . $key] ?? 0);
                    $asimilasi = (int)($_POST['edit_asimilasi_' . $key] ?? 0);
                    $bdh = (int)($_POST['edit_bdh_' . $key] ?? 0);
                    $lainnya = (int)($_POST['edit_lainnya_' . $key] ?? 0);
                    $ket_lainnya = sanitize($_POST['edit_ket_lainnya_' . $key] ?? '');

                    $stmtInsert->execute([
                        $upt_id, $tanggal, $klasifikasi,
                        $bebas_murni, $pb, $cb, $cmb, $asimilasi, $bdh, $lainnya,
                        $ket_lainnya, $keterangan_global
                    ]);
                }

                $pdo->commit();
                $message = "Berhasil memperbarui data bebas harian tanggal " . formatDate($tanggal) . ".";

                $stmtUpt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
                $stmtUpt->execute([$upt_id]);
                $namaUpt = $stmtUpt->fetchColumn();
                logActivity('edit', 'bebas_harian', "Memperbarui data bebas harian UPT $namaUpt tanggal $tanggal", ['upt_id' => $upt_id, 'tanggal' => $tanggal]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }

        // 3. DELETE (Hapus per UPT & Tanggal)
        if ($action === 'delete_batch') {
            $upt_id = (int)$_POST['del_upt_id'];
            $tanggal = sanitize($_POST['del_tanggal']);

            try {
                $stmt = $pdo->prepare("DELETE FROM data_bebas_harian WHERE upt_id = ? AND tanggal = ?");
                $stmt->execute([$upt_id, $tanggal]);
                $message = 'Data bebas harian berhasil dihapus.';
                logActivity('hapus', 'bebas_harian', "Menghapus data bebas harian UPT ID $upt_id tanggal $tanggal", ['upt_id' => $upt_id, 'tanggal' => $tanggal]);
            } catch (PDOException $e) {
                $error = 'Gagal menghapus data: ' . $e->getMessage();
            }
        }
    }
}

$uptList = getAllUPT();
$userUptFilter = getUserUptFilter();

// Filter
$tanggalMulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggalSampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : '';
$filterUpt = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : 0;

if ($userUptFilter) {
    $filterUpt = $userUptFilter;
}

$whereClauses = [];
$queryParams = [];

if ($tanggalMulai && $tanggalSampai) {
    $whereClauses[] = "dbh.tanggal BETWEEN ? AND ?";
    $queryParams[] = $tanggalMulai;
    $queryParams[] = $tanggalSampai;
} elseif ($tanggalMulai) {
    $whereClauses[] = "dbh.tanggal >= ?";
    $queryParams[] = $tanggalMulai;
} elseif ($tanggalSampai) {
    $whereClauses[] = "dbh.tanggal <= ?";
    $queryParams[] = $tanggalSampai;
}

if ($filterUpt > 0) {
    $whereClauses[] = "dbh.upt_id = ?";
    $queryParams[] = $filterUpt;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Rekap list per UPT dan Tanggal
$sqlList = "SELECT 
        dbh.upt_id,
        dbh.tanggal,
        u.nama_upt,
        SUM(dbh.bebas_murni) as total_bm,
        SUM(dbh.pb) as total_pb,
        SUM(dbh.cb) as total_cb,
        SUM(dbh.cmb) as total_cmb,
        SUM(dbh.asimilasi) as total_asimilasi,
        SUM(dbh.bdh) as total_bdh,
        SUM(dbh.lainnya) as total_lainnya,
        SUM(dbh.bebas_murni + dbh.pb + dbh.cb + dbh.cmb + dbh.asimilasi + dbh.bdh + dbh.lainnya) as grand_total,
        MAX(dbh.keterangan) as keterangan,
        MAX(dbh.created_at) as created_at
    FROM data_bebas_harian dbh
    JOIN upt u ON dbh.upt_id = u.id
    $whereSql
    GROUP BY dbh.upt_id, dbh.tanggal, u.nama_upt
    ORDER BY dbh.tanggal DESC, u.nama_upt ASC";

$stmtList = $pdo->prepare($sqlList);
$stmtList->execute($queryParams);
$dataList = $stmtList->fetchAll();

// Jika request ajax untuk fetch detail saat Edit
if (isset($_GET['ajax_detail']) && isset($_GET['upt_id']) && isset($_GET['tanggal'])) {
    header('Content-Type: application/json');
    $fetchUpt = (int)$_GET['upt_id'];
    $fetchTgl = sanitize($_GET['tanggal']);

    $stmtFetch = $pdo->prepare("SELECT * FROM data_bebas_harian WHERE upt_id = ? AND tanggal = ?");
    $stmtFetch->execute([$fetchUpt, $fetchTgl]);
    $details = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($details);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= htmlspecialchars(getFavicon()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Bebas Harian - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--body-bg, #f1f5f9);
            min-height: 100vh;
        }
        .main-content {
            margin-left: 280px !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
            width: calc(100% - 280px);
        }
        .content-wrapper {
            padding: 20px 24px;
            width: 100%;
            box-sizing: border-box;
        }
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        .input-matrix-table {
            min-width: 860px;
            width: 100%;
        }
        .input-matrix-table th {
            text-align: center;
            vertical-align: middle;
            font-size: 12px;
            background: #f1f5f9;
            padding: 8px 4px;
            white-space: nowrap;
        }
        .input-matrix-table td {
            vertical-align: middle;
            padding: 4px 3px;
        }
        .input-matrix-table input[type="number"] {
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 3px !important;
            height: 36px;
            width: 100%;
            min-width: 48px;
            border-radius: 6px;
            -moz-appearance: textfield; /* Firefox */
        }
        /* Chrome, Safari, Edge, Opera: hide spin buttons agar angka leluasa & tidak terpotong */
        .input-matrix-table input[type="number"]::-webkit-outer-spin-button,
        .input-matrix-table input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .input-matrix-table input[type="text"] {
            font-size: 12px;
            height: 36px;
            padding: 6px 8px;
            border-radius: 6px;
        }
        .total-badge {
            font-size: 14px;
            font-weight: 700;
        }
        .summary-card {
            border-radius: 12px;
            padding: 16px;
            color: #fff;
            margin-bottom: 20px;
        }
        .bg-grad-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .bg-grad-success { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-grad-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .bg-grad-danger  { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .bg-grad-info    { background: linear-gradient(135deg, #06b6d4, #0891b2); }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php if (!empty($instansi['logo'])): ?>
                <img src="<?= htmlspecialchars($instansi['logo']) ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-shield-alt fa-2x text-primary"></i>
            <?php endif; ?>
            <h5><?= htmlspecialchars($instansi['nama_instansi'] ?? 'Sultan Banten') ?></h5>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            
            <?php if (hasAccess('penghuni') || hasAccess('overstaying') || hasAccess('integrasi') || hasAccess('bebas_harian')): ?>
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
                    <a href="bebas_harian.php" class="active"><i class="fas fa-door-open"></i><span>Bebas Harian</span></a>
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
                    <a href="instansi.php"><i class="fas fa-cogs"></i><span>Pengaturan Instansi</span></a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (hasAccess('wa_settings') || hasAccess('wa_schedule')): ?>
            <div class="menu-parent collapsed" data-bs-toggle="collapse" data-bs-target="#menuWA">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp Bot</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse" id="menuWA">
                <div class="sidebar-submenu">
                    <?php if (hasAccess('wa_settings')): ?>
                    <a href="wa-settings.php"><i class="fas fa-sliders-h"></i><span>Pengaturan Bot</span></a>
                    <?php endif; ?>
                    <?php if (hasAccess('wa_schedule')): ?>
                    <a href="wa-schedule.php"><i class="fas fa-clock"></i><span>Jadwal Laporan</span></a>
                    <a href="wa-pimpinan.php"><i class="fas fa-user-tie"></i><span>Target Pimpinan</span></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <a href="profile.php"><i class="fas fa-user"></i><span>Profil</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0"><i class="fas fa-door-open me-2 text-primary"></i>Data Bebas Harian</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="me-2"><?= getUserAvatarHtml($user, 28) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            <div class="content-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold text-dark">Pencatatan Bebas Harian</h5>
                    <p class="text-muted mb-0 small">Pencatatan harian WBP / Tahanan bebas (Bebas Murni, PB, CB, CMB, Asimilasi, BDH, Lainnya)</p>
                </div>
                <?php if ($canInput): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fas fa-plus me-1"></i> Input Bebas Hari Ini
                </button>
                <?php endif; ?>
            </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$canInput): ?>
            <div class="alert alert-warning">
                <i class="fas fa-clock me-1"></i> <?= htmlspecialchars($timeRestrictionMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <?php if (!$userUptFilter): ?>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">UPT</label>
                        <select name="upt_id" class="form-select form-select-sm">
                            <option value="">-- Semua UPT --</option>
                            <?php foreach ($uptList as $upt): ?>
                                <option value="<?= $upt['id'] ?>" <?= $filterUpt == $upt['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($upt['nama_upt']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tanggalMulai) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Tanggal Sampai</label>
                        <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="<?= htmlspecialchars($tanggalSampai) ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="bebas_harian.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                        <a href="generate_excel.php?type=bebas_harian&<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-sm btn-success" title="Download Excel">
                            <i class="fas fa-file-excel"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Data List -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Tanggal</th>
                                <th>UPT</th>
                                <th class="text-center">Bebas Murni</th>
                                <th class="text-center">PB</th>
                                <th class="text-center">CB</th>
                                <th class="text-center">CMB</th>
                                <th class="text-center">Asimilasi</th>
                                <th class="text-center">BDH</th>
                                <th class="text-center">Lainnya</th>
                                <th class="text-center bg-light fw-bold">Total</th>
                                <th>Keterangan</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dataList)): ?>
                                <tr>
                                    <td colspan="13" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-2 d-block"></i>
                                        Belum ada data bebas harian yang tercatat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($dataList as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= formatDate($row['tanggal']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_upt']) ?></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?= $row['total_bm'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-primary"><?= $row['total_pb'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-info"><?= $row['total_cb'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-warning text-dark"><?= $row['total_cmb'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-success"><?= $row['total_asimilasi'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-danger"><?= $row['total_bdh'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-dark"><?= $row['total_lainnya'] ?></span></td>
                                        <td class="text-center fw-bold bg-light">
                                            <span class="badge bg-success fs-6"><?= $row['grand_total'] ?></span>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></small></td>
                                        <td class="text-center">
                                            <?php if ($canInput): ?>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= $row['upt_id'] ?>, '<?= $row['tanggal'] ?>')" title="Edit Data">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus seluruh data bebas harian UPT ini pada tanggal <?= formatDate($row['tanggal']) ?>?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_batch">
                                                <input type="hidden" name="del_upt_id" value="<?= $row['upt_id'] ?>">
                                                <input type="hidden" name="del_tanggal" value="<?= $row['tanggal'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Data">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten 2026
        </footer>
    </div><!-- .content-wrapper -->
</div><!-- .main-content -->

    <!-- MODAL TAMBAH DATA (GRID MATRIX) -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-door-open me-2"></i> Form Input Bebas Harian
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formTambah">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="add_all">
                    
                    <div class="modal-body">
                        <!-- UPT & Tanggal -->
                        <div class="row g-3 mb-4 bg-light p-3 rounded">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">UPT Pemasyarakatan <span class="text-danger">*</span></label>
                                <?php if ($userUptFilter): ?>
                                    <input type="hidden" name="upt_id" value="<?= $userUptFilter ?>">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama_upt'] ?? '-') ?>" readonly>
                                <?php else: ?>
                                    <select name="upt_id" class="form-select" required>
                                        <option value="">-- Pilih UPT --</option>
                                        <?php foreach ($uptList as $upt): ?>
                                            <option value="<?= $upt['id'] ?>"><?= htmlspecialchars($upt['nama_upt']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Bebas <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Matrix Table Input -->
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm input-matrix-table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="min-width: 160px;">Klasifikasi Tindak Pidana</th>
                                        <th style="width: 75px;">Bebas Murni</th>
                                        <th style="width: 75px;">PB</th>
                                        <th style="width: 75px;">CB</th>
                                        <th style="width: 75px;">CMB</th>
                                        <th style="width: 75px;">Asimilasi</th>
                                        <th style="width: 75px;">BDH</th>
                                        <th style="width: 75px;">Lainnya</th>
                                        <th style="min-width: 160px;">Ket. Jika Lainnya</th>
                                        <th style="width: 70px;" class="text-center bg-secondary">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($klasifikasiList as $klasifikasi => $key): ?>
                                        <tr data-key="<?= $key ?>">
                                            <td class="fw-bold text-start ps-3">
                                                <?= $klasifikasi ?>
                                            </td>
                                            <td>
                                                <input type="number" name="bebas_murni_<?= $key ?>" class="form-control form-control-sm matrix-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="pb_<?= $key ?>" class="form-control form-control-sm matrix-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="cb_<?= $key ?>" class="form-control form-control-sm matrix-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="cmb_<?= $key ?>" class="form-control form-control-sm matrix-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="asimilasi_<?= $key ?>" class="form-control form-control-sm matrix-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="bdh_<?= $key ?>" class="form-control form-control-sm matrix-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="lainnya_<?= $key ?>" class="form-control form-control-sm matrix-input input-lainnya" min="0" value="0" data-key="<?= $key ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="ket_lainnya_<?= $key ?>" class="form-control form-control-sm input-ket-lainnya" placeholder="Tulis rincian jika lainnya..." disabled id="ket_<?= $key ?>">
                                            </td>
                                            <td class="text-center fw-bold row-total bg-light">0</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr class="table-primary text-dark">
                                        <td class="text-end pe-3">TOTAL KESELURUHAN:</td>
                                        <td class="text-center" id="total_bm">0</td>
                                        <td class="text-center" id="total_pb">0</td>
                                        <td class="text-center" id="total_cb">0</td>
                                        <td class="text-center" id="total_cmb">0</td>
                                        <td class="text-center" id="total_asimilasi">0</td>
                                        <td class="text-center" id="total_bdh">0</td>
                                        <td class="text-center" id="total_lainnya">0</td>
                                        <td></td>
                                        <td class="text-center fs-6 text-primary" id="grand_total_all">0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Keterangan Global -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Catatan / Keterangan Tambahan</label>
                            <textarea name="keterangan_global" class="form-control" rows="2" placeholder="Catatan tambahan mengenai pembebasan hari ini (opsional)..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Simpan Data Bebas Harian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT DATA (GRID MATRIX) -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-edit me-2"></i> Edit Data Bebas Harian
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEdit">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="edit_all">
                    <input type="hidden" name="edit_upt_id" id="edit_upt_id">
                    <input type="hidden" name="edit_tanggal" id="edit_tanggal">
                    
                    <div class="modal-body">
                        <!-- Info UPT & Tanggal -->
                        <div class="alert alert-info py-2 d-flex justify-content-between align-items-center mb-3">
                            <div><strong>UPT:</strong> <span id="display_edit_upt">-</span></div>
                            <div><strong>Tanggal:</strong> <span id="display_edit_tanggal">-</span></div>
                        </div>

                        <!-- Matrix Table Edit -->
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm input-matrix-table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="min-width: 160px;">Klasifikasi Tindak Pidana</th>
                                        <th style="width: 75px;">Bebas Murni</th>
                                        <th style="width: 75px;">PB</th>
                                        <th style="width: 75px;">CB</th>
                                        <th style="width: 75px;">CMB</th>
                                        <th style="width: 75px;">Asimilasi</th>
                                        <th style="width: 75px;">BDH</th>
                                        <th style="width: 75px;">Lainnya</th>
                                        <th style="min-width: 160px;">Ket. Jika Lainnya</th>
                                        <th style="width: 70px;" class="text-center bg-secondary">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($klasifikasiList as $klasifikasi => $key): ?>
                                        <tr data-edit-key="<?= $key ?>">
                                            <td class="fw-bold text-start ps-3">
                                                <?= $klasifikasi ?>
                                            </td>
                                            <td>
                                                <input type="number" name="edit_bebas_murni_<?= $key ?>" id="edit_bm_<?= $key ?>" class="form-control form-control-sm matrix-edit-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="edit_pb_<?= $key ?>" id="edit_pb_<?= $key ?>" class="form-control form-control-sm matrix-edit-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="edit_cb_<?= $key ?>" id="edit_cb_<?= $key ?>" class="form-control form-control-sm matrix-edit-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="edit_cmb_<?= $key ?>" id="edit_cmb_<?= $key ?>" class="form-control form-control-sm matrix-edit-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="edit_asimilasi_<?= $key ?>" id="edit_asimilasi_<?= $key ?>" class="form-control form-control-sm matrix-edit-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="edit_bdh_<?= $key ?>" id="edit_bdh_<?= $key ?>" class="form-control form-control-sm matrix-edit-input" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="edit_lainnya_<?= $key ?>" id="edit_lainnya_<?= $key ?>" class="form-control form-control-sm matrix-edit-input edit-input-lainnya" min="0" value="0" data-edit-key="<?= $key ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="edit_ket_lainnya_<?= $key ?>" id="edit_ket_<?= $key ?>" class="form-control form-control-sm edit-input-ket-lainnya" placeholder="Tulis rincian jika lainnya..." disabled>
                                            </td>
                                            <td class="text-center fw-bold edit-row-total bg-light">0</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr class="table-warning text-dark">
                                        <td class="text-end pe-3">TOTAL KESELURUHAN:</td>
                                        <td class="text-center" id="edit_total_bm">0</td>
                                        <td class="text-center" id="edit_total_pb">0</td>
                                        <td class="text-center" id="edit_total_cb">0</td>
                                        <td class="text-center" id="edit_total_cmb">0</td>
                                        <td class="text-center" id="edit_total_asimilasi">0</td>
                                        <td class="text-center" id="edit_total_bdh">0</td>
                                        <td class="text-center" id="edit_total_lainnya">0</td>
                                        <td></td>
                                        <td class="text-center fs-6 text-dark" id="edit_grand_total_all">0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Keterangan Global Edit -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Catatan / Keterangan Tambahan</label>
                            <textarea name="edit_keterangan_global" id="edit_keterangan_global" class="form-control" rows="2" placeholder="Catatan tambahan mengenai pembebasan hari ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        });
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
            this.classList.remove('show');
        });

        // 1. Calculation & Toggle Ket Lainnya for Add Form
        function calculateAddTotals() {
            let colBm = 0, colPb = 0, colCb = 0, colCmb = 0, colAsim = 0, colBdh = 0, colLain = 0, grandTotal = 0;

            document.querySelectorAll('#formTambah tbody tr').forEach(tr => {
                const bm = parseInt(tr.querySelector('input[name^="bebas_murni_"]')?.value) || 0;
                const pb = parseInt(tr.querySelector('input[name^="pb_"]')?.value) || 0;
                const cb = parseInt(tr.querySelector('input[name^="cb_"]')?.value) || 0;
                const cmb = parseInt(tr.querySelector('input[name^="cmb_"]')?.value) || 0;
                const asim = parseInt(tr.querySelector('input[name^="asimilasi_"]')?.value) || 0;
                const bdh = parseInt(tr.querySelector('input[name^="bdh_"]')?.value) || 0;
                const lain = parseInt(tr.querySelector('input[name^="lainnya_"]')?.value) || 0;

                const rowSum = bm + pb + cb + cmb + asim + bdh + lain;
                tr.querySelector('.row-total').textContent = rowSum;

                colBm += bm;
                colPb += pb;
                colCb += cb;
                colCmb += cmb;
                colAsim += asim;
                colBdh += bdh;
                colLain += lain;
                grandTotal += rowSum;
            });

            document.getElementById('total_bm').textContent = colBm;
            document.getElementById('total_pb').textContent = colPb;
            document.getElementById('total_cb').textContent = colCb;
            document.getElementById('total_cmb').textContent = colCmb;
            document.getElementById('total_asimilasi').textContent = colAsim;
            document.getElementById('total_bdh').textContent = colBdh;
            document.getElementById('total_lainnya').textContent = colLain;
            document.getElementById('grand_total_all').textContent = grandTotal;
        }

        document.querySelectorAll('#formTambah .matrix-input').forEach(input => {
            input.addEventListener('input', calculateAddTotals);
        });

        document.querySelectorAll('#formTambah .input-lainnya').forEach(input => {
            input.addEventListener('input', function() {
                const key = this.getAttribute('data-key');
                const ketInput = document.getElementById('ket_' + key);
                if (parseInt(this.value) > 0) {
                    ketInput.disabled = false;
                    ketInput.focus();
                } else {
                    ketInput.disabled = true;
                    ketInput.value = '';
                }
            });
        });

        // 2. Calculation & Toggle Ket Lainnya for Edit Form
        function calculateEditTotals() {
            let colBm = 0, colPb = 0, colCb = 0, colCmb = 0, colAsim = 0, colBdh = 0, colLain = 0, grandTotal = 0;

            document.querySelectorAll('#formEdit tbody tr').forEach(tr => {
                const bm = parseInt(tr.querySelector('input[name^="edit_bebas_murni_"]')?.value) || 0;
                const pb = parseInt(tr.querySelector('input[name^="edit_pb_"]')?.value) || 0;
                const cb = parseInt(tr.querySelector('input[name^="edit_cb_"]')?.value) || 0;
                const cmb = parseInt(tr.querySelector('input[name^="edit_cmb_"]')?.value) || 0;
                const asim = parseInt(tr.querySelector('input[name^="edit_asimilasi_"]')?.value) || 0;
                const bdh = parseInt(tr.querySelector('input[name^="edit_bdh_"]')?.value) || 0;
                const lain = parseInt(tr.querySelector('input[name^="edit_lainnya_"]')?.value) || 0;

                const rowSum = bm + pb + cb + cmb + asim + bdh + lain;
                tr.querySelector('.edit-row-total').textContent = rowSum;

                colBm += bm;
                colPb += pb;
                colCb += cb;
                colCmb += cmb;
                colAsim += asim;
                colBdh += bdh;
                colLain += lain;
                grandTotal += rowSum;
            });

            document.getElementById('edit_total_bm').textContent = colBm;
            document.getElementById('edit_total_pb').textContent = colPb;
            document.getElementById('edit_total_cb').textContent = colCb;
            document.getElementById('edit_total_cmb').textContent = colCmb;
            document.getElementById('edit_total_asimilasi').textContent = colAsim;
            document.getElementById('edit_total_bdh').textContent = colBdh;
            document.getElementById('edit_total_lainnya').textContent = colLain;
            document.getElementById('edit_grand_total_all').textContent = grandTotal;
        }

        document.querySelectorAll('#formEdit .matrix-edit-input').forEach(input => {
            input.addEventListener('input', calculateEditTotals);
        });

        document.querySelectorAll('#formEdit .edit-input-lainnya').forEach(input => {
            input.addEventListener('input', function() {
                const key = this.getAttribute('data-edit-key');
                const ketInput = document.getElementById('edit_ket_' + key);
                if (parseInt(this.value) > 0) {
                    ketInput.disabled = false;
                } else {
                    ketInput.disabled = true;
                    ketInput.value = '';
                }
            });
        });

        // 3. Open Edit Modal via AJAX Fetch
        const klasifikasiKeyMap = <?= json_encode($klasifikasiList) ?>;

        function openEditModal(uptId, tanggal) {
            fetch(`bebas_harian.php?ajax_detail=1&upt_id=${uptId}&tanggal=${tanggal}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('edit_upt_id').value = uptId;
                    document.getElementById('edit_tanggal').value = tanggal;
                    document.getElementById('display_edit_tanggal').textContent = tanggal;
                    
                    // Reset fields first
                    document.querySelectorAll('#formEdit input[type="number"]').forEach(el => el.value = 0);
                    document.querySelectorAll('#formEdit input[type="text"]').forEach(el => {
                        el.value = '';
                        el.disabled = true;
                    });
                    document.getElementById('edit_keterangan_global').value = '';

                    let globalKet = '';

                    data.forEach(item => {
                        const key = klasifikasiKeyMap[item.klasifikasi_pidana];
                        if (key) {
                            if (document.getElementById('edit_bm_' + key)) document.getElementById('edit_bm_' + key).value = item.bebas_murni;
                            if (document.getElementById('edit_pb_' + key)) document.getElementById('edit_pb_' + key).value = item.pb;
                            if (document.getElementById('edit_cb_' + key)) document.getElementById('edit_cb_' + key).value = item.cb;
                            if (document.getElementById('edit_cmb_' + key)) document.getElementById('edit_cmb_' + key).value = item.cmb;
                            if (document.getElementById('edit_asimilasi_' + key)) document.getElementById('edit_asimilasi_' + key).value = item.asimilasi;
                            if (document.getElementById('edit_bdh_' + key)) document.getElementById('edit_bdh_' + key).value = item.bdh;
                            if (document.getElementById('edit_lainnya_' + key)) document.getElementById('edit_lainnya_' + key).value = item.lainnya;
                            
                            const ketEl = document.getElementById('edit_ket_' + key);
                            if (ketEl && item.ket_lainnya) {
                                ketEl.value = item.ket_lainnya;
                                ketEl.disabled = false;
                            }
                        }
                        if (item.keterangan) globalKet = item.keterangan;
                    });

                    document.getElementById('edit_keterangan_global').value = globalKet;

                    // Fetch UPT name from table row
                    const uptName = document.querySelector(`tr:has(input[value="${uptId}"])`)?.cells[2]?.textContent || 'UPT';
                    document.getElementById('display_edit_upt').textContent = uptName;

                    calculateEditTotals();
                    new bootstrap.Modal(document.getElementById('modalEdit')).show();
                })
                .catch(err => {
                    alert('Gagal mengambil data untuk diedit: ' + err.message);
                });
        }

        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        if (sidebar && toggleBtn) {
            function toggleSidebar() {
                sidebar.classList.toggle('show');
                overlay?.classList.toggle('show');
            }
            toggleBtn.addEventListener('click', toggleSidebar);
            overlay?.addEventListener('click', toggleSidebar);
            
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) toggleSidebar();
                });
            });
        }
    </script>
</body>
</html>
