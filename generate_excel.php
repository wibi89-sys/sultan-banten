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
    'bebas_harian' => 'Laporan Data Bebas Harian',
    'rekapitulasi' => 'Laporan Rekapitulasi'
];

// Get data function
function getReportData($type, $uptId, $tanggalMulai, $tanggalAkhir) {
    global $pdo;
    
    $data = [];
    $uptClause = $uptId ? "AND upt_id = ?" : "";
    
    $dateClause = "";
    $dateParams = [];
    if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
        $dateClause = "AND tanggal BETWEEN ? AND ?";
        $dateParams = [$tanggalMulai, $tanggalAkhir];
    } elseif (!empty($tanggalMulai)) {
        $dateClause = "AND tanggal >= ?";
        $dateParams = [$tanggalMulai];
    } elseif (!empty($tanggalAkhir)) {
        $dateClause = "AND tanggal <= ?";
        $dateParams = [$tanggalAkhir];
    }
    
    try {
        switch ($type) {
            case 'bebas_harian':
                $sql = "SELECT 
                        dbh.*, u.nama_upt 
                        FROM data_bebas_harian dbh
                        JOIN upt u ON dbh.upt_id = u.id
                        WHERE 1=1 " . str_replace("AND upt_id", "AND dbh.upt_id", $uptClause) . " " . str_replace("AND tanggal", "AND dbh.tanggal", $dateClause) . "
                        ORDER BY dbh.tanggal DESC, u.nama_upt ASC, dbh.id ASC";
                $params = [];
                if ($uptId) $params[] = $uptId;
                $params = array_merge($params, $dateParams);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'penghuni':
                // Custom query for per-date report exactly like PDF
                $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
                $excludePlaceholders = implode(',', array_fill(0, count($excludeKlasifikasi), '?'));
                
                $whereClause = "dp.klasifikasi_pidana NOT IN ($excludePlaceholders)";
                $params = $excludeKlasifikasi;
                
                if ($uptId) {
                    $whereClause .= " AND u.id = ?";
                    $params[] = $uptId;
                }
                
                if (!empty($dateClause)) {
                    $cleanDateClause = preg_replace('/^AND\s+/', '', trim($dateClause));
                    $whereClause .= " AND " . $cleanDateClause;
                    $params = array_merge($params, $dateParams);
                }
                
                $sql = "SELECT 
                    u.id, u.nama_upt, u.kapasitas, dp.tanggal, MAX(dp.created_at) as waktu_input,
                    COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan), 0) as total_tahanan,
                    COALESCE(SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_narapidana,
                    COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                        dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni
                    FROM upt u
                    JOIN data_penghuni dp ON u.id = dp.upt_id
                    WHERE $whereClause
                    GROUP BY u.id, u.nama_upt, u.kapasitas, dp.tanggal
                    ORDER BY u.nama_upt, dp.tanggal";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
                
            case 'overstaying':
                $sql = "SELECT do.*, u.nama_upt FROM data_overstaying do 
                        JOIN upt u ON do.upt_id = u.id 
                        WHERE 1=1 $uptClause $dateClause 
                        ORDER BY u.nama_upt, do.tanggal";
                $params = [];
                if ($uptId) $params[] = $uptId;
                $params = array_merge($params, $dateParams);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
                
            case 'integrasi':
                $sql = "SELECT di.*, u.nama_upt FROM data_integrasi di 
                        JOIN upt u ON di.upt_id = u.id 
                        WHERE 1=1 $uptClause $dateClause 
                        ORDER BY u.nama_upt, di.tanggal";
                $params = [];
                if ($uptId) $params[] = $uptId;
                $params = array_merge($params, $dateParams);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
                
            case 'rekapitulasi':
                $dateClauseRekap = str_replace("tanggal", "tanggal", $dateClause);
                // Optimized Rekapitulasi query using subqueries
                $sql = "SELECT 
                        u.nama_upt, u.kapasitas,
                        (SELECT COALESCE(SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan), 0) 
                         FROM data_penghuni WHERE upt_id = u.id $dateClauseRekap) as total_tahanan,
                        (SELECT COALESCE(SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan), 0) 
                         FROM data_penghuni WHERE upt_id = u.id $dateClauseRekap) as total_narapidana,
                        (SELECT COALESCE(SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av + 
                                            overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av), 0) 
                         FROM data_overstaying WHERE upt_id = u.id $dateClauseRekap) as total_overstaying,
                        (SELECT COALESCE(SUM(usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + 
                                            sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi), 0) 
                         FROM data_integrasi WHERE upt_id = u.id $dateClauseRekap) as total_integrasi
                        FROM upt u";
                
                $params = array_merge($dateParams, $dateParams, $dateParams, $dateParams);
                
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

