<?php
/**
 * Dashboard Page - Public access
 */

require_once 'includes/functions.php';

// No login required - dashboard is public
$user = getCurrentUser();
$instansi = getInstansi();

// Filter parameters
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$filter_upt = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : 0;

// Get all UPT for filter
$uptList = getAllUPT();

// Build WHERE clause for filters
$whereClause = "WHERE tanggal = ?";
$params = [$filter_tanggal];

if ($filter_upt > 0) {
    $whereClause .= " AND upt_id = ?";
    $params[] = $filter_upt;
}

// Klasifikasi categories
$klasifikasiPidum = ['Pidana Umum'];
$klasifikasiPidsus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];
$klasifikasiUUITE = ['UU ITE'];
$klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun'];
$klasifikasiUtama = array_merge($klasifikasiPidum, $klasifikasiPidsus, $klasifikasiUUITE);

// Get statistics with filters
// Count UPT yang memiliki data pada tanggal filter (dari semua tabel: penghuni, overstaying, integrasi)
if ($filter_upt > 0) {
    // Jika filter UPT dipilih, cek apakah ada data di salah satu tabel
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM (
            SELECT DISTINCT upt_id FROM data_penghuni WHERE tanggal = ? AND upt_id = ?
            UNION
            SELECT DISTINCT upt_id FROM data_overstaying WHERE tanggal = ? AND upt_id = ?
            UNION
            SELECT DISTINCT upt_id FROM data_integrasi WHERE tanggal = ? AND upt_id = ?
        ) as combined
    ");
    $stmt->execute([$filter_tanggal, $filter_upt, $filter_tanggal, $filter_upt, $filter_tanggal, $filter_upt]);
    $totalUPTWithData = $stmt->fetch()['total'] > 0 ? 1 : 0;
} else {
    // Hitung jumlah UPT yang memiliki data pada tanggal tersebut (dari semua tabel)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM (
            SELECT DISTINCT upt_id FROM data_penghuni WHERE tanggal = ?
            UNION
            SELECT DISTINCT upt_id FROM data_overstaying WHERE tanggal = ?
            UNION
            SELECT DISTINCT upt_id FROM data_integrasi WHERE tanggal = ?
        ) as combined
    ");
    $stmt->execute([$filter_tanggal, $filter_tanggal, $filter_tanggal]);
    $totalUPTWithData = $stmt->fetch()['total'] ?? 0;
}

$stats = [
    'total_upt' => $totalUPTWithData,
    'total_kapasitas' => 0,
    'total_tahanan' => 0,
    'total_narapidana' => 0,
    'total_penghuni' => 0,
    'total_overstaying' => 0,
    'total_integrasi' => 0,
    'persen_overkapasitas' => 0,
    'total_pidum' => 0,
    'total_pidsus' => 0,
    'total_uu_ite' => 0,
    'total_wna' => 0,
    'total_sakit' => 0,
    'total_lansia' => 0
];

// Get total kapasitas - sesuai filter UPT
if ($filter_upt > 0) {
    $stmt = $pdo->prepare("SELECT SUM(kapasitas) as total FROM upt WHERE id = ?");
    $stmt->execute([$filter_upt]);
    $stats['total_kapasitas'] = $stmt->fetch()['total'] ?? 0;
} else {
    // Jika tidak ada filter UPT, hitung kapasitas dari UPT yang memiliki data pada tanggal tersebut
    $stmt = $pdo->prepare("
        SELECT SUM(u.kapasitas) as total FROM upt u
        WHERE u.id IN (
            SELECT DISTINCT upt_id FROM data_penghuni WHERE tanggal = ?
            UNION
            SELECT DISTINCT upt_id FROM data_overstaying WHERE tanggal = ?
            UNION
            SELECT DISTINCT upt_id FROM data_integrasi WHERE tanggal = ?
        )
    ");
    $stmt->execute([$filter_tanggal, $filter_tanggal, $filter_tanggal]);
    $stats['total_kapasitas'] = $stmt->fetch()['total'] ?? 0;
}

// Helper function to get total by klasifikasi
function getKlasifikasiTotal($pdo, $klasifikasiList, $whereClause, $params) {
    $placeholders = implode(',', array_fill(0, count($klasifikasiList), '?'));
    $sql = "SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
            narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total 
            FROM data_penghuni 
            WHERE klasifikasi_pidana IN ($placeholders) " . str_replace('WHERE', 'AND', $whereClause);
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($klasifikasiList, $params));
    return $stmt->fetch()['total'] ?? 0;
}

// Get totals by klasifikasi
$stats['total_pidum'] = getKlasifikasiTotal($pdo, $klasifikasiPidum, $whereClause, $params);
$stats['total_pidsus'] = getKlasifikasiTotal($pdo, $klasifikasiPidsus, $whereClause, $params);
$stats['total_uu_ite'] = getKlasifikasiTotal($pdo, $klasifikasiUUITE, $whereClause, $params);
$stats['total_wna'] = getKlasifikasiTotal($pdo, ['WNA'], $whereClause, $params);
$stats['total_sakit'] = getKlasifikasiTotal($pdo, ['Sakit Berkepanjangan'], $whereClause, $params);
$stats['total_lansia'] = getKlasifikasiTotal($pdo, ['Lansia >70 tahun'], $whereClause, $params);

// Get penghuni stats (exclude Lainnya from main calculation)
$excludeKlasifikasi = $klasifikasiLainnya;
$excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));

