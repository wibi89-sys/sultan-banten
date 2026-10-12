<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/database.php';

// Klasifikasi Lainnya yang tidak termasuk perhitungan statistik
$klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * CSRF Protection Functions
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Get Public Statistics for Main Website
 * Aggregates data from comprehensive report and active announcements
 * @param int|null $upt_id Optional UPT ID filter
 * @param string|null $tanggal Optional Date filter (YYYY-MM-DD)
 * @return array
 */
function getPublicStats($upt_id = null, $tanggal = null) {
    $data = [];
    
    if (!$tanggal) $tanggal = date('Y-m-d');
    
    // 1. Get Comprehensive Report Data
    $reportData = generateComprehensiveReport($tanggal, $upt_id);
    $data['statistics'] = $reportData;
    
    // 2. Get Active Announcements (Pengumuman)
    global $pdo;
    $today = date('Y-m-d');
    $sql = "
        SELECT p.*, u.nama_upt 
        FROM pengumuman p 
        LEFT JOIN upt u ON p.upt_id = u.id 
        WHERE p.aktif = 1 
        AND (p.tanggal_mulai IS NULL OR p.tanggal_mulai <= ?) 
        AND (p.tanggal_selesai IS NULL OR p.tanggal_selesai >= ?) 
    ";
    
    $params = [$today, $today];
    if ($upt_id) {
        $sql .= " AND (p.tipe = 'Global' OR p.upt_id = ?)";
        $params[] = $upt_id;
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['announcements'] = $stmt->fetchAll();
    
    // 3. Get Instansi Info
    $data['instansi'] = getInstansi();
    
    return $data;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    global $pdo;
    static $currentUser = null;
    
    if ($currentUser !== null) {
        return $currentUser;
    }
    
    startSession();
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT u.*, upt.nama_upt FROM users u LEFT JOIN upt ON u.upt_id = upt.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
    return $currentUser;
}

/**
 * Get user avatar HTML (photo or placeholder)
 * @param array $user
 * @param int $size
 * @return string
 */
function getUserAvatarHtml($user, $size = 32) {
    if (!$user) return '';
    
    $name = htmlspecialchars($user['nama_lengkap'] ?? $user['username'] ?? 'User');
    $initial = strtoupper(substr($name, 0, 1));
    $fontSize = floor($size * 0.45);
    
    if (!empty($user['foto']) && file_exists($user['foto'])) {
        return '<img src="' . htmlspecialchars($user['foto']) . '" alt="' . $name . '" style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    } else {
        // Vibrant gradient placeholder
        return '<div style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: ' . $fontSize . 'px; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-shadow: 0 1px 2px rgba(0,0,0,0.2);">' . $initial . '</div>';
    }
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Check user access to specific menu
 */
function hasAccess($menu) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    // Admin has full access
    if ($user['role'] === 'admin') return true;
    
    $accessField = 'akses_' . $menu;
    return isset($user[$accessField]) && $user[$accessField] == 1;
}

/**
 * Require access to specific menu
 */
function requireAccess($menu) {
    if (!hasAccess($menu)) {
        die('Anda tidak memiliki akses ke halaman ini.');
    }
}

/**
 * Get UPT filter for current user
 * Returns UPT ID if user is assigned to specific UPT, null otherwise
 */
function getUserUptFilter() {
    $user = getCurrentUser();
    if (!$user) return null;
    
    // Admin with no specific UPT can see all
    if ($user['role'] === 'admin' && empty($user['upt_id'])) return null;
    
    return $user['upt_id'];
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate($date) {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Get instansi data
 */
function getInstansi() {
    global $pdo;
    static $instansi = null;
    
    if ($instansi !== null) {
        return $instansi;
    }
    
    $stmt = $pdo->query("SELECT * FROM instansi LIMIT 1");
    $instansi = $stmt->fetch();
    return $instansi;
}

/**
 * Get favicon URL
 * Returns custom favicon if set, otherwise returns default
 */
function getFavicon() {
    $instansi = getInstansi();
    
    // Check if custom favicon exists
    if (!empty($instansi['favicon'])) {
        $faviconPath = __DIR__ . '/../' . $instansi['favicon'];
        if (file_exists($faviconPath)) {
            return $instansi['favicon'];
        }
    }
    
    // Return default favicon (data URI for reliability)
    return 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="%23667eea" width="100" height="100" rx="20"/><text x="50" y="68" text-anchor="middle" font-size="50" font-weight="bold" fill="white">SB</text></svg>';
}

/**
 * Get all UPT
 */
function getAllUPT() {
    global $pdo;
    
    // If user has specific UPT, only return that UPT
    $uptFilter = getUserUptFilter();
    
    if ($uptFilter) {
        $stmt = $pdo->prepare("SELECT * FROM upt WHERE id = ? ORDER BY nama_upt");
        $stmt->execute([$uptFilter]);
    } else {
        $stmt = $pdo->query("SELECT * FROM upt ORDER BY nama_upt");
    }
    
    return $stmt->fetchAll();
}

/**
 * Get UPT by ID
 */
function getUPTById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM upt WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get dashboard statistics
 * Note: Excludes 'Lainnya' category (WNA, Sakit Berkepanjangan, Lansia >70 tahun)
 */
function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    $uptFilter = getUserUptFilter();
    
    // Klasifikasi yang TIDAK termasuk perhitungan (Lainnya)
    $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
    $excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));
    
    // Total UPT
    if ($uptFilter) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM upt WHERE id = ?");
        $stmt->execute([$uptFilter]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM upt");
    }
    $stats['total_upt'] = $stmt->fetch()['total'];
    
    // Total Kapasitas
    if ($uptFilter) {
        $stmt = $pdo->prepare("SELECT SUM(kapasitas) as total FROM upt WHERE id = ?");
        $stmt->execute([$uptFilter]);
    } else {
        $stmt = $pdo->query("SELECT SUM(kapasitas) as total FROM upt");
    }
    $stats['total_kapasitas'] = $stmt->fetch()['total'] ?? 0;
    
    // Build UPT filter clause
    $uptClause = "";
    $uptParams = [];
    if ($uptFilter) {
        $uptClause = "AND upt_id = ?";
        $uptParams = [$uptFilter];
    }
    
    // Build exclude klasifikasi clause
    $klasifikasiClause = "klasifikasi_pidana NOT IN ($excludePlaceholders)";
    
    // Total Tahanan (exclude Lainnya)
    $sql = "SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) as total 
            FROM data_penghuni 
            WHERE $klasifikasiClause $uptClause";
    $stmt = $pdo->prepare($sql);
    $params = array_merge($excludeKlasifikasi, $uptParams);
    $stmt->execute($params);
    $stats['total_tahanan'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Narapidana (exclude Lainnya)
    $sql = "SELECT SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total 
            FROM data_penghuni 
            WHERE $klasifikasiClause $uptClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats['total_narapidana'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Penghuni
    $stats['total_penghuni'] = $stats['total_tahanan'] + $stats['total_narapidana'];
    
    // Persen Overkapasitas
    $stats['persen_overkapasitas'] = 0;
    if ($stats['total_kapasitas'] > 0) {
        $stats['persen_overkapasitas'] = round(($stats['total_penghuni'] / $stats['total_kapasitas']) * 100, 1);
    }
    
    // Total Overstaying
    if ($uptFilter) {
        $sql = "SELECT SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av + overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total FROM data_overstaying WHERE upt_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uptFilter]);
    } else {
        $stmt = $pdo->query("SELECT SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av + overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av) as total FROM data_overstaying");
    }
    $stats['total_overstaying'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Integrasi
    if ($uptFilter) {
        $sql = "SELECT SUM(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi) as total FROM data_integrasi WHERE upt_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uptFilter]);
    } else {
        $stmt = $pdo->query("SELECT SUM(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi) as total FROM data_integrasi");
    }
    $stats['total_integrasi'] = $stmt->fetch()['total'] ?? 0;
    
    // Breakdown Penghuni (exclude Lainnya)
    $sql = "SELECT 
        SUM(tahanan_dewasa_laki) as tahanan_dewasa_laki,
        SUM(tahanan_dewasa_perempuan) as tahanan_dewasa_perempuan,
        SUM(tahanan_anak_laki) as tahanan_anak_laki,
        SUM(tahanan_anak_perempuan) as tahanan_anak_perempuan,
        SUM(narapidana_dewasa_laki) as narapidana_dewasa_laki,
        SUM(narapidana_dewasa_perempuan) as narapidana_dewasa_perempuan,
        SUM(narapidana_anak_laki) as narapidana_anak_laki,
        SUM(narapidana_anak_perempuan) as narapidana_anak_perempuan
        FROM data_penghuni 
        WHERE $klasifikasiClause $uptClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats['penghuni_breakdown'] = $stmt->fetch();
    
    return $stats;
}

/**
 * Get data by klasifikasi for charts
 * Note: Excludes 'Lainnya' category from statistics
 */
function getPenghuniByKlasifikasi($uptClause = "", $params = []) {
    global $pdo;
    
    // Klasifikasi yang TIDAK termasuk perhitungan (Lainnya)
    $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
    $excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));
    
    $whereClause = "WHERE klasifikasi_pidana NOT IN ($excludePlaceholders)";
    $finalParams = $excludeKlasifikasi;
    
    if (!empty($uptClause)) {
        $whereClause .= " AND " . ltrim($uptClause, "WHERE ");
        $finalParams = array_merge($finalParams, $params);
    }
    
    $sql = "SELECT 
        klasifikasi_pidana,
        SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
            narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total
        FROM data_penghuni
        $whereClause
        GROUP BY klasifikasi_pidana
        ORDER BY total DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($finalParams);
    
    return $stmt->fetchAll();
}

/**
 * Get data by UPT
 * Note: Excludes 'Lainnya' category from statistics
 */
function getPenghuniByUPT($uptClause = "", $params = []) {
    global $pdo;
    
    // Klasifikasi yang TIDAK termasuk perhitungan (Lainnya)
    $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
    $excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));
    
    $whereClause = "WHERE d.klasifikasi_pidana NOT IN ($excludePlaceholders)";
    $finalParams = $excludeKlasifikasi;
    
    if (!empty($uptClause)) {
        $whereClause .= " AND " . ltrim($uptClause, "WHERE ");
        $finalParams = array_merge($finalParams, $params);
    }
    
    $sql = "SELECT 
        u.nama_upt,
        SUM(d.tahanan_dewasa_laki + d.tahanan_dewasa_perempuan + d.tahanan_anak_laki + d.tahanan_anak_perempuan + 
            d.narapidana_dewasa_laki + d.narapidana_dewasa_perempuan + d.narapidana_anak_laki + d.narapidana_anak_perempuan) as total
        FROM data_penghuni d
        JOIN upt u ON d.upt_id = u.id
        $whereClause
        GROUP BY u.id, u.nama_upt
        ORDER BY total DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($finalParams);
    
    return $stmt->fetchAll();
}

