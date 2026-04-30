<?php
/**
 * Export Data to Excel - For Input Data Pages
 * Exports data based on current filter settings
 */

require_once 'includes/functions.php';
requireLogin();

// Get export type
$type = $_GET['type'] ?? 'penghuni';

// Check access based on type
switch ($type) {
    case 'penghuni':
        requireAccess('penghuni');
        break;
    case 'overstaying':
        requireAccess('overstaying');
        break;
    case 'integrasi':
        requireAccess('integrasi');
        break;
    default:
        die('Invalid export type');
}

$user = getCurrentUser();
$instansi = getInstansi();

if (!is_array($instansi)) {
    $instansi = [];
}

// Get filter parameters
$tab = $_GET['tab'] ?? 'all';
$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSampai = $_GET['tanggal_sampai'] ?? '';
$filterUpt = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : 0;

// Klasifikasi options (same as penghuni.php)
$klasifikasiUmum = ['Pidana Umum'];
$klasifikasiKhusus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];
$klasifikasiUUITE = ['UU ITE'];
$klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun'];
$klasifikasiUtama = array_merge($klasifikasiUmum, $klasifikasiKhusus, $klasifikasiUUITE);
$allKlasifikasi = array_merge($klasifikasiUtama, $klasifikasiLainnya);

// Build WHERE clause based on filters
function buildWhereClause($type, $tab, $tanggalMulai, $tanggalSampai, $filterUpt, $allKlasifikasi, $klasifikasiUtama, $klasifikasiLainnya) {
    $whereClause = "";
    $params = [];
    
    if ($type === 'penghuni') {
        // Tab filter for klasifikasi
        if ($tab === 'utama') {
            $placeholders = implode(',', array_fill(0, count($klasifikasiUtama), '?'));
            $whereClause = "WHERE klasifikasi_pidana IN ($placeholders)";
            $params = $klasifikasiUtama;
        } elseif ($tab === 'lainnya') {
            $placeholders = implode(',', array_fill(0, count($klasifikasiLainnya), '?'));
            $whereClause = "WHERE klasifikasi_pidana IN ($placeholders)";
            $params = $klasifikasiLainnya;
        } elseif ($tab !== 'all' && in_array($tab, $allKlasifikasi)) {
            $whereClause = "WHERE klasifikasi_pidana = ?";
            $params[] = $tab;
        }
    }
    
    // Date filter
    if ($tanggalMulai && $tanggalSampai) {
        if ($whereClause) {
            $whereClause .= " AND tanggal BETWEEN ? AND ?";
        } else {
            $whereClause = "WHERE tanggal BETWEEN ? AND ?";
        }
        $params[] = $tanggalMulai;
        $params[] = $tanggalSampai;
    } elseif ($tanggalMulai) {
        if ($whereClause) {
            $whereClause .= " AND tanggal >= ?";
        } else {
            $whereClause = "WHERE tanggal >= ?";
        }
        $params[] = $tanggalMulai;
    } elseif ($tanggalSampai) {
        if ($whereClause) {
            $whereClause .= " AND tanggal <= ?";
        } else {
            $whereClause = "WHERE tanggal <= ?";
        }
        $params[] = $tanggalSampai;
    }
    
    // UPT filter
    if ($filterUpt > 0) {
        if ($whereClause) {
            $whereClause .= " AND upt_id = ?";
        } else {
            $whereClause = "WHERE upt_id = ?";
        }
        $params[] = $filterUpt;
    }
    
    return ['clause' => $whereClause, 'params' => $params];
}

// Get UPT name
$uptName = 'Semua UPT';
if ($filterUpt > 0) {
    $stmt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
    $stmt->execute([$filterUpt]);
    $upt = $stmt->fetch();
    $uptName = $upt['nama_upt'] ?? 'UPT';
}

// Build filter info and get data
$filterInfo = buildWhereClause($type, $tab, $tanggalMulai, $tanggalSampai, $filterUpt, $allKlasifikasi, $klasifikasiUtama, $klasifikasiLainnya);
$whereClause = $filterInfo['clause'];
$params = $filterInfo['params'];

// Set filename
$filename = 'data_' . $type . '_' . date('Ymd_His') . '.xls';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Output Excel content
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"></head>';
echo '<body>';
echo '<table border="1">';

// Header info
$namaInstansi = $instansi['nama_instansi'] ?? 'KEMENTERIAN HUKUM DAN HAK ASASI MANUSIA';
$alamat = $instansi['alamat'] ?? '';