// Calculate dynamic colspan based on report type
$headerColspan = 10;
switch ($reportType) {
    case 'penghuni':
        $headerColspan = 10;
        break;
    case 'overstaying':
        $headerColspan = 11;
        break;
    case 'integrasi':
        $headerColspan = 15;
        break;
    case 'rekapitulasi':
        $headerColspan = 9;
        break;
}

echo '<tr><td colspan="' . $headerColspan . '" align="center" style="font-size: 16px; font-weight: bold;">' . htmlspecialchars($reportTitles[$reportType]) . '</td></tr>';

// Print Periode correctly based on filter
$periodeStr = 'Semua Periode';
if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
    $periodeStr = date('d F Y', strtotime($tanggalMulai)) . ' - ' . date('d F Y', strtotime($tanggalAkhir));
} elseif (!empty($tanggalMulai)) {
    $periodeStr = 'Mulai ' . date('d F Y', strtotime($tanggalMulai));
} elseif (!empty($tanggalAkhir)) {
    $periodeStr = 'Sampai ' . date('d F Y', strtotime($tanggalAkhir));
}

echo '<tr><td colspan="' . $headerColspan . '">Hari/Tanggal: ' . $periodeStr . '</td></tr>';
echo '<tr><td colspan="' . $headerColspan . '"></td></tr>';