/**
 * Get UPT data for table
 * Note: Excludes 'Lainnya' category from statistics
 */
function getUptDataTable($dateClause = "", $params = [], $uptFilter = null) {
    global $pdo;
    
    // Klasifikasi yang TIDAK termasuk perhitungan (Lainnya)
    $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
    $excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));
    
    $whereClause = "dp.klasifikasi_pidana NOT IN ($excludePlaceholders)";
    $finalParams = $excludeKlasifikasi;
    
    if (!empty($dateClause)) {
        $whereClause .= " AND " . ltrim($dateClause, "WHERE ");
        $finalParams = array_merge($finalParams, $params);
    }
    
    $mainWhere = "1=1";
    if ($uptFilter) {
        $mainWhere = "u.id = ?";
        $finalParams[] = $uptFilter;
    }
    
    $sql = "SELECT 
        u.id, u.nama_upt, u.kapasitas,
        COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan), 0) as total_tahanan,
        COALESCE(SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_narapidana,
        COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
            dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni
        FROM upt u
        LEFT JOIN data_penghuni dp ON u.id = dp.upt_id AND $whereClause
        WHERE $mainWhere
        GROUP BY u.id, u.nama_upt, u.kapasitas
        ORDER BY u.nama_upt";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($finalParams);
    return $stmt->fetchAll();
}