$reportTitles = [
    'penghuni' => 'Data Penghuni UPT',
    'overstaying' => 'Data Overstaying UPT',
    'integrasi' => 'Data Integrasi UPT'
];

echo '<tr><td colspan="20" align="center"><b>' . strtoupper($namaInstansi) . '</b></td></tr>';
if (!empty($alamat)) {
    echo '<tr><td colspan="20" align="center">' . $alamat . '</td></tr>';
}
echo '<tr><td colspan="20" align="center"><b>' . ($reportTitles[$type] ?? 'Data') . '</b></td></tr>';

// Filter info
$filterText = [];
if ($tanggalMulai || $tanggalSampai) {
    $filterText[] = 'Periode: ' . ($tanggalMulai ? date('d/m/Y', strtotime($tanggalMulai)) : '-') . ' - ' . ($tanggalSampai ? date('d/m/Y', strtotime($tanggalSampai)) : '-');
}
$filterText[] = 'UPT: ' . $uptName;
if ($tab !== 'all' && $type === 'penghuni') {
    if ($tab === 'utama') $filterText[] = 'Klasifikasi: Data Penghuni (Utama)';
    elseif ($tab === 'lainnya') $filterText[] = 'Klasifikasi: Lainnya';
    else $filterText[] = 'Klasifikasi: ' . $tab;
}

echo '<tr><td colspan="20">' . implode(' | ', $filterText) . '</td></tr>';
echo '<tr><td colspan="20"></td></tr>';