$stmt = $pdo->prepare("SELECT 
    SUM(tahanan_dewasa_laki) as tahanan_dewasa_laki,
    SUM(tahanan_dewasa_perempuan) as tahanan_dewasa_perempuan,
    SUM(tahanan_anak_laki) as tahanan_anak_laki,
    SUM(tahanan_anak_perempuan) as tahanan_anak_perempuan,
    SUM(narapidana_dewasa_laki) as narapidana_dewasa_laki,
    SUM(narapidana_dewasa_perempuan) as narapidana_dewasa_perempuan,
    SUM(narapidana_anak_laki) as narapidana_anak_laki,
    SUM(narapidana_anak_perempuan) as narapidana_anak_perempuan
    FROM data_penghuni 
    WHERE klasifikasi_pidana NOT IN ($excludePlaceholders) " . str_replace('WHERE', 'AND', $whereClause));
$stmt->execute(array_merge($excludeKlasifikasi, $params));
$penghuniData = $stmt->fetch();

if ($penghuniData) {
    $stats['total_tahanan'] = 
        ($penghuniData['tahanan_dewasa_laki'] ?? 0) + 
        ($penghuniData['tahanan_dewasa_perempuan'] ?? 0) + 
        ($penghuniData['tahanan_anak_laki'] ?? 0) + 
        ($penghuniData['tahanan_anak_perempuan'] ?? 0);
    
    $stats['total_narapidana'] = 
        ($penghuniData['narapidana_dewasa_laki'] ?? 0) + 
        ($penghuniData['narapidana_dewasa_perempuan'] ?? 0) +
        ($penghuniData['narapidana_anak_laki'] ?? 0) + 
        ($penghuniData['narapidana_anak_perempuan'] ?? 0);
    
    $stats['total_penghuni'] = $stats['total_tahanan'] + $stats['total_narapidana'];
    
    // Calculate overkapasitas percentage: (isi - kapasitas) / kapasitas * 100
    if ($stats['total_kapasitas'] > 0) {
        $stats['persen_overkapasitas'] = round((($stats['total_penghuni'] - $stats['total_kapasitas']) / $stats['total_kapasitas']) * 100, 1);
    }
}

// Get overstaying stats - sesuai filter tanggal
$stmt = $pdo->prepare("SELECT 
    SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) as total_tahanan,
    SUM(overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total_overstaying
    FROM data_overstaying 
    $whereClause");
$stmt->execute($params);
$overstayingData = $stmt->fetch();
$stats['total_tahanan_overstaying'] = (int)($overstayingData['total_tahanan'] ?? 0);
$stats['total_overstaying'] = (int)($overstayingData['total_overstaying'] ?? 0);

// Get integrasi stats
$stmt = $pdo->prepare("SELECT 
    SUM(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + 
        sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi) as total 
    FROM data_integrasi $whereClause");
$stmt->execute($params);
$stats['total_integrasi'] = $stmt->fetch()['total'] ?? 0;

// Get overstaying stats per kategori (AI-AV) for donut chart
// Sesuai dengan filter tanggal
$overstayingStats = [];
$kategoriOverstaying = ['ai' => 'AI', 'aii' => 'AII', 'aiii' => 'AIII', 'aiv' => 'AIV', 'av' => 'AV'];

// Inisialisasi dengan nilai default
foreach ($kategoriOverstaying as $key => $label) {
    $overstayingStats[$key] = [
        'tahanan' => 0,
        'overstaying' => 0,
        'normal' => 0
    ];
}

// Get overstaying data sesuai filter
$overstayingQuery = "SELECT 
    SUM(tahanan_ai) as tahanan_ai, SUM(tahanan_aii) as tahanan_aii, 
    SUM(tahanan_aiii) as tahanan_aiii, SUM(tahanan_aiv) as tahanan_aiv, SUM(tahanan_av) as tahanan_av,
    SUM(overstaying_ai) as overstaying_ai, SUM(overstaying_aii) as overstaying_aii, 
    SUM(overstaying_aiii) as overstaying_aiii, SUM(overstaying_aiv) as overstaying_aiv, SUM(overstaying_av) as overstaying_av
    FROM data_overstaying 
    $whereClause";
$stmt = $pdo->prepare($overstayingQuery);
$stmt->execute($params);
$overstayingData = $stmt->fetch();

// Isi dengan data dari database jika ada
if ($overstayingData) {
    foreach ($kategoriOverstaying as $key => $label) {
        $tahanan = (int)($overstayingData['tahanan_' . $key] ?? 0);
        $overstaying = (int)($overstayingData['overstaying_' . $key] ?? 0);
        $overstayingStats[$key] = [
            'tahanan' => $tahanan,
            'overstaying' => $overstaying,
            'normal' => $tahanan - $overstaying
        ];
    }
}

// Get data per UPT for overkapasitas table (exclude Lainnya)
$uptDataQuery = "SELECT 
    u.id, u.nama_upt, u.kapasitas,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun') 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan ELSE 0 END), 0) as total_tahanan,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun') 
        THEN dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as total_narapidana,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun') 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as total_penghuni
    FROM upt u
    LEFT JOIN data_penghuni dp ON u.id = dp.upt_id AND dp.tanggal = ?
    " . ($filter_upt > 0 ? "WHERE u.id = ?" : "") . "
    GROUP BY u.id, u.nama_upt, u.kapasitas
    ORDER BY u.nama_upt";

$uptParams = [$filter_tanggal];
if ($filter_upt > 0) {
    $uptParams[] = $filter_upt;
}

$stmt = $pdo->prepare($uptDataQuery);
$stmt->execute($uptParams);
$uptDataTable = $stmt->fetchAll();

// Get chart data (exclude Lainnya)
$stmt = $pdo->prepare("SELECT 
    klasifikasi_pidana,
    SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
        narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total
    FROM data_penghuni 
    WHERE klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun') " . str_replace('WHERE', 'AND', $whereClause) . "
    GROUP BY klasifikasi_pidana
    ORDER BY total DESC");
$stmt->execute($params);
$penghuniByKlasifikasi = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT 
    u.nama_upt,
    SUM(d.tahanan_dewasa_laki + d.tahanan_dewasa_perempuan + d.tahanan_anak_laki + d.tahanan_anak_perempuan + 
        d.narapidana_dewasa_laki + d.narapidana_dewasa_perempuan + d.narapidana_anak_laki + d.narapidana_anak_perempuan) as total
    FROM data_penghuni d
    JOIN upt u ON d.upt_id = u.id
    WHERE d.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun') " . str_replace('WHERE', 'AND', $whereClause) . "
    GROUP BY u.id, u.nama_upt
    ORDER BY total DESC");
$stmt->execute($params);
$penghuniByUPT = $stmt->fetchAll();

// Get UPT locations for map with penghuni count
$uptLocationsQuery = "SELECT 
    u.id, u.nama_upt, u.alamat, u.kapasitas, u.latitude, u.longitude,
    COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni
    FROM upt u
    LEFT JOIN data_penghuni dp ON u.id = dp.upt_id AND dp.tanggal = ? AND dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun')
    WHERE u.latitude IS NOT NULL AND u.longitude IS NOT NULL
    " . ($filter_upt > 0 ? " AND u.id = ?" : "") . "
    GROUP BY u.id, u.nama_upt, u.alamat, u.kapasitas, u.latitude, u.longitude";

$stmtUptLoc = $pdo->prepare($uptLocationsQuery);
$uptLocParams = [$filter_tanggal];
if ($filter_upt > 0) {
    $uptLocParams[] = $filter_upt;
}
$stmtUptLoc->execute($uptLocParams);
$uptLocations = $stmtUptLoc->fetchAll();

// Get data for "Data Penghuni Berdasarkan Jenis Kejahatan" table with detailed Pidsus breakdown
$jenisKejahatanQuery = "SELECT 
    u.id, u.nama_upt,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Pidana Umum' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as pidum,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Tipikor' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as tipikor,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Narkoba Bandar' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as narkoba_bandar,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Narkoba Pengguna' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as narkoba_pengguna,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Teroris' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as teroris,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Trafficking' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as trafficking,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Ilegal Logging' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as ilegal_logging,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Money Laundering' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as money_laundering,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Genosida' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as genosida,
    COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'UU ITE' 
        THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as uu_ite,
    COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                 dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni
    FROM upt u
    LEFT JOIN data_penghuni dp ON u.id = dp.upt_id AND dp.tanggal = ?
    " . ($filter_upt > 0 ? "WHERE u.id = ?" : "") . "
    GROUP BY u.id, u.nama_upt
    ORDER BY u.nama_upt";

$stmtJenisKejahatan = $pdo->prepare($jenisKejahatanQuery);
$jenisKejahatanParams = [$filter_tanggal];
if ($filter_upt > 0) {
    $jenisKejahatanParams[] = $filter_upt;
}
$stmtJenisKejahatan->execute($jenisKejahatanParams);
$jenisKejahatanData = $stmtJenisKejahatan->fetchAll();

// Get data for "Data Penghuni Berdasarkan Umur dan Jenis Kelamin" chart
// Sesuai dengan filter tanggal dan UPT (menggunakan pola yang sama dengan Total Penghuni)
// Mengecualikan Lainnya (WNA, Sakit Berkepanjangan, Lansia)
$umurGenderQuery = "SELECT
    SUM(tahanan_dewasa_laki + narapidana_dewasa_laki) as dewasa_laki,
    SUM(tahanan_dewasa_perempuan + narapidana_dewasa_perempuan) as dewasa_perempuan,
    SUM(tahanan_anak_laki + narapidana_anak_laki) as anak_laki,
    SUM(tahanan_anak_perempuan + narapidana_anak_perempuan) as anak_perempuan
    FROM data_penghuni
    WHERE klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun') " . str_replace('WHERE', 'AND', $whereClause);
$stmtUmurGender = $pdo->prepare($umurGenderQuery);
$stmtUmurGender->execute($params);
$umurGenderData = $stmtUmurGender->fetch();

function getOverkapasitasColor($persen) {
    // Rumus baru: (isi - kapasitas) / kapasitas * 100
    // Negatif = masih ada ruang (hijau), positif = overkapasitas (merah)
    if ($persen < -50) return 'bg-success text-white';           // Sangat longgar
    if ($persen < -25) return 'bg-success text-white';           // Longgar
    if ($persen < 0) return 'bg-info text-white';                // Masih ada ruang
    if ($persen == 0) return 'bg-warning text-dark';             // Pas penuh
    if ($persen <= 20) return 'bg-orange text-white';            // Sedikit over
    if ($persen <= 50) return 'bg-warning text-dark';            // Over sedang
    if ($persen <= 100) return 'bg-danger text-white';           // Over tinggi
    return 'bg-dark-red text-white';                             // Sangat over
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
    <title>Dashboard - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <!-- Modern Theme CSS -->
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        /* ============================================
           PAGE-SPECIFIC STYLES FOR DASHBOARD
           ============================================ */
        
        /* Dark Mode Variables Override */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --text-overstaying: #dc2626;
            --text-lainnya: #e65100;
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-topbar: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --text-overstaying: #f87171;
            --text-lainnya: #fb923c;
        }

        /* Text color classes */
        .text-overstaying { color: var(--text-overstaying); }
        .text-lainnya { color: var(--text-lainnya); }

        /* Dark Mode Text Fixes */
        [data-theme="dark"] .stat-label {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .small,
        [data-theme="dark"] small {
            color: var(--text-secondary);
        }

        [data-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }

        [data-theme="dark"] .form-label {
            color: var(--text-secondary);
        }

        [data-theme="dark"] h5 {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] h6 {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .table {
            color: var(--text-primary);
        }

        [data-theme="dark"] .table th {
            background-color: rgba(102, 126, 234, 0.2);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .table td {
            border-color: var(--border-color);
        }

        [data-theme="dark"] .table-light th,
        [data-theme="dark"] .table-light td {
            background-color: rgba(102, 126, 234, 0.15);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .table-success td {
            background-color: rgba(34, 197, 94, 0.2);
            color: var(--text-primary);
        }

        [data-theme="dark"] .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
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
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        [data-theme="dark"] .topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
        }

        /* Dark mode badges with opacity */
        [data-theme="dark"] .bg-opacity-10 {
            --bs-bg-opacity: 0.2 !important;
        }
        [data-theme="dark"] .bg-opacity-25 {
            --bs-bg-opacity: 0.3 !important;
        }
        [data-theme="dark"] .text-primary {
            color: #a5b4fc !important;
        }
        [data-theme="dark"] .text-dark {
            color: var(--text-primary) !important;
        }
        [data-theme="dark"] .text-warning-emphasis {
            color: #fbbf24 !important;
        }
        
        /* Badge background colors for dark mode */
        [data-theme="dark"] .badge.bg-primary { background: rgba(99, 102, 241, 0.3) !important; color: #a5b4fc !important; }
        [data-theme="dark"] .badge.bg-info { background: rgba(6, 182, 212, 0.3) !important; color: #67e8f9 !important; }
        [data-theme="dark"] .badge.bg-warning { background: rgba(245, 158, 11, 0.3) !important; color: #fcd34d !important; }
        [data-theme="dark"] .badge.bg-success { background: rgba(34, 197, 94, 0.3) !important; color: #86efac !important; }
        
        /* Table header backgrounds for dark mode */
        [data-theme="dark"] .bg-primary.bg-opacity-10 { background-color: rgba(99, 102, 241, 0.15) !important; }
        [data-theme="dark"] .bg-info.bg-opacity-25 { background-color: rgba(6, 182, 212, 0.2) !important; }
        [data-theme="dark"] .bg-warning.bg-opacity-25 { background-color: rgba(245, 158, 11, 0.2) !important; }
        [data-theme="dark"] .bg-success.bg-opacity-25 { background-color: rgba(34, 197, 94, 0.2) !important; }
        
        /* Chart card backgrounds */
        [data-theme="dark"] .chart-card,
        [data-theme="dark"] .table-card,
        [data-theme="dark"] .filter-card,
        [data-theme="dark"] .detail-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }
        
        /* Section headers */
        [data-theme="dark"] .section-header {
            color: var(--text-secondary);
            border-bottom-color: var(--border-color);
        }

        /* Stat Card Icon Gradients */
        .stat-icon.bg-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.bg-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.bg-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.bg-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-icon.bg-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-icon.bg-6 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .stat-icon.bg-7 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.bg-8 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.bg-9 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.bg-10 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }

        /* Smaller Stat Cards */
        .stat-card-sm {
            padding: 12px 15px;
            border-radius: 10px;
        }
        .stat-icon-sm {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }
        .stat-icon-sm.bg-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon-sm.bg-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon-sm.bg-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon-sm.bg-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-icon-sm.bg-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-icon-sm.bg-6 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .stat-icon-sm.bg-7 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon-sm.bg-8 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon-sm.bg-9 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon-sm.bg-10 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon-sm.bg-warning { background: linear-gradient(135deg, #ff9800 0%, #ffc107 100%); }
        .stat-icon-sm.bg-danger { background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); }
        .stat-value-sm {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }
        .stat-label-sm {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 500;
            margin: 0;
        }
        [data-theme="dark"] .stat-value-sm { color: var(--text-primary) !important; }
        [data-theme="dark"] .stat-label-sm { color: var(--text-secondary) !important; }
        [data-theme="dark"] .stat-card-sm.lainnya {
            background: linear-gradient(135deg, #3d2a1a 0%, #4a3520 100%);
        }
        [data-theme="dark"] .stat-card-sm.lainnya .stat-value-sm { color: #ffb74d !important; }
        [data-theme="dark"] .stat-card-sm.lainnya .stat-label-sm { color: #ffd54f !important; }

        /* Card Category Colors */
        .stat-card.pidum { border-left: 4px solid #11998e; }
        .stat-card.pidsus { border-left: 4px solid #667eea; }
        .stat-card.uu-ite { border-left: 4px solid #f5576c; }
        .stat-card.lainnya { 
            border-left: 4px solid #ff9800; 
            background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
        }
        .stat-card.lainnya .stat-label { color: #e65100; }
        .stat-card.lainnya .stat-value { color: #bf360c; }
        .stat-card-sm.lainnya { 
            border-left: 4px solid #ff9800; 
            background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
        }
        .stat-card-sm.lainnya .stat-label-sm { color: #e65100; }
        .stat-card-sm.lainnya .stat-value-sm { color: #bf360c; }

        /* Dark Mode Card Overrides */
        [data-theme="dark"] .stat-card {
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .stat-card.lainnya {
            background: linear-gradient(135deg, #3d2a1a 0%, #4a3520 100%);
            border-left: 4px solid #ff9800;
        }

        [data-theme="dark"] .stat-card.lainnya .stat-value {
            color: #ffb74d !important;
        }

        [data-theme="dark"] .stat-card.lainnya .stat-label {
            color: #ffd54f !important;
        }

        [data-theme="dark"] .stat-card .stat-value {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .stat-icon {
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        /* Overkapasitas Specific */
        .stat-overkapasitas {
            transition: all 0.3s ease;
        }

        .stat-overkapasitas.text-white .stat-value-sm,
        .stat-overkapasitas.text-white .stat-label-sm,
        [data-theme="dark"] .stat-overkapasitas.text-white .stat-value-sm,
        [data-theme="dark"] .stat-overkapasitas.text-white .stat-label-sm {
            color: #ffffff !important;
        }

        .stat-overkapasitas.text-dark .stat-value-sm,
        .stat-overkapasitas.text-dark .stat-label-sm,
        [data-theme="dark"] .stat-overkapasitas.text-dark .stat-value-sm,
        [data-theme="dark"] .stat-overkapasitas.text-dark .stat-label-sm {
            color: #212529 !important;
        }

        .stat-overkapasitas .stat-icon-sm i {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .stat-overkapasitas {
            /* background will be set by dynamic class */
        }

        [data-theme="dark"] .stat-card [style*="color: #dc2626"],
        [data-theme="dark"] .stat-card [style*="color:#dc2626"] {
            color: #f87171 !important;
        }


        /* Overkapasitas Colors */
        .bg-orange { background-color: #ff9800 !important; }
        .bg-dark-red { background-color: #b71c1c !important; }

        /* Section Header */
        .section-header {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-header.lainnya {
            color: #e65100;
            border-bottom-color: #ffcc80;
        }

        [data-theme="dark"] .section-header.lainnya {
            color: #ffb74d;
            border-bottom-color: #5d4037;
        }

        /* Chart Card, Table Card, Filter Card */
        .chart-card, .table-card, .detail-card, .filter-card {
            background: var(--bg-card, #ffffff);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color, #e9ecef);
        }

        .chart-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        /* Custom Legend Styles */
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .chart-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 500;
        }

        .chart-legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        [data-theme="dark"] .chart-legend-item {
            color: var(--text-secondary);
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

        [data-theme="dark"] .dark-mode-switch .switch-label {
            color: var(--text-secondary);
        }

        /* Legend */
        .legend-item {
            display: inline-flex;
            align-items: center;
            margin-right: 12px;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            margin-right: 4px;
        }

        /* 5-column layout */
        .col-md-2-4 {
            flex: 0 0 auto;
            width: 20%;
        }

        @media (max-width: 767.98px) {
            .col-md-2-4 {
                width: 33.333%;
            }
        }

        @media (max-width: 575.98px) {
            .col-md-2-4 {
                width: 50%;
            }
        }

        /* No Sidebar Mode - for public view */
        .main-content.no-sidebar {
            margin-left: 0;
            padding: 0;
        }

        .main-content.no-sidebar .topbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 18px 25px;
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .main-content.no-sidebar .topbar .public-logo {
            height: 45px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            animation: fadeInLeft 0.5s ease-out;
        }

        .main-content.no-sidebar .topbar .public-title {
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: fadeInLeft 0.5s ease-out 0.1s both;
        }

        .main-content.no-sidebar .topbar .public-subtitle {
            color: rgba(255,255,255,0.9);
            text-shadow: 0 1px 2px rgba(0,0,0,0.15);
            animation: fadeInLeft 0.5s ease-out 0.2s both;
        }

        .main-content.no-sidebar .topbar .btn-login-public {
            background: rgba(255,255,255,0.95);
            color: var(--primary-color);
            border: none;
            padding: 8px 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .main-content.no-sidebar .topbar .btn-login-public:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .main-content.no-sidebar .topbar .dark-mode-switch .switch-label {
            color: rgba(255,255,255,0.8);
        }

        .main-content.no-sidebar .topbar .dark-mode-switch .slider {
            background-color: rgba(255,255,255,0.3);
        }

        .main-content.no-sidebar .topbar .dark-mode-switch .slider:before {
            background-color: white;
        }

        .main-content.no-sidebar .content-wrapper {
            padding: 15px;
            animation: fadeInUp 0.5s ease-out 0.3s both;
        }

        .main-content.no-sidebar .filter-card,
        .main-content.no-sidebar .chart-card,
        .main-content.no-sidebar .table-card,
        .main-content.no-sidebar .detail-card {
            border-radius: 12px;
        }

        /* Animations */
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Map Styles */
        #mapBanten {
            border: 1px solid var(--border-color, #e9ecef);
        }

        [data-theme="dark"] #mapBanten {
            border-color: var(--border-color);
        }

        .leaflet-popup-content-wrapper {
            border-radius: 8px;
        }

        .leaflet-popup-content {
            margin: 10px 12px;
        }

        .custom-marker {
            background: transparent;
        }

        [data-theme="dark"] .leaflet-popup-content-wrapper {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        [data-theme="dark"] .leaflet-popup-tip {
            background: var(--bg-card);
        }

        /* Welcome Modal Responsive Styles */
        #welcomeModal .modal-content {
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        #welcomeModal .btn-close {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 100;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 1;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: 14px;
        }
        #welcomeModal .btn-close:hover {
            transform: rotate(90deg) scale(1.1);
            background-color: #fff;
            box-shadow: 0 6px 15px rgba(0,0,0,0.25);
        }
        #welcomeModal .modal-body {
            padding: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        #welcomeModal .welcome-banner-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }
        #welcomeModal .welcome-banner-img {
            width: 100%;
            max-height: 55vh;
            object-fit: contain;
            display: block;
        }
        #welcomeModal .welcome-text-container {
            padding: 1.5rem;
            flex-shrink: 0;
        }
        @media (max-height: 700px) {
            #welcomeModal .welcome-banner-img {
                max-height: 45vh;
            }
            #welcomeModal .welcome-text-container {
                padding: 1rem;
            }
            #welcomeModal h3 {
                font-size: 1.25rem;
            }
        }

        /* Pengumuman Modal Styles - A4 Paper Size */
        .pengumuman-modal .modal-dialog {
            max-width: 794px;
            width: 95%;
        }

        .pengumuman-modal .modal-content {
            border: none;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
        }

        .pengumuman-modal .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 20px 25px;
        }

        .pengumuman-modal .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
            font-size: 1.2rem;
        }

        .pengumuman-modal .modal-header .btn-close:hover {
            opacity: 1;
        }

        .pengumuman-modal .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .pengumuman-modal .modal-body {
            padding: 0;
            max-height: 80vh;
            overflow-y: auto;
            background: white;
        }

        [data-theme="dark"] .pengumuman-modal .modal-body {
            background: var(--bg-card);
        }

        .pengumuman-modal-item {
            padding: 25px 30px;
            border-bottom: 2px solid var(--border-color, #e9ecef);
            transition: background 0.2s;
        }

        .pengumuman-modal-item:last-child {
            border-bottom: none;
        }

        .pengumuman-modal-item:hover {
            background: rgba(102, 126, 234, 0.03);
        }

        .pengumuman-modal-item-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .pengumuman-modal-kategori {
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kategori-pengumuman { background: #667eea; color: white; }
        .kategori-berita { background: #198754; color: white; }
        .kategori-urgent { background: #dc3545; color: white; animation: pulse 2s infinite; }
        .kategori-info { background: #0dcaf0; color: #000; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .pengumuman-modal-judul {
            font-weight: 700;
            font-size: 18px;
            color: var(--text-primary);
            flex: 1;
            line-height: 1.4;
        }

        .pengumuman-modal-image-container {
            width: 100%;
            margin-bottom: 15px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }

        [data-theme="dark"] .pengumuman-modal-image-container {
            background: rgba(255,255,255,0.05);
        }

        .pengumuman-modal-image {
            max-width: 100%;
            max-height: 600px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .pengumuman-modal-isi {
            font-size: 15px;
            color: var(--text-primary);
            line-height: 1.8;
            margin-bottom: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .pengumuman-modal-meta {
            font-size: 12px;
            color: var(--text-muted, #6c757d);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding-top: 10px;
            border-top: 1px dashed var(--border-color, #e9ecef);
        }

        .pengumuman-modal-meta i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        .pengumuman-modal-footer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px 25px;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }

        [data-theme="dark"] .pengumuman-modal-footer {
            background: var(--bg-card);
            border-top-color: var(--border-color);
        }

        .pengumuman-modal-footer i {
            margin-right: 5px;
        }

        .pengumuman-counter {
            font-size: 12px;
            opacity: 0.8;
            margin-left: auto;
            padding: 4px 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
        }

        @media (max-width: 768px) {
            .pengumuman-modal .modal-dialog {
                margin: 5px;
                width: calc(100% - 10px);
            }
            
            .pengumuman-modal-item {
                padding: 15px 20px;
            }
            
            .pengumuman-modal-judul {
                font-size: 16px;
            }
            
            .pengumuman-modal-isi {
                font-size: 14px;
            }
            
            .pengumuman-modal-image {
                max-height: 400px;
            }
        }

        /* Responsive Overrides */
        @media (max-width: 767.98px) {
            .stat-card { padding: 12px; }
            .stat-card .stat-icon { width: 38px; height: 38px; font-size: 14px; }
            .stat-card .stat-value { font-size: 18px; }
            .stat-card .stat-label { font-size: 10px; }
            .topbar { padding: 10px; }
            h4 { font-size: 16px; }
            .dark-mode-switch { margin-right: 8px; }
            .dark-mode-switch .switch-label { display: none; }
            .chart-card, .table-card, .filter-card { padding: 12px; }
            #mapBanten { height: 300px !important; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 8px; }
            .stat-card { padding: 10px; margin-bottom: 6px; }
            .stat-card .stat-value { font-size: 16px; }
            .stat-card .stat-label { font-size: 10px; }
            .section-header { font-size: 11px; }
            .public-title { font-size: 14px !important; }
            .public-subtitle { font-size: 11px !important; }
            .btn-login-public { padding: 6px 12px !important; font-size: 12px; }
            .dark-mode-switch .switch { width: 42px; height: 22px; }
            .dark-mode-switch .slider:before { height: 16px; width: 16px; }
            .dark-mode-switch input:checked + .slider:before { transform: translateX(20px); }
        }

        /* Extra small devices */
        @media (max-width: 359.98px) {
            .stat-card { padding: 8px; }
            .stat-card .stat-icon { width: 32px; height: 32px; font-size: 12px; }
            .stat-card .stat-value { font-size: 14px; }
            .stat-card .stat-label { font-size: 9px; }
            .stat-card .stat-icon { margin-right: 8px !important; }
        }
        
        /* iOS Fixes */
        @supports (-webkit-touch-callout: none) {
            .sidebar, .table-responsive { -webkit-overflow-scrolling: touch; }
            input, select, textarea { font-size: 16px !important; }
        }
    </style>
</head>
<body>
    <?php if ($user): ?>
    <!-- Sidebar Overlay - Only for logged in users -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar - Only for logged in users -->
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
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            
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
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content <?= !$user ? 'no-sidebar' : '' ?>">
        <!-- Topbar / Header -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <?php if ($user): ?>
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0">Dashboard</h4>
                <?php else: ?>
                <!-- Public Header with Logo -->
                <?php if (!empty($instansi['logo'])): ?>
                <img src="<?= htmlspecialchars($instansi['logo']) ?>" alt="Logo" class="public-logo">
                <?php endif; ?>
                <div>
                    <h5 class="mb-0 fw-bold public-title"><?= htmlspecialchars($instansi['nama_instansi'] ?? 'Sultan Banten') ?></h5>
                    <small class="public-subtitle"><i class="fas fa-chart-line me-1"></i>Dashboard Sistem Update Laporan Terpadu Harian</small>
                </div>
                <?php endif; ?>
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

                <?php if ($user): ?>
                <div class="me-2"><?= getUserAvatarHtml($user, 32) ?></div>
                <span class="d-none d-sm-inline me-1"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                <?php else: ?>
                <a href="index.php?login=1" class="btn btn-login-public">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Get pengumuman for popup (only for logged in users)
        $pengumumanPopup = [];
        
        if ($user) {
            // Get active pengumuman
            $popupQuery = "SELECT p.*, u.nama_upt FROM pengumuman p 
                LEFT JOIN upt u ON p.upt_id = u.id 
                WHERE p.aktif = 1 
                AND (p.tanggal_mulai IS NULL OR p.tanggal_mulai <= CURDATE()) 
                AND (p.tanggal_selesai IS NULL OR p.tanggal_selesai >= CURDATE())";
            
            $uptFilter = getUserUptFilter();
            if ($uptFilter) {
                $popupQuery .= " AND (p.tipe = 'Global' OR p.upt_id = " . (int)$uptFilter . ")";
            }
            $popupQuery .= " ORDER BY 
                CASE WHEN p.kategori = 'Urgent' THEN 1 
                     WHEN p.kategori = 'Pengumuman' THEN 2 
                     WHEN p.kategori = 'Berita' THEN 3 
                     ELSE 4 END, 
                p.created_at DESC";
            
            $popupStmt = $pdo->query($popupQuery);
            $pengumumanPopup = $popupStmt->fetchAll();
        }
        ?>

        <?php if (!$user): ?>
        <div class="content-wrapper">
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" class="row g-2 align-items-stretch">
                <div class="col-6 col-md-4">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($filter_tanggal) ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small mb-1">UPT</label>
                    <select name="upt_id" class="form-select">
                        <option value="0">-- Semua UPT --</option>
                        <?php foreach ($uptList as $upt): ?>
                        <option value="<?= $upt['id'] ?>" <?= $filter_upt == $upt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($upt['nama_upt']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>

        <!-- Main Statistics Cards -->
        <div class="section-header"><i class="fas fa-chart-bar me-1"></i>Statistik Utama</div>
        <div class="row mb-3 g-2">
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-1 me-2"><i class="fas fa-building"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_upt'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">UPT dengan Data</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-2 me-2"><i class="fas fa-user-lock"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_tahanan'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Tahanan</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-3 me-2"><i class="fas fa-user-times"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_narapidana'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Narapidana</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-4 me-2"><i class="fas fa-users"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_penghuni'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Penghuni</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-5 me-2"><i class="fas fa-bed"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_kapasitas'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Kapasitas</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm stat-overkapasitas h-100 <?= getOverkapasitasColor($stats['persen_overkapasitas']) ?>">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-white bg-opacity-25 me-2"><i class="fas fa-percentage"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= $stats['persen_overkapasitas'] ?>%</div>
                            <p class="stat-label-sm">Overkapasitas</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid var(--danger-500, #ef4444);">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm me-2" style="background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);"><i class="fas fa-clock"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-overstaying"><?= number_format($stats['total_overstaying'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Overstaying</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Klasifikasi Statistics Cards -->
        <div class="section-header"><i class="fas fa-gavel me-1"></i>Pidum dan Pidsus</div>
        <div class="row mb-3 g-2">
            <!-- Pidana Umum -->
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm pidum h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-7 me-2"><i class="fas fa-user"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_pidum'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Pidum</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pidana Khusus -->
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm pidsus h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-8 me-2"><i class="fas fa-gavel"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_pidsus'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Pidsus</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- UU ITE -->
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm uu-ite h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-9 me-2"><i class="fas fa-laptop"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm"><?= number_format($stats['total_uu_ite'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total UU ITE</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Penghuni Lainnya Cards -->
        <div class="section-header lainnya"><i class="fas fa-exclamation-triangle me-1"></i>Lainnya</div>
        <div class="row mb-3 g-2">
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm lainnya h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-globe"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-lainnya"><?= number_format($stats['total_wna'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total WNA</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm lainnya h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-hospital"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-lainnya"><?= number_format($stats['total_sakit'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Sakit Berkepanjangan</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card stat-card-sm lainnya h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-user-clock"></i></div>
                        <div class="flex-grow-1">
                            <div class="stat-value-sm text-lainnya"><?= number_format($stats['total_lansia'], 0, ',', '.') ?></div>
                            <p class="stat-label-sm">Total Lansia >70 Tahun</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peta Lokasi UPT -->
        <div class="chart-card">
            <h5 class="mb-3"><i class="fas fa-map-marked-alt me-2"></i>Peta Lokasi UPT di Banten</h5>
            <div id="mapBanten" style="height: 400px; width: 100%; border-radius: 8px; z-index: 1;"></div>
            <div class="mt-2 text-muted small">
                <i class="fas fa-info-circle"></i> Klik marker untuk melihat detail UPT
            </div>
        </div>

        <!-- Overkapasitas Table -->
        <div class="table-card">
            <h5 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Data Penghuni & Overkapasitas per UPT</h5>
            
            <!-- Legend -->
            <div class="mb-2" style="font-size: 10px;">
                <span class="legend-item"><span class="legend-color bg-success"></span>Longgar</span>
                <span class="legend-item"><span class="legend-color bg-info"></span>Hampir Penuh</span>
                <span class="legend-item"><span class="legend-color bg-warning"></span>Sedikit Over</span>
                <span class="legend-item"><span class="legend-color bg-danger"></span>Over</span>
                <span class="legend-item"><span class="legend-color bg-dark-red"></span>Sangat Over</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0" style="font-size: 11px; margin-bottom: 0;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-1" style="width: 30px;">No</th>
                            <th class="py-1" style="min-width: 100px;">Unit Pelaksana Teknis</th>
                            <th class="text-center py-1" style="width: 60px;">Kapasitas</th>
                            <th class="text-center py-1" style="width: 60px;">Tahanan</th>
                            <th class="text-center py-1" style="width: 60px;">Narapidana</th>
                            <th class="text-center py-1" style="width: 70px;">Total</th>
                            <th class="text-center py-1" style="width: 70px;">% Over</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($uptDataTable as $row): 
                            // Rumus: (isi - kapasitas) / kapasitas * 100
                            $persen = $row['kapasitas'] > 0 ? round((($row['total_penghuni'] - $row['kapasitas']) / $row['kapasitas']) * 100, 1) : 0;
                            $colorClass = getOverkapasitasColor($persen);
                        ?>
                        <tr>
                            <td class="text-center py-1"><?= $no++ ?></td>
                            <td class="py-1"><?= htmlspecialchars($row['nama_upt']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['kapasitas']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['total_tahanan']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['total_narapidana']) ?></td>
                            <td class="text-center py-1"><strong><?= number_format($row['total_penghuni']) ?></strong></td>
                            <td class="text-center py-1 <?= $colorClass ?>" style="font-size: 10px;"><strong><?= $persen > 0 ? '+' : '' ?><?= $persen ?>%</strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($uptDataTable)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-2">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end py-1">TOTAL</td>
                            <td class="text-center py-1"><?= number_format($stats['total_kapasitas']) ?></td>
                            <td class="text-center py-1"><?= number_format($stats['total_tahanan']) ?></td>
                            <td class="text-center py-1"><?= number_format($stats['total_narapidana']) ?></td>
                            <td class="text-center py-1"><strong><?= number_format($stats['total_penghuni']) ?></strong></td>
                            <td class="text-center py-1 <?= getOverkapasitasColor($stats['persen_overkapasitas']) ?>" style="font-size: 10px;">
                                <strong><?= $stats['persen_overkapasitas'] > 0 ? '+' : '' ?><?= $stats['persen_overkapasitas'] ?>%</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Charts Row: Penghuni per Klasifikasi & Distribusi Narapidana/Tahanan -->
        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="chart-card h-100">
                    <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Penghuni per Klasifikasi</h5>
                    <div style="height: 320px;">
                        <canvas id="chartKlasifikasi"></canvas>
                    </div>
                    <div id="klasifikasiLegend" class="mt-3 w-100"></div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="chart-card h-100 d-flex flex-column">
                    <h5 class="mb-2" style="font-size: 14px;"><i class="fas fa-chart-pie me-2"></i>Distribusi Narapidana & Tahanan</h5>
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="max-height: 260px;">
                        <canvas id="chartNarapidanaTahanan"></canvas>
                    </div>
                    <div class="text-center mt-1">
                        <span class="badge me-1" style="background: #667eea; font-size: 10px;"><i class="fas fa-user-check"></i> Napi: <?= number_format($stats['total_narapidana']) ?></span>
                        <span class="badge" style="background: #f5576c; font-size: 10px;"><i class="fas fa-user-clock"></i> Tahanan: <?= number_format($stats['total_tahanan']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Penghuni Berdasarkan Jenis Kejahatan -->
        <div class="table-card">
            <h5 class="mb-2"><i class="fas fa-table me-2"></i>Data Penghuni Berdasarkan Jenis Kejahatan per UPT</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0" style="font-size: 10px;">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="text-center align-middle py-1" style="width: 25px;">No</th>
                            <th rowspan="2" class="align-middle py-1" style="min-width: 80px;">UPT</th>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px;">Pidum</th>
                            <th colspan="9" class="text-center py-1 bg-info bg-opacity-25" style="font-size: 9px;">Pidsus</th>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px;">ITE</th>
                            <th colspan="3" class="text-center py-1 bg-warning bg-opacity-25" style="font-size: 9px;">Lainnya</th>
                        </tr>
                        <tr>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px; font-size: 9px;">Jml</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Tipikor</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Nrk<br>Br</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Nrk<br>Pg</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Teror</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Traff</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Ileg<br>Log</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Money<br>Lau</th>
                            <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Geno</th>
                            <th class="text-center py-1 bg-info bg-opacity-25 bg-success text-white" style="width: 42px; font-size: 9px;"><strong>Jml</strong></th>
                            <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px; font-size: 9px;">Jml</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">WNA</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">Sakit</th>
                            <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">Lansia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $totalPidum = 0;
                        $totalTipikor = 0; $totalNarkobaBandar = 0; $totalNarkobaPengguna = 0;
                        $totalTeroris = 0; $totalTrafficking = 0; $totalIlegalLogging = 0;
                        $totalMoneyLaundering = 0; $totalGenosida = 0; $totalPidsus = 0;
                        $totalUUITE = 0;
                        $totalWNA = 0; $totalSakit = 0; $totalLansia = 0;
                        $grandTotalPenghuni = 0;
                        
                        foreach ($jenisKejahatanData as $row): 
                            // Get WNA, Sakit, Lansia separately
                            $stmtDetail = $pdo->prepare("SELECT 
                                COALESCE(SUM(CASE WHEN klasifikasi_pidana = 'WNA' 
                                    THEN tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
                                    narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan ELSE 0 END), 0) as wna,
                                COALESCE(SUM(CASE WHEN klasifikasi_pidana = 'Sakit Berkepanjangan' 
                                    THEN tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
                                    narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan ELSE 0 END), 0) as sakit,
                                COALESCE(SUM(CASE WHEN klasifikasi_pidana = 'Lansia >70 tahun' 
                                    THEN tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
                                    narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan ELSE 0 END), 0) as lansia
                                FROM data_penghuni WHERE upt_id = ? AND tanggal = ?");
                            $stmtDetail->execute([$row['id'], $filter_tanggal]);
                            $detail = $stmtDetail->fetch();
                            
                            $wna = (int)($detail['wna'] ?? 0);
                            $sakit = (int)($detail['sakit'] ?? 0);
                            $lansia = (int)($detail['lansia'] ?? 0);
                            
                            // Calculate Pidsus total
                            $pidsusTotal = (int)$row['tipikor'] + (int)$row['narkoba_bandar'] + (int)$row['narkoba_pengguna'] + 
                                           (int)$row['teroris'] + (int)$row['trafficking'] + (int)$row['ilegal_logging'] + 
                                           (int)$row['money_laundering'] + (int)$row['genosida'];
                            
                            $totalPidum += (int)$row['pidum'];
                            $totalTipikor += (int)$row['tipikor'];
                            $totalNarkobaBandar += (int)$row['narkoba_bandar'];
                            $totalNarkobaPengguna += (int)$row['narkoba_pengguna'];
                            $totalTeroris += (int)$row['teroris'];
                            $totalTrafficking += (int)$row['trafficking'];
                            $totalIlegalLogging += (int)$row['ilegal_logging'];
                            $totalMoneyLaundering += (int)$row['money_laundering'];
                            $totalGenosida += (int)$row['genosida'];
                            $totalPidsus += $pidsusTotal;
                            $totalUUITE += (int)$row['uu_ite'];
                            $totalWNA += $wna;
                            $totalSakit += $sakit;
                            $totalLansia += $lansia;
                            $grandTotalPenghuni += (int)$row['total_penghuni'];
                            
                            $statistikTotalRow = (int)$row['pidum'] + $pidsusTotal + (int)$row['uu_ite'];
                            $p_pidum = $statistikTotalRow > 0 ? round(($row['pidum'] / $statistikTotalRow) * 100, 1) : 0;
                            $p_pidsus = $statistikTotalRow > 0 ? round(($pidsusTotal / $statistikTotalRow) * 100, 1) : 0;
                            $p_ite = $statistikTotalRow > 0 ? round(($row['uu_ite'] / $statistikTotalRow) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="text-center py-1"><?= $no++ ?></td>
                            <td class="py-1"><?= htmlspecialchars($row['nama_upt']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['pidum']) ?><br><small class="text-muted" style="font-size: 8px;"><?= $p_pidum ?>%</small></td>
                            <td class="text-center py-1"><?= number_format($row['tipikor']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['narkoba_bandar']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['narkoba_pengguna']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['teroris']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['trafficking']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['ilegal_logging']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['money_laundering']) ?></td>
                            <td class="text-center py-1"><?= number_format($row['genosida']) ?></td>
                            <td class="text-center py-1 table-success"><strong><?= number_format($pidsusTotal) ?></strong><br><small class="text-success" style="font-size: 8px;">(<?= $p_pidsus ?>%)</small></td>
                            <td class="text-center py-1"><?= number_format($row['uu_ite']) ?><br><small class="text-muted" style="font-size: 8px;"><?= $p_ite ?>%</small></td>
                            <td class="text-center py-1 text-warning-emphasis align-middle"><?= number_format($wna) ?></td>
                            <td class="text-center py-1 text-warning-emphasis align-middle"><?= number_format($sakit) ?></td>
                            <td class="text-center py-1 text-warning-emphasis align-middle"><?= number_format($lansia) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <?php $statistikGrandTotal = $totalPidum + $totalPidsus + $totalUUITE; ?>
                        <tr class="fw-bold" style="font-size: 10px;">
                            <td colspan="2" class="text-end py-1">TOTAL</td>
                            <td class="text-center py-1">
                                <?= number_format($totalPidum) ?><br>
                                <small class="text-muted" style="font-size: 8px;"><?= $statistikGrandTotal > 0 ? round(($totalPidum / $statistikGrandTotal) * 100, 1) : 0 ?>%</small>
                            </td>
                            <td class="text-center py-1"><?= number_format($totalTipikor) ?></td>
                            <td class="text-center py-1"><?= number_format($totalNarkobaBandar) ?></td>
                            <td class="text-center py-1"><?= number_format($totalNarkobaPengguna) ?></td>
                            <td class="text-center py-1"><?= number_format($totalTeroris) ?></td>
                            <td class="text-center py-1"><?= number_format($totalTrafficking) ?></td>
                            <td class="text-center py-1"><?= number_format($totalIlegalLogging) ?></td>
                            <td class="text-center py-1"><?= number_format($totalMoneyLaundering) ?></td>
                            <td class="text-center py-1"><?= number_format($totalGenosida) ?></td>
                            <td class="text-center py-1 table-success">
                                <?= number_format($totalPidsus) ?><br>
                                <small class="text-success" style="font-size: 8px;"><?= $statistikGrandTotal > 0 ? round(($totalPidsus / $statistikGrandTotal) * 100, 1) : 0 ?>%</small>
                            </td>
                            <td class="text-center py-1">
                                <?= number_format($totalUUITE) ?><br>
                                <small class="text-muted" style="font-size: 8px;"><?= $statistikGrandTotal > 0 ? round(($totalUUITE / $statistikGrandTotal) * 100, 1) : 0 ?>%</small>
                            </td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">
                                <?= number_format($totalWNA) ?>
                            </td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">
                                <?= number_format($totalSakit) ?>
                            </td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">
                                <?= number_format($totalLansia) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-1 text-muted" style="font-size: 9px;">
                <i class="fas fa-info-circle"></i> 
                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 9px;">Pidum & ITE</span> + 
                <span class="badge bg-info bg-opacity-25" style="font-size: 9px;">Pidsus</span>: Statistik | 
                <span class="badge bg-warning bg-opacity-25" style="font-size: 9px;">Lainnya</span>: Non-statistik | 
                <span class="badge bg-success bg-opacity-25" style="font-size: 9px;">Jml</span>: Total Pidsus
            </div>
        </div>


        <!-- Chart Penghuni Berdasarkan Umur dan Jenis Kelamin -->
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="chart-card h-100">
                    <h5 class="mb-3"><i class="fas fa-venus-mars me-2"></i>Penghuni Berdasarkan Umur & Jenis Kelamin</h5>
                    <!-- Legend dengan jumlah -->
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(54, 162, 235, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(54, 162, 235, 0.9); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Dewasa ♂: <span id="legend-dewasa-laki">0</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(54, 162, 235, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(54, 162, 235, 0.6); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Anak ♂: <span id="legend-anak-laki">0</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 99, 132, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(255, 99, 132, 0.9); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Dewasa ♀: <span id="legend-dewasa-perempuan">0</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 99, 132, 0.1); flex: 1 1 45%;">
                            <div style="width: 12px; height: 12px; background: rgba(255, 99, 132, 0.6); border-radius: 3px;"></div>
                            <span class="small fw-medium" style="font-size: 11px;">Anak ♀: <span id="legend-anak-perempuan">0</span></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center w-100 mt-1 pt-1" style="border-top: 1px dashed var(--border-color);">
                            <span class="small fw-bold text-primary">Total: <span id="legend-total-umur-gender">0</span></span>
                        </div>
                    </div>
                    <div style="height: 220px;">
                        <canvas id="chartUmurGender"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="chart-card h-100">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Distribusi Jenis Kelamin</h5>
                    <div class="row align-items-center">
                        <div class="col-5">
                            <div style="height: 180px;">
                                <canvas id="chartGenderDistribution"></canvas>
                            </div>
                        </div>
                        <div class="col-7">
                            <div id="genderLegendContainer">
                                <!-- Custom legend akan diisi oleh JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overstaying Donut Charts -->
        <div class="chart-card">
            <h5 class="mb-3"><i class="fas fa-clock me-2 text-danger"></i>Overstaying</h5>
            <div class="mb-3">
                <span class="legend-item"><span class="legend-color" style="background:#22c55e;"></span>Tidak Overstaying (Normal)</span>
                <span class="legend-item"><span class="legend-color" style="background:#ef4444;"></span>Overstaying</span>
            </div>
            <div class="row">
                <?php 
                $kategoriLabels = ['ai' => 'AI', 'aii' => 'AII', 'aiii' => 'AIII', 'aiv' => 'AIV', 'av' => 'AV'];
                foreach ($kategoriLabels as $key => $label): 
                    $stat = $overstayingStats[$key];
                ?>
                <div class="col-4 col-md-2-4 col-lg mb-3">
                    <div class="text-center">
                        <div style="position: relative; width: 100%; max-width: 100px; margin: 0 auto;">
                            <canvas id="chartOverstaying<?= strtoupper($key) ?>"></canvas>
                        </div>
                        <div class="mt-2">
                            <strong style="font-size: 14px;"><?= $label ?></strong>
                            <div class="small text-muted">
                                <span class="text-danger"><?= $stat['overstaying'] ?></span> / <span class="text-success"><?= $stat['tahanan'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$user): ?>
        </div><!-- content-wrapper -->
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
        © Ditjenpas Banten 2026
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Register ChartDataLabels plugin
        Chart.register(ChartDataLabels);

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;

        // Check saved preference or system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            html.setAttribute('data-theme', 'dark');
            if (darkModeToggle) darkModeToggle.checked = true;
        }

        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    html.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    html.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                }
            });
        }

        // Sidebar toggle - only if sidebar exists
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

        // Charts
        <?php if (!empty($penghuniByKlasifikasi)): ?>
        (function() {
            const labels = <?= json_encode(array_column($penghuniByKlasifikasi, 'klasifikasi_pidana')) ?>;
            const dataCounts = <?= json_encode(array_column($penghuniByKlasifikasi, 'total')) ?>;
            const colors = ['#11998e', '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'];
            const total = dataCounts.reduce((a, b) => parseInt(a) + parseInt(b), 0);

            new Chart(document.getElementById('chartKlasifikasi'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Penghuni',
                        data: dataCounts,
                        backgroundColor: colors,
                        borderRadius: 6,
                        borderWidth: 2,
                        borderColor: 'rgba(255,255,255,0.5)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    const val = context.parsed.y;
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return 'Jumlah: ' + formatNumber(val) + ' (' + pct + '%)';
                                }
                            }
                        },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: function() {
                            return document.documentElement.getAttribute('data-theme') === 'dark' ? '#e2e8f0' : '#333';
                        },
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: function(value) {
                            return formatNumber(value);
                        }
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { 
                            font: { size: 10 },
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        } 
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } } 
                    } 
                }
            },
            plugins: [ChartDataLabels]
        });

        // Populate Legend
        const legendContainer = document.getElementById('klasifikasiLegend');
        if (legendContainer) {
            let leftColHtml = '';
            let rightColHtml = '';
            const half = Math.ceil(labels.length / 2);
            
            labels.forEach((label, i) => {
                const count = dataCounts[i];
                const percent = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                const rowHtml = `
                    <tr>
                        <td style="width: 20px; padding: 4px;">
                            <span style="display: inline-block; width: 14px; height: 14px; background: ${colors[i % colors.length]}; border-radius: 3px;"></span>
                        </td>
                        <td style="font-weight: 500; padding: 4px;">${label}</td>
                        <td class="text-end" style="font-weight: 600; padding: 4px;">${formatNumber(count)}</td>
                        <td class="text-end text-muted" style="width: 60px; padding: 4px;">(${percent}%)</td>
                    </tr>
                `;
                if (i < half) {
                    leftColHtml += rowHtml;
                } else {
                    rightColHtml += rowHtml;
                }
            });
            
            legendContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-6 pe-md-1">
                        <table class="table table-sm table-borderless table-hover mb-0" style="font-size: 11px;">
                            <tbody>
                                ${leftColHtml}
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6 ps-md-1 mt-2 mt-md-0">
                        <table class="table table-sm table-borderless table-hover mb-0" style="font-size: 11px;">
                            <tbody>
                                ${rightColHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="border-top mt-2 pt-2">
                    <table class="table table-sm table-borderless mb-0 w-100" style="font-size: 12px;">
                        <tr>
                            <td style="width: 20px; padding: 4px;"></td>
                            <td style="font-weight: 600; padding: 4px;">Total</td>
                            <td class="text-end" style="font-weight: 700; padding: 4px;">${formatNumber(total)}</td>
                            <td class="text-end text-muted" style="width: 60px; padding: 4px;">(100.0%)</td>
                        </tr>
                    </table>
                </div>
            `;
        }
        })();
        <?php endif; ?>

        // Pie Chart Distribusi Narapidana & Tahanan
        new Chart(document.getElementById('chartNarapidanaTahanan'), {
            type: 'pie',
            data: {
                labels: ['Narapidana', 'Tahanan'],
                datasets: [{
                    data: [<?= $stats['total_narapidana'] ?>, <?= $stats['total_tahanan'] ?>],
                    backgroundColor: ['#667eea', '#f5576c'],
                    borderWidth: 2,
                    borderColor: 'transparent'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: { size: 11 },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + formatNumber(context.parsed) + ' (' + percentage + '%)';
                            }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 12
                        },
                        formatter: function(value, context) {
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return percentage + '%';
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });



        // Overstaying Donut Charts
        <?php foreach ($overstayingStats as $key => $stat): ?>
        (function() {
            const ctx = document.getElementById('chartOverstaying<?= strtoupper($key) ?>');
            if (!ctx) return;
            
            const normal = <?= $stat['normal'] ?>;
            const overstaying = <?= $stat['overstaying'] ?>;
            
            if (normal === 0 && overstaying === 0) {
                // Empty state
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Tidak Ada Data'],
                        datasets: [{
                            data: [1],
                            backgroundColor: ['#e9ecef'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '55%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        }
                    }
                });
            } else {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Normal', 'Overstaying'],
                        datasets: [{
                            data: [normal, overstaying],
                            backgroundColor: ['rgba(34, 197, 94, 0.9)', 'rgba(239, 68, 68, 0.9)'],
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 8,
                            hoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '55%',
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 800
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: { size: 11, weight: 'bold' },
                                bodyFont: { size: 10 },
                                padding: 8,
                                cornerRadius: 6,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return label + ': ' + value + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
        <?php endforeach; ?>

        // Chart Penghuni Berdasarkan Umur dan Jenis Kelamin (Grouped Bar Chart)
        const umurGenderData = <?= json_encode($umurGenderData) ?>;

        // Helper function for Indonesian number format
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Konversi ke integer untuk memastikan operasi matematika benar
        const intDewasaLaki = parseInt(umurGenderData.dewasa_laki) || 0;
        const intAnakLaki = parseInt(umurGenderData.anak_laki) || 0;
        const intDewasaPerempuan = parseInt(umurGenderData.dewasa_perempuan) || 0;
        const intAnakPerempuan = parseInt(umurGenderData.anak_perempuan) || 0;

        // Update legend values with percentages
        const totalUmurGender = intDewasaLaki + intAnakLaki + intDewasaPerempuan + intAnakPerempuan;
        
        function getLegendText(val) {
            const percent = totalUmurGender > 0 ? ((val / totalUmurGender) * 100).toFixed(1) : 0;
            return formatNumber(val) + ' (' + percent + '%)';
        }

        document.getElementById('legend-dewasa-laki').textContent = getLegendText(intDewasaLaki);
        document.getElementById('legend-anak-laki').textContent = getLegendText(intAnakLaki);
        document.getElementById('legend-dewasa-perempuan').textContent = getLegendText(intDewasaPerempuan);
        document.getElementById('legend-anak-perempuan').textContent = getLegendText(intAnakPerempuan);
        document.getElementById('legend-total-umur-gender').textContent = formatNumber(totalUmurGender);

        new Chart(document.getElementById('chartUmurGender'), {
            type: 'bar',
            data: {
                labels: ['Dewasa', 'Anak'],
                datasets: [
                    {
                        label: 'Laki-laki',
                        data: [intDewasaLaki, intAnakLaki],
                        backgroundColor: 'rgba(54, 162, 235, 0.9)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        hoverBackgroundColor: 'rgba(54, 162, 235, 1)',
                        hoverBorderColor: 'rgba(54, 162, 235, 1)',
                        hoverBorderWidth: 3
                    },
                    {
                        label: 'Perempuan',
                        data: [intDewasaPerempuan, intAnakPerempuan],
                        backgroundColor: 'rgba(255, 99, 132, 0.9)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        hoverBackgroundColor: 'rgba(255, 99, 132, 1)',
                        hoverBorderColor: 'rgba(255, 99, 132, 1)',
                        hoverBorderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatNumber(context.parsed.y) + ' orang';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12, weight: '500' }
                        }
                    }
                }
            }
        });

        // Chart Distribusi Jenis Kelamin (Doughnut Chart)
        // Menggunakan variabel yang sudah dikonversi ke integer di atas
        const totalLaki = intDewasaLaki + intAnakLaki;
        const totalPerempuan = intDewasaPerempuan + intAnakPerempuan;
        const totalGender = totalLaki + totalPerempuan;

        // Buat doughnut chart untuk perbandingan yang lebih jelas
        new Chart(document.getElementById('chartGenderDistribution'), {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [totalLaki, totalPerempuan],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: {
                        display: false // Sembunyikan legend default, pakai custom legend
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const percentage = totalGender > 0 ? ((value / totalGender) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Custom Legend dengan jumlah dan persentase
        const persenLaki = totalGender > 0 ? ((totalLaki / totalGender) * 100).toFixed(1) : 0;
        const persenPerempuan = totalGender > 0 ? ((totalPerempuan / totalGender) * 100).toFixed(1) : 0;

        document.getElementById('genderLegendContainer').innerHTML = `
            <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                <tbody>
                    <tr>
                        <td style="width: 20px;">
                            <span style="display: inline-block; width: 14px; height: 14px; background: rgba(54, 162, 235, 0.8); border-radius: 3px;"></span>
                        </td>
                        <td style="width: 80px; font-weight: 500;">Laki-laki</td>
                        <td class="text-end" style="font-weight: 600;">${totalLaki.toLocaleString('id-ID')}</td>
                        <td class="text-end" style="color: var(--text-secondary);">(${persenLaki}%)</td>
                    </tr>
                    <tr>
                        <td>
                            <span style="display: inline-block; width: 14px; height: 14px; background: rgba(255, 99, 132, 0.8); border-radius: 3px;"></span>
                        </td>
                        <td style="font-weight: 500;">Perempuan</td>
                        <td class="text-end" style="font-weight: 600;">${totalPerempuan.toLocaleString('id-ID')}</td>
                        <td class="text-end" style="color: var(--text-secondary);">(${persenPerempuan}%)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border-color);">
                        <td colspan="2" style="font-weight: 600;">Total Penghuni</td>
                        <td class="text-end" style="font-weight: 700;">${totalGender.toLocaleString('id-ID')}</td>
                        <td class="text-end" style="color: var(--text-secondary);">(100%)</td>
                    </tr>
                </tbody>
            </table>
        `;

        // Initialize Map - Peta Banten
        const mapBanten = L.map('mapBanten').setView([-6.4409, 106.1385], 9);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapBanten);

        // Banten Province Boundary (simplified GeoJSON)
        const bantenBoundary = {
            "type": "Feature",
            "properties": {"name": "Banten"},
            "geometry": {
                "type": "Polygon",
                "coordinates": [[
                    [105.1, -5.9], [105.3, -5.85], [105.6, -5.9], [105.8, -6.0],
                    [106.0, -6.1], [106.2, -6.2], [106.4, -6.3], [106.6, -6.5],
                    [106.8, -6.6], [106.9, -6.7], [107.0, -6.85], [106.95, -7.0],
                    [106.8, -7.1], [106.6, -7.05], [106.4, -6.95], [106.2, -6.85],
                    [106.0, -6.75], [105.8, -6.6], [105.6, -6.45], [105.4, -6.3],
                    [105.2, -6.1], [105.1, -5.9]
                ]]
            }
        };

        // Add boundary layer
        L.geoJSON(bantenBoundary, {
            style: {
                color: '#667eea',
                weight: 2,
                opacity: 0.6,
                fillColor: '#667eea',
                fillOpacity: 0.1
            }
        }).addTo(mapBanten);

        // Custom marker icon
        const uptIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); border: 2px solid white;"><i class="fas fa-building" style="font-size: 12px;"></i></div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });

        // Add markers for each UPT
        const uptLocations = <?= json_encode($uptLocations) ?>;
        const markers = [];

        uptLocations.forEach(upt => {
            const totalPenghuni = parseInt(upt.total_penghuni) || 0;
            const kapasitas = parseInt(upt.kapasitas) || 0;
            // Rumus baru: (isi - kapasitas) / kapasitas * 100
            const persen = kapasitas > 0 ? Math.round(((totalPenghuni - kapasitas) / kapasitas) * 100) : 0;
            const persenText = persen > 0 ? '+' + persen + '%' : persen + '%';
            
            // Determine color based on capacity percentage (new formula)
            let statusColor = '#22c55e'; // green (masih longgar)
            let statusText = 'Masih Longgar';
            if (persen > 100) { statusColor = '#b71c1c'; statusText = 'Sangat Over'; }
            else if (persen > 20) { statusColor = '#ef4444'; statusText = 'Over Kapasitas'; }
            else if (persen > 0) { statusColor = '#ff9800'; statusText = 'Sedikit Over'; }
            else if (persen === 0) { statusColor = '#eab308'; statusText = 'Pas Penuh'; }
            else if (persen > -25) { statusColor = '#06b6d4'; statusText = 'Hampir Penuh'; }

            const marker = L.marker([parseFloat(upt.latitude), parseFloat(upt.longitude)], { icon: uptIcon })
                .addTo(mapBanten)
                .bindPopup(`
                    <div style="min-width: 220px;">
                        <h6 style="margin: 0 0 8px 0; color: #667eea; font-weight: bold;">
                            <i class="fas fa-building"></i> ${upt.nama_upt}
                        </h6>
                        <p style="margin: 0 0 5px 0; font-size: 12px;">
                            <i class="fas fa-map-marker-alt text-danger"></i> ${upt.alamat || 'Alamat tidak tersedia'}
                        </p>
                        <hr style="margin: 8px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                            <span style="font-size: 12px;"><i class="fas fa-bed text-info"></i> Kapasitas:</span>
                            <strong style="font-size: 12px;">${formatNumber(kapasitas)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                            <span style="font-size: 12px;"><i class="fas fa-users text-primary"></i> Penghuni:</span>
                            <strong style="font-size: 12px;">${formatNumber(totalPenghuni)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 12px;"><i class="fas fa-percentage" style="color:${statusColor}"></i> Status:</span>
                            <strong style="font-size: 12px; color: ${statusColor};">${persenText} (${statusText})</strong>
                        </div>
                    </div>
                `);
            markers.push(marker);
        });

        // Fit bounds to show all markers if there are any
        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            mapBanten.fitBounds(group.getBounds().pad(0.1));
        }

        // Fix map display issue when in hidden container
        setTimeout(() => {
            mapBanten.invalidateSize();
        }, 500);
    </script>

    <?php if ($user && !empty($pengumumanPopup)): ?>
    <!-- Modal Pengumuman Popup - A4 Paper Size -->
    <div class="modal fade pengumuman-modal" id="pengumumanPopupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bullhorn me-2"></i>Pengumuman & Berita
                        <span class="pengumuman-counter" id="pengumumanCounter"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="pengumumanCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                        <div class="carousel-inner">
                            <?php 
                            $totalPengumuman = count($pengumumanPopup);
                            $index = 0;
                            foreach ($pengumumanPopup as $p): 
                            ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="pengumuman-modal-item">
                                    <div class="pengumuman-modal-item-header">
                                        <span class="pengumuman-modal-kategori kategori-<?= strtolower($p['kategori']) ?>"><?= $p['kategori'] ?></span>
                                        <span class="pengumuman-modal-judul"><?= htmlspecialchars($p['judul']) ?></span>
                                    </div>
                                    <?php if ($p['gambar'] && file_exists($p['gambar'])): ?>
                                    <div class="pengumuman-modal-image-container">
                                        <img src="<?= htmlspecialchars($p['gambar']) ?>" class="pengumuman-modal-image" alt="Gambar Pengumuman">
                                    </div>
                                    <?php endif; ?>
                                    <div class="pengumuman-modal-isi"><?= nl2br(htmlspecialchars($p['isi'])) ?></div>
                                    <div class="pengumuman-modal-meta">
                                        <span><i class="fas fa-clock"></i><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></span>
                                        <?php if (!empty($p['nama_upt'])): ?>
                                        <span><i class="fas fa-building"></i><?= htmlspecialchars($p['nama_upt']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['tipe'] == 'Global'): ?>
                                        <span><i class="fas fa-globe"></i>Global</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            $index++;
                            endforeach; 
                            ?>
                        </div>
                        <?php if ($totalPengumuman > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pengumuman-modal-footer">
                    <i class="fas fa-info-circle me-1"></i>Klik tombol <strong>X</strong> di pojok kanan atas untuk menutup
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show pengumuman popup on page load
        document.addEventListener('DOMContentLoaded', function() {
            var pengumumanModal = new bootstrap.Modal(document.getElementById('pengumumanPopupModal'));
            pengumumanModal.show();
            
            <?php if ($totalPengumuman > 1): ?>
            // Update counter on slide
            var carousel = document.getElementById('pengumumanCarousel');
            var counter = document.getElementById('pengumumanCounter');
            var totalSlides = <?= $totalPengumuman ?>;
            
            function updateCounter() {
                var activeItem = carousel.querySelector('.carousel-item.active');
                var items = carousel.querySelectorAll('.carousel-item');
                var currentIndex = Array.from(items).indexOf(activeItem) + 1;
                counter.textContent = currentIndex + ' / ' + totalSlides;
            }
            
            carousel.addEventListener('slid.bs.carousel', updateCounter);
            updateCounter();
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <!-- Welcome Modal -->
    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content border-0 shadow-lg" style="background: var(--bg-card, #fff);">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center">
                    <?php if (!empty($instansi['welcome_banner'])): ?>
                    <div class="welcome-banner-container">
                        <img src="<?= htmlspecialchars($instansi['welcome_banner']) ?>" class="img-fluid welcome-banner-img" alt="Welcome Sultan Banten">
                    </div>
                    <?php endif; ?>
                    <div class="welcome-text-container">
                        <h3 class="fw-bold mb-2" style="color: var(--text-primary, #212529);">Selamat Datang di Sultan Banten</h3>
                        <p class="mb-3" style="color: var(--text-secondary, #6c757d);">Sistem Informasi Layanan Terpadu dan Notifikasi Banten</p>
                        <button type="button" class="btn btn-primary px-5 py-2 shadow-sm" data-bs-dismiss="modal" style="border-radius: 30px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                            Mulai Jelajahi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show welcome modal if it hasn't been shown in this session
            // AND if there is a banner to show
            if (!sessionStorage.getItem('welcomeShown') && <?= !empty($instansi['welcome_banner']) ? 'true' : 'false' ?>) {
                var welcomeModalElement = document.getElementById('welcomeModal');
                if (welcomeModalElement) {
                    var welcomeModal = new bootstrap.Modal(welcomeModalElement);
                    setTimeout(function() {
                        welcomeModal.show();
                    }, 1000); 
                    sessionStorage.setItem('welcomeShown', 'true');
                }
            }
        });
    </script>

    <?php include 'includes/marquee.php'; ?>
</body>
</html>
