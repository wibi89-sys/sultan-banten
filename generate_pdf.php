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
    'rekapitulasi' => 'LAPORAN REKAPITULASI'
];

// Custom PDF class
class MYPDF extends TCPDF {
    private $instansi = [];
    private $reportTitle = '';
    
    public function setInstansi($instansi) {
        $this->instansi = is_array($instansi) ? $instansi : [];
    }
    
    public function setReportTitle($title) {
        $this->reportTitle = $title;
    }
    
    public function Header() {
        if ($this->getPage() == 1) {
            $namaInstansi = $this->instansi['nama_instansi'] ?? 'KEMENTERIAN HUKUM DAN HAK ASASI MANUSIA';
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 8, strtoupper($namaInstansi), 0, 1, 'C');
            
            $alamat = $this->instansi['alamat'] ?? '';
            if (!empty($alamat)) {
                $this->SetFont('helvetica', '', 9);
                $this->Cell(0, 5, $alamat, 0, 1, 'C');
            }
            $this->Ln(5);
            
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 7, $this->reportTitle, 0, 1, 'C');
            $this->Ln(5);
        }
    }
    
    public function Footer() {
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
                $sql = "SELECT 
                        u.nama_upt, u.kapasitas,
                        COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan), 0) as total_tahanan,
                        COALESCE(SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_narapidana,
                        COALESCE(SUM(do.tahanan_ai + do.tahanan_aii + do.tahanan_aiii + do.tahanan_aiv + do.tahanan_av + 
                            do.overstaying_ai + do.overstaying_aii + do.overstaying_aiii + do.overstaying_aiv + do.overstaying_av), 0) as total_overstaying,
                        COALESCE(SUM(di.usulan_pb + di.usulan_cb + di.usulan_cmb + di.usulan_cmk + di.usulan_asimilasi + 
                            di.sk_terbit_pb + di.sk_terbit_cb + di.sk_terbit_cmb + di.sk_terbit_cmk + di.sk_terbit_asimilasi), 0) as total_integrasi
                        FROM upt u
                        LEFT JOIN data_penghuni dp ON u.id = dp.upt_id AND dp.tanggal BETWEEN ? AND ?
                        LEFT JOIN data_overstaying do ON u.id = do.upt_id AND do.tanggal BETWEEN ? AND ?
                        LEFT JOIN data_integrasi di ON u.id = di.upt_id AND di.tanggal BETWEEN ? AND ?";
                $params = [$tanggalMulai, $tanggalAkhir, $tanggalMulai, $tanggalAkhir, $tanggalMulai, $tanggalAkhir];
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
    $widths = [8, 75, 18, 35, 13, 13, 13, 13, 13, 13, 13, 13, 17];
    
    // Header row 1
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell($widths[0], 12, 'No', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 12, 'UPT', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 12, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 12, 'Klasifikasi', 1, 0, 'C', true);
    $pdf->Cell(52, 6, 'Tahanan', 1, 0, 'C', true);
    $pdf->Cell(52, 6, 'Narapidana', 1, 0, 'C', true);
    $pdf->Cell($widths[12], 12, 'Total', 1, 1, 'C', true);
    
    // Header row 2
    $pdf->Cell(array_sum(array_slice($widths, 0, 4)), 0, '', 0, 0);
    $pdf->SetFillColor(66, 87, 108);
    $pdf->Cell($widths[4], 6, 'DL', 1, 0, 'C', true);
    $pdf->Cell($widths[5], 6, 'DP', 1, 0, 'C', true);
    $pdf->Cell($widths[6], 6, 'AL', 1, 0, 'C', true);
    $pdf->Cell($widths[7], 6, 'AP', 1, 0, 'C', true);
    $pdf->Cell($widths[8], 6, 'DL', 1, 0, 'C', true);
    $pdf->Cell($widths[9], 6, 'DP', 1, 0, 'C', true);
    $pdf->Cell($widths[10], 6, 'AL', 1, 0, 'C', true);
    $pdf->Cell($widths[11], 6, 'AP', 1, 0, 'C', true);
    $pdf->Cell($widths[12], 0, '', 0, 1);
    
    // Data
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    
    $no = 1;
    $grandTotal = 0;
    $fill = false;
    
    foreach ($data as $row) {
        $total = (int)($row['tahanan_dewasa_laki'] ?? 0) + (int)($row['tahanan_dewasa_perempuan'] ?? 0) + 
                 (int)($row['tahanan_anak_laki'] ?? 0) + (int)($row['tahanan_anak_perempuan'] ?? 0) +
                 (int)($row['narapidana_dewasa_laki'] ?? 0) + (int)($row['narapidana_dewasa_perempuan'] ?? 0) + 
                 (int)($row['narapidana_anak_laki'] ?? 0) + (int)($row['narapidana_anak_perempuan'] ?? 0);
        $grandTotal += $total;
        
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 247 : 255, $fill ? 250 : 255);
        $fill = !$fill;
        
        $pdf->Cell($widths[0], 5.5, $no++, 1, 0, 'C', true);
        $pdf->Cell($widths[1], 5.5, $row['nama_upt'] ?? '-', 1, 0, 'L', true);
        $pdf->Cell($widths[2], 5.5, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C', true);
        $pdf->Cell($widths[3], 5.5, $row['klasifikasi_pidana'] ?? '-', 1, 0, 'L', true);
        $pdf->Cell($widths[4], 5.5, (int)($row['tahanan_dewasa_laki'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[5], 5.5, (int)($row['tahanan_dewasa_perempuan'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[6], 5.5, (int)($row['tahanan_anak_laki'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[7], 5.5, (int)($row['tahanan_anak_perempuan'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[8], 5.5, (int)($row['narapidana_dewasa_laki'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[9], 5.5, (int)($row['narapidana_dewasa_perempuan'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[10], 5.5, (int)($row['narapidana_anak_laki'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[11], 5.5, (int)($row['narapidana_anak_perempuan'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[12], 5.5, $total, 1, 1, 'C', true);
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(array_sum(array_slice($widths, 0, 12)), 6, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell($widths[12], 6, $grandTotal, 1, 1, 'C', true);
}

// Generate Overstaying Table
function generateOverstayingTable($pdf, $data) {
    $widths = [8, 70, 16, 13, 13, 13, 13, 13, 13, 13, 13, 13, 13, 16];
    
    // Header row 1
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell($widths[0], 12, 'No', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 12, 'UPT', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 12, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell(65, 6, 'Tahanan', 1, 0, 'C', true);
    $pdf->Cell(65, 6, 'Overstaying', 1, 0, 'C', true);
    $pdf->Cell($widths[13], 12, 'Total', 1, 1, 'C', true);
    
    // Header row 2
    $pdf->Cell(array_sum(array_slice($widths, 0, 3)), 0, '', 0, 0);
    $pdf->SetFillColor(66, 87, 108);
    $pdf->Cell($widths[3], 6, 'AI', 1, 0, 'C', true);
    $pdf->Cell($widths[4], 6, 'AII', 1, 0, 'C', true);
    $pdf->Cell($widths[5], 6, 'AIII', 1, 0, 'C', true);
    $pdf->Cell($widths[6], 6, 'AIV', 1, 0, 'C', true);
    $pdf->Cell($widths[7], 6, 'AV', 1, 0, 'C', true);
    $pdf->Cell($widths[8], 6, 'AI', 1, 0, 'C', true);
    $pdf->Cell($widths[9], 6, 'AII', 1, 0, 'C', true);
    $pdf->Cell($widths[10], 6, 'AIII', 1, 0, 'C', true);
    $pdf->Cell($widths[11], 6, 'AIV', 1, 0, 'C', true);
    $pdf->Cell($widths[12], 6, 'AV', 1, 0, 'C', true);
    $pdf->Cell($widths[13], 0, '', 0, 1);
    
    // Data
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    
    $no = 1;
    $grandTotal = 0;
    $fill = false;
    
    foreach ($data as $row) {
        $total = (int)($row['tahanan_ai'] ?? 0) + (int)($row['tahanan_aii'] ?? 0) + (int)($row['tahanan_aiii'] ?? 0) + 
                 (int)($row['tahanan_aiv'] ?? 0) + (int)($row['tahanan_av'] ?? 0) +
                 (int)($row['overstaying_ai'] ?? 0) + (int)($row['overstaying_aii'] ?? 0) + (int)($row['overstaying_aiii'] ?? 0) + 
                 (int)($row['overstaying_aiv'] ?? 0) + (int)($row['overstaying_av'] ?? 0);
        $grandTotal += $total;
        
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 247 : 255, $fill ? 250 : 255);
        $fill = !$fill;
        
        $pdf->Cell($widths[0], 5.5, $no++, 1, 0, 'C', true);
        $pdf->Cell($widths[1], 5.5, $row['nama_upt'] ?? '-', 1, 0, 'L', true);
        $pdf->Cell($widths[2], 5.5, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C', true);
        $pdf->Cell($widths[3], 5.5, (int)($row['tahanan_ai'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[4], 5.5, (int)($row['tahanan_aii'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[5], 5.5, (int)($row['tahanan_aiii'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[6], 5.5, (int)($row['tahanan_aiv'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[7], 5.5, (int)($row['tahanan_av'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[8], 5.5, (int)($row['overstaying_ai'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[9], 5.5, (int)($row['overstaying_aii'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[10], 5.5, (int)($row['overstaying_aiii'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[11], 5.5, (int)($row['overstaying_aiv'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[12], 5.5, (int)($row['overstaying_av'] ?? 0), 1, 0, 'C', true);
        $pdf->Cell($widths[13], 5.5, $total, 1, 1, 'C', true);
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(array_sum(array_slice($widths, 0, 13)), 6, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell($widths[13], 6, $grandTotal, 1, 1, 'C', true);
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

// Create PDF
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setInstansi($instansi);
$pdf->setReportTitle($reportTitles[$reportType] ?? 'Laporan');
$pdf->SetCreator('Sistem Monitoring UPT');
$pdf->SetAuthor($user['nama_lengkap'] ?? 'Admin');
$pdf->SetTitle($reportTitles[$reportType] ?? 'Laporan');
$pdf->SetSubject('Laporan Monitoring UPT');

$pdf->SetMargins(10, 15, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(15);
$pdf->SetAutoPageBreak(true, 15);

$pdf->AddPage();

// Period info
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' - ' . date('d/m/Y', strtotime($tanggalAkhir)), 0, 1, 'L');

if ($uptId) {
    $stmt = $pdo->prepare("SELECT nama_upt FROM upt WHERE id = ?");
    $stmt->execute([$uptId]);
    $upt = $stmt->fetch();
    $pdf->Cell(0, 5, 'UPT: ' . ($upt['nama_upt'] ?? '-'), 0, 1, 'L');
} else {
    $pdf->Cell(0, 5, 'UPT: Semua UPT', 0, 1, 'L');
}

$pdf->Ln(5);

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
    case 'rekapitulasi':
        generateRekapitulasiTable($pdf, $data);
        break;
}

// Output
$filename = $reportType . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