// Get and output data based on type
switch ($type) {
    case 'penghuni':
        $sql = "SELECT dp.*, u.nama_upt FROM data_penghuni dp JOIN upt u ON dp.upt_id = u.id $whereClause ORDER BY dp.tanggal DESC, dp.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
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
        $totals = ['tdl' => 0, 'tdp' => 0, 'tal' => 0, 'tap' => 0, 'ndl' => 0, 'ndp' => 0, 'nal' => 0, 'nap' => 0];
        
        foreach ($data as $row) {
            $total = (int)($row['tahanan_dewasa_laki'] ?? 0) + (int)($row['tahanan_dewasa_perempuan'] ?? 0) + 
                     (int)($row['tahanan_anak_laki'] ?? 0) + (int)($row['tahanan_anak_perempuan'] ?? 0) +
                     (int)($row['narapidana_dewasa_laki'] ?? 0) + (int)($row['narapidana_dewasa_perempuan'] ?? 0) + 
                     (int)($row['narapidana_anak_laki'] ?? 0) + (int)($row['narapidana_anak_perempuan'] ?? 0);
            $grandTotal += $total;
            
            $totals['tdl'] += (int)($row['tahanan_dewasa_laki'] ?? 0);
            $totals['tdp'] += (int)($row['tahanan_dewasa_perempuan'] ?? 0);
            $totals['tal'] += (int)($row['tahanan_anak_laki'] ?? 0);
            $totals['tap'] += (int)($row['tahanan_anak_perempuan'] ?? 0);
            $totals['ndl'] += (int)($row['narapidana_dewasa_laki'] ?? 0);
            $totals['ndp'] += (int)($row['narapidana_dewasa_perempuan'] ?? 0);
            $totals['nal'] += (int)($row['narapidana_anak_laki'] ?? 0);
            $totals['nap'] += (int)($row['narapidana_anak_perempuan'] ?? 0);
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . ($row['nama_upt'] ?? '-') . '</td>';
            echo '<td align="center">' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
            echo '<td>' . ($row['klasifikasi_pidana'] ?? '-') . '</td>';
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
        echo '<td colspan="4" align="center"><b>TOTAL</b></td>';
        echo '<td align="center"><b>' . $totals['tdl'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['tdp'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['tal'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['tap'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['ndl'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['ndp'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['nal'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['nap'] . '</b></td>';
        echo '<td align="center"><b>' . $grandTotal . '</b></td>';
        echo '</tr>';
        break;
        
    case 'overstaying':
        $sql = "SELECT do.*, u.nama_upt FROM data_overstaying do JOIN upt u ON do.upt_id = u.id $whereClause ORDER BY do.tanggal DESC, do.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
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
        $totals = ['tai' => 0, 'taii' => 0, 'taiii' => 0, 'taiv' => 0, 'tav' => 0, 'oai' => 0, 'oaii' => 0, 'oaiii' => 0, 'oaiv' => 0, 'oav' => 0];
        
        foreach ($data as $row) {
            $total = (int)($row['tahanan_ai'] ?? 0) + (int)($row['tahanan_aii'] ?? 0) + (int)($row['tahanan_aiii'] ?? 0) + 
                     (int)($row['tahanan_aiv'] ?? 0) + (int)($row['tahanan_av'] ?? 0) +
                     (int)($row['overstaying_ai'] ?? 0) + (int)($row['overstaying_aii'] ?? 0) + (int)($row['overstaying_aiii'] ?? 0) + 
                     (int)($row['overstaying_aiv'] ?? 0) + (int)($row['overstaying_av'] ?? 0);
            $grandTotal += $total;
            
            $totals['tai'] += (int)($row['tahanan_ai'] ?? 0);
            $totals['taii'] += (int)($row['tahanan_aii'] ?? 0);
            $totals['taiii'] += (int)($row['tahanan_aiii'] ?? 0);
            $totals['taiv'] += (int)($row['tahanan_aiv'] ?? 0);
            $totals['tav'] += (int)($row['tahanan_av'] ?? 0);
            $totals['oai'] += (int)($row['overstaying_ai'] ?? 0);
            $totals['oaii'] += (int)($row['overstaying_aii'] ?? 0);
            $totals['oaiii'] += (int)($row['overstaying_aiii'] ?? 0);
            $totals['oaiv'] += (int)($row['overstaying_aiv'] ?? 0);
            $totals['oav'] += (int)($row['overstaying_av'] ?? 0);
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . ($row['nama_upt'] ?? '-') . '</td>';
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
        echo '<td colspan="3" align="center"><b>TOTAL</b></td>';
        echo '<td align="center"><b>' . $totals['tai'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['taii'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['taiii'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['taiv'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['tav'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['oai'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['oaii'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['oaiii'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['oaiv'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['oav'] . '</b></td>';
        echo '<td align="center"><b>' . $grandTotal . '</b></td>';
        echo '</tr>';
        break;
        
    case 'integrasi':
        $sql = "SELECT di.*, u.nama_upt FROM data_integrasi di JOIN upt u ON di.upt_id = u.id $whereClause ORDER BY di.tanggal DESC, di.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
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
        $totals = ['upb' => 0, 'ucb' => 0, 'ucmb' => 0, 'ucmk' => 0, 'uasim' => 0, 'spb' => 0, 'scb' => 0, 'scmb' => 0, 'scmk' => 0, 'sasim' => 0];
        
        foreach ($data as $row) {
            $total = (int)($row['usulan_pb'] ?? 0) + (int)($row['usulan_cb'] ?? 0) + (int)($row['usulan_cmb'] ?? 0) + 
                     (int)($row['usulan_cmk'] ?? 0) + (int)($row['usulan_asimilasi'] ?? 0) +
                     (int)($row['sk_terbit_pb'] ?? 0) + (int)($row['sk_terbit_cb'] ?? 0) + (int)($row['sk_terbit_cmb'] ?? 0) + 
                     (int)($row['sk_terbit_cmk'] ?? 0) + (int)($row['sk_terbit_asimilasi'] ?? 0);
            $grandTotal += $total;
            
            $totals['upb'] += (int)($row['usulan_pb'] ?? 0);
            $totals['ucb'] += (int)($row['usulan_cb'] ?? 0);
            $totals['ucmb'] += (int)($row['usulan_cmb'] ?? 0);
            $totals['ucmk'] += (int)($row['usulan_cmk'] ?? 0);
            $totals['uasim'] += (int)($row['usulan_asimilasi'] ?? 0);
            $totals['spb'] += (int)($row['sk_terbit_pb'] ?? 0);
            $totals['scb'] += (int)($row['sk_terbit_cb'] ?? 0);
            $totals['scmb'] += (int)($row['sk_terbit_cmb'] ?? 0);
            $totals['scmk'] += (int)($row['sk_terbit_cmk'] ?? 0);
            $totals['sasim'] += (int)($row['sk_terbit_asimilasi'] ?? 0);
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . ($row['nama_upt'] ?? '-') . '</td>';
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
        echo '<td colspan="3" align="center"><b>TOTAL</b></td>';
        echo '<td align="center"><b>' . $totals['upb'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['ucb'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['ucmb'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['ucmk'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['uasim'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['spb'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['scb'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['scmb'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['scmk'] . '</b></td>';
        echo '<td align="center"><b>' . $totals['sasim'] . '</b></td>';
        echo '<td align="center"><b>' . $grandTotal . '</b></td>';
        echo '</tr>';
        break;
}

echo '</table>';
echo '</body>';
echo '</html>';
