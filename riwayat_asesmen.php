<?php
/**
 * Riwayat Asesmen Page
 */

require_once 'includes/functions.php';
require_once 'auth_asesmen.php';

$user = getCurrentUser();
$instansi = getInstansi();
$uptList = getAllUPT();
$userUptFilter = getUserUptFilter();

$success_msg = '';
if (isset($_GET['delete_id']) && true) {
    try {
        $stmt = $pdo->prepare("DELETE FROM data_asesmen WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $success_msg = 'Data Asesmen berhasil dihapus.';
    } catch(PDOException $e) {}
}

// Handle filter
$filter_nama = $_GET['nama'] ?? '';
$filter_upt = $_GET['upt'] ?? '';

if ($userUptFilter) {
    $filter_upt = $userUptFilter;
}

$query = "SELECT * FROM data_asesmen WHERE 1=1";
$params = [];

if ($filter_nama !== '') {
    $query .= " AND (nama LIKE ? OR no_register LIKE ?)";
    $params[] = "%$filter_nama%";
    $params[] = "%$filter_nama%";
}

if ($filter_upt !== '') {
    $query .= " AND upt = ?";
    $params[] = $filter_upt;
}

$query .= " ORDER BY tgl_asesmen DESC, id DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$asesmenList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stat_query = "SELECT kategori_hasil, COUNT(*) as jml FROM data_asesmen WHERE 1=1";
if ($filter_nama !== '') {
    $stat_query .= " AND (nama LIKE ? OR no_register LIKE ?)";
}
if ($filter_upt !== '') {
    $stat_query .= " AND upt = ?";
}
$stat_query .= " GROUP BY kategori_hasil";
$stmt_stat = $pdo->prepare($stat_query);
$stmt_stat->execute($params);
$stats_data = $stmt_stat->fetchAll(PDO::FETCH_ASSOC);

$tot_asesmen = 0;
$tot_layak = 0;
$tot_layak_bersyarat = 0;
$tot_tidak_layak = 0;
$tot_ditunda = 0;

foreach ($stats_data as $sd) {
    $k = strtolower($sd['kategori_hasil']);
    $cnt = (int)$sd['jml'];
    $tot_asesmen += $cnt;
    
    if (strpos($k, 'layak bersyarat') !== false) {
        $tot_layak_bersyarat += $cnt;
    } elseif (strpos($k, 'tidak layak') !== false || strpos($k, 'tidak memenuhi syarat') !== false) {
        $tot_tidak_layak += $cnt;
    } elseif (strpos($k, 'ditunda') !== false || strpos($k, 'tunda') !== false) {
        $tot_ditunda += $cnt;
    } elseif (strpos($k, 'layak') !== false) {
        $tot_layak += $cnt;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Asesmen - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Overlay - Only for logged in users -->
                <!-- Standalone Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-clipboard-list fa-3x text-white mb-2"></i>
            <h5>Portal Asesmen</h5>
        </div>
        <div class="sidebar-menu">
            <a href="asesmen.php" class="<?= basename($_SERVER['PHP_SELF']) == 'asesmen.php' ? 'active' : '' ?>"><i class="fas fa-plus"></i><span>Input Asesmen</span></a>
            <a href="riwayat_asesmen.php" class="<?= basename($_SERVER['PHP_SELF']) == 'riwayat_asesmen.php' ? 'active' : '' ?>"><i class="fas fa-history"></i><span>Riwayat Asesmen</span></a>
            <a href="import_asesmen.php" class="<?= basename($_SERVER['PHP_SELF']) == 'import_asesmen.php' ? 'active' : '' ?>"><i class="fas fa-upload"></i><span>Import Asesmen</span></a>
            <a href="auth_asesmen.php?logout=1" style="color:#ffcccc; margin-top:20px;"><i class="fas fa-sign-out-alt"></i><span>Logout Asesor</span></a>
        </div>
    </nav>

    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat & Cetak Asesmen</h4>
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <a href="asesmen.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Asesmen Baru</a>
            </div>
        </div>
        
        <!-- Dashboard Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-sm-4 col-6">
                <div class="card bg-primary text-white text-center h-100">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1" style="font-size:0.9rem;">Total Asesmen</h6>
                        <h3 class="fw-bold mb-0"><?= $tot_asesmen ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="card bg-success text-white text-center h-100">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1" style="font-size:0.9rem;">Layak</h6>
                        <h3 class="fw-bold mb-0"><?= $tot_layak ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-6">
                <div class="card bg-info text-dark text-center h-100">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1" style="font-size:0.9rem;">Layak Bersyarat</h6>
                        <h3 class="fw-bold mb-0"><?= $tot_layak_bersyarat ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 col-6">
                <div class="card bg-danger text-white text-center h-100">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1" style="font-size:0.9rem;">Tidak Layak</h6>
                        <h3 class="fw-bold mb-0"><?= $tot_tidak_layak ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="card bg-warning text-dark text-center h-100">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1" style="font-size:0.9rem;">Ditunda / Rujuk TPP</h6>
                        <h3 class="fw-bold mb-0"><?= $tot_ditunda ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <?php if($success_msg): ?><div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="nama" placeholder="Cari Nama atau No. Register..." value="<?= htmlspecialchars($filter_nama) ?>">
                </div>
                <div class="col-md-5">
                    <select class="form-select" name="upt" <?= $userUptFilter ? 'disabled' : '' ?>>
                        <option value="">Semua UPT</option>
                        <?php foreach ($uptList as $u): ?>
                            <option value="<?= htmlspecialchars($u['nama_upt']) ?>" <?= $filter_upt === $u['nama_upt'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama_upt']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Cari</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Tanggal</th>
                            <th width="20%">Nama / Register</th>
                            <th width="20%">UPT</th>
                            <th width="15%">Kategori Akhir</th>
                            <th width="10%">Total Skor</th>
                            <th width="18%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($asesmenList) > 0): ?>
                            <?php foreach ($asesmenList as $idx => $row): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tgl_asesmen'])) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['nama']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['no_register']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['upt']) ?></td>
                                    <td>
                                        <?php
                                        $cat = $row['kategori_hasil'];
                                        $badge = 'bg-secondary';
                                        if (strpos(strtolower($cat), 'layak bersyarat') !== false) $badge = 'bg-info text-dark';
                                        elseif (strpos(strtolower($cat), 'tidak layak') !== false) $badge = 'bg-danger';
                                        elseif (strpos(strtolower($cat), 'tunda') !== false) $badge = 'bg-warning text-dark';
                                        elseif (strpos(strtolower($cat), 'layak') !== false) $badge = 'bg-success';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($cat) ?></span>
                                    </td>
                                    <td class="text-center fw-bold"><?= htmlspecialchars($row['total_skor']) ?></td>
                                    <td class="text-center text-nowrap">
                                        <a href="cetak_asesmen.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-success me-1" title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="asesmen.php?edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-primary me-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="riwayat_asesmen.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data asesmen ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-3">Tidak ada data asesmen ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar?.classList.toggle('show');
            overlay?.classList.toggle('show');
        }

        toggleBtn?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>