// Table headers based on report type
switch ($reportType) {
    case 'penghuni':
        echo '<tr>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>No</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Tanggal</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Unit Pelaksana Teknis</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Kapasitas</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Tahanan</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Narapidana</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Total</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Tingkat Hunian</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>% Over</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Waktu Input</b></td>';
        echo '</tr>';
        
        $no = 1;
        $grandTotalKapasitas = 0;
        $grandTotalTahanan = 0;
        $grandTotalNapi = 0;
        $grandTotalPenghuni = 0;
        
        foreach ($data as $row) {
            $kapasitas = (int)($row['kapasitas'] ?? 0);
            $tahanan = (int)($row['total_tahanan'] ?? 0);
            $napi = (int)($row['total_narapidana'] ?? 0);
            $total = (int)($row['total_penghuni'] ?? 0);
            
            $grandTotalKapasitas += $kapasitas;
            $grandTotalTahanan += $tahanan;
            $grandTotalNapi += $napi;
            $grandTotalPenghuni += $total;
            
            $persen = $kapasitas > 0 ? round((($total - $kapasitas) / $kapasitas) * 100, 1) : 0;
            $tingkat_hunian = $kapasitas > 0 ? round(($total / $kapasitas) * 100, 1) : 0;
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            $tanggalVal = !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-';
            echo '<td align="center">' . $tanggalVal . '</td>';
            $textUPT = htmlspecialchars($row['nama_upt'] ?? '-');
            echo '<td>' . $textUPT . '</td>';
            echo '<td align="center">' . number_format($kapasitas) . '</td>';
            echo '<td align="center">' . number_format($tahanan) . '</td>';
            echo '<td align="center">' . number_format($napi) . '</td>';
            echo '<td align="center"><b>' . number_format($total) . '</b></td>';
            echo '<td align="center">' . $tingkat_hunian . '%</td>';
            echo '<td align="center">' . ($persen > 0 ? '+' : '') . $persen . '%</td>';
            $waktuInput = isset($row['waktu_input']) ? date('d/m/Y H:i:s', strtotime($row['waktu_input'])) : '-';
            $styleWaktu = '';
            if (isset($row['waktu_input']) && isset($row['tanggal'])) {
                $waktuTime = strtotime($row['waktu_input']);
                $batasWaktu = strtotime($row['tanggal'] . ' 08:00:00');
                if ($waktuTime > $batasWaktu) {
                    $styleWaktu = ' style="color: red; font-weight: bold;"';
                }
            }
            echo '<td align="center"' . $styleWaktu . '>' . $waktuInput . '</td>';
            echo '</tr>';
        }
        
        $totalPersen = $grandTotalKapasitas > 0 ? round((($grandTotalPenghuni - $grandTotalKapasitas) / $grandTotalKapasitas) * 100, 1) : 0;
        $totalTingkatHunian = $grandTotalKapasitas > 0 ? round(($grandTotalPenghuni / $grandTotalKapasitas) * 100, 1) : 0;
        
        echo '<tr>';
        echo '<td colspan="3" align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">TOTAL</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . number_format($grandTotalKapasitas) . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . number_format($grandTotalTahanan) . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . number_format($grandTotalNapi) . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . number_format($grandTotalPenghuni) . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $totalTingkatHunian . '%</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . ($totalPersen > 0 ? '+' : '') . $totalPersen . '%</td>';
        echo '<td bgcolor="#dcdcdc" style="color: black; font-weight: bold;"></td>';
        echo '</tr>';
        break;
        
    case 'overstaying':
        echo '<tr>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>NO</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>TANGGAL</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>NAMA UPT</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>AI</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>AII</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>AIII</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>AIV</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>AV</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>JUMLAH</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>KETERANGAN</b></td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black;"><b>Waktu Input</b></td>';
        echo '</tr>';
        
        $no = 1;
        $total_o_ai = 0; $total_o_aii = 0; $total_o_aiii = 0; $total_o_aiv = 0; $total_o_av = 0; $total_o_jml = 0;
        
        foreach ($data as $row) {
            $o_jml = (int)($row['overstaying_ai'] ?? 0) + (int)($row['overstaying_aii'] ?? 0) + (int)($row['overstaying_aiii'] ?? 0) + (int)($row['overstaying_aiv'] ?? 0) + (int)($row['overstaying_av'] ?? 0);
            
            $total_o_ai += (int)($row['overstaying_ai'] ?? 0);
            $total_o_aii += (int)($row['overstaying_aii'] ?? 0);
            $total_o_aiii += (int)($row['overstaying_aiii'] ?? 0);
            $total_o_aiv += (int)($row['overstaying_aiv'] ?? 0);
            $total_o_av += (int)($row['overstaying_av'] ?? 0);
            $total_o_jml += $o_jml;
            
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            $tanggalVal = !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-';
            echo '<td align="center">' . $tanggalVal . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_ai'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_aii'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_aiii'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_aiv'] ?? 0) . '</td>';
            echo '<td align="center">' . (int)($row['overstaying_av'] ?? 0) . '</td>';
            echo '<td align="center"><b>' . $o_jml . '</b></td>';
            echo '<td>' . (!empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : 'Nihil') . '</td>';
            $waktuInput = isset($row['created_at']) ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-';
            $styleWaktu = '';
            if (isset($row['created_at']) && isset($row['tanggal'])) {
                $waktuTime = strtotime($row['created_at']);
                $batasWaktu = strtotime($row['tanggal'] . ' 08:00:00');
                if ($waktuTime > $batasWaktu) {
                    $styleWaktu = ' style="color: red; font-weight: bold;"';
                }
            }
            echo '<td align="center"' . $styleWaktu . '>' . $waktuInput . '</td>';
            echo '</tr>';
        }
        
        echo '<tr>';
        echo '<td colspan="3" align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">SUBTOTAL</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_ai . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_aii . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_aiii . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_aiv . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_av . '</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_jml . '</td>';
        echo '<td bgcolor="#dcdcdc" style="color: black; font-weight: bold;"></td>';
        echo '<td bgcolor="#dcdcdc" style="color: black; font-weight: bold;"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td colspan="8" align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">TOTAL</td>';
        echo '<td align="center" bgcolor="#dcdcdc" style="color: black; font-weight: bold;">' . $total_o_jml . '</td>';
        echo '<td bgcolor="#dcdcdc" style="color: black; font-weight: bold;"></td>';
        echo '<td bgcolor="#dcdcdc" style="color: black; font-weight: bold;"></td>';
        echo '</tr>';
        break;
        
    case 'integrasi':
        echo '<tr>';
        echo '<td rowspan="2" align="center" bgcolor="#34495e" style="color: white;"><b>No</b></td>';
        echo '<td rowspan="2" align="center" bgcolor="#34495e" style="color: white;"><b>Tanggal</b></td>';
        echo '<td rowspan="2" align="center" bgcolor="#34495e" style="color: white;"><b>UPT</b></td>';
        echo '<td colspan="5" align="center" bgcolor="#34495e" style="color: white;"><b>Usulan</b></td>';
        echo '<td colspan="5" align="center" bgcolor="#34495e" style="color: white;"><b>SK Terbit</b></td>';
        echo '<td rowspan="2" align="center" bgcolor="#34495e" style="color: white;"><b>Total</b></td>';
        echo '<td rowspan="2" align="center" bgcolor="#34495e" style="color: white;"><b>Waktu Input</b></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>PB</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>CB</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>CMB</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>CMK</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>Asim</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>PB</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>CB</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>CMB</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>CMK</b></td>';
        echo '<td align="center" bgcolor="#42576c" style="color: white;"><b>Asim</b></td>';
        echo '</tr>';
        
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
            $tanggalVal = !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-';
            echo '<td align="center">' . $tanggalVal . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
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
            $waktuInput = isset($row['created_at']) ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-';
            $styleWaktu = '';
            if (isset($row['created_at']) && isset($row['tanggal'])) {
                $waktuTime = strtotime($row['created_at']);
                $batasWaktu = strtotime($row['tanggal'] . ' 08:00:00');
                if ($waktuTime > $batasWaktu) {
                    $styleWaktu = ' style="color: red; font-weight: bold;"';
                }
            }
            echo '<td align="center"' . $styleWaktu . '>' . $waktuInput . '</td>';
            echo '</tr>';
        }
        
        echo '<tr>';
        echo '<td colspan="13" align="center" bgcolor="#34495e" style="color: white; font-weight: bold;">TOTAL</td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white; font-weight: bold;">' . $grandTotal . '</td>';
        echo '<td bgcolor="#34495e" style="color: white; font-weight: bold;"></td>';
        echo '</tr>';
        break;

    case 'bebas_harian':
        echo '<tr>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>No</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Tanggal</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>UPT</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Klasifikasi Pidana</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Bebas Murni</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>PB</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>CB</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>CMB</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Asimilasi</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>BDH</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Lainnya</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Rincian Lainnya</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Total</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Catatan</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Waktu Input</b></td>';
        echo '</tr>';

        $no = 1;
        $totBm = 0; $totPb = 0; $totCb = 0; $totCmb = 0; $totAsim = 0; $totBdh = 0; $totLain = 0; $grandTotal = 0;

        foreach ($data as $row) {
            $bm = (int)($row['bebas_murni'] ?? 0);
            $pb = (int)($row['pb'] ?? 0);
            $cb = (int)($row['cb'] ?? 0);
            $cmb = (int)($row['cmb'] ?? 0);
            $asim = (int)($row['asimilasi'] ?? 0);
            $bdh = (int)($row['bdh'] ?? 0);
            $lain = (int)($row['lainnya'] ?? 0);
            $subTotal = $bm + $pb + $cb + $cmb + $asim + $bdh + $lain;

            $totBm += $bm; $totPb += $pb; $totCb += $cb; $totCmb += $cmb; $totAsim += $asim; $totBdh += $bdh; $totLain += $lain;
            $grandTotal += $subTotal;

            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            $tanggalVal = !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-';
            echo '<td align="center">' . $tanggalVal . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_upt'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['klasifikasi_pidana'] ?? '-') . '</td>';
            echo '<td align="center">' . $bm . '</td>';
            echo '<td align="center">' . $pb . '</td>';
            echo '<td align="center">' . $cb . '</td>';
            echo '<td align="center">' . $cmb . '</td>';
            echo '<td align="center">' . $asim . '</td>';
            echo '<td align="center">' . $bdh . '</td>';
            echo '<td align="center">' . $lain . '</td>';
            echo '<td>' . htmlspecialchars($row['ket_lainnya'] ?? '-') . '</td>';
            echo '<td align="center"><b>' . $subTotal . '</b></td>';
            echo '<td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>';
            $waktuInput = isset($row['created_at']) ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-';
            echo '<td align="center">' . $waktuInput . '</td>';
            echo '</tr>';
        }

        echo '<tr bgcolor="#34495e" style="color: white; font-weight: bold;">';
        echo '<td colspan="4" align="center">TOTAL</td>';
        echo '<td align="center">' . $totBm . '</td>';
        echo '<td align="center">' . $totPb . '</td>';
        echo '<td align="center">' . $totCb . '</td>';
        echo '<td align="center">' . $totCmb . '</td>';
        echo '<td align="center">' . $totAsim . '</td>';
        echo '<td align="center">' . $totBdh . '</td>';
        echo '<td align="center">' . $totLain . '</td>';
        echo '<td></td>';
        echo '<td align="center">' . $grandTotal . '</td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';
        break;
        
    case 'rekapitulasi':
        echo '<tr>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>No</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>UPT</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Kapasitas</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Tahanan</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Narapidana</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Total</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>% Kapasitas</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Overstaying</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>Integrasi</b></td>';
        echo '</tr>';
        
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
        
        echo '<tr>';
        echo '<td colspan="2" align="center" bgcolor="#34495e" style="color: white;"><b>TOTAL</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;">-</td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>' . number_format($grandTahanan) . '</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>' . number_format($grandNarapidana) . '</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>' . number_format($grandTotal) . '</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;">-</td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>' . number_format($grandOverstaying) . '</b></td>';
        echo '<td align="center" bgcolor="#34495e" style="color: white;"><b>' . number_format($grandIntegrasi) . '</b></td>';
        echo '</tr>';
        break;
}

echo '</table>';
echo '</body>';
echo '</html>';

// Flush the output buffer
ob_end_flush();
?>