/**
 * Log activity
 * @param string $aksi - Action type: login, logout, tambah, edit, hapus, view, export, import, setting, error
 * @param string $modul - Module: auth, penghuni, overstaying, integrasi, user, upt, instansi, wa, laporan, pengumuman
 * @param string $deskripsi - Description of the activity
 * @param array $detail - Additional details (will be JSON encoded)
 * @return bool
 */
function logActivity($aksi, $modul, $deskripsi, $detail = null) {
    global $pdo;
    
    startSession();
    
    $user = getCurrentUser();
    $userId = $user ? $user['id'] : null;
    $userName = $user ? $user['nama_lengkap'] : 'Unknown';
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $detailJson = $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_name, aksi, modul, deskripsi, detail, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $userName, $aksi, $modul, $deskripsi, $detailJson, $ipAddress, $userAgent]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get activity logs with filters
 * @param array $filters - Filter parameters
 * @param int $limit - Number of records to return
 * @param int $offset - Offset for pagination
 * @return array
 */
function getActivityLogs($filters = [], $limit = 50, $offset = 0) {
    global $pdo;
    
    $sql = "SELECT * FROM activity_logs WHERE 1=1";
    $params = [];
    
    if (!empty($filters['aksi'])) {
        $sql .= " AND aksi = ?";
        $params[] = $filters['aksi'];
    }
    
    if (!empty($filters['modul'])) {
        $sql .= " AND modul = ?";
        $params[] = $filters['modul'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['tanggal_mulai'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['tanggal_mulai'];
    }
    
    if (!empty($filters['tanggal_akhir'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['tanggal_akhir'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (deskripsi LIKE ? OR user_name LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Count activity logs with filters
 * @param array $filters - Filter parameters
 * @return int
 */
function countActivityLogs($filters = []) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) FROM activity_logs WHERE 1=1";
    $params = [];
    
    if (!empty($filters['aksi'])) {
        $sql .= " AND aksi = ?";
        $params[] = $filters['aksi'];
    }
    
    if (!empty($filters['modul'])) {
        $sql .= " AND modul = ?";
        $params[] = $filters['modul'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['tanggal_mulai'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['tanggal_mulai'];
    }
    
    if (!empty($filters['tanggal_akhir'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['tanggal_akhir'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (deskripsi LIKE ? OR user_name LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int)$stmt->fetchColumn();
}

/**
 * Get activity statistics
 * @return array
 */
function getActivityStats() {
    global $pdo;
    
    $stats = [];
    
    // Total activities
    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    // Today's activities
    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = (int)$stmt->fetchColumn();
    
    // This week's activities
    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
    $stats['this_week'] = (int)$stmt->fetchColumn();
    
    // Activities by action
    $stmt = $pdo->query("SELECT aksi, COUNT(*) as total FROM activity_logs GROUP BY aksi ORDER BY total DESC");
    $stats['by_action'] = $stmt->fetchAll();
    
    // Top active users
    $stmt = $pdo->query("SELECT user_name, COUNT(*) as total FROM activity_logs GROUP BY user_id, user_name ORDER BY total DESC LIMIT 5");
    $stats['top_users'] = $stmt->fetchAll();
    
    return $stats;
}

/**
 * Send JSON response
 */


function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Check if current time is within allowed input hours
 * Returns array with 'allowed' boolean and 'message' string
 */
function isInputTimeAllowed() {
    global $pdo;
    
    $user = getCurrentUser();
    
    // Admin can always input (bypass time restriction)
    if ($user && $user['role'] === 'admin') {
        return ['allowed' => true, 'message' => ''];
    }
    
    // Get time restriction settings from instansi
    $stmt = $pdo->query("SELECT batas_jam_aktif, jam_mulai, jam_selesai FROM instansi LIMIT 1");
    $instansi = $stmt->fetch();
    
    // If time restriction is not active, allow input
    if (!$instansi || empty($instansi['batas_jam_aktif'])) {
        return ['allowed' => true, 'message' => ''];
    }
    
    $jamMulai = $instansi['jam_mulai'] ?? '08:00:00';
    $jamSelesai = $instansi['jam_selesai'] ?? '11:00:00';
    
    // Get current time
    $currentTime = date('H:i:s');
    
    // Check if current time is within allowed range
    if ($currentTime >= $jamMulai && $currentTime <= $jamSelesai) {
        return ['allowed' => true, 'message' => ''];
    }
    
    // Format time for display
    $jamMulaiDisplay = date('H:i', strtotime($jamMulai));
    $jamSelesaiDisplay = date('H:i', strtotime($jamSelesai));
    
    return [
        'allowed' => false,
        'message' => "Input data hanya dapat dilakukan pada pukul {$jamMulaiDisplay} - {$jamSelesaiDisplay}. Saat ini pukul " . date('H:i')
    ];
}

/**
 * Get time restriction settings
 */
function getTimeRestriction() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT batas_jam_aktif, jam_mulai, jam_selesai FROM instansi LIMIT 1");
    $instansi = $stmt->fetch();
    
    if (!$instansi) {
        return [
            'active' => false,
            'jam_mulai' => '08:00',
            'jam_selesai' => '11:00'
        ];
    }
    
    return [
        'active' => (bool)($instansi['batas_jam_aktif'] ?? false),
        'jam_mulai' => substr($instansi['jam_mulai'] ?? '08:00:00', 0, 5),
        'jam_selesai' => substr($instansi['jam_selesai'] ?? '11:00:00', 0, 5)
    ];
}

/**
 * Get WhatsApp settings
 */
function getWASettings() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM wa_settings LIMIT 1");
    return $stmt->fetch();
}

/**
 * Send WhatsApp message via gateway
 * @param string $target Phone number or group ID
 * @param string $message Message to send
 * @return array Response with status
 */
function sendWAMessage($target, $message) {
    global $pdo;
    
    $settings = getWASettings();
    
    if (!$settings || empty($settings['api_key']) || empty($settings['aktif'])) {
        return [
            'success' => false,
            'message' => 'WhatsApp notification is not configured or inactive'
        ];
    }
    
    // Format target based on type
    // Group ID for Fonnte contains letters, don't format it
    // Phone numbers need formatting (remove leading 0, add 62)
    
    $isGroup = preg_match('/[a-zA-Z]/', $target); // Contains letters = likely group ID
    
    if (!$isGroup) {
        // Format as phone number
        $target = preg_replace('/^0/', '62', $target);
        $target = preg_replace('/[^0-9]/', '', $target);
    }
    // If it's a group ID, use as-is
    
    // Prepare API call based on gateway
    $apiKey = $settings['api_key'];
    $apiUrl = $settings['api_url'];
    $gateway = $settings['gateway_name'];
    
    $ch = curl_init();
    
    if ($gateway === 'fonnte') {
        // Fonnte API
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);
    } else {
        // Default/other gateway - assume similar API
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'target' => $target,
                'message' => $message,
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $apiKey,
                'Content-Type: application/json'
            ],
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $success = ($httpCode >= 200 && $httpCode < 300) && !$error;
    
    // Parse Fonnte response to check actual status
    $responseData = json_decode($response, true);
    $actualStatus = $success ? 'success' : 'failed';
    $actualMessage = 'Message sent successfully';
    
    // Fonnte specific: check response body for actual delivery status
    if ($gateway === 'fonnte' && $responseData) {
        // Fonnte returns status in response
        if (isset($responseData['status']) && $responseData['status'] === false) {
            $actualStatus = 'failed';
            $actualMessage = $responseData['reason'] ?? $responseData['message'] ?? 'Unknown error from Fonnte';
            $success = false;
        } elseif (isset($responseData['status']) && $responseData['status'] === true) {
            $actualStatus = 'success';
            $actualMessage = 'Sent successfully via Fonnte';
        }
    }
    
    return [
        'success' => $success,
        'message' => $actualMessage,
        'response' => $response,
        'error' => $error,
        'http_code' => $httpCode,
        'parsed_response' => $responseData
    ];
}

/**
 * Send reminder to all operators in the morning
 * @return array Result of the operation
 */
function sendMorningReminder() {
    global $pdo;
    
    $settings = getWASettings();
    
    if (!$settings || empty($settings['api_key']) || empty($settings['aktif'])) {
        return [
            'success' => false,
            'message' => 'WhatsApp notification is not configured or inactive',
            'sent' => 0
        ];
    }
    
    $today = date('Y-m-d');
    
    // 1. Get UPTs that have already submitted data today
    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_penghuni WHERE tanggal = ?");
    $stmt->execute([$today]);
    $donePenghuni = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_overstaying WHERE tanggal = ?");
    $stmt->execute([$today]);
    $doneOverstaying = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_integrasi WHERE tanggal = ?");
    $stmt->execute([$today]);
    $doneIntegrasi = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_bebas_harian WHERE tanggal = ?");
    $stmt->execute([$today]);
    $doneBebasHarian = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Get time restriction
    $timeRestriction = getTimeRestriction();
    $jamMulai = $timeRestriction['jam_mulai'];
    $jamSelesai = $timeRestriction['jam_selesai'];
    
    // 3. Get all operators with WhatsApp number
    $stmt = $pdo->query("SELECT u.id, u.nama_lengkap, u.no_wa, u.upt_id, up.nama_upt 
        FROM users u 
        LEFT JOIN upt up ON u.upt_id = up.id 
        WHERE u.role = 'operator' AND u.no_wa IS NOT NULL AND u.no_wa != ''");
    $operators = $stmt->fetchAll();
    
    $sent = 0;
    $failed = 0;
    $skipped = 0;
    $responses = [];
    
    foreach ($operators as $op) {
        $uptId = $op['upt_id'];
        
        // 4. Check what is missing for this UPT
        $missing = [];
        if (!in_array($uptId, $donePenghuni)) $missing[] = "Data Penghuni";
        if (!in_array($uptId, $doneOverstaying)) $missing[] = "Data Overstaying";
        if (!in_array($uptId, $doneIntegrasi)) $missing[] = "Data Integrasi";
        if (!in_array($uptId, $doneBebasHarian)) $missing[] = "Data Bebas Harian";
        
        // 5. If everything is done, skip this operator
        if (empty($missing)) {
            $skipped++;
            continue;
        }
        
        $missingText = implode(", ", $missing);
        
        // 6. Build the smart message
        $template = $settings['template_pagi'] ?? "🌅 *PENGINGAT INPUT DATA*\n\nYth. {nama}\nUPT: {upt}\n\n⚠️ *Belum Input:* {list_missing}\n\nMohon segera lengkapi data hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_";
        
        $message = str_replace(
            ['{nama}', '{upt}', '{jam_mulai}', '{jam_selesai}', '{list_missing}'],
            [$op['nama_lengkap'], $op['nama_upt'] ?? '-', $jamMulai, $jamSelesai, $missingText],
            $template
        );
        
        $result = sendWAMessage($op['no_wa'], $message);
        
        if ($result['success']) {
            $sent++;
        } else {
            $failed++;
        }
        
        $responses[] = [
            'name' => $op['nama_lengkap'],
            'upt' => $op['nama_upt'],
            'status' => $result['success'] ? 'success' : 'failed'
        ];
    }
    
    // Log the batch notification
    try {
        $stmt = $pdo->prepare("INSERT INTO wa_notification_logs (tipe, target, pesan, status, jumlah_penerima) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'reminder_pagi',
            'operator belum input',
            "Smart reminder sent to $sent operators. $skipped operators skipped (already input).",
            $failed > 0 ? 'partial' : 'success',
            $sent
        ]);
    } catch (Exception $e) {}
    
    return [
        'success' => true,
        'message' => "Reminder sent to {$sent} operators, {$skipped} skipped.",
        'sent' => $sent,
        'failed' => $failed,
        'skipped' => $skipped,
        'details' => $responses
    ];
}

/**
 * Send afternoon report of UPTs that haven't submitted data
 * @return array Result of the operation
 */
function sendAfternoonReport() {
    global $pdo;
    
    $settings = getWASettings();
    
    if (!$settings || empty($settings['api_key']) || empty($settings['aktif'])) {
        return [
            'success' => false,
            'message' => 'WhatsApp notification is not configured or inactive'
        ];
    }
    
    if (empty($settings['group_id'])) {
        return [
            'success' => false,
            'message' => 'Group ID not configured'
        ];
    }
    
    $today = date('Y-m-d');
    
    // Get all UPTs
    $stmt = $pdo->query("SELECT id, nama_upt FROM upt ORDER BY nama_upt");
    $allUPTs = $stmt->fetchAll();
    
    // Get UPTs that have submitted data today
    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_penghuni WHERE tanggal = ?");
    $stmt->execute([$today]);
    $penghuniUPTs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_overstaying WHERE tanggal = ?");
    $stmt->execute([$today]);
    $overstayingUPTs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_integrasi WHERE tanggal = ?");
    $stmt->execute([$today]);
    $integrasiUPTs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT DISTINCT upt_id FROM data_bebas_harian WHERE tanggal = ?");
    $stmt->execute([$today]);
    $bebasHarianUPTs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Find UPTs that haven't submitted each data type
    $missingPenghuni = [];
    $missingOverstaying = [];
    $missingIntegrasi = [];
    $missingBebasHarian = [];
    
    foreach ($allUPTs as $upt) {
        if (!in_array($upt['id'], $penghuniUPTs)) {
            $missingPenghuni[] = $upt['nama_upt'];
        }
        if (!in_array($upt['id'], $overstayingUPTs)) {
            $missingOverstaying[] = $upt['nama_upt'];
        }
        if (!in_array($upt['id'], $integrasiUPTs)) {
            $missingIntegrasi[] = $upt['nama_upt'];
        }
        if (!in_array($upt['id'], $bebasHarianUPTs)) {
            $missingBebasHarian[] = $upt['nama_upt'];
        }
    }
    
    // Build list of missing UPTs
    $listBelumInput = "";
    
    if (empty($missingPenghuni) && empty($missingOverstaying) && empty($missingIntegrasi) && empty($missingBebasHarian)) {
        $listBelumInput = "✅ *SEMUA UPT SUDAH MENGINPUT DATA LENGKAP*";
    } else {
        if (!empty($missingPenghuni)) {
            $listBelumInput .= "🔴 *Belum Input Data Penghuni* (" . count($missingPenghuni) . " UPT):\n";
            foreach ($missingPenghuni as $name) {
                $listBelumInput .= "   • $name\n";
            }
            $listBelumInput .= "\n";
        }
        
        if (!empty($missingOverstaying)) {
            $listBelumInput .= "🔴 *Belum Input Data Overstaying* (" . count($missingOverstaying) . " UPT):\n";
            foreach ($missingOverstaying as $name) {
                $listBelumInput .= "   • $name\n";
            }
            $listBelumInput .= "\n";
        }
        
        if (!empty($missingIntegrasi)) {
            $listBelumInput .= "🔴 *Belum Input Data Integrasi* (" . count($missingIntegrasi) . " UPT):\n";
            foreach ($missingIntegrasi as $name) {
                $listBelumInput .= "   • $name\n";
            }
            $listBelumInput .= "\n";
        }

        if (!empty($missingBebasHarian)) {
            $listBelumInput .= "🔴 *Belum Input Data Bebas Harian* (" . count($missingBebasHarian) . " UPT):\n";
            foreach ($missingBebasHarian as $name) {
                $listBelumInput .= "   • $name\n";
            }
            $listBelumInput .= "\n";
        }
    }
    
    // Get template and replace placeholders
    $template = $settings['template_siang'] ?? "📋 *LAPORAN INPUT DATA HARIAN*\n📅 Tanggal: {tanggal}\n\n{list_belum_input}\n\n_Sultan Banten Ditjenpas_";
    $message = str_replace(
        ['{tanggal}', '{list_belum_input}'],
        [formatIndonesianDate($today), $listBelumInput],
        $template
    );
    
    // Send to group
    $result = sendWAMessage($settings['group_id'], $message);
    
    // Log the notification
    try {
        $stmt = $pdo->prepare("INSERT INTO wa_notification_logs (tipe, target, pesan, status, jumlah_penerima) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'reminder_siang',
            $settings['group_id'],
            $message,
            $result['success'] ? 'success' : 'failed',
            1
        ]);
    } catch (Exception $e) {}
    
    return [
        'success' => $result['success'],
        'message' => $result['success'] ? 'Report sent to group successfully' : 'Failed to send report',
        'missing_penghuni' => count($missingPenghuni),
        'missing_overstaying' => count($missingOverstaying),
        'missing_integrasi' => count($missingIntegrasi),
        'response' => $result['response'] ?? null,
        'error' => $result['error'] ?? null
    ];
}

/**
 * Get default template for WA scheduled reports
 * @return string Default template
 */
function getDefaultTemplate() {
    return "📊 *LAPORAN HARIAN SULTAN BANTEN*
📅 {tanggal}
⏰ {waktu_kirim} WIB

👤 *DATA PENGAUNI*
├─ Narapidana: {narapidana}
├─ Tahanan: {tahanan}
├─ Total: {isi_penghuni}
└─ Kapasitas: {kapasitas} ({persen_overkapasitas}%)

👶 *KATEGORI UMUR*
├─ Anak: {anak}
└─ Dewasa: {dewasa}

⚖️ *JENIS PIDANA*
├─ Pidum + UU ITE: {pidum_ite}
├─ Pidsus: {pidsus}
└─ Detail: Tipikor {tipikor}, Narkoba {narkoba_bandar}/{narkoba_pengguna}

📋 *KATEGORI LAINNYA*
├─ WNA: {wna}
├─ Lansia >70 th: {lansia}
├─ Sakit Berkepanjangan: {sakit}
└─ UU ITE: {uu_ite}

👥 *JENIS KELAMIN*
├─ Laki-laki: {laki_laki}
└─ Perempuan: {perempuan}

⏰ *OVERSTAYING*
├─ AI: {overstaying_ai}
├─ AII: {overstaying_aii}
├─ AIII: {overstaying_aiii}
├─ AIV: {overstaying_aiv}
├─ AV: {overstaying_av}
└─ Total: {total_overstaying}

🔄 *INTEGRASI*
├─ PB: {sk_pb}
├─ CB: {sk_cb}
├─ CMB: {sk_cmb}
├─ Asimilasi: {sk_asimilasi}
└─ Total: {total_sk}

_Sultan Banten Ditjenpas_";
}

/**
 * Get template variables with descriptions
 * @return array Variables and their descriptions
 */
function getTemplateVariables() {
    return [
        // Umum
        'tanggal' => 'Tanggal laporan (Hari, 26 April 2026)',
        'tanggal_indo' => 'Tanggal laporan tanpa hari (26 April 2026)',
        'waktu_kirim' => 'Waktu pengiriman (HH:MM)',
        
        // Data Penghuni
        'narapidana' => 'Total narapidana',
        'tahanan' => 'Total tahanan',
        'isi_penghuni' => 'Total penghuni (tahanan + narapidana)',
        'kapasitas' => 'Total kapasitas UPT',
        'persen_overkapasitas' => 'Persentase overkapasitas',
        'tingkat_hunian' => 'Persentase tingkat hunian',
        
        // Kategori Umur
        'anak' => 'Total penghuni anak (tahanan + narapidana, laki + perempuan)',
        'dewasa' => 'Total penghuni dewasa (tahanan + narapidana, laki + perempuan)',
        
        // Jenis Kelamin
        'laki_laki' => 'Total penghuni laki-laki',
        'perempuan' => 'Total penghuni perempuan',
        
        // Jenis Pidana - Kelompok
        'pidum_ite' => 'Total Pidana Umum + UU ITE',
        'pidsus' => 'Total Pidana Khusus (Pidsus)',
        
        // Jenis Pidana - Detail
        'tipikor' => 'Tindak Pidana Korupsi',
        'narkoba_bandar' => 'Narkoba Bandar',
        'narkoba_pengguna' => 'Narkoba Pengguna',
        'teroris' => 'Teroris',
        'ilegal_logging' => 'Ilegal Logging',
        'trafficking' => 'Trafficking',
        'money_laundering' => 'Money Laundering',
        'genosida' => 'Genosida',
        
        // Kategori Lainnya
        'wna' => 'Warga Negara Asing',
        'lansia' => 'Lansia >70 tahun',
        'sakit' => 'Sakit Berkepanjangan',
        'uu_ite' => 'Pidana UU ITE',
        'pidana_mati' => 'Pidana Mati',
        'seumur_hidup' => 'Seumur Hidup',
        
        // Overstaying
        'overstaying_ai' => 'Overstaying kategori AI',
        'overstaying_aii' => 'Overstaying kategori AII',
        'overstaying_aiii' => 'Overstaying kategori AIII',
        'overstaying_aiv' => 'Overstaying kategori AIV',
        'overstaying_av' => 'Overstaying kategori AV',
        'total_overstaying' => 'Total semua overstaying',
        
        // Integrasi
        'sk_pb' => 'SK Terbit Pembebasan Bersyarat',
        'sk_cb' => 'SK Terbit Cuti Bersyarat',
        'sk_cmb' => 'SK Terbit Cuti Menjelang Bebas',
        'sk_asimilasi' => 'SK Terbit Asimilasi',
        'total_sk' => 'Total SK terbit',

        // Bebas Harian
        'bebas_murni' => 'Total Bebas Murni',
        'bebas_pb' => 'Total Bebas PB',
        'bebas_cb' => 'Total Bebas CB',
        'bebas_cmb' => 'Total Bebas CMB',
        'bebas_asimilasi' => 'Total Bebas Asimilasi',
        'bebas_bdh' => 'Total Bebas Demi Hukum (BDH)',
        'bebas_lainnya' => 'Total Bebas Lainnya',
        'total_bebas' => 'Total Semua Bebas Hari Ini',
    ];
}

/**
 * Generate comprehensive report data for WA template
 * @param string $tanggal Date for report (default: today)
 * @param int|null $upt_id Optional UPT ID filter
 * @return array Report data
 */
function generateComprehensiveReport($tanggal = null, $upt_id = null) {
    global $pdo;
    
    if (!$tanggal) {
        $tanggal = date('Y-m-d');
    }
    
    $report = [];
    
    // Klasifikasi categories
    $klasifikasiPidum = ['Pidana Umum'];
    $klasifikasiPidsus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];
    $klasifikasiUUITE = ['UU ITE'];
    $klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
    
    // 1. Get UPT count
    $sql = "SELECT COUNT(DISTINCT upt_id) as total FROM (
            SELECT upt_id FROM data_penghuni WHERE tanggal = ?
            UNION
            SELECT upt_id FROM data_overstaying WHERE tanggal = ?
            UNION
            SELECT upt_id FROM data_integrasi WHERE tanggal = ?
        ) as combined";
    $params = [$tanggal, $tanggal, $tanggal];
    if ($upt_id) {
        $sql = "SELECT COUNT(*) as total FROM upt WHERE id = ?";
        $params = [$upt_id];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $report['total_upt'] = (int)$stmt->fetch()['total'];
    
    // 2. Get total kapasitas
    if ($upt_id) {
        $stmt = $pdo->prepare("SELECT kapasitas FROM upt WHERE id = ?");
        $stmt->execute([$upt_id]);
    } else {
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
        $stmt->execute([$tanggal, $tanggal, $tanggal]);
    }
    $resKapasitas = $stmt->fetch();
    $report['kapasitas'] = (int)($resKapasitas['total'] ?? $resKapasitas['kapasitas'] ?? 0);
    
    // Get penghuni data (exclude Lainnya)
    $excludeKlasifikasi = $klasifikasiLainnya;
    $excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));
    
    $sql = "SELECT 
        SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) as total_tahanan,
        SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total_narapidana,
        SUM(tahanan_dewasa_laki + narapidana_dewasa_laki + tahanan_anak_laki + narapidana_anak_laki) as total_laki,
        SUM(tahanan_dewasa_perempuan + narapidana_dewasa_perempuan + tahanan_anak_perempuan + narapidana_anak_perempuan) as total_perempuan,
        SUM(tahanan_anak_laki + tahanan_anak_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total_anak,
        SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan) as total_dewasa
        FROM data_penghuni 
        WHERE klasifikasi_pidana NOT IN ($excludePlaceholders) AND tanggal = ?";
    $params = array_merge($excludeKlasifikasi, [$tanggal]);
    
    if ($upt_id) {
        $sql .= " AND upt_id = ?";
        $params[] = $upt_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $penghuniData = $stmt->fetch();
    
    $report['tahanan'] = (int)($penghuniData['total_tahanan'] ?? 0);
    $report['narapidana'] = (int)($penghuniData['total_narapidana'] ?? 0);
    $report['isi_penghuni'] = $report['tahanan'] + $report['narapidana'];
    $report['laki_laki'] = (int)($penghuniData['total_laki'] ?? 0);
    $report['perempuan'] = (int)($penghuniData['total_perempuan'] ?? 0);
    $report['anak'] = (int)($penghuniData['total_anak'] ?? 0);
    $report['dewasa'] = (int)($penghuniData['total_dewasa'] ?? 0);
    
    // Calculate overkapasitas and tingkat hunian percentage
    if ($report['kapasitas'] > 0) {
        $report['persen_overkapasitas'] = round((($report['isi_penghuni'] - $report['kapasitas']) / $report['kapasitas']) * 100, 1);
        $report['tingkat_hunian'] = round(($report['isi_penghuni'] / $report['kapasitas']) * 100, 1);
    } else {
        $report['persen_overkapasitas'] = 0;
        $report['tingkat_hunian'] = 0;
    }
    
    // Get by klasifikasi
    // Pidum + UU ITE
    $sql = "SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
        narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total 
        FROM data_penghuni 
        WHERE klasifikasi_pidana IN ('Pidana Umum', 'UU ITE') AND tanggal = ?";
    $params = [$tanggal];
    if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $report['pidum_ite'] = (int)($stmt->fetch()['total'] ?? 0);
    
    // Pidsus
    $pidsusPlaceholders = implode(',', array_fill(0, count($klasifikasiPidsus), '?'));
    $sql = "SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
        narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total 
        FROM data_penghuni 
        WHERE klasifikasi_pidana IN ($pidsusPlaceholders) AND tanggal = ?";
    $params = array_merge($klasifikasiPidsus, [$tanggal]);
    if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $report['pidsus'] = (int)($stmt->fetch()['total'] ?? 0);
    
    // Individual pidsus types
    $klasifikasiMap = [
        'tipikor' => 'Tipikor',
        'narkoba_bandar' => 'Narkoba Bandar',
        'narkoba_pengguna' => 'Narkoba Pengguna',
        'teroris' => 'Teroris',
        'ilegal_logging' => 'Ilegal Logging',
        'trafficking' => 'Trafficking',
        'money_laundering' => 'Money Laundering',
        'genosida' => 'Genosida',
    ];
    
    foreach ($klasifikasiMap as $key => $klasifikasi) {
        $sql = "SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
            narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total 
            FROM data_penghuni 
            WHERE klasifikasi_pidana = ? AND tanggal = ?";
        $params = [$klasifikasi, $tanggal];
        if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $report[$key] = (int)($stmt->fetch()['total'] ?? 0);
    }
    
    // Kategori Lainnya (WNA, Lansia, Sakit, UU ITE)
    $lainnyaArr = ['WNA' => 'wna', 'Lansia >70 tahun' => 'lansia', 'Sakit Berkepanjangan' => 'sakit', 'UU ITE' => 'uu_ite', 'Pidana Mati' => 'pidana_mati', 'Seumur Hidup' => 'seumur_hidup'];
    foreach ($lainnyaArr as $dbVal => $repKey) {
        $sql = "SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + 
            narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total 
            FROM data_penghuni WHERE klasifikasi_pidana = ? AND tanggal = ?";
        $params = [$dbVal, $tanggal];
        if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $report[$repKey] = (int)($stmt->fetch()['total'] ?? 0);
    }
    
    // Overstaying data
    $sql = "SELECT SUM(overstaying_ai) as ai, SUM(overstaying_aii) as aii, SUM(overstaying_aiii) as aiii, 
        SUM(overstaying_aiv) as aiv, SUM(overstaying_av) as av
        FROM data_overstaying WHERE tanggal = ?";
    $params = [$tanggal];
    if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $overData = $stmt->fetch();
    $report['overstaying_ai'] = (int)($overData['ai'] ?? 0);
    $report['overstaying_aii'] = (int)($overData['aii'] ?? 0);
    $report['overstaying_aiii'] = (int)($overData['aiii'] ?? 0);
    $report['overstaying_aiv'] = (int)($overData['aiv'] ?? 0);
    $report['overstaying_av'] = (int)($overData['av'] ?? 0);
    $report['total_overstaying'] = $report['overstaying_ai'] + $report['overstaying_aii'] + $report['overstaying_aiii'] + $report['overstaying_aiv'] + $report['overstaying_av'];
    
    // Integrasi data
    $sql = "SELECT SUM(sk_terbit_pb) as pb, SUM(sk_terbit_cb) as cb, SUM(sk_terbit_cmb) as cmb, SUM(sk_terbit_asimilasi) as asimilasi
        FROM data_integrasi WHERE tanggal = ?";
    $params = [$tanggal];
    if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $intData = $stmt->fetch();
    $report['sk_pb'] = (int)($intData['pb'] ?? 0);
    $report['sk_cb'] = (int)($intData['cb'] ?? 0);
    $report['sk_cmb'] = (int)($intData['cmb'] ?? 0);
    $report['sk_asimilasi'] = (int)($intData['asimilasi'] ?? 0);
    $report['total_sk'] = $report['sk_pb'] + $report['sk_cb'] + $report['sk_cmb'] + $report['sk_asimilasi'];

    // Bebas Harian data
    $sql = "SELECT SUM(bebas_murni) as bm, SUM(pb) as pb, SUM(cb) as cb, SUM(cmb) as cmb, SUM(asimilasi) as asim, SUM(bdh) as bdh, SUM(lainnya) as lny
        FROM data_bebas_harian WHERE tanggal = ?";
    $params = [$tanggal];
    if ($upt_id) { $sql .= " AND upt_id = ?"; $params[] = $upt_id; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $bebasData = $stmt->fetch();
    $report['bebas_murni'] = (int)($bebasData['bm'] ?? 0);
    $report['bebas_pb'] = (int)($bebasData['pb'] ?? 0);
    $report['bebas_cb'] = (int)($bebasData['cb'] ?? 0);
    $report['bebas_cmb'] = (int)($bebasData['cmb'] ?? 0);
    $report['bebas_asimilasi'] = (int)($bebasData['asim'] ?? 0);
    $report['bebas_bdh'] = (int)($bebasData['bdh'] ?? 0);
    $report['bebas_lainnya'] = (int)($bebasData['lny'] ?? 0);
    $report['total_bebas'] = $report['bebas_murni'] + $report['bebas_pb'] + $report['bebas_cb'] + $report['bebas_cmb'] + $report['bebas_asimilasi'] + $report['bebas_bdh'] + $report['bebas_lainnya'];
    
    // Format tanggal (Indonesian Formats)
    $report['tanggal'] = formatIndonesianDate($tanggal, true);
    $report['tanggal_indo'] = formatIndonesianDate($tanggal, false);
    $report['waktu_kirim'] = date('H:i');
    
    return $report;
}

/**
 * Format template message with report data
 * @param string $template Template with placeholders
 * @param array $data Report data
 * @return string Formatted message
 */
function formatTemplateMessage($template, $data) {
    $variables = array_keys(getTemplateVariables());
    
    foreach ($variables as $var) {
        $placeholder = '{' . $var . '}';
        $value = $data[$var] ?? 0;
        $template = str_replace($placeholder, $value, $template);
    }
    
    return $template;
}

/**
 * Send scheduled report
 * @param int $scheduleId Schedule ID
 * @return array Result of the operation
 */
function sendScheduledReport($scheduleId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM wa_scheduled_reports WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        return ['success' => false, 'message' => 'Schedule not found'];
    }
    
    // Generate report data
    $reportData = generateComprehensiveReport();
    
    // Get template
    $template = $schedule['template_pesan'];
    if (empty($template)) {
        $template = getDefaultTemplate();
    }
    
    // Format message
    $message = formatTemplateMessage($template, $reportData);
    
    // Send to group if not skipped
    $result = ['success' => true]; // Default true if skipped
    if (!empty($schedule['group_id']) && $schedule['group_id'] !== '-') {
        $result = sendWAMessage($schedule['group_id'], $message);
    }
    
    // Update last sent
    if ($result['success']) {
        $stmt = $pdo->prepare("UPDATE wa_scheduled_reports SET last_sent = NOW() WHERE id = ?");
        $stmt->execute([$scheduleId]);
    }
    
    // Log the send
    try {
        $stmt = $pdo->prepare("INSERT INTO wa_scheduled_report_logs (report_id, status, response) VALUES (?, ?, ?)");
        $stmt->execute([
            $scheduleId,
            $result['success'] ? 'success' : 'failed',
            $result['response'] ?? null
        ]);
    } catch (Exception $e) {}
    
    // Send to pimpinan if enabled
    if (!empty($schedule['kirim_ke_pimpinan']) && $result['success']) {
        $stmt = $pdo->query("SELECT * FROM wa_pimpinan WHERE aktif = 1 ORDER BY urutan ASC");
        $pimpinanList = $stmt->fetchAll();
        
        foreach ($pimpinanList as $pimpinan) {
            sendWAMessage($pimpinan['no_wa'], $message);
        }
    }
    
    return $result;
}

/**
 * Process all due scheduled reports
 * @return array Results of processing
 */
function processScheduledReports() {
    global $pdo;
    
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    $currentDayNum = date('w'); // 0 (Sunday) to 6 (Saturday)
    $isWeekday = ($currentDayNum >= 1 && $currentDayNum <= 5);
    
    // Get all active schedules that haven't been sent today
    $stmt = $pdo->prepare("SELECT * FROM wa_scheduled_reports 
        WHERE aktif = 1 
        AND jam_kirim <= ? 
        AND (last_sent IS NULL OR DATE(last_sent) < ?)");
    $stmt->execute([$currentTime, $today]);
    $schedules = $stmt->fetchAll();
    
    $results = [];
    foreach ($schedules as $s) {
        $shouldSend = false;
        $hari = $s['hari'];
        
        if ($hari === 'daily') {
            $shouldSend = true;
        } elseif ($hari === 'weekdays' && $isWeekday) {
            $shouldSend = true;
        } elseif ((string)$hari === (string)$currentDayNum) {
            $shouldSend = true;
        }
        
        if ($shouldSend) {
            $res = sendScheduledReport($s['id']);
            $results[] = [
                'id' => $s['id'],
                'nama' => $s['nama_laporan'],
                'status' => $res['success'] ? 'success' : 'failed',
                'message' => $res['message'] ?? ''
            ];
        }
    }
    
    return $results;
}


/**
 * Get active announcements that should be shown in marquee
 * @param int|null $upt_id Filter by UPT ID
 * @return array List of announcements
 */
function getMarqueeAnnouncements($upt_id = null) {
    global $pdo;
    
    $today = date('Y-m-d');
    $sql = "SELECT judul, isi, kategori FROM pengumuman 
            WHERE aktif = 1 
            AND tampilkan_marquee = 1 
            AND (tanggal_mulai IS NULL OR tanggal_mulai <= ?) 
            AND (tanggal_selesai IS NULL OR tanggal_selesai >= ?) 
            AND (tipe = 'Global' OR upt_id = ?) 
            ORDER BY created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today, $today, $upt_id]);
    return $stmt->fetchAll();
}

/**
 * Format date to Indonesian long format
 * @param string $date Date string
 * @param bool $includeDay Whether to include day name (e.g. Jumat)
 * @return string Formatted date
 */
function formatIndonesianDate($date, $includeDay = true) {
    if (!$date) return '-';
    
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $monthName = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    if ($includeDay) {
        $dayName = $days[date('w', $timestamp)];
        return "$dayName, $day $monthName $year";
    }
    
    return "$day $monthName $year";
}
