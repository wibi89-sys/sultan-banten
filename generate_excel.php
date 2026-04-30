<?php
/**
 * Excel Report Generator
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

require_once 'includes/functions.php';
requireLogin();
requireAccess('laporan');

$user = getCurrentUser();
$instansi = getInstansi();
$userUptFilter = getUserUptFilter();

if (!is_array($instansi)) {
    $instansi = [];
}

$reportType = $_GET['type'] ?? 'penghuni';
$uptId = isset($_GET['upt_id']) && $_GET['upt_id'] !== '' ? (int)$_GET['upt_id'] : null;
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

if ($userUptFilter) {
    $uptId = $userUptFilter;
}

$reportTitles = [
    'penghuni' => 'Laporan Data Penghuni',
    'overstaying' => 'Laporan Data Overstaying',
    'integrasi' => 'Laporan Data Integrasi',
    'rekapitulasi' => 'Laporan Rekapitulasi'
];

// Get data function
function getReportData($type, $uptId, $tanggalMulai, $tanggalAkhir) {
    global $pdo;
    
    $data = [];
    $uptClause = $uptId ? "AND upt_id = ?" : "";
    
    try {
        switch ($type) {
            case 'penghuni':
                $sql = "SELECT dp.*, u.nama_upt FROM data_penghuni dp 
                        JOIN upt u ON dp.upt_id = u.id 
                        WHERE 1=1 $uptClause AND tanggal BETWEEN ? AND ? 
                        ORDER BY u.nama_upt, dp.tanggal";
                $params = [];
                if ($uptId) $params[] = $uptId;
                $params[] = $tanggalMulai;
                $params[] = $tanggalAkhir;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
                
            case 'overstaying':
                $sql = "SELECT do.*, u.nama_upt FROM data_overstaying do 
                        JOIN upt u ON do.upt_id = u.id 
                        WHERE 1=1 $uptClause AND tanggal BETWEEN ? AND ? 
                        ORDER BY u.nama_upt, do.tanggal";
                $params = [];
                if ($uptId) $params[] = $uptId;
                $params[] = $tanggalMulai;
                $params[] = $tanggalAkhir;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
                
            case 'integrasi':
                $sql = "SELECT di.*, u.nama_upt FROM data_integrasi di 
                        JOIN upt u ON di.upt_id = u.id 
                        WHERE 1=1 $uptClause AND tanggal BETWEEN ? AND ? 
                        ORDER BY u.nama_upt, di.tanggal";
                $params = [];
                if ($uptId) $params[] = $uptId;
                $params[] = $tanggalMulai;
                $params[] = $tanggalAkhir;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
                
            case 'rekapitulasi':
                // Optimized Rekapitulasi query using subqueries to avoid Cartesian product multiplications
                $sql = "SELECT 
                        u.nama_upt, u.kapasitas,
                        (SELECT COALESCE(SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan), 0) 
                         FROM data_penghuni WHERE upt_id = u.id AND tanggal BETWEEN ? AND ?) as total_tahanan,
                        (SELECT COALESCE(SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan), 0) 
                         FROM data_penghuni WHERE upt_id = u.id AND tanggal BETWEEN ? AND ?) as total_narapidana,
                        (SELECT COALESCE(SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av + 
                                            overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av), 0) 
                         FROM data_overstaying WHERE upt_id = u.id AND tanggal BETWEEN ? AND ?) as total_overstaying,
                        (SELECT COALESCE(SUM(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + 
                                            sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi), 0) 
                         FROM data_integrasi WHERE upt_id = u.id AND tanggal BETWEEN ? AND ?) as total_integrasi
                        FROM upt u";
                
                $params = [$tanggalMulai, $tanggalAkhir, $tanggalMulai, $tanggalAkhir, $tanggalMulai, $tanggalAkhir, $tanggalMulai, $tanggalAkhir];
                
                if ($uptId) {
                    $sql .= " WHERE u.id = ?";
                    $params[] = $uptId;
                }
                
                $sql .= " ORDER BY u.nama_upt";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
        }
    } catch (Exception $e) {
        // Log error if needed: error_log($e->getMessage());
        $data = [];
    }
    
    return $data;
}

// Get UPT name
$uptName = 'Semua UPT';
if ($uptId) {
    $stmt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
    $stmt->execute([$uptId]);
    $upt = $stmt->fetch();
    $uptName = $upt['nama_upt'] ?? 'UPT';
}

// Get data
$data = getReportData($reportType, $uptId, $tanggalMulai, $tanggalAkhir);

// Clear output buffer to ensure clean file content
if (ob_get_length()) ob_clean();

// Set headers for Excel download
$filename = $reportType . '_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Expires: 0');

// Output Excel content (HTML table format that Excel can read)
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"></head>';
echo '<body>';
echo '<table border="1">';

// Header info
$namaInstansi = $instansi['nama_instansi'] ?? 'KEMENTERIAN HUKUM DAN HAK ASASI MANUSIA';
$alamat = $instansi['alamat'] ?? '';

echo '<tr><td colspan="20" align="center"><b>' . htmlspecialchars(strtoupper($namaInstansi)) . '</b></td></tr>';
if (!empty($alamat)) {
    echo '<tr><td colspan="20" align="center">' . htmlspecialchars($alamat) . '</td></tr>';
}
echo '<tr><td colspan="20" align="center"><b>' . htmlspecialchars($reportTitles[$reportType]) . '</b></td></tr>';
echo '<tr><td colspan="20">Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' - ' . date('d/m/Y', strtotime($tanggalAkhir)) . '</td></tr>';
echo '<tr><td colspan="20">UPT: ' . htmlspecialchars($uptName) . '</td></tr>';
echo '<tr><td colspan="20"></td></tr>';

// Table headers based on report type
switch ($reportType) {
    case 'penghuni':
        // Header row 1
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td rowspan="2" align="center"><b>No</b></td>';
        echo '<td rowspan="2" align="center"><b>UPT</b></td>';
        echo '<td rowspan="2" align="center"><b>Tanggal</b></td>';
        echo '<td rowspan="2" align="center"><b>Klasifikasi</b></td>';
        echo '<td colspan="4" align="center"><b>Tahanan</b></td>';
        echo '<td colspan="4" align="center"><b>Narapidana</b></td>';
        echo '<td rowspan="2" align="center"><b>Total</b></td>';
        echo '</tr>';
        // Header row 2
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td align="center"><b>DL</b></td>';
        echo '<td align="center"><b>DP</b></td>';
        echo '<td align="center"><b>AL</b></td>';
        echo '<td align="center"><b>AP</b></td>';
        echo '<td align="center"><b>DL</b></td>';
        echo '<td align="center"><b>DP</b></td>';
        echo '<td align="center"><b>AL</b></td>';
        echo '<td align="center"><b>AP</b></td>';
        echo '</tr>';
        
        // Data rows
        $no = 1;
        $grandTotal = 0;
        foreach ($data as $row) {
            $total = (int)($row['tahanan_dewasa_laki'] ?? 0) + (int)($row['tahanan_dewasa_perempuan'] ?? 0) + 
                     (int)($row['tahanan_anak_laki'] ?? 0) + (int)($row['tahanan_anak_perempuan'] ?? 0) +
                     (int)($row['narapidana_dewasa_laki'] ?? 0) + (int)($row['narapidana_dewasa_perempuan'] ?? 0) + 
                     (int)($row['narapidana_anak_laki'] ?? 0) + (int)($row['narapidana_anak_perempuan'] ?? 0);
            $grandTotal += $total;
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
            echo '<td align="center">' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
            echo '<td>' . htmlspecialchars($row['klasifikasi_pidana'] ?? '-') . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_dewasa_laki'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_dewasa_perempuan'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_anak_laki'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_anak_perempuan'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['narapidana_dewasa_laki'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['narapidana_dewasa_perempuan'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['narapidana_anak_laki'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['narapidana_anak_perempuan'] ?? 0) . '</td>';
            echo '<td align="center"><b>' . $total . '</b></td>';
            echo '</tr>';
        }
        
        // Total row
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td colspan="12" align="center"><b>TOTAL</b></td>';
        echo '<td align="center"><b>' . $grandTotal . '</b></td>';
        echo '</tr>';
        break;
        
    case 'overstaying':
        // Header row 1
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td rowspan="2" align="center"><b>No</b></td>';
        echo '<td rowspan="2" align="center"><b>UPT</b></td>';
        echo '<td rowspan="2" align="center"><b>Tanggal</b></td>';
        echo '<td colspan="5" align="center"><b>Tahanan</b></td>';
        echo '<td colspan="5" align="center"><b>Overstaying</b></td>';
        echo '<td rowspan="2" align="center"><b>Total</b></td>';
        echo '</tr>';
        // Header row 2
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td align="center"><b>AI</b></td>';
        echo '<td align="center"><b>AII</b></td>';
        echo '<td align="center"><b>AIII</b></td>';
        echo '<td align="center"><b>AIV</b></td>';
        echo '<td align="center"><b>AV</b></td>';
        echo '<td align="center"><b>AI</b></td>';
        echo '<td align="center"><b>AII</b></td>';
        echo '<td align="center"><b>AIII</b></td>';
        echo '<td align="center"><b>AIV</b></td>';
        echo '<td align="center"><b>AV</b></td>';
        echo '</tr>';
        
        // Data rows
        $no = 1;
        $grandTotal = 0;
        foreach ($data as $row) {
            $total = (int)($row['tahanan_ai'] ?? 0) + (int)($row['tahanan_aii'] ?? 0) + (int)($row['tahanan_aiii'] ?? 0) + 
                     (int)($row['tahanan_aiv'] ?? 0) + (int)($row['tahanan_av'] ?? 0) +
                     (int)($row['overstaying_ai'] ?? 0) + (int)($row['overstaying_aii'] ?? 0) + (int)($row['overstaying_aiii'] ?? 0) + 
                     (int)($row['overstaying_aiv'] ?? 0) + (int)($row['overstaying_av'] ?? 0);
            $grandTotal += $total;
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
            echo '<td align="center">' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_ai'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_aii'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_aiii'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_aiv'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['tahanan_av'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_ai'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_aii'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_aiii'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_aiv'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_av'] ?? 0) . '</td>';
            echo '<td align="center"><b>' . $total . '</b></td>';
            echo '</tr>';
        }
        
        // Total row
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td colspan="13" align="center"><b>TOTAL</b></td>';
        echo '<td align="center"><b>' . $grandTotal . '</b></td>';
        echo '</tr>';
        break;
        
    case 'integrasi':
        // Header row 1
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td rowspan="2" align="center"><b>No</b></td>';
        echo '<td rowspan="2" align="center"><b>UPT</b></td>';
        echo '<td rowspan="2" align="center"><b>Tanggal</b></td>';
        echo '<td colspan="5" align="center"><b>Usulan</b></td>';
        echo '<td colspan="5" align="center"><b>SK Terbit</b></td>';
        echo '<td rowspan="2" align="center"><b>Total</b></td>';
        echo '</tr>';
        // Header row 2
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td align="center"><b>PB</b></td>';
        echo '<td align="center"><b>CB</b></td>';
        echo '<td align="center"><b>CMB</b></td>';
        echo '<td align="center"><b>CMK</b></td>';
        echo '<td align="center"><b>Asim</b></td>';
        echo '<td align="center"><b>PB</b></td>';
        echo '<td align="center"><b>CB</b></td>';
        echo '<td align="center"><b>CMB</b></td>';
        echo '<td align="center"><b>CMK</b></td>';
        echo '<td align="center"><b>Asim</b></td>';
        echo '</tr>';
        
        // Data rows
        $no = 1;
        $grandTotal = 0;
        foreach ($data as $row) {
            $total = (int)($row['usulan_pb'] ?? 0) + (int)($row['usulan_cb'] ?? 0) + (int)($row['usulan_cmb'] ?? 0) + 
                     (int)($row['usulan_cmk'] ?? 0) + (int)($row['usulan_asimilasi'] ?? 0) +
                     (int)($row['sk_terbit_pb'] ?? 0) + (int)($row['sk_terbit_cb'] ?? 0) + (int)($row['sk_terbit_cmb'] ?? 0) + 
                     (int)($row['sk_terbit_cmk'] ?? 0) + (int)($row['sk_terbit_asimilasi'] ?? 0);
            $grandTotal += $total;
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
            echo '<td align="center">' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
            echo '<td align="center">' . (int)($row['usulan_pb'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['usulan_cb'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['usulan_cmb'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['usulan_cmk'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['usulan_asimilasi'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['sk_terbit_pb'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['sk_terbit_cb'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['sk_terbit_cmb'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['sk_terbit_cmk'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['sk_terbit_asimilasi'] ?? 0) . '</td>';
            echo '<td align="center"><b>' . $total . '</b></td>';
            echo '</tr>';
        }
        
        // Total row
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td colspan="13" align="center"><b>TOTAL</b></td>';
        echo '<td align="center"><b>' . $grandTotal . '</b></td>';
        echo '</tr>';
        break;
        
    case 'rekapitulasi':
        // Header
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td align="center"><b>No</b></td>';
        echo '<td align="center"><b>UPT</b></td>';
        echo '<td align="center"><b>Kapasitas</b></td>';
        echo '<td align="center"><b>Tahanan</b></td>';
        echo '<td align="center"><b>Narapidana</b></td>';
        echo '<td align="center"><b>Total</b></td>';
        echo '<td align="center"><b>% Kapasitas</b></td>';
        echo '<td align="center"><b>Overstaying</b></td>';
        echo '<td align="center"><b>Integrasi</b></td>';
        echo '</tr>';
        
        // Data rows
        $no = 1;
        $grandTotal = 0;
        $grandTahanan = 0;
        $grandNarapidana = 0;
        $grandOverstaying = 0;
        $grandIntegrasi = 0;
        
        foreach ($data as $row) {
            $total = (int)($row['total_tahanan'] ?? 0) + (int)($row['total_narapidana'] ?? 0);
            $kapasitas = (int)($row['kapasitas'] ?? 0);
            $persen = $kapasitas > 0 ? round(($total / $kapasitas) * 100, 1) : 0;
            
            $grandTotal += $total;
            $grandTahanan += (int)($row['total_tahanan'] ?? 0);
            $grandNarapidana += (int)($row['total_narapidana'] ?? 0);
            $grandOverstaying += (int)($row['total_overstaying'] ?? 0);
            $grandIntegrasi += (int)($row['total_integrasi'] ?? 0);
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
            echo '<td align="center">' . number_format($kapasitas) . '</td>';
            echo '<td align="center">' . number_format((int)($row['total_tahanan'] ?? 0)) . '</td>';
            echo '<td align="center">' . number_format((int)($row['total_narapidana'] ?? 0)) . '</td>';
            echo '<td align="center">' . number_format($total) . '</td>';
            echo '<td align="center">' . $persen . '%</td>';
            echo '<td align="center">' . number_format((int)($row['total_overstaying'] ?? 0)) . '</td>';
            echo '<td align="center">' . number_format((int)($row['total_integrasi'] ?? 0)) . '</td>';
            echo '</tr>';
        }
        
        // Total row
        echo '<tr style="background-color: #34495e; color: white;">';
        echo '<td colspan="2" align="center"><b>TOTAL</b></td>';
        echo '<td align="center">-</td>';
        echo '<td align="center"><b>' . number_format($grandTahanan) . '</b></td>';
        echo '<td align="center"><b>' . number_format($grandNarapidana) . '</b></td>';
        echo '<td align="center"><b>' . number_format($grandTotal) . '</b></td>';
        echo '<td align="center">-</td>';
        echo '<td align="center"><b>' . number_format($grandOverstaying) . '</b></td>';
        echo '<td align="center"><b>' . number_format($grandIntegrasi) . '</b></td>';
        echo '</tr>';
        break;
}

echo '</table>';
echo '</body>';
echo '</html>';

// Flush the output buffer
ob_end_flush();
?>
