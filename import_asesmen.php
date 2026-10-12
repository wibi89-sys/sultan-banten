<?php
/**
 * Import Data Asesmen
 */
require_once 'includes/functions.php';
require_once 'auth_asesmen.php';

$user = getCurrentUser();
$uptList = getAllUPT();

$success_msg = '';
$error_msg = '';

function findRowValue($row, $candidates) {
    foreach ($candidates as $c) {
        if (isset($row[$c]) && trim((string)$row[$c]) !== '') {
            return trim((string)$row[$c]);
        }
    }
    $cleanCandidates = array_map(function($c) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $c));
    }, $candidates);

    foreach ($row as $k => $v) {
        if (trim((string)$v) === '') continue;
        $cleanK = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $k));
        if (in_array($cleanK, $cleanCandidates)) {
            return trim((string)$v);
        }
    }
    return '';
}

function parseExcelDate($val) {
    if ($val === null || $val === '') return '';
    $val = trim((string)$val);
    if ($val === '' || $val === '-' || $val === '0000-00-00') return '';

    // If Excel serial number (numeric, e.g. 44927)
    if (is_numeric($val) && (float)$val > 1000 && (float)$val < 100000) {
        $excelSerial = (float)$val;
        $unixTimestamp = ($excelSerial - 25569) * 86400;
        return gmdate('Y-m-d', (int)$unixTimestamp);
    }

    // YYYY-MM-DD
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    // DD/MM/YYYY or DD-MM-YYYY
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }

    // ISO string like 2026-05-01T00:00:00.000Z
    if (strpos($val, 'T') !== false) {
        $time = strtotime($val);
        if ($time !== false && $time > 0) {
            return date('Y-m-d', $time);
        }
    }

    // Standard strtotime
    $time = strtotime($val);
    if ($time !== false && $time > 0) {
        return date('Y-m-d', $time);
    }

    // Indonesian month names
    $bulanIndo = [
        'januari' => '01', 'jan' => '01',
        'februari' => '02', 'feb' => '02',
        'maret' => '03', 'mar' => '03',
        'april' => '04', 'apr' => '04',
        'mei' => '05', 'may' => '05',
        'juni' => '06', 'jun' => '06',
        'juli' => '07', 'jul' => '07',
        'agustus' => '08', 'ags' => '08', 'agt' => '08',
        'september' => '09', 'sep' => '09',
        'oktober' => '10', 'okt' => '10', 'oct' => '10',
        'november' => '11', 'nov' => '11',
        'desember' => '12', 'des' => '12', 'dec' => '12'
    ];
    $lower = strtolower($val);
    foreach ($bulanIndo as $name => $num) {
        if (strpos($lower, $name) !== false) {
            $lower = str_replace($name, $num, $lower);
            if (preg_match('/(\d{1,2})\s*[\/\-\s]\s*(\d{1,2})\s*[\/\-\s]\s*(\d{4})/', $lower, $m)) {
                return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
        }
    }

    return $val;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excel_data'])) {
    $data_array = json_decode($_POST['excel_data'], true);
    
    if (is_array($data_array) && count($data_array) > 0) {
        $imported = 0;
        foreach ($data_array as $row) {
            $nama = findRowValue($row, ['Nama', 'nama', 'Nama_Lengkap', 'Nama Lengkap', 'WBP', 'Nama WBP']);
            $no_register = findRowValue($row, ['No_Register', 'no_register', 'Register', 'No Register', 'ID_SDP', 'ID SDP', 'No. Register', 'No Register / ID SDP']);
            $upt = findRowValue($row, ['UPT', 'upt', 'Upt', 'Nama UPT', 'Lapas', 'Rutan', 'Satuan Kerja']);
            $ttl = findRowValue($row, ['Tempat_Tanggal_Lahir', 'ttl', 'TTL', 'Tempat Tanggal Lahir', 'Tempat, Tanggal Lahir', 'Tempat Tgl Lahir', 'Tempat / Tgl Lahir']);
            $usia = findRowValue($row, ['Usia_Saat_Asesmen', 'Usia', 'usia', 'Umur', 'Usia Saat Asesmen']);
            $pasal = findRowValue($row, ['Perkara_Pasal', 'Pasal', 'pasal', 'Perkara', 'Pasal dan Perkara', 'Pasal / Perkara', 'Perkara / Pasal']);
            $lama_pidana = findRowValue($row, ['Lama_Pidana', 'lama_pidana', 'Pidana', 'Lama Pidana', 'Masa Pidana']);
            
            $tgl_mulai = parseExcelDate(findRowValue($row, [
                'Tgl_Mulai_Pidana', 'tgl_mulai', 'Tanggal Mulai Pidana', 'Tgl Mulai Pidana', 
                'Tgl Mulai', 'Tanggal Mulai', 'Mulai Pidana', 'Awal Pidana', 'Tgl Awal Pidana'
            ]));
            
            $tgl_23 = parseExcelDate(findRowValue($row, [
                'Tgl_2_3_Pidana', 'tgl_23', 'Tanggal 2/3 Pidana', 'Tgl 2/3 Pidana', 
                'Tanggal 2/3', 'Tgl 2/3', '2/3 Masa Pidana', '2/3 Pidana', 
                'Tgl 2/3 Masa Pidana', 'Tanggal 2/3 Masa Pidana', 'Dua Pertiga', 
                'Tgl Dua Pertiga', 'Tgl_23_Pidana', 'Tgl 2 3 Pidana'
            ]));
            
            $tgl_bebas = parseExcelDate(findRowValue($row, [
                'Tgl_Bebas', 'tgl_bebas', 'Tanggal Bebas', 'Tgl Bebas', 
                'Ekspirasi', 'Tgl Ekspirasi', 'Tanggal Ekspirasi', 'Bebas', 'Tgl Bebas Murni'
            ]));

            if (empty($nama)) continue;

            $extra_data = [
                'm0_pasal' => $pasal,
                'm0_lama_pidana' => $lama_pidana,
                'm0_tgl_mulai' => $tgl_mulai,
                'm0_tgl_23' => $tgl_23,
                'm0_tgl_bebas' => $tgl_bebas
            ];
            
            $gejala = [];
            for ($i = 0; $i < 11; $i++) {
                $gejala["gejala_$i"] = '';
            }
            $gejala['extra_data'] = $extra_data; 
            $gejala_json = json_encode($gejala);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO data_asesmen (
                    nama, no_register, upt, ttl, usia, tgl_asesmen,
                    gejala_json, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?
                )");
                $stmt->execute([
                    $nama, $no_register, $upt, $ttl, $usia, date('Y-m-d'),
                    $gejala_json, $user['id'] ?? null
                ]);
                $imported++;
            } catch (Exception $e) {
                // ignore duplicate or db error
            }
        }
        if ($imported > 0) {
            $success_msg = "Berhasil mengimpor $imported data WBP.";
        } else {
            $error_msg = "Tidak ada data yang berhasil diimpor atau data kosong.";
        }
    } else {
        $error_msg = "Data Excel tidak valid atau kosong.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Asesmen - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <!-- SheetJS for Excel Reading/Writing -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-clipboard-list fa-3x text-white mb-2"></i>
            <h5>Portal Asesmen</h5>
        </div>
        <div class="sidebar-menu">
            <a href="asesmen.php"><i class="fas fa-plus"></i><span>Input Asesmen</span></a>
            <a href="riwayat_asesmen.php"><i class="fas fa-history"></i><span>Riwayat Asesmen</span></a>
            <a href="import_asesmen.php" class="active"><i class="fas fa-upload"></i><span>Import Asesmen</span></a>
            <a href="auth_asesmen.php?logout=1" style="color:#ffcccc; margin-top:20px;"><i class="fas fa-sign-out-alt"></i><span>Logout Asesor</span></a>
        </div>
    </nav>

    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0"><i class="fas fa-upload me-2"></i>Import Data Awal Asesmen</h4>
            </div>
        </div>

        <div class="content-card">
            <?php if($success_msg): ?><div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if($error_msg): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error_msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="fas fa-file-excel me-2"></i>Format Import Excel</h5>
                            <p class="card-text">Gunakan format file Excel (.xlsx atau .xls) untuk mengimpor data awal Warga Binaan Pemasyarakatan (WBP). Klik tombol di bawah untuk mengunduh template Excel.</p>
                            <button type="button" class="btn btn-outline-success" onclick="downloadExcelTemplate()"><i class="fas fa-download me-2"></i>Download Template Excel</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="fas fa-upload me-2"></i>Unggah File Excel</h5>
                            <form action="" method="POST" id="importForm">
                                <input type="hidden" name="excel_data" id="excel_data">
                                <div class="mb-3">
                                    <label class="form-label">Pilih file .xlsx atau .xls</label>
                                    <input type="file" class="form-control" id="file_excel" accept=".xlsx, .xls, .csv" required>
                                </div>
                                <button type="button" class="btn btn-success" id="btnImport"><i class="fas fa-upload me-2"></i>Import Data Excel</button>
                            </form>
                        </div>
                    </div>
                </div>
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

        // Generate and download Excel Template
        function downloadExcelTemplate() {
            var wb = XLSX.utils.book_new();
            var ws_data = [
                ["Nama", "No Register", "UPT", "Tempat Tanggal Lahir", "Usia", "Perkara Pasal", "Lama Pidana", "Tanggal Mulai Pidana", "Tanggal 2/3 Pidana", "Tanggal Bebas"],
                ["Budi Santoso", "BI.1234.2023", "Lapas Kelas I Tangerang", "Tangerang, 01 Januari 1990", 33, "Pasal 114 UU Narkotika", "5 Tahun", "2023-01-01", "2026-05-01", "2028-01-01"]
            ];
            var ws = XLSX.utils.aoa_to_sheet(ws_data);
            XLSX.utils.book_append_sheet(wb, ws, "Data_WBP");
            XLSX.writeFile(wb, "Template_WBP_Asesmen.xlsx");
        }

        // Handle Excel Upload
        document.getElementById('btnImport').addEventListener('click', function() {
            var fileInput = document.getElementById('file_excel');
            if(fileInput.files.length === 0) {
                alert("Silakan pilih file Excel terlebih dahulu!");
                return;
            }

            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {type: 'array', cellDates: true, dateNF: 'yyyy-mm-dd'});
                
                // ambil sheet pertama
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];
                
                // konversi sheet ke json
                var jsonData = XLSX.utils.sheet_to_json(worksheet, {raw: false, dateNF: 'yyyy-mm-dd', defval: ""});
                
                if(jsonData.length > 0) {
                    document.getElementById('excel_data').value = JSON.stringify(jsonData);
                    document.getElementById('importForm').submit();
                } else {
                    alert("File Excel kosong atau format tidak sesuai.");
                }
            };
            reader.readAsArrayBuffer(file);
        });
    </script>
</body>
</html>
