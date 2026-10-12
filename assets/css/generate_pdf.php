<?php
/**
 * PDF Report Generator using TCPDF
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('memory_limit', '256M');
set_time_limit(120);

require_once 'includes/functions.php';
requireLogin();
requireAccess('laporan');

require_once __DIR__ . '/tcpdf/tcpdf.php';

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
    'penghuni' => 'LAPORAN DATA PENGHUNI',
    'overstaying' => 'LAPORAN DATA OVERSTAYING',
    'integrasi' => 'LAPORAN DATA INTEGRASI',
    'bebas_harian' => 'LAPORAN DATA BEBAS HARIAN',
    'rekapitulasi' => 'LAPORAN REKAPITULASI'
];

// Custom PDF class
class MYPDF extends TCPDF {
    private $instansi = [];
    private $reportTitle = '';
    public $reportType = 'penghuni';
    
    public function setInstansi($instansi) {
        $this->instansi = is_array($instansi) ? $instansi : [];
    }
    
    public function setReportTitle($title) {
        $this->reportTitle = $title;
    }
    
    public function Header() {
        // Leave empty to avoid overlap with margins.
        // We will print the headers manually on the first page.
    }
    
    public function Footer() {
        // Set Y to 20 mm from bottom for QR Code
        $this->SetY(-20);
        $y = $this->GetY();
        
        $qrText = "✅ Dokumen Valid\ndicetak melalui :\nSULTAN BANTEN @Ditjenpas Banten";
        $style = array(
            'border' => false,
            'padding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false
        );
        // QR Code at X=15, Y=$y, Width=15, Height=15
        $this->write2DBarcode($qrText, 'QRCODE,L', 15, $y, 15, 15, $style, 'N');
        
        // Page number
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);
    }
}

// Get data
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
                $whereClause = "1=1";
                $params = [];
                if ($uptId) {
                    $whereClause .= " AND dbh.upt_id = ?";
                    $params[] = $uptId;
                }
                if (!empty($dateClause)) {
                    $cleanDateClause = preg_replace('/^AND\s+/', '', trim($dateClause));
                    $whereClause .= " AND " . str_replace('tanggal', 'dbh.tanggal', $cleanDateClause);
                    $params = array_merge($params, $dateParams);
                }
                $sql = "SELECT 
                    u.id as upt_id, u.nama_upt, dbh.tanggal,
                    SUM(dbh.bebas_murni) as total_bm,
                    SUM(dbh.pb) as total_pb,
                    SUM(dbh.cb) as total_cb,
                    SUM(dbh.cmb) as total_cmb,
                    SUM(dbh.asimilasi) as total_asimilasi,
                    SUM(dbh.bdh) as total_bdh,
                    SUM(dbh.lainnya) as total_lainnya,
                    SUM(dbh.bebas_murni + dbh.pb + dbh.cb + dbh.cmb + dbh.asimilasi + dbh.bdh + dbh.lainnya) as total_bebas,
                    GROUP_CONCAT(DISTINCT NULLIF(dbh.ket_lainnya, '') SEPARATOR '; ') as rincian_lainnya,
                    MAX(dbh.keterangan) as keterangan
                    FROM data_bebas_harian dbh
                    JOIN upt u ON dbh.upt_id = u.id
                    WHERE $whereClause
                    GROUP BY u.id, u.nama_upt, dbh.tanggal
                    ORDER BY dbh.tanggal ASC, u.nama_upt ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'penghuni':
                // Custom query for per-date report
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
                    u.id, u.nama_upt, u.kapasitas, dp.tanggal,
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
                $sql = "SELECT 
                        u.nama_upt, u.kapasitas,
                        COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan), 0) as total_tahanan,
                        COALESCE(SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_narapidana,
                        COALESCE(SUM(do.tahanan_ai + do.tahanan_aii + do.tahanan_aiii + do.tahanan_aiv + do.tahanan_av + 
                            do.overstaying_ai + do.overstaying_aii + do.overstaying_aiii + do.overstaying_aiv + do.overstaying_av), 0) as total_overstaying,
                        COALESCE(SUM(di.usulan_pb + di.usulan_cb + di.usulan_cmb + di.usulan_cmk + di.usulan_asimilasi + 
                            di.sk_terbit_pb + di.sk_terbit_cb + di.sk_terbit_cmb + di.sk_terbit_cmk + di.sk_terbit_asimilasi), 0) as total_integrasi
                        FROM upt u
                        LEFT JOIN data_penghuni dp ON u.id = dp.upt_id " . str_replace("tanggal", "dp.tanggal", $dateClause) . "
                        LEFT JOIN data_overstaying do ON u.id = do.upt_id " . str_replace("tanggal", "do.tanggal", $dateClause) . "
                        LEFT JOIN data_integrasi di ON u.id = di.upt_id " . str_replace("tanggal", "di.tanggal", $dateClause);
                
                $params = array_merge($dateParams, $dateParams, $dateParams);
                if ($uptId) {
                    $sql .= " WHERE u.id = ?";
                    $params[] = $uptId;
                }
                $sql .= " GROUP BY u.id, u.nama_upt, u.kapasitas ORDER BY u.nama_upt";
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

// Generate Penghuni Table
function generatePenghuniTable($pdf, $data) {
    global $tanggalMulai, $tanggalAkhir;
    
    // Hari/Tanggal string calculation (same as overstaying)
    $hariTanggalStr = 'Semua Periode';
    if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
        if ($tanggalMulai == $tanggalAkhir) {
            $hariTanggalStr = function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, true) : date('d F Y', strtotime($tanggalMulai));
        } else {
            $hariTanggalStr = (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, false) : date('d F Y', strtotime($tanggalMulai))) . ' s/d ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalAkhir, false) : date('d F Y', strtotime($tanggalAkhir)));
        }
    } elseif (!empty($tanggalMulai)) {
        $hariTanggalStr = 'Mulai ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, false) : date('d F Y', strtotime($tanggalMulai)));
    } elseif (!empty($tanggalAkhir)) {
        $hariTanggalStr = 'Sampai ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalAkhir, false) : date('d F Y', strtotime($tanggalAkhir)));
    }
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Hari/Tanggal :', 0, 1, 'L');
    $pdf->Cell(0, 5, $hariTanggalStr, 0, 1, 'L');
    $pdf->Ln(2);
    
    // Total width = 190
    $widths = [10, 65, 18, 18, 20, 20, 20, 19];
    
    // Header
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 220); // Light gray
    $pdf->SetTextColor(0, 0, 0);
    
    // We use MultiCell for header to handle 'Unit Pelaksana Teknis' wrapped if needed
    // Let's make header height 8 to save space.
    $pdf->Cell($widths[0], 8, 'No', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 8, 'Unit Pelaksana Teknis', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 8, 'Kapasitas', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 8, 'Tahanan', 1, 0, 'C', true);
    $pdf->Cell($widths[4], 8, 'Narapidana', 1, 0, 'C', true);
    $pdf->Cell($widths[5], 8, 'Total', 1, 0, 'C', true);
    
    // Tingkat Hunian in MultiCell for wrapping
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell($widths[6], 8, "Tingkat\nHunian", 1, 'C', true, 0, $x, $y, true, 0, false, true, 8, 'M');
    $pdf->Cell($widths[7], 8, '% Over', 1, 1, 'C', true);
    
    // Data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    $no = 1;
    $grandTotalKapasitas = 0;
    $grandTotalTahanan = 0;
    $grandTotalNapi = 0;
    $grandTotalPenghuni = 0;
    
    // Helper function for PDF colors based on overkapasitas percentage
    $getPdfColor = function($persen) {
        if ($persen <= 0) return ['fill' => [40, 167, 69], 'text' => [255, 255, 255]];    // Success (Green)
        if ($persen <= 20) return ['fill' => [23, 162, 184], 'text' => [255, 255, 255]];  // Info (Blue)
        if ($persen <= 50) return ['fill' => [255, 193, 7], 'text' => [0, 0, 0]];         // Warning (Yellow)
        if ($persen <= 100) return ['fill' => [220, 53, 69], 'text' => [255, 255, 255]];  // Danger (Red)
        return ['fill' => [139, 0, 0], 'text' => [255, 255, 255]];                        // Dark Red
    };
    
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
        
        $textUPT = htmlspecialchars($row['nama_upt'] ?? '-');
        $numLines = $pdf->getNumLines($textUPT, $widths[1]);
        $rowH = max(7, $numLines * 3.8); // Reduced multiplier and minimum to compress rows
        
        $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
        
        // MultiCell for UPT
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
        
        $pdf->Cell($widths[2], $rowH, number_format($kapasitas), 1, 0, 'C');
        $pdf->Cell($widths[3], $rowH, number_format($tahanan), 1, 0, 'C');
        $pdf->Cell($widths[4], $rowH, number_format($napi), 1, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($widths[5], $rowH, number_format($total), 1, 0, 'C');
        
        $color = $getPdfColor($persen);
        $pdf->SetFillColor($color['fill'][0], $color['fill'][1], $color['fill'][2]);
        $pdf->SetTextColor($color['text'][0], $color['text'][1], $color['text'][2]);
        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell($widths[6], $rowH, $tingkat_hunian . '%', 1, 0, 'C', true);
        $pdf->Cell($widths[7], $rowH, ($persen > 0 ? '+' : '') . $persen . '%', 1, 1, 'C', true);
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
    }
    
    // Total Row (Only if single day filter)
    if ($tanggalMulai === $tanggalAkhir && !empty($tanggalMulai)) {
        $totalPersen = $grandTotalKapasitas > 0 ? round((($grandTotalPenghuni - $grandTotalKapasitas) / $grandTotalKapasitas) * 100, 1) : 0;
        $totalTingkatHunian = $grandTotalKapasitas > 0 ? round(($grandTotalPenghuni / $grandTotalKapasitas) * 100, 1) : 0;
        
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell($widths[0] + $widths[1], 7, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell($widths[2], 7, number_format($grandTotalKapasitas), 1, 0, 'C', true);
        $pdf->Cell($widths[3], 7, number_format($grandTotalTahanan), 1, 0, 'C', true);
        $pdf->Cell($widths[4], 7, number_format($grandTotalNapi), 1, 0, 'C', true);
        $pdf->Cell($widths[5], 7, number_format($grandTotalPenghuni), 1, 0, 'C', true);
        
        $totalColor = $getPdfColor($totalPersen);
        $pdf->SetFillColor($totalColor['fill'][0], $totalColor['fill'][1], $totalColor['fill'][2]);
        $pdf->SetTextColor($totalColor['text'][0], $totalColor['text'][1], $totalColor['text'][2]);
        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell($widths[6], 7, $totalTingkatHunian . '%', 1, 0, 'C', true);
        $pdf->Cell($widths[7], 7, ($totalPersen > 0 ? '+' : '') . $totalPersen . '%', 1, 1, 'C', true);
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // Footer & Signature
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(1);
    $pdf->Cell(0, 5, 'Demikian laporan ini disampaikan, atas perhatiannya diucapkan terima kasih.', 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // Signatures
    $tempatTtd = $_GET['tempat_ttd'] ?? 'Serang';
    $jabatanTtd = $_GET['jabatan_ttd'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
    $namaTtd = $_GET['nama_ttd'] ?? 'Surya Dharma';
    
    $tglTtd = function_exists('formatIndonesianDate') ? formatIndonesianDate(date('Y-m-d'), false) : date('d F Y');
    
    $sigX = 130;
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $tempatTtd . ', ' . $tglTtd, 0, 1, 'C');
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $jabatanTtd, 0, 1, 'C');
    
    $pdf->Ln(2); // Compressed signature space
    
    $yBeforeSig = $pdf->GetY();
    
    global $instansi;
    
    // Print TTD first (so Stempel overlaps it)
    if (!empty($instansi['ttd_image']) && file_exists(__DIR__ . '/' . $instansi['ttd_image'])) {
        // TTD di tengah blok tanda tangan, lebar proporsional 40mm
        $pdf->Image(__DIR__ . '/' . $instansi['ttd_image'], $sigX + 15, $yBeforeSig - 2, 40, 0);
    }
    
    // Print Stempel
    if (!empty($instansi['stempel_image']) && file_exists(__DIR__ . '/' . $instansi['stempel_image'])) {
        // Stempel ukuran 4 cm (40 mm), diletakkan lebih menimpa TTD
        $pdf->Image(__DIR__ . '/' . $instansi['stempel_image'], $sigX - 12, $yBeforeSig - 7, 40, 0);
    }
    
    $pdf->Ln(15);
    $pdf->SetX($sigX);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(70, 5, $namaTtd, 0, 1, 'C');
}

// Generate Overstaying Table
function generateOverstayingTable($pdf, $data) {
    global $tanggalMulai, $tanggalAkhir;
    
    // Hari/Tanggal string calculation
    $hariTanggalStr = 'Semua Periode';
    if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
        if ($tanggalMulai == $tanggalAkhir) {
            $hariTanggalStr = function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, true) : date('d F Y', strtotime($tanggalMulai));
        } else {
            $hariTanggalStr = (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, false) : date('d F Y', strtotime($tanggalMulai))) . ' s/d ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalAkhir, false) : date('d F Y', strtotime($tanggalAkhir)));
        }
    } elseif (!empty($tanggalMulai)) {
        $hariTanggalStr = 'Mulai ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, false) : date('d F Y', strtotime($tanggalMulai)));
    } elseif (!empty($tanggalAkhir)) {
        $hariTanggalStr = 'Sampai ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalAkhir, false) : date('d F Y', strtotime($tanggalAkhir)));
    }
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Hari/Tanggal :', 0, 1, 'L');
    $pdf->Cell(0, 5, $hariTanggalStr, 0, 1, 'L');
    $pdf->Ln(2);
    
    // Widths for A4 Portrait (Total 190)
    // Shrink AI-AV and balance UPT & Keterangan
    $widths = [8, 62, 10, 10, 10, 10, 10, 15, 55];
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 220); // Light gray
    $pdf->SetTextColor(0, 0, 0);
    
    // Header row
    $pdf->Cell($widths[0], 7, 'NO', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 7, 'NAMA UPT', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 7, 'AI', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 7, 'AII', 1, 0, 'C', true);
    $pdf->Cell($widths[4], 7, 'AIII', 1, 0, 'C', true);
    $pdf->Cell($widths[5], 7, 'AIV', 1, 0, 'C', true);
    $pdf->Cell($widths[6], 7, 'AV', 1, 0, 'C', true);
    $pdf->Cell($widths[7], 7, 'JUMLAH', 1, 0, 'C', true);
    $pdf->Cell($widths[8], 7, 'KETERANGAN', 1, 1, 'C', true);
    
    // Data rows
    $pdf->SetFont('helvetica', '', 8);
    
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
        
        $textUPT = htmlspecialchars($row['nama_upt'] ?? '-');
        $keterangan = !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : 'Nihil';
        $textKet = $keterangan;
        
        // Calculate required number of lines
        $numLinesUPT = $pdf->getNumLines($textUPT, $widths[1]);
        $numLinesKet = $pdf->getNumLines($textKet, $widths[8]);
        
        $maxLines = max($numLinesUPT, $numLinesKet, 1);
        $rowH = max(6, $maxLines * 4.5);
        
        // Note: For cell heights to match MultiCell exactly, we just pass $rowH
        $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
        
        // MultiCell for UPT
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
        
        $pdf->Cell($widths[2], $rowH, (int)($row['overstaying_ai'] ?? 0), 1, 0, 'C');
        $pdf->Cell($widths[3], $rowH, (int)($row['overstaying_aii'] ?? 0), 1, 0, 'C');
        $pdf->Cell($widths[4], $rowH, (int)($row['overstaying_aiii'] ?? 0), 1, 0, 'C');
        $pdf->Cell($widths[5], $rowH, (int)($row['overstaying_aiv'] ?? 0), 1, 0, 'C');
        $pdf->Cell($widths[6], $rowH, (int)($row['overstaying_av'] ?? 0), 1, 0, 'C');
        
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($widths[7], $rowH, $o_jml, 1, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        
        // MultiCell for Keterangan
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell($widths[8], $rowH, $textKet, 1, 'L', false, 1, $x, $y, true, 0, false, true, $rowH, 'M');
    }
    
    // Subtotal & Total (Only if single day filter)
    if ($tanggalMulai === $tanggalAkhir && !empty($tanggalMulai)) {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();
        
        $pdf->Cell($widths[0] + $widths[1], 6, 'SUBTOTAL', 1, 0, 'C', true);
        $pdf->Cell($widths[2], 6, $total_o_ai, 1, 0, 'C', true);
        $pdf->Cell($widths[3], 6, $total_o_aii, 1, 0, 'C', true);
        $pdf->Cell($widths[4], 6, $total_o_aiii, 1, 0, 'C', true);
        $pdf->Cell($widths[5], 6, $total_o_aiv, 1, 0, 'C', true);
        $pdf->Cell($widths[6], 6, $total_o_av, 1, 0, 'C', true);
        
        $spannedX = $pdf->GetX();
        
        // JUMLAH & KETERANGAN (spanning 2 rows, vertically centered)
        $pdf->MultiCell($widths[7], 12, $total_o_jml, 1, 'C', true, 0, $spannedX, $startY, true, 0, false, true, 12, 'M');
        $pdf->MultiCell($widths[8], 12, '', 1, 'L', true, 0, $spannedX + $widths[7], $startY, true, 0, false, true, 12, 'M');
        
        // Total row
        $pdf->SetXY($startX, $startY + 6);
        $pdf->Cell($widths[0] + $widths[1] + $widths[2] + $widths[3] + $widths[4] + $widths[5] + $widths[6], 6, 'TOTAL', 1, 1, 'C', true);
    }
    
    // Footer & Signature
    $pdf->Ln(2); // Reduced from 5
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Sumber: Data berikut diperoleh dari SDP.', 0, 1, 'L');
    $pdf->Ln(1); // Reduced from 2
    $pdf->Cell(0, 5, 'Demikian laporan ini disampaikan, atas perhatiannya diucapkan terima kasih.', 0, 1, 'L');
    
    $pdf->Ln(5); // Reduced from 15
    
    // Signatures
    $tempatTtd = $_GET['tempat_ttd'] ?? 'Serang';
    $jabatanTtd = $_GET['jabatan_ttd'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
    $namaTtd = $_GET['nama_ttd'] ?? 'Surya Dharma';
    
    // Date of signature = today
    $tglTtd = function_exists('formatIndonesianDate') ? formatIndonesianDate(date('Y-m-d'), false) : date('d F Y');
    
    // Move to the right side (width is 190, signature box can be 70 wide at position 130)
    $sigX = 130;
    
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $tempatTtd . ', ' . $tglTtd, 0, 1, 'C');
    
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $jabatanTtd, 0, 1, 'C');
    
    // Signature space
    $pdf->Ln(2); // Reduced space to make room for image
    
    $yBeforeSig = $pdf->GetY();
    
    global $instansi;
    
    // Print TTD first (so Stempel overlaps it)
    if (!empty($instansi['ttd_image']) && file_exists(__DIR__ . '/' . $instansi['ttd_image'])) {
        // TTD di tengah blok tanda tangan, lebar proporsional 40mm
        $pdf->Image(__DIR__ . '/' . $instansi['ttd_image'], $sigX + 15, $yBeforeSig - 2, 40, 0);
    }
    
    // Print Stempel
    if (!empty($instansi['stempel_image']) && file_exists(__DIR__ . '/' . $instansi['stempel_image'])) {
        // Stempel ukuran 4 cm (40 mm), diletakkan lebih menimpa TTD
        $pdf->Image(__DIR__ . '/' . $instansi['stempel_image'], $sigX - 12, $yBeforeSig - 7, 40, 0);
    }
    
    $pdf->Ln(15);
    
    $pdf->SetX($sigX);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(70, 5, $namaTtd, 0, 1, 'C');
}

// Generate Integrasi Table
function generateIntegrasiTable($pdf, $data) {
    $widths = [8, 65, 16, 13, 13, 13, 13, 14, 13, 13, 13, 13, 14, 15];
    
    // Header row 1
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell($widths[0], 12, 'No', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 12, 'UPT', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 12, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell(66, 6, 'Usulan', 1, 0, 'C', true);
    $pdf->Cell(66, 6, 'SK Terbit', 1, 0, 'C', true);
    $pdf->Cell($widths[13], 12, 'Total', 1, 1, 'C', true);
    
    // Header row 2
    $pdf->Cell(array_sum(array_slice($widths, 0, 3)), 0, '', 0, 0);
    $pdf->SetFillColor(66, 87, 108);
    $pdf->Cell($widths[3], 6, 'PB', 1, 0, 'C', true);
    $pdf->Cell($widths[4], 6, 'CB', 1, 0, 'C', true);
    $pdf->Cell($widths[5], 6, 'CMB', 1, 0, 'C', true);
    $pdf->Cell($widths[6], 6, 'CMK', 1, 0, 'C', true);
    $pdf->Cell($widths[7], 6, 'Asim', 1, 0, 'C', true);
    $pdf->Cell($widths[8], 6, 'PB', 1, 0, 'C', true);
    $pdf->Cell($widths[9], 6, 'CB', 1, 0, 'C', true);
    $pdf->Cell($widths[10], 6, 'CMB', 1, 0, 'C', true);
    $pdf->Cell($widths[11], 6, 'CMK', 1, 0, 'C', true);
    $pdf->Cell($widths[12], 6, 'Asim', 1, 0, 'C', true);
    $pdf->Cell($widths[13], 0, '', 0, 1);
    
    // Data
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    
    $no = 1;
    $grandTotal = 0;
    $fill = false;
    
    foreach ($data as $row) {
        $total = (int)($row['usulan_pb'] ?? 0) + (int)($row['usulan_cb'] ?? 0) + (int)($row['usulan_cmb'] ?? 0) + 
                 (int)($row['usulan_cmk'] ?? 0) + (int)($row['usulan_asimilasi'] ?? 0) +
                 (int)($row['sk_terbit_pb'] ?? 0) + (int)($row['sk_terbit_cb'] ?? 0) + (int)($row['sk_terbit_cmb'] ?? 0) + 
                 (int)($row['sk_terbit_cmk'] ?? 0) + (int)($row['sk_terbit_asimilasi'] ?? 0);
        $grandTotal += $total;
        
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 247 : 255, $fill ? 250 : 255);
        $fill = !$fill;
        
        $pdf->Cell($widths[0], 5.5, $no++, 1, 0, 'C', true);
        $pdf->Cell($widths[1], 5.5, $row['nama_upt'] ?? '-', 1, 0, 'L', true);
        $pdf->Cell($widths[2], 5.5, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C', true);
        $pdf->Cell($widths[3], 5.5, (int)($row['usulan_pb'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[4], 5.5, (int)($row['usulan_cb'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[5], 5.5, (int)($row['usulan_cmb'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[6], 5.5, (int)($row['usulan_cmk'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[7], 5.5, (int)($row['usulan_asimilasi'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[8], 5.5, (int)($row['sk_terbit_pb'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[9], 5.5, (int)($row['sk_terbit_cb'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[10], 5.5, (int)($row['sk_terbit_cmb'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[11], 5.5, (int)($row['sk_terbit_cmk'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[12], 5.5, (int)($row['sk_terbit_asimilasi'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[13], 5.5, $total, 1, 1, 'C', true);
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(array_sum(array_slice($widths, 0, 13)), 6, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell($widths[13], 6, $grandTotal, 1, 1, 'C', true);
}

// Generate Rekapitulasi Table
function generateRekapitulasiTable($pdf, $data) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    
    $headers = ['No', 'UPT', 'Kapasitas', 'Tahanan', 'Narapidana', 'Total', '% Kapasitas', 'Overstaying', 'Integrasi'];
    $widths = [10, 95, 22, 22, 22, 22, 22, 22, 22];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 6, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    $no = 1;
    $grandTotal = 0;
    $grandTahanan = 0;
    $grandNarapidana = 0;
    $grandOverstaying = 0;
    $grandIntegrasi = 0;
    $fill = false;
    
    foreach ($data as $row) {
        $total = (int)($row['total_tahanan'] ?? 0) + (int)($row['total_narapidana'] ?? 0);
        $kapasitas = (int)($row['kapasitas'] ?? 0);
        $persen = $kapasitas > 0 ? round(($total / $kapasitas) * 100, 1) : 0;
        
        $grandTotal += $total;
        $grandTahanan += (int)($row['total_tahanan'] ?? 0);
        $grandNarapidana += (int)($row['total_narapidana'] ?? 0);
        $grandOverstaying += (int)($row['total_overstaying'] ?? 0);
        $grandIntegrasi += (int)($row['total_integrasi'] ?? 0);
        
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 247 : 255, $fill ? 250 : 255);
        $fill = !$fill;
        
        $pdf->Cell($widths[0], 5.5, $no++, 1, 0, 'C', true);
        $pdf->Cell($widths[1], 5.5, $row['nama_upt'] ?? '-', 1, 0, 'L', true);
        $pdf->Cell($widths[2], 5.5, number_format($kapasitas), 1, 0, 'C', true);
        $pdf->Cell($widths[3], 5.5, number_format((int)($row['total_tahanan'] ?? 0)), 1, 0, 'C', true);
        $pdf->Cell($widths[4], 5.5, number_format((int)($row['total_narapidana'] ?? 0)), 1, 0, 'C', true);
        $pdf->Cell($widths[5], 5.5, number_format($total), 1, 0, 'C', true);
        $pdf->Cell($widths[6], 5.5, $persen . '%', 1, 0, 'C', true);
        $pdf->Cell($widths[7], 5.5, number_format((int)($row['total_overstaying'] ?? 0)), 1, 0, 'C', true);
        $pdf->Cell($widths[8], 5.5, number_format((int)($row['total_integrasi'] ?? 0)), 1, 0, 'C', true);
        $pdf->Ln();
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($widths[0] + $widths[1], 6, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 6, '-', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 6, number_format($grandTahanan), 1, 0, 'C', true);
    $pdf->Cell($widths[4], 6, number_format($grandNarapidana), 1, 0, 'C', true);
    $pdf->Cell($widths[5], 6, number_format($grandTotal), 1, 0, 'C', true);
    $pdf->Cell($widths[6], 6, '-', 1, 0, 'C', true);
    $pdf->Cell($widths[7], 6, number_format($grandOverstaying), 1, 0, 'C', true);
    $pdf->Cell($widths[8], 6, number_format($grandIntegrasi), 1, 0, 'C', true);
}

// Function generateBebasHarianTable
function generateBebasHarianTable($pdf, $data) {
    global $tanggalMulai, $tanggalAkhir;
    
    $hariTanggalStr = '-';
    if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
        if ($tanggalMulai === $tanggalAkhir) {
            $hariTanggalStr = function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, true) : date('d F Y', strtotime($tanggalMulai));
        } else {
            $hariTanggalStr = (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, false) : date('d F Y', strtotime($tanggalMulai))) . ' s/d ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalAkhir, false) : date('d F Y', strtotime($tanggalAkhir)));
        }
    } elseif (!empty($tanggalMulai)) {
        $hariTanggalStr = 'Mulai ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalMulai, false) : date('d F Y', strtotime($tanggalMulai)));
    } elseif (!empty($tanggalAkhir)) {
        $hariTanggalStr = 'Sampai ' . (function_exists('formatIndonesianDate') ? formatIndonesianDate($tanggalAkhir, false) : date('d F Y', strtotime($tanggalAkhir)));
    }
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Hari/Tanggal : ' . $hariTanggalStr, 0, 1, 'L');
    $pdf->Ln(2);
    
    // Widths for A4 Portrait (Total 188 <= 190)
    // No(8), UPT(42), BM(12), PB(12), CB(12), CMB(12), Asim(12), BDH(12), Lainnya(14), Total(14), Ket(40)
    $widths = [8, 42, 12, 12, 12, 12, 12, 12, 14, 14, 40];
    
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetTextColor(0, 0, 0);
    
    // Header row
    $pdf->Cell($widths[0], 7, 'NO', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 7, 'NAMA UPT', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 7, 'BM', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 7, 'PB', 1, 0, 'C', true);
    $pdf->Cell($widths[4], 7, 'CB', 1, 0, 'C', true);
    $pdf->Cell($widths[5], 7, 'CMB', 1, 0, 'C', true);
    $pdf->Cell($widths[6], 7, 'ASIM', 1, 0, 'C', true);
    $pdf->Cell($widths[7], 7, 'BDH', 1, 0, 'C', true);
    $pdf->Cell($widths[8], 7, 'LAINNYA', 1, 0, 'C', true);
    $pdf->Cell($widths[9], 7, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell($widths[10], 7, 'KETERANGAN', 1, 1, 'C', true);
    
    // Data rows
    $pdf->SetFont('helvetica', '', 7.5);
    
    $no = 1;
    $totBm = 0; $totPb = 0; $totCb = 0; $totCmb = 0; $totAsim = 0; $totBdh = 0; $totLain = 0; $grandTotal = 0;
    
    foreach ($data as $row) {
        $bm = (int)($row['total_bm'] ?? 0);
        $pb = (int)($row['total_pb'] ?? 0);
        $cb = (int)($row['total_cb'] ?? 0);
        $cmb = (int)($row['total_cmb'] ?? 0);
        $asim = (int)($row['total_asimilasi'] ?? 0);
        $bdh = (int)($row['total_bdh'] ?? 0);
        $lain = (int)($row['total_lainnya'] ?? 0);
        $sub = (int)($row['total_bebas'] ?? 0);
        
        $totBm += $bm; $totPb += $pb; $totCb += $cb; $totCmb += $cmb; $totAsim += $asim; $totBdh += $bdh; $totLain += $lain;
        $grandTotal += $sub;
        
        $textUPT = htmlspecialchars($row['nama_upt'] ?? '-');
        $rincianLainnya = !empty($row['rincian_lainnya']) ? ' (' . $row['rincian_lainnya'] . ')' : '';
        $ketGlobal = !empty($row['keterangan']) ? $row['keterangan'] : '';
        $textKet = trim($ketGlobal . $rincianLainnya);
        if (empty($textKet)) $textKet = '-';
        
        $numLinesUPT = $pdf->getNumLines($textUPT, $widths[1]);
        $numLinesKet = $pdf->getNumLines($textKet, $widths[10]);
        $maxLines = max($numLinesUPT, $numLinesKet, 1);
        $rowH = max(6, $maxLines * 4.5);
        
        $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
        
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
        
        $pdf->Cell($widths[2], $rowH, $bm, 1, 0, 'C');
        $pdf->Cell($widths[3], $rowH, $pb, 1, 0, 'C');
        $pdf->Cell($widths[4], $rowH, $cb, 1, 0, 'C');
        $pdf->Cell($widths[5], $rowH, $cmb, 1, 0, 'C');
        $pdf->Cell($widths[6], $rowH, $asim, 1, 0, 'C');
        $pdf->Cell($widths[7], $rowH, $bdh, 1, 0, 'C');
        $pdf->Cell($widths[8], $rowH, $lain, 1, 0, 'C');
        
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell($widths[9], $rowH, $sub, 1, 0, 'C');
        $pdf->SetFont('helvetica', '', 7.5);
        
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell($widths[10], $rowH, $textKet, 1, 'L', false, 1, $x, $y, true, 0, false, true, $rowH, 'M');
    }
    
    // Total Row
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell($widths[0] + $widths[1], 6, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 6, $totBm, 1, 0, 'C', true);
    $pdf->Cell($widths[3], 6, $totPb, 1, 0, 'C', true);
    $pdf->Cell($widths[4], 6, $totCb, 1, 0, 'C', true);
    $pdf->Cell($widths[5], 6, $totCmb, 1, 0, 'C', true);
    $pdf->Cell($widths[6], 6, $totAsim, 1, 0, 'C', true);
    $pdf->Cell($widths[7], 6, $totBdh, 1, 0, 'C', true);
    $pdf->Cell($widths[8], 6, $totLain, 1, 0, 'C', true);
    $pdf->Cell($widths[9], 6, $grandTotal, 1, 0, 'C', true);
    $pdf->Cell($widths[10], 6, '', 1, 1, 'C', true);
    
    // Footer & Signature
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Demikian laporan pengeluaran / bebas harian ini disampaikan, atas perhatiannya diucapkan terima kasih.', 0, 1, 'L');
    $pdf->Ln(5);
    
    $tempatTtd = $_GET['tempat_ttd'] ?? 'Serang';
    $jabatanTtd = $_GET['jabatan_ttd'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
    $namaTtd = $_GET['nama_ttd'] ?? 'Surya Dharma';
    $tglTtd = function_exists('formatIndonesianDate') ? formatIndonesianDate(date('Y-m-d'), false) : date('d F Y');
    
    $sigX = 130;
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $tempatTtd . ', ' . $tglTtd, 0, 1, 'C');
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $jabatanTtd, 0, 1, 'C');
    $pdf->Ln(2);
    
    $yBeforeSig = $pdf->GetY();
    global $instansi;
    if (!empty($instansi['ttd_image']) && file_exists(__DIR__ . '/' . $instansi['ttd_image'])) {
        $pdf->Image(__DIR__ . '/' . $instansi['ttd_image'], $sigX + 15, $yBeforeSig - 2, 40, 0);
    }
    if (!empty($instansi['stempel_image']) && file_exists(__DIR__ . '/' . $instansi['stempel_image'])) {
        $pdf->Image(__DIR__ . '/' . $instansi['stempel_image'], $sigX + 5, $yBeforeSig - 5, 35, 0);
    }
    
    $pdf->Ln(20);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetX($sigX);
    $pdf->Cell(70, 5, $namaTtd, 0, 1, 'C');
}

// Create PDF
$orientation = ($reportType === 'overstaying' || $reportType === 'penghuni' || $reportType === 'bebas_harian') ? 'P' : 'L';
$pdf = new MYPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
$pdf->setInstansi($instansi);
$pdf->reportType = $reportType;
$pdf->setReportTitle($reportTitles[$reportType] ?? 'Laporan');
$pdf->SetCreator('Sistem Monitoring UPT');
$pdf->SetAuthor($user['nama_lengkap'] ?? 'Admin');
$pdf->SetTitle($reportTitles[$reportType] ?? 'Laporan');
$pdf->SetSubject('Laporan Monitoring UPT');

$pdf->SetMargins(10, 10, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(25);
$pdf->SetAutoPageBreak(true, 25);

$pdf->AddPage();

// Print First Page Header
if ($reportType === 'overstaying' || $reportType === 'penghuni' || $reportType === 'bebas_harian') {
    // Print Logo if available
    $logoKopPath = '';
    if (!empty($instansi['logo_kop']) && file_exists(__DIR__ . '/' . $instansi['logo_kop'])) {
        $logoKopPath = __DIR__ . '/' . $instansi['logo_kop'];
    } elseif (!empty($instansi['logo']) && file_exists(__DIR__ . '/' . $instansi['logo'])) {
        $logoKopPath = __DIR__ . '/' . $instansi['logo'];
    }
    
    if ($logoKopPath) {
        // Draw image, X=15, Y=10, Width=25
        $pdf->Image($logoKopPath, 15, 10, 25);
    }
    
    // Shift text to the right of the logo so they don't overlap
    // Width of page minus margins: 210 - 10 - 10 = 190
    // Logo takes up space until X=40. Let's start text at X=45.
    $wKop = 155; // 200 (right margin) - 45
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(45);
    $pdf->Cell($wKop, 5, 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 0, 1, 'C');
    $pdf->SetX(45);
    $pdf->Cell($wKop, 5, 'DIREKTORAT JENDERAL PEMASYARAKATAN', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetX(45);
    $pdf->Cell($wKop, 5, 'KANTOR WILAYAH BANTEN', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(45);
    $pdf->Cell($wKop, 5, "Jl.KH.Sam'un No.44 Serang, Banten", 0, 1, 'C');
    $pdf->SetX(45);
    $pdf->Cell($wKop, 5, "Telepon: 0822-6666-2044, Faksimilie: 0822-6666-2044", 0, 1, 'C');
    $pdf->SetX(45);
    $pdf->Cell($wKop, 5, "Laman: djpasbanten@gmail.com, Pos-el: djpasbanten@gmail.com", 0, 1, 'C');
    
    $pdf->Ln(2);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // 200 for A4 Portrait (210-10)
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', 'B', 11);
    
    $judulLaporan = 'LAPORAN OVERSTAYING TAHANAN';
    if ($reportType === 'penghuni') {
        $judulLaporan = 'LAPORAN DATA PENGHUNI & OVERKAPASITAS';
    } elseif ($reportType === 'bebas_harian') {
        $judulLaporan = 'LAPORAN PENGELUARAN / BEBAS HARIAN';
    }
    
    $pdf->Cell(0, 6, $judulLaporan, 0, 1, 'C');
    $pdf->Cell(0, 6, 'UPT PEMASYARAKATAN KANTOR WILAYAH BANTEN', 0, 1, 'C');
    $pdf->Ln(2);
} else {
    $namaInstansi = $instansi['nama_instansi'] ?? 'KEMENTERIAN HUKUM DAN HAK ASASI MANUSIA';
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, strtoupper($namaInstansi), 0, 1, 'C');
    
    $alamat = $instansi['alamat'] ?? '';
    if (!empty($alamat)) {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, $alamat, 0, 1, 'C');
    }
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, $reportTitles[$reportType] ?? 'Laporan', 0, 1, 'C');
    $pdf->Ln(5);
}

if ($reportType !== 'overstaying' && $reportType !== 'penghuni' && $reportType !== 'bebas_harian') {
    // Period info
    $pdf->SetFont('helvetica', '', 9);
    $periodeStr = 'Semua Periode';
    if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
        $periodeStr = date('d/m/Y', strtotime($tanggalMulai)) . ' - ' . date('d/m/Y', strtotime($tanggalAkhir));
    } elseif (!empty($tanggalMulai)) {
        $periodeStr = 'Mulai ' . date('d/m/Y', strtotime($tanggalMulai));
    } elseif (!empty($tanggalAkhir)) {
        $periodeStr = 'Sampai ' . date('d/m/Y', strtotime($tanggalAkhir));
    }
    $pdf->Cell(0, 5, 'Periode: ' . $periodeStr, 0, 1, 'L');

    if ($uptId) {
        $stmt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
        $stmt->execute([$uptId]);
        $upt = $stmt->fetch();
        $pdf->Cell(0, 5, 'UPT: ' . ($upt['nama_upt'] ?? '-'), 0, 1, 'L');
    } else {
        $pdf->Cell(0, 5, 'UPT: Semua UPT', 0, 1, 'L');
    }

    $pdf->Ln(5);
}

// Get data
$data = getReportData($reportType, $uptId, $tanggalMulai, $tanggalAkhir);

// Generate table
switch ($reportType) {
    case 'penghuni':
        generatePenghuniTable($pdf, $data);
        break;
    case 'overstaying':
        generateOverstayingTable($pdf, $data);
        break;
    case 'integrasi':
        generateIntegrasiTable($pdf, $data);
        break;
    case 'bebas_harian':
        generateBebasHarianTable($pdf, $data);
        break;
    case 'rekapitulasi':
        generateRekapitulasiTable($pdf, $data);
        break;
}

// Output
$filename = $reportType . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
