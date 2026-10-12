<?php
/**
 * Asesmen Page
 * Instrumen Kelayakan dan Penempatan Rehabilitasi dan Re-Edukasi Narapidana
 */

require_once 'includes/functions.php';
require_once 'auth_asesmen.php';

$user = getCurrentUser();
$instansi = getInstansi();
$uptList = getAllUPT();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect all JSON data for fields not in schema
        $extra_data = [
            'jalur_asesmen' => $_POST['jalur_asesmen'] ?? 'rehabilitasi',
            'm0_pasal' => $_POST['m0_pasal'] ?? '',
            'm0_lama_pidana' => $_POST['m0_lama_pidana'] ?? '',
            'm0_tgl_mulai' => $_POST['m0_tgl_mulai'] ?? '',
            'm0_tgl_23' => $_POST['m0_tgl_23'] ?? '',
            'm0_tgl_bebas' => $_POST['m0_tgl_bebas'] ?? '',
            'm0_nama_asesor' => $_POST['m0_nama_asesor'] ?? '',
            'm0_nama_reviu' => $_POST['m0_nama_reviu'] ?? '',
            'modul0_7' => $_POST['modul0_7'] ?? '',
            'modul0_8' => $_POST['modul0_8'] ?? '',
            'modul0_9' => $_POST['modul0_9'] ?? '',
            'm1_pasal' => $_POST['m1_pasal'] ?? '',
            'm1_bb' => $_POST['m1_bb'] ?? '',
            'm1_peran' => $_POST['m1_peran'] ?? '',
            'm1_tat' => $_POST['m1_tat'] ?? '',
            'm1_urin' => $_POST['m1_urin'] ?? '',
            'm1_frekuensi' => $_POST['m1_frekuensi'] ?? '',
            'm1_cara' => $_POST['m1_cara'] ?? '',
            'm1_terakhir' => $_POST['m1_terakhir'] ?? '',
            'm1_rehab' => $_POST['m1_rehab'] ?? '',
            'm1_rehab_ket' => $_POST['m1_rehab_ket'] ?? '',
            'm1_berhenti' => $_POST['m1_berhenti'] ?? '',
            'm1_berhenti_kali' => $_POST['m1_berhenti_kali'] ?? '',
            'm1_berhenti_ket' => $_POST['m1_berhenti_ket'] ?? '',
            'm1_tgl_urin' => $_POST['m1_tgl_urin'] ?? '',
            'm1_tri_1' => $_POST['m1_tri_1'] ?? '',
            'm1_tri_2' => $_POST['m1_tri_2'] ?? '',
            'm1_tri_3' => $_POST['m1_tri_3'] ?? '',
            'm1_tri_3_ket' => $_POST['m1_tri_3_ket'] ?? '',
            'm1_rf_kuat1' => $_POST['m1_rf_kuat1'] ?? '',
            'm1_rf_kuat2' => $_POST['m1_rf_kuat2'] ?? '',
            'm1_rf_kuat3' => $_POST['m1_rf_kuat3'] ?? '',
            'm1_rf_kuat4' => $_POST['m1_rf_kuat4'] ?? '',
            'm1_rf_dukung1' => $_POST['m1_rf_dukung1'] ?? '',
            'm1_rf_dukung2' => $_POST['m1_rf_dukung2'] ?? '',
            'm1_rf_dukung3' => $_POST['m1_rf_dukung3'] ?? '',
            'm1_rf_dukung4' => $_POST['m1_rf_dukung4'] ?? '',
            'm1_catatan' => $_POST['m1_catatan'] ?? '',
            'm1_rekomendasi' => $_POST['m1_rekomendasi'] ?? '',
            'm2_tb' => $_POST['m2_tb'] ?? '',
            'm2_hiv' => $_POST['m2_hiv'] ?? '',
            'm2_kronis' => $_POST['m2_kronis'] ?? '',
            'm2_intoksikasi' => $_POST['m2_intoksikasi'] ?? '',
            'm2_srq' => $_POST['m2_srq'] ?? '',
            'm2_srq_batas' => $_POST['m2_srq_batas'] ?? '',
            'm2_jiwa' => $_POST['m2_jiwa'] ?? '',
            'm2_syarat' => $_POST['m2_syarat'] ?? '',
            'm2_layanan1' => $_POST['m2_layanan1'] ?? '',
            'm2_layanan2' => $_POST['m2_layanan2'] ?? '',
            'm2_layanan3' => $_POST['m2_layanan3'] ?? '',
            'm2_layanan4' => $_POST['m2_layanan4'] ?? '',
            'm2_layanan5' => $_POST['m2_layanan5'] ?? '',
            'm2_nakes_nama' => $_POST['m2_nakes_nama'] ?? '',
            'm2_nakes_tgl' => $_POST['m2_nakes_tgl'] ?? '',
            'm2_imt' => $_POST['m2_imt'] ?? '',
            'pernapasan' => $_POST['pernapasan'] ?? '',
            'm3_kriminogenik' => $_POST['m3_kriminogenik'] ?? '',
            'm4a_poin_minat' => $_POST['m4a_poin_minat'] ?? '',
            'm4a_pengalaman' => $_POST['m4a_pengalaman'] ?? '',
            'm4a_bidang_kerja' => $_POST['m4a_bidang_kerja'] ?? '',
            'm4a_kesediaan' => $_POST['m4a_kesediaan'] ?? '',
            'score_kementan' => $_POST['score_kementan'] ?? '',
            'score_kkp' => $_POST['score_kkp'] ?? '',
            'score_mekanik' => $_POST['score_mekanik'] ?? '',
            'score_produksi' => $_POST['score_produksi'] ?? '',
            'score_jasa' => $_POST['score_jasa'] ?? '',
            'm4b_raw' => $_POST['m4b_raw'] ?? '',
            'm4b_t1' => $_POST['m4b_t1'] ?? '',
            'm4b_t2' => $_POST['m4b_t2'] ?? '',
            'm4b_t3' => $_POST['m4b_t3'] ?? '',
            'm4b_catatan' => $_POST['m4b_catatan'] ?? '',
            'm4b_konsisten' => $_POST['m4b_konsisten'] ?? '',
            'm4b_ketidaksesuaian' => $_POST['m4b_ketidaksesuaian'] ?? '',
            'm4c_terbatas' => $_POST['m4c_terbatas'] ?? '',
            'catatan_m1_k2' => $_POST['catatan_m1_k2'] ?? '',
            'catatan_m2_bersyarat' => $_POST['catatan_m2_bersyarat'] ?? '',
            'catatan_ketidaksesuaian' => $_POST['catatan_ketidaksesuaian'] ?? '',
            'catatan_kekerasan' => $_POST['catatan_kekerasan'] ?? '',
            'catatan_sikap_terbatas' => $_POST['catatan_sikap_terbatas'] ?? '',
            'catatan_minat_rendah' => $_POST['catatan_minat_rendah'] ?? '',
            'dasar_rekomendasi' => $_POST['dasar_rekomendasi'] ?? '',
            'tl_medis' => $_POST['tl_medis'] ?? '',
            'tl_pembinaan' => $_POST['tl_pembinaan'] ?? '',
            'alasan_tl' => $_POST['alasan_tl'] ?? '',
            'alasan_tpp' => $_POST['alasan_tpp'] ?? '',
            'keputusan_tpp' => $_POST['keputusan_tpp'] ?? '',
            'ketua_tpp' => $_POST['ketua_tpp'] ?? '',
            'tgl_sidang' => $_POST['tgl_sidang'] ?? ''
        ];
        
        $m4a_cats = ['kementan', 'kkp', 'mekanik', 'produksi', 'jasa'];
        foreach($m4a_cats as $cat) {
            for($j = 0; $j < 3; $j++) {
                $key = "m4a_item_{$cat}_{$j}";
                $extra_data[$key] = $_POST[$key] ?? '';
            }
        }
        for($j = 0; $j < 12; $j++) {
            $extra_data["m4b_q_{$j}"] = $_POST["m4b_q_{$j}"] ?? '';
        }
        
        for($i=1; $i<=5; $i++) {
            $extra_data["m4c_d{$i}_p1"] = $_POST["m4c_d{$i}_p1"] ?? '';
            $extra_data["m4c_d{$i}_p2"] = $_POST["m4c_d{$i}_p2"] ?? '';
            $extra_data["m4c_d{$i}_akhir"] = $_POST["m4c_d{$i}_akhir"] ?? '';
            $extra_data["m4c_d{$i}_bukti"] = $_POST["m4c_d{$i}_bukti"] ?? '';
        }
        $extra_data['m4c_p1_nama'] = $_POST['m4c_p1_nama'] ?? '';
        $extra_data['m4c_p1_lama'] = $_POST['m4c_p1_lama'] ?? '';
        $extra_data['m4c_p2_nama'] = $_POST['m4c_p2_nama'] ?? '';
        $extra_data['m4c_p2_lama'] = $_POST['m4c_p2_lama'] ?? '';
        $extra_data['m4d_1'] = $_POST['m4d_1'] ?? '';
        $extra_data['m4d_2'] = $_POST['m4d_2'] ?? '';
        $extra_data['m4d_3'] = $_POST['m4d_3'] ?? '';
        
        $gejala = [];
        for ($i = 0; $i < 11; $i++) {
            $gejala["gejala_$i"] = $_POST["gejala_$i"] ?? '';
        }
        $gejala['extra_data'] = $extra_data; // Store extra data in gejala_json column
        $gejala_json = json_encode($gejala);

        $data_params = [
            'nama' => $_POST['nama'] ?? '',
            'no_register' => $_POST['no_register'] ?? '',
            'upt' => $_POST['upt'] ?? '',
            'ttl' => $_POST['ttl'] ?? '',
            'usia' => !empty($_POST['usia']) ? $_POST['usia'] : null,
            'tgl_asesmen' => !empty($_POST['tgl_asesmen']) ? $_POST['tgl_asesmen'] : null,
            
            'modul0_1' => $_POST['modul0_1'] ?? '',
            'modul0_2' => $_POST['modul0_2'] ?? '',
            'modul0_3' => $_POST['modul0_3'] ?? '',
            'modul0_4' => $_POST['modul0_4'] ?? '',
            'modul0_5' => $_POST['modul0_5'] ?? '',
            'modul0_6' => $_POST['modul0_6'] ?? '',
            'hasil_modul0' => $_POST['hasil_modul0'] ?? '',
            
            'zat_utama' => $_POST['zat_utama'] ?? '',
            'usia_pertama_memakai' => !empty($_POST['usia_pertama_memakai']) ? $_POST['usia_pertama_memakai'] : null,
            'lama_pemakaian' => !empty($_POST['lama_pemakaian']) ? $_POST['lama_pemakaian'] : null,
            'gejala_json' => $gejala_json,
            'kategori_modul1' => $_POST['kategori_modul1'] ?? '',
            
            'td' => $_POST['td'] ?? '',
            'nadi' => $_POST['nadi'] ?? '',
            'suhu' => $_POST['suhu'] ?? '',
            'bb' => $_POST['bb'] ?? '',
            'tb' => $_POST['tb'] ?? '',
            'kesimpulan_modul2' => $_POST['kesimpulan_modul2'] ?? '',
            
            'modul3_1' => !empty($_POST['modul3_1']) ? $_POST['modul3_1'] : null,
            'modul3_2' => !empty($_POST['modul3_2']) ? $_POST['modul3_2'] : null,
            'modul3_3' => !empty($_POST['modul3_3']) ? $_POST['modul3_3'] : null,
            'modul3_4' => !empty($_POST['modul3_4']) ? $_POST['modul3_4'] : null,
            'modul3_5' => !empty($_POST['modul3_5']) ? $_POST['modul3_5'] : null,
            
            'klaster_4a' => $_POST['klaster_4a'] ?? '',
            'skor_4a' => !empty($_POST['skor_4a']) ? $_POST['skor_4a'] : null,
            'skor_4b' => !empty($_POST['skor_4b']) ? $_POST['skor_4b'] : null,
            'skor_4c' => !empty($_POST['skor_4c']) ? $_POST['skor_4c'] : null,
            'skor_4d' => !empty($_POST['skor_4d']) ? $_POST['skor_4d'] : null,
            
            'total_skor' => !empty($_POST['total_skor']) ? $_POST['total_skor'] : null,
            'kategori_hasil' => $_POST['kategori_hasil'] ?? '',
            'keputusan_tpp' => $_POST['keputusan_tpp'] ?? '',
            'catatan' => $_POST['catatan'] ?? '',
            'created_by' => null
        ];
        
        if (!empty($_POST['id'])) {
            $update_sql = "UPDATE data_asesmen SET 
                nama=:nama, no_register=:no_register, upt=:upt, ttl=:ttl, usia=:usia, tgl_asesmen=:tgl_asesmen,
                modul0_1=:modul0_1, modul0_2=:modul0_2, modul0_3=:modul0_3, modul0_4=:modul0_4, modul0_5=:modul0_5, modul0_6=:modul0_6, hasil_modul0=:hasil_modul0,
                zat_utama=:zat_utama, usia_pertama_memakai=:usia_pertama_memakai, lama_pemakaian=:lama_pemakaian, gejala_json=:gejala_json, kategori_modul1=:kategori_modul1,
                td=:td, nadi=:nadi, suhu=:suhu, bb=:bb, tb=:tb, kesimpulan_modul2=:kesimpulan_modul2,
                modul3_1=:modul3_1, modul3_2=:modul3_2, modul3_3=:modul3_3, modul3_4=:modul3_4, modul3_5=:modul3_5,
                klaster_4a=:klaster_4a, skor_4a=:skor_4a, skor_4b=:skor_4b, skor_4c=:skor_4c, skor_4d=:skor_4d,
                total_skor=:total_skor, kategori_hasil=:kategori_hasil, keputusan_tpp=:keputusan_tpp, catatan=:catatan
                WHERE id=:id";
            $data_params['id'] = $_POST['id'];
            unset($data_params['created_by']); // don't update created_by
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute($data_params);
            $inserted_id = $_POST['id'];
            $success_msg = 'Data Asesmen berhasil diperbarui! <a href="cetak_asesmen.php?id=' . $inserted_id . '" target="_blank" class="btn btn-sm btn-success ms-3"><i class="fas fa-print me-1"></i> Cetak Format Blanko</a>';
        } else {
            $insert_sql = "INSERT INTO data_asesmen (
                nama, no_register, upt, ttl, usia, tgl_asesmen,
                modul0_1, modul0_2, modul0_3, modul0_4, modul0_5, modul0_6, hasil_modul0,
                zat_utama, usia_pertama_memakai, lama_pemakaian, gejala_json, kategori_modul1,
                td, nadi, suhu, bb, tb, kesimpulan_modul2,
                modul3_1, modul3_2, modul3_3, modul3_4, modul3_5,
                klaster_4a, skor_4a, skor_4b, skor_4c, skor_4d,
                total_skor, kategori_hasil, keputusan_tpp, catatan, created_by
            ) VALUES (
                :nama, :no_register, :upt, :ttl, :usia, :tgl_asesmen,
                :modul0_1, :modul0_2, :modul0_3, :modul0_4, :modul0_5, :modul0_6, :hasil_modul0,
                :zat_utama, :usia_pertama_memakai, :lama_pemakaian, :gejala_json, :kategori_modul1,
                :td, :nadi, :suhu, :bb, :tb, :kesimpulan_modul2,
                :modul3_1, :modul3_2, :modul3_3, :modul3_4, :modul3_5,
                :klaster_4a, :skor_4a, :skor_4b, :skor_4c, :skor_4d,
                :total_skor, :kategori_hasil, :keputusan_tpp, :catatan, :created_by
            )";
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute($data_params);
            $inserted_id = $pdo->lastInsertId();
            $success_msg = 'Data Asesmen berhasil disimpan ke database! <a href="cetak_asesmen.php?id=' . $inserted_id . '" target="_blank" class="btn btn-sm btn-success ms-3"><i class="fas fa-print me-1"></i> Cetak Format Blanko</a>';
        }
    } catch (PDOException $e) {
        $error_msg = 'Gagal menyimpan data: ' . $e->getMessage();
    }
}

$edit_data = null;
$edit_extra = [];
$edit_gejala = [];
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM data_asesmen WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_data) {
        $edit_gejala = json_decode($edit_data['gejala_json'] ?? '', true) ?: [];
        $edit_extra = $edit_gejala['extra_data'] ?? [];
    }
}

function formatInputDate($val) {
    if (empty($val) || $val === '0000-00-00') return '';
    $val = trim((string)$val);
    if (is_numeric($val) && (float)$val > 1000 && (float)$val < 100000) {
        return gmdate('Y-m-d', (int)(((float)$val - 25569) * 86400));
    }
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    $time = strtotime($val);
    if ($time !== false && $time > 0) return date('Y-m-d', $time);
    return $val;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asesmen Rehabilitasi - Sultan Banten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .nav-pills .nav-link.active { background-color: var(--primary-color) !important; color: #1a1a1a !important; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .nav-pills .nav-link:disabled { opacity: 0.5; cursor: not-allowed; }
        .form-section { background: var(--bg-card, #fff); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--border-color, #dee2e6); }
        .section-title { color: var(--primary-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 20px; font-weight: 600; }
        .q-row { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color, #eee); }
        .sub-title { font-weight: bold; margin-top: 15px; margin-bottom: 10px; color: #495057; }
        [data-theme="dark"] .form-section { background: var(--bg-card); border-color: var(--border-color); }
        [data-theme="dark"] .text-muted { color: var(--text-secondary) !important; }
        [data-theme="dark"] .nav-link { color: var(--text-primary); }
        
        @media print {
            body { background-color: #fff !important; color: #000 !important; }
            .sidebar, .topbar, #sidebarOverlay, .nav-pills, .btn { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; background: #fff !important; }
            .content-card { padding: 0 !important; margin: 0 !important; box-shadow: none !important; border: none !important; }
            .tab-content > .tab-pane { display: block !important; opacity: 1 !important; visibility: visible !important; margin-bottom: 30px; page-break-inside: auto; }
            .form-section { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 20px !important; }
            .section-title { color: #000 !important; border-bottom: 2px solid #000 !important; }
            input, select, textarea { border: 1px solid #ccc !important; background: transparent !important; color: #000 !important; box-shadow: none !important; -webkit-appearance: none; }
            table { border-collapse: collapse !important; width: 100% !important; }
            table, th, td { border: 1px solid #000 !important; }
            .bg-light { background-color: #f8f9fa !important; border: 1px solid #ddd !important; }
            * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
        }
    </style>
</head>
<body>
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
                <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Asesmen Rehabilitasi Lengkap</h4>
            </div>
        </div>

        <div class="content-card">
            <?php if($success_msg): ?><div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if($error_msg): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error_msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <ul class="nav nav-pills mb-4 border-bottom pb-3" id="pills-tab" role="tablist">
                <li class="nav-item"><button class="nav-link active rounded-pill px-4 me-2" id="tab-modul0" data-bs-toggle="pill" data-bs-target="#pills-identitas" type="button">Modul 0</button></li>
                <li class="nav-item"><button class="nav-link rounded-pill px-4 me-2" id="tab-modul1" data-bs-toggle="pill" data-bs-target="#pills-modul1" type="button" disabled>Modul 1</button></li>
                <li class="nav-item"><button class="nav-link rounded-pill px-4 me-2" id="tab-modul2" data-bs-toggle="pill" data-bs-target="#pills-modul2" type="button" disabled>Modul 2</button></li>
                <li class="nav-item"><button class="nav-link rounded-pill px-4 me-2" id="tab-modul3" data-bs-toggle="pill" data-bs-target="#pills-modul3" type="button" disabled>Modul 3</button></li>
                <li class="nav-item"><button class="nav-link rounded-pill px-4 me-2" id="tab-modul4" data-bs-toggle="pill" data-bs-target="#pills-modul4" type="button" disabled>Modul 4</button></li>
                <li class="nav-item"><button class="nav-link rounded-pill px-4" id="tab-rekap" data-bs-toggle="pill" data-bs-target="#pills-rekap" type="button" disabled>Rekap</button></li>
            </ul>

            <form action="asesmen.php" method="POST" id="asesmenForm">
                <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['edit_id'] ?? '') ?>">
                <div class="tab-content" id="pills-tabContent">
                    
                    <!-- IDENTITAS DAN MODUL 0 -->
                    <div class="tab-pane fade show active" id="pills-identitas">
                        <div class="form-section">
                            <h5 class="section-title">IDENTITAS DAN MODUL 0. SKRINNING AWAL (SELEKSI ADMINISTRATIF)</h5>
                            
                            <!-- SWITCH JALUR ASESMEN -->
                            <div class="card mb-4 border-2 border-primary shadow-sm" style="background: #f8fafc;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <span class="badge bg-primary px-3 py-2 fs-6 mb-1"><i class="fas fa-toggle-on me-1"></i> JALUR / PINTU ASESMEN</span>
                                            <div class="text-muted small">Pilih pintu asesmen untuk menyesuaikan modul dan pembobotan skor:</div>
                                        </div>
                                        <div class="btn-group" role="group" aria-label="Jalur Asesmen">
                                            <input type="radio" class="btn-check" name="jalur_asesmen" id="jalur_rehabilitasi" value="rehabilitasi" <?= ($edit_extra['jalur_asesmen'] ?? 'rehabilitasi') !== 'reedukasi' ? 'checked' : '' ?> onchange="handleJalurChange()">
                                            <label class="btn btn-outline-primary px-3 py-2 fw-bold" for="jalur_rehabilitasi">
                                                <i class="fas fa-pills me-1"></i> Pintu Rehabilitasi (Narkotika)
                                            </label>

                                            <input type="radio" class="btn-check" name="jalur_asesmen" id="jalur_reedukasi" value="reedukasi" <?= ($edit_extra['jalur_asesmen'] ?? '') === 'reedukasi' ? 'checked' : '' ?> onchange="handleJalurChange()">
                                            <label class="btn btn-outline-success px-3 py-2 fw-bold" for="jalur_reedukasi">
                                                <i class="fas fa-graduation-cap me-1"></i> Pintu Re-edukasi (Non-Narkotika)
                                            </label>
                                        </div>
                                    </div>
                                    <div id="jalur_info_reedukasi" class="alert alert-success py-2 px-3 mb-0 mt-3 small <?= ($edit_extra['jalur_asesmen'] ?? '') === 'reedukasi' ? '' : 'd-none' ?>">
                                        <i class="fas fa-info-circle me-1"></i> <strong>Mode Pintu Re-edukasi (Non-Narkotika) Aktif:</strong> Modul 1 dilewati, 6 butir narkotika pada Modul 4B (butir 1, 2, 3, 4, 10, 11) dilewati, dan skor mentah 4B dikonversi dari rentang 6–24 ke skala 25 poin (skor maksimal tetap 98).
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6"><label class="form-label">Nama</label><input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">No. Register / ID SDP</label><input type="text" class="form-control" name="no_register" value="<?= htmlspecialchars($edit_data['no_register'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">UPT</label>
                                    <input class="form-control" list="uptOptions" name="upt" placeholder="Ketik atau pilih UPT..." value="<?= htmlspecialchars($edit_data['upt'] ?? '') ?>">
                                    <datalist id="uptOptions">
                                        <?php foreach ($uptList as $u): ?><option value="<?= htmlspecialchars($u['nama_upt']) ?>"></option><?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="col-md-6"><label class="form-label">Kanwil</label><input type="text" class="form-control" value="Banten" readonly></div>
                                <div class="col-md-4"><label class="form-label">Tempat, Tgl Lahir</label><input type="text" class="form-control" name="ttl" value="<?= htmlspecialchars($edit_data['ttl'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="form-label">Usia saat asesmen</label><input type="number" class="form-control" name="usia" value="<?= htmlspecialchars($edit_data['usia'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="form-label">Tanggal asesmen</label><input type="date" class="form-control" name="tgl_asesmen" value="<?= htmlspecialchars(formatInputDate($edit_data['tgl_asesmen'] ?? date('Y-m-d'))) ?>"></div>
                                <div class="col-md-12"><label class="form-label">Pasal dan perkara</label><input type="text" class="form-control" name="m0_pasal" value="<?= htmlspecialchars($edit_extra['m0_pasal'] ?? '') ?>"></div>
                                <div class="col-md-3"><label class="form-label">Lama Pidana</label><input type="text" class="form-control" name="m0_lama_pidana" value="<?= htmlspecialchars($edit_extra['m0_lama_pidana'] ?? '') ?>"></div>
                                <div class="col-md-3"><label class="form-label">Tgl Mulai Pidana</label><input type="date" class="form-control" name="m0_tgl_mulai" value="<?= htmlspecialchars(formatInputDate($edit_extra['m0_tgl_mulai'] ?? '')) ?>"></div>
                                <div class="col-md-3"><label class="form-label">Tgl 2/3 Masa Pidana</label><input type="date" class="form-control" name="m0_tgl_23" value="<?= htmlspecialchars(formatInputDate($edit_extra['m0_tgl_23'] ?? '')) ?>"></div>
                                <div class="col-md-3"><label class="form-label">Tgl Bebas</label><input type="date" class="form-control" name="m0_tgl_bebas" value="<?= htmlspecialchars(formatInputDate($edit_extra['m0_tgl_bebas'] ?? '')) ?>"></div>
                                <div class="col-md-6"><label class="form-label">Nama Asesor</label><input type="text" class="form-control" name="m0_nama_asesor" value="<?= htmlspecialchars($edit_extra['m0_nama_asesor'] ?? $user['nama'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">Nama Asesor Kedua / Reviu</label><input type="text" class="form-control" name="m0_nama_reviu" value="<?= htmlspecialchars($edit_extra['m0_nama_reviu'] ?? '') ?>"></div>
                            </div>
                            
                            <div class="alert alert-secondary py-2 px-3 mb-3" style="font-size: 0.85rem;">
                                <strong>PETUNJUK PENGISIAN :</strong><br>
                                Isi butir 0.1 sampai 0.8 dari SDP dan berkas sebelum bertemu napi. Satu jawaban yang menghentikan sudah cukup untuk berhenti; tidak perlu melanjutkan ke modul berikutnya.
                            </div>

                            <h6 class="mb-3">Skrinning Syarat Administratif</h6>
                            <table class="table table-bordered align-middle">
                                <thead class="table-light"><tr><th style="width: 5%">No</th><th>Syarat</th><th style="width: 25%">Hasil</th></tr></thead>
                                <tbody>
                                    <tr><td>0.1</td><td>Usia pada tanggal asesmen 18-45 tahun (P1)</td><td><select class="form-select form-select-sm m0-check" name="modul0_1"><option value="">Pilih</option><option value="ya">Ya</option><option value="tidak">Tidak: berhenti</option></select></td></tr>
                                    <tr><td>0.2</td><td>Bukan pidana mati atau pidana seumur hidup</td><td><select class="form-select form-select-sm m0-check" name="modul0_2"><option value="">Pilih</option><option value="ya">Ya</option><option value="tidak">Tidak: berhenti</option></select></td></tr>
                                    <tr><td>0.3</td><td>Putusan tidak menyatakan secara eksplisit peran bandar, produsen, atau pengendali jaringan peredaran</td><td><select class="form-select form-select-sm m0-check" name="modul0_3"><option value="">Pilih</option><option value="ya">Ya</option><option value="tidak">Tidak: berhenti</option></select></td></tr>
                                    <tr><td>0.4</td><td>Bukan kelompok yang dikecualikan (korupsi, napiter kategori merah, Anak Binaan). Napiter kategori hijau hanya dengan rekomendasi BNPT</td><td><select class="form-select form-select-sm m0-check" name="modul0_4"><option value="">Pilih</option><option value="ya">Ya</option><option value="tidak">Tidak: berhenti</option></select></td></tr>
                                    <tr><td>0.5</td><td>Perkara bukan perlindungan anak, kekerasan seksual, atau kesusilaan (P5)</td><td><select class="form-select form-select-sm m0-check" name="modul0_5"><option value="">Pilih</option><option value="bukan">Bukan</option><option value="termasuk">Termasuk : status DITUNDA</option></select></td></tr>
                                    <tr><td>0.6</td><td>Register F (P3)</td><td><select class="form-select form-select-sm m0-check" name="modul0_6"><option value="">Pilih</option><option value="tidak">Tidak pernah tercatat</option><option value="ringan">Pernah, ringan, lebih dari 12 bulan lalu (lanjut hanya bila P3 melonggarkan; jika tidak, berhenti)</option><option value="lainnya">Pernah, lainnya: berhenti</option></select></td></tr>
                                    <tr><td>0.7</td><td>Sisa pidana (P4)</td><td><select class="form-select form-select-sm m0-check" name="modul0_7"><option value="">Pilih</option><option value="ya">Jarak &ge; 6 bulan</option><option value="tidak">Jarak &lt; 6 bulan : catat untuk pengaturan gelombang</option><option value="dalam_batas">Di dalam batas P4</option><option value="luar_batas">Di luar batas: berhenti</option></select></td></tr>
                                    <tr><td>0.8</td><td>Tidak sedang menjalani proses hukum lain (tersangka/terdakwa perkara lain) dan tidak ada penetapan lain yang menghalangi</td><td><select class="form-select form-select-sm m0-check" name="modul0_8"><option value="">Pilih</option><option value="ya">Ya</option><option value="tidak">Tidak: berhenti</option></select></td></tr>

                                </tbody>
                            </table>
                            <div class="mt-3">
                                <label class="form-label fw-bold">Hasil Modul 0 (Otomatis)</label>
                                <input type="text" class="form-control fw-bold" id="hasil_modul0_display" readonly>
                                <input type="hidden" name="hasil_modul0" id="hasil_modul0">
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-primary px-4 py-2" onclick="goToNextFromModul0()">Selanjutnya <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- MODUL 1 -->
                    <div class="tab-pane fade" id="pills-modul1">
                        <div class="form-section">
                            <h5 class="section-title">MODUL 1. VERIFIKASI STATUS PENGGUNA NARKOTIKA</h5>
                            
                            <!-- BANNER SKIP RE-EDUKASI -->
                            <div id="m1_skip_notice" class="alert alert-warning py-3 px-4 mb-3 d-none">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-2"></i>Modul 1 Dilewati (Pintu Re-edukasi Non-Narkotika)</h6>
                                        <p class="mb-0 small text-muted">Karena warga binaan ini dinilai melalui <strong>Pintu Re-edukasi (Non-Narkotika)</strong>, Modul 1 tidak digunakan dan tidak mempengaruhi penetapan kategori hasil.</p>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm px-3" onclick="nextTab('tab-modul2')">
                                        Lanjut ke Modul 2 <i class="fas fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="m1_content_wrapper">
                                <div class="alert alert-secondary py-2 px-3 mb-3" style="font-size: 0.85rem;">
                                    Modul ini menjawab satu pertanyaan : apakah napi benar pengguna berdasarkan riwayat, bukan hanya berdasarkan pasal. Hasil TAT BNN menjadi rujukan utama. Modul ini tidak diberi skor; hasilnya berupa Kategori 1 sampai 4.
                                </div>
                            
                            <div class="sub-title">1.1 Data dokumen (diisi sebelum wawancara)</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6"><label class="form-label">Pasal didakwa dan diputus</label><input type="text" class="form-control form-control-sm" name="m1_pasal"></div>
                                <div class="col-md-6"><label class="form-label">Jenis dan berat barang bukti (gram/butir)</label><input type="text" class="form-control form-control-sm" name="m1_bb"></div>
                                <div class="col-md-4"><label class="form-label">Peran dalam putusan</label><select class="form-select form-select-sm" name="m1_peran"><option value="">Pilih</option><option value="Pemakai">Pemakai</option><option value="Pembeli untuk dipakai">Pembeli untuk dipakai</option><option value="Kurir/perantara">Kurir/perantara</option><option value="Lainnya">Lainnya</option></select></div>
                                <div class="col-md-4"><label class="form-label">Hasil TAT BNN / ASSIST/ asesmen rehabilitasi sebelumnya</label>
                                    <select class="form-select form-select-sm" name="m1_tat">
                                        <option value="Belum ada">Belum ada</option>
                                        <option value="Pengguna">Ada, menyatakan pengguna</option>
                                        <option value="Bukan Pengguna">Ada, menyatakan bukan pengguna</option>
                                    </select>
                                    <input type="text" class="form-control form-control-sm mt-1" name="m1_rekomendasi" placeholder="Rekomendasi (Jika Ada)">
                                </div>
                                <div class="col-md-3"><label class="form-label">Hasil tes urin di UPT (bila ada)</label><select class="form-select form-select-sm" name="m1_urin" id="m1_urin" onchange="toggleTglUrin()"><option value="Belum dites">Belum dites</option><option value="Positif">Positif</option><option value="Negatif">Negatif</option></select></div>
                                <div class="col-md-3"><label class="form-label">Tanggal tes urin</label><input type="date" class="form-control form-control-sm" name="m1_tgl_urin" id="m1_tgl_urin" disabled></div>
                            </div>

                            <div class="sub-title">1.2 Wawancara riwayat penggunaan</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4"><label class="form-label">a. Zat utama</label><select class="form-select form-select-sm" name="zat_utama"><option value="">Pilih</option><option>Sabu</option><option>Ganja</option><option>Heroin</option><option>Ekstasi</option><option>Obat</option></select></div>
                                <div class="col-md-4"><label class="form-label">b. Usia pertama kali memakai (tahun)</label><input type="number" class="form-control form-control-sm" name="usia_pertama_memakai"></div>
                                <div class="col-md-4"><label class="form-label">c. Lama pemakaian rutin (tahun)</label><input type="number" class="form-control form-control-sm" name="lama_pemakaian"></div>
                                <div class="col-md-4"><label class="form-label">d. Frekuensi dalam 3 bln sblm ditahan</label><select class="form-select form-select-sm" name="m1_frekuensi"><option value="">Pilih</option><option value="Harian">Harian</option><option value="Mingguan">Mingguan</option><option value="Bulanan">Bulanan</option><option value="Tidak memakai">Tidak memakai</option></select></div>
                                <div class="col-md-4"><label class="form-label">e. Cara pakai</label><select class="form-select form-select-sm" name="m1_cara"><option value="">Pilih</option><option value="Dihisap">Dihisap/ditelan</option><option value="Suntik">Suntik</option></select></div>
                                <div class="col-md-4"><label class="form-label">f. Pemakaian terakhir</label><input type="date" class="form-control form-control-sm" name="m1_terakhir"></div>
                                <div class="col-md-6">
                                    <label class="form-label">g. Pernah menjalani rehabilitasi, terapi, atau konseling</label>
                                    <select class="form-select form-select-sm mb-1" name="m1_rehab"><option value="">Pilih</option><option value="Tidak">Tidak</option><option value="Ya">Ya</option></select>
                                    <input type="text" class="form-control form-control-sm" name="m1_rehab_ket" placeholder="Di mana, kapan, hasilnya:">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">h. Pernah mencoba berhenti sendiri</label>
                                    <div class="d-flex gap-2 mb-1">
                                        <select class="form-select form-select-sm" name="m1_berhenti"><option value="">Pilih</option><option value="Tidak">Tidak</option><option value="Ya">Ya</option></select>
                                        <input type="text" class="form-control form-control-sm" name="m1_berhenti_kali" placeholder="Berapa kali" style="max-width: 120px;">
                                    </div>
                                    <input type="text" class="form-control form-control-sm" name="m1_berhenti_ket" placeholder="Penyebab kambuh:">
                                </div>
                            </div>

                            <div class="sub-title">1.3 Daftar gejala gangguan penggunaan zat (12 bulan sebelum ditahan)</div>
                            <div class="alert alert-secondary py-2 px-3 mb-3" style="font-size: 0.85rem;">
                                Tanyakan satu per satu; centang "Ya" bila napi membenarkan dengan contoh yang jelas.<br>
                                <small><strong>SKOR :</strong> 0–1: tidak memenuhi ; 2–3: ringan ; 4–5: sedang ; &ge;6: berat</small>
                            </div>
                            <?php 
                            $gejala = [
                                "Memakai lebih banyak atau lebih lama daripada yang direncanakan", 
                                "Ingin atau sudah mencoba mengurangi atau berhenti, tetapi gagal", 
                                "Banyak waktu tersita untuk mencari, memakai, atau pulih dari efek zat", 
                                "Dorongan atau keinginan kuat untuk memakai (craving)", 
                                "Gagal menjalankan kewajiban di pekerjaan, sekolah, atau rumah karena pemakaian", 
                                "Tetap memakai walaupun menimbulkan masalah dengan keluarga atau orang lain", 
                                "Meninggalkan atau mengurangi kegiatan penting (pekerjaan, hobi, pergaulan) karena pemakaian", 
                                "Memakai dalam situasi berbahaya (misalnya sambil berkendara)", 
                                "Tetap memakai walaupun tahu menimbulkan masalah fisik atau kejiwaan", 
                                "Toleransi: perlu dosis makin banyak untuk efek yang sama", 
                                "Gejala putus zat saat berhenti, atau memakai zat untuk menghilangkan gejala itu"
                            ];
                            foreach($gejala as $i => $g): ?>
                            <div class="q-row d-flex justify-content-between align-items-center py-1">
                                <div><small><?= ($i+1).". ".$g ?></small></div>
                                <div><select class="form-select form-select-sm m1-calc" name="gejala_<?= $i ?>" style="width: 100px;"><option value="0">Tidak</option><option value="1">Ya</option></select></div>
                            </div>
                            <?php endforeach; ?>

                            <div class="sub-title">1.4 Triangulasi (cek silang)</div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-12"><label class="form-label"><small>Cerita napi dibandingkan putusan/BAP</small></label><select class="form-select form-select-sm" name="m1_tri_1"><option value="">Pilih</option><option value="Konsisten">Konsisten</option><option value="Sebagian">Sebagian</option><option value="Tidak konsisten">Tidak konsisten</option></select></div>
                                <div class="col-md-6"><label class="form-label"><small>Keterangan keluarga atau catatan lain (bila ada)</small></label><input type="text" class="form-control form-control-sm" name="m1_tri_2"></div>
                                <div class="col-md-6">
                                    <label class="form-label"><small>Keterkaitan dengan jaringan peredaran (putusan, informasi intelijen Lapas)</small></label>
                                    <div class="d-flex gap-2">
                                        <select class="form-select form-select-sm" name="m1_tri_3" style="max-width:130px;"><option value="">Pilih</option><option value="Tidak ada">Tidak ada</option><option value="Ada">Ada</option></select>
                                        <input type="text" class="form-control form-control-sm" name="m1_tri_3_ket" placeholder="Keterangan jika Ada">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="sub-title">1.5 Tanda bahaya peredaran (red flag)</div>
                            <div class="row g-2 mb-3">
                                <div class="col-12"><small class="text-danger fw-bold d-block mb-1">Kuat (satu saja berarti rujuk ke TPP, bukan otomatis lolos) :</small>
                                    <div class="form-check"><input class="form-check-input rf-kuat" type="checkbox" name="m1_rf_kuat1" value="1" id="rfk1"><label class="form-check-label" for="rfk1"><small>Barang bukti melebihi ambang pemakaian SEMA 4/2010, tanpa riwayat ketergantungan yang sejalan</small></label></div>
                                    <div class="form-check"><input class="form-check-input rf-kuat" type="checkbox" name="m1_rf_kuat2" value="1" id="rfk2"><label class="form-check-label" for="rfk2"><small>Putusan atau BAP memuat peran menjual, mengedarkan, mengendalikan, atau menerima transaksi</small></label></div>
                                    <div class="form-check"><input class="form-check-input rf-kuat" type="checkbox" name="m1_rf_kuat3" value="1" id="rfk3"><label class="form-check-label" for="rfk3"><small>Informasi intelijen atau catatan tentang peredaran/transaksi narkotika di dalam Lapas/Rutan</small></label></div>
                                    <div class="form-check"><input class="form-check-input rf-kuat" type="checkbox" name="m1_rf_kuat4" value="1" id="rfk4"><label class="form-check-label" for="rfk4"><small>Keterlibatan jaringan terorganisir (pengendali, bandar, kurir jaringan)</small></label></div>
                                </div>
                                <div class="col-12 mt-2"><small class="text-warning fw-bold d-block mb-1">Pendukung (dua atau lebih berarti rujuk ke TPP):</small>
                                    <div class="form-check"><input class="form-check-input rf-pend" type="checkbox" name="m1_rf_dukung1" value="1" id="rfp1"><label class="form-check-label" for="rfp1"><small>Ditemukan timbangan, plastik klip dalam jumlah banyak, atau alat pengemas</small></label></div>
                                    <div class="form-check"><input class="form-check-input rf-pend" type="checkbox" name="m1_rf_dukung2" value="1" id="rfp2"><label class="form-check-label" for="rfp2"><small>Uang dalam jumlah tidak wajar atau catatan transaksi</small></label></div>
                                    <div class="form-check"><input class="form-check-input rf-pend" type="checkbox" name="m1_rf_dukung3" value="1" id="rfp3"><label class="form-check-label" for="rfp3"><small>Daftar gejala pada tabel/ poin 1.3 berjumlah 0–1 padahal perkara peredaran</small></label></div>
                                    <div class="form-check"><input class="form-check-input rf-pend" type="checkbox" name="m1_rf_dukung4" value="1" id="rfp4"><label class="form-check-label" for="rfp4"><small>Pernah dipidana perkara narkotika dengan peran peredaran</small></label></div>
                                </div>
                            </div>

                            <div class="sub-title">1.6 Kesimpulan Modul 1 (pilih satu)</div>
                            <table class="table table-bordered mt-2 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">KESIMPULAN KATEGORI MODUL 1</td>
                                    <td class="fw-bold text-center align-middle" style="width: 25%;">
                                        <input type="text" class="form-control-plaintext fw-bold text-center text-primary p-0" id="kategori_modul1_display" readonly>
                                        <input type="hidden" name="kategori_modul1" id="kategori_modul1">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <label class="form-label"><small>Catatan asesor (Modul 1):</small></label>
                                        <textarea class="form-control form-control-sm" name="m1_catatan" rows="2"></textarea>
                                    </td>
                                </tr>
                            </table>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary px-4 py-2" onclick="prevTab('tab-modul0')"><i class="fas fa-arrow-left me-2"></i> Sebelumnya</button>
                                <button type="button" class="btn btn-primary px-4 py-2" onclick="nextTab('tab-modul2')">Selanjutnya <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- MODUL 2 -->
                    <div class="tab-pane fade" id="pills-modul2">
                        <div class="form-section">
                            <h5 class="section-title">MODUL 2. KESIAPAN FISIK DAN MENTAL (DIISI TENAGA KESEHATAN)</h5>
                            
                            <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 0.85rem;">
                                <strong>Kerahasiaan.</strong> Bagian 2.1 disimpan pada rekam medis tertutup UPT. Hanya Bagian 2.2 (kesimpulan kemampuan bekerja, tanpa diagnosis) yang diserahkan ke asesor dan dibaca TPP.
                            </div>

                            <div class="sub-title">2.1 Pemeriksaan (rekam medis tertutup)</div>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><small>Tanda vital dan antropometri</small></label>
                                <div class="row g-2">
                                    <div class="col-md-2 col-4"><input type="text" class="form-control form-control-sm" name="td" placeholder="TD"></div>
                                    <div class="col-md-2 col-4"><input type="text" class="form-control form-control-sm" name="nadi" placeholder="Nadi"></div>
                                    <div class="col-md-2 col-4"><input type="text" class="form-control form-control-sm" name="suhu" placeholder="Suhu"></div>
                                    <div class="col-md-2 col-4"><input type="text" class="form-control form-control-sm" name="pernapasan" placeholder="Napas"></div>
                                    <div class="col-md-2 col-4"><input type="text" class="form-control form-control-sm" name="bb" id="bb_input" placeholder="BB (kg)"></div>
                                    <div class="col-md-2 col-4"><input type="text" class="form-control form-control-sm" name="tb" id="tb_input" placeholder="TB (cm)"></div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Skrining TB (gejala dan pemeriksaan sesuai prosedur)</label>
                                    <select class="form-select form-select-sm m2-calc" name="m2_tb">
                                        <option value="">Pilih</option>
                                        <option value="aman">Tidak ada gejala</option>
                                        <option value="tunda">Perlu pemeriksaan lanjut</option>
                                        <option value="bersyarat">TB dalam pengobatan</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Skrining HIV dan hepatitis (sesuai prosedur dan persetujuan)</label>
                                    <select class="form-select form-select-sm m2-calc" name="m2_hiv">
                                        <option value="">Pilih</option>
                                        <option value="aman">Tidak diperiksa</option>
                                        <option value="bersyarat">Dilakukan, hasil di rekam medis</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Penyakit kronis atau keterbatasan gerak</label>
                                    <select class="form-select form-select-sm m2-calc" name="m2_kronis">
                                        <option value="">Pilih</option>
                                        <option value="aman">Tidak ada</option>
                                        <option value="bersyarat">Ada, terkontrol</option>
                                        <option value="tunda">Ada, tidak terkontrol</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Tanda intoksikasi atau putus zat saat ini</label>
                                    <select class="form-select form-select-sm m2-calc" name="m2_intoksikasi">
                                        <option value="">Pilih</option>
                                        <option value="aman">Tidak ada</option>
                                        <option value="tunda">Ada</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Skrining kesehatan jiwa SRQ-20 (batas sesuai pedoman Kemenkes; umumnya &ge; 6) (optional/ jika ada)</label>
                                    <div class="d-flex mb-2 gap-2">
                                        <input type="text" class="form-control form-control-sm" name="m2_srq" placeholder="Skor SRQ-20" style="max-width:180px;">
                                        <select class="form-select form-select-sm" name="m2_srq_batas" style="max-width:200px;">
                                            <option value="">Pilih Batas</option>
                                            <option value="bawah">Di bawah batas</option>
                                            <option value="atas">Di atas batas</option>
                                        </select>
                                    </div>
                                    <label class="form-label mt-2">Riwayat gangguan jiwa berat</label>
                                    <select class="form-select form-select-sm m2-calc" name="m2_jiwa">
                                        <option value="">Pilih</option>
                                        <option value="aman">Tidak ada</option>
                                        <option value="bersyarat">Ada, terkontrol</option>
                                        <option value="tunda">Ada, tidak terkontrol</option>
                                    </select>
                                </div>
                            </div>

                            <div class="sub-title">2.2 Kesimpulan kemampuan bekerja (diserahkan ke asesor)</div>
                            <table class="table table-bordered mt-2 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">KESIMPULAN & POIN MODUL 2</td>
                                    <td class="fw-bold text-center align-middle" style="width: 25%;">
                                        <input type="text" class="form-control-plaintext fw-bold text-center text-primary p-0" id="kesimpulan_modul2_display" readonly>
                                        <input type="hidden" name="kesimpulan_modul2" id="kesimpulan_modul2">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <label class="form-label"><small>Syarat (Jika Layak Bersyarat):</small></label>
                                        <input type="text" class="form-control form-control-sm mb-3" name="m2_syarat">
                                        
                                        <label class="form-label"><small>Kebutuhan layanan lanjutan (tanpa menyebut diagnosis):</small></label>
                                        <div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="m2_layanan1" value="1" id="l1"><label class="form-check-label" for="l1"><small>Obat rutin</small></label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="m2_layanan2" value="1" id="l2"><label class="form-check-label" for="l2"><small>Kontrol berkala</small></label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="m2_layanan3" value="1" id="l3"><label class="form-check-label" for="l3"><small>Rujuk RS</small></label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="m2_layanan4" value="1" id="l4"><label class="form-check-label" for="l4"><small>Evaluasi psikolog</small></label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="m2_layanan5" value="1" id="l5"><label class="form-check-label" for="l5"><small>Stabilisasi/detoksifikasi (BNN)</small></label></div>
                                        </div>
                                        <div class="row g-2 mt-3 pt-2 border-top">
                                            <div class="col-md-8">
                                                <label class="form-label mb-1"><small>Nama, jabatan, paraf tenaga kesehatan:</small></label>
                                                <input type="text" class="form-control form-control-sm" name="m2_nakes_nama">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-1"><small>Tanggal:</small></label>
                                                <input type="date" class="form-control form-control-sm" name="m2_nakes_tgl">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div class="alert alert-warning py-2 px-3 mt-3 mb-0" style="font-size: 0.85rem;">
                                <strong>Catatan :</strong> Pemeriksaan fisik pada Modul 4D hanya dilakukan bila Modul 2 berstatus Layak atau Layak bersyarat dan atas izin tenaga kesehatan.
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary px-4 py-2" onclick="goToPrevFromModul2()"><i class="fas fa-arrow-left me-2"></i> Sebelumnya</button>
                                <button type="button" class="btn btn-primary px-4 py-2" onclick="nextTab('tab-modul3')">Selanjutnya <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MODUL 3 -->
                    <div class="tab-pane fade" id="pills-modul3">
                        <div class="form-section">
                            <h5 class="section-title">MODUL 3. RISIKO DAN STABILITAS (DATA SDP, SKOR MAKS 20)</h5>
                            <div class="alert alert-secondary py-2 px-3 mb-3" style="font-size: 0.85rem;">
                                <strong>PETUNJUK PENGISIAN :</strong> Seluruh butir diambil dari SDP dan register; tidak ada wawancara.
                            </div>
                            <table class="table table-bordered align-middle table-sm">
                                <thead>
                                    <tr class="table-light"><th style="width:5%">No</th><th style="width:45%">Indikator</th><th style="width:15%">Sumber</th><th style="width:35%">Pilihan dan poin</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">3.1</td>
                                        <td>Kategori risiko residivisme pada Risiko Residivisme Indonesia (RRI)</td>
                                        <td class="text-center">SDP</td>
                                        <td><select class="form-select form-select-sm m3-calc" name="modul3_1"><option value="0">Pilih</option><option value="7">Rendah (7)</option><option value="5">Sedang (5)</option><option value="3">Tinggi (3)</option><option value="0">Sangat Tinggi (0)</option></select></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">3.2</td>
                                        <td>Hasil screening penempatan (risiko keamanan, keselamatan, stabilitas, pemasyarakatan)</td>
                                        <td class="text-center">SDP</td>
                                        <td><select class="form-select form-select-sm m3-calc" name="modul3_2"><option value="0">Pilih</option><option value="6">Minimum (6)</option><option value="3">Medium (3)</option><option value="0">Maksimum (0)</option><option value="-99">Super maksimum (risiko pelarian/kekerasan sangat tinggi) : Berhenti, Tidak Layak</option></select></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">3.3</td>
                                        <td>Riwayat kabur atau percobaan kabur</td>
                                        <td class="text-center">SDP</td>
                                        <td><select class="form-select form-select-sm m3-calc" name="modul3_3"><option value="0">Tidak pernah (0)</option><option value="-99">Pernah : Tidak Layak</option></select></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">3.4</td>
                                        <td>Catatan kekerasan 12 bulan terakhir</td>
                                        <td class="text-center">Register</td>
                                        <td><select class="form-select form-select-sm m3-calc" name="modul3_4"><option value="4">Tidak ada (4)</option><option value="0">Ada (0)</option></select></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">3.5</td>
                                        <td>Catatan pembinaan 12 bulan terakhir</td>
                                        <td class="text-center">Register</td>
                                        <td><select class="form-select form-select-sm m3-calc" name="modul3_5"><option value="3">Tanpa teguran (3)</option><option value="1">Teguran Lisan/Tertulis tanpa register F (1)</option></select></td>
                                    </tr>
                                </tbody>
                            </table>
                            <table class="table table-bordered mt-3 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">TOTAL SKOR MODUL 3 (Maks. 20)</td>
                                    <td class="fw-bold text-center align-middle fs-5" style="width: 25%;" id="m3-total">0</td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <label class="form-label"><small>Kebutuhan kriminogenik tinggi (tidak diskor, untuk rencana pembinaan):</small></label>
                                        <textarea class="form-control form-control-sm" name="m3_kriminogenik" rows="2"></textarea>
                                    </td>
                                </tr>
                            </table>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary px-4 py-2" onclick="prevTab('tab-modul2')"><i class="fas fa-arrow-left me-2"></i> Sebelumnya</button>
                                <button type="button" class="btn btn-primary px-4 py-2" onclick="nextTab('tab-modul4')">Selanjutnya <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MODUL 4 -->
                    <div class="tab-pane fade" id="pills-modul4">
                        <div class="form-section">
                            <h5 class="section-title">MODUL 4. MINAT, BAKAT, MOTIVASI, DAN SIKAP KERJA</h5>
                            
                            <div class="sub-title border-bottom pb-2">4A. Minat dan bakat (skor maks 13)</div>
                            <div class="alert alert-secondary py-2 px-3 mb-2" style="font-size: 0.85rem;">
                                <strong>PETUNJUK :</strong> 0 = tidak tertarik; 1 = biasa saja atau mau mencoba; 2 = sangat tertarik.
                            </div>
                            <?php
                            $m4a_cats = [
                                'Kementan' => [
                                    'code' => 'kementan',
                                    'label' => 'A. Pertanian (klaster Kementan)', 
                                    'qs' => ['A1. Mengolah tanah, menanam, dan merawat padi atau tanaman di sawah/ladang', 'A2. Merawat tanaman pangan, sayur, atau kebun', 'A3. Mengoperasikan atau merawat alat pertanian (Traktor Tangan, Pompa Air)']
                                ],
                                'KKP' => [
                                    'code' => 'kkp',
                                    'label' => 'B. Perikanan dan kelautan (klaster KKP)',
                                    'qs' => ['B1. Bekerja di tambak: memberi pakan, mengatur air, memanen', 'B2. Bekerja sebagai awak kapal ikan dan tinggal di kapal', 'B3. Mengolah hasil ikan atau garam : membersihkan, mengolah, mengemas.']
                                ],
                                'Mekanik' => [
                                    'code' => 'mekanik',
                                    'label' => 'C. Mekanik dan teknik (non-lahan)',
                                    'qs' => ['C1. Memperbaiki mesin, kendaraan, atau peralatan', 'C2. Pertukangan: bangunan, las, listrik, pipa', 'C3. Mengoperasikan mesin atau peralatan kerja']
                                ],
                                'Produksi' => [
                                    'code' => 'produksi',
                                    'label' => 'D. Pengolahan dan produksi (non-lahan)',
                                    'qs' => ['D1. Memasak atau membuat makanan, roti, atau olahan pangan', 'D2. Memilah, menimbang, mengemas, atau mengelola stok gudang', 'D3. Menjahit, membuat kerajinan, atau memproduksi barang']
                                ],
                                'Jasa' => [
                                    'code' => 'jasa',
                                    'label' => 'E. Jasa dan administrasi (non-lahan)',
                                    'qs' => ['E1. Mencatat dan merapikan data (hasil panen, stok, kehadiran)', 'E2. Pekerjaan jasa: kebersihan, laundry, cukur rambut, layanan', 'E3. Belajar komputer atau mengikuti pelatihan kelas dan sertifikasi']
                                ]
                            ];
                            foreach($m4a_cats as $cat_key => $cat_data): ?>
                            <div class="fw-bold text-secondary mt-3 mb-1"><small><?= $cat_data['label'] ?></small></div>
                            <?php foreach($cat_data['qs'] as $q_idx => $q): ?>
                            <div class="q-row d-flex justify-content-between align-items-center py-1">
                                <div style="padding-right: 10px;"><small><?= $q ?></small></div>
                                <div>
                                    <select class="form-select form-select-sm m4a-<?= $cat_data['code'] ?> calc-trigger" name="m4a_item_<?= $cat_data['code'] ?>_<?= $q_idx ?>" style="width: 70px;">
                                        <option value="0">0</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; endforeach; ?>
                            
                            <div class="mt-4 p-2 bg-light border border-dark mb-3">
                                <small class="fw-bold d-block mb-2 text-center">Rekap minat dan rekomendasi klaster</small>
                                <table class="table table-sm table-bordered text-center align-middle mb-2 bg-white border-dark">
                                    <thead class="bg-light">
                                        <tr>
                                            <th></th>
                                            <th>A</th>
                                            <th>B</th>
                                            <th>C</th>
                                            <th>D</th>
                                            <th>E</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-start fw-bold"><small>Jumlah skor area (0-6)</small></td>
                                            <td id="score-kementan">0</td>
                                            <td id="score-kkp">0</td>
                                            <td id="score-mekanik">0</td>
                                            <td id="score-produksi">0</td>
                                            <td id="score-jasa">0</td>
                                        </tr>
                                        <tr>
                                            <td class="text-start fw-bold"><small>Klaster</small></td>
                                            <td><small>Kementan</small></td>
                                            <td><small>KKP</small></td>
                                            <td><small>Re-edukasi<br>non-lahan</small></td>
                                            <td><small>Re-edukasi<br>non-lahan</small></td>
                                            <td><small>Re-edukasi<br>non-lahan</small></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <input type="hidden" name="score_kementan" id="score_kementan_input">
                                <input type="hidden" name="score_kkp" id="score_kkp_input">
                                <input type="hidden" name="score_mekanik" id="score_mekanik_input">
                                <input type="hidden" name="score_produksi" id="score_produksi_input">
                                <input type="hidden" name="score_jasa" id="score_jasa_input">

                                <small class="text-muted d-block mb-3" style="font-size: 0.75rem;">Catatan : Poin minat = skor area tertinggi (0–6). Bila dua area sama tinggi, pilih berdasarkan pengalaman kerja (4A.2), hasil 4D, dan kuota klaster. Bila semua area &le; 2, rekomendasikan re-edukasi non-lahan dan beri tanda.</small>
                                
                                <div class="row g-2 align-items-center">
                                    <div class="col-12">
                                        <small class="fw-bold">4A.1 Poin minat (skor area tertinggi):</small> 
                                        <span class="fw-bold fs-5 px-2 text-primary border-bottom border-primary" id="m4a1-poin">0</span> 
                                        <small>dari 6 &nbsp; &nbsp; Area terpilih: </small> 
                                        <span class="fw-bold text-primary border-bottom border-primary px-2" id="m4a1-area">-</span>
                                        <input type="hidden" name="m4a_poin_minat" id="m4a_poin_minat">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><small><b>4A.2 Pengalaman kerja relevan</b></small></label>
                                    <select class="form-select form-select-sm calc-trigger mb-1" id="m4a-pengalaman" name="m4a_pengalaman">
                                        <option value="0">Tidak ada atau tidak relevan (0)</option>
                                        <option value="2">Informal atau kurang dari 1 tahun (2)</option>
                                        <option value="3">1 tahun atau lebih (3)</option>
                                        <option value="5">Punya sertifikat atau keahlian terbukti, termasuk pelatihan kerja di Lapas (5)</option>
                                    </select>
                                    <input type="text" class="form-control form-control-sm" name="m4a_bidang_kerja" placeholder="Bidang/jenis pekerjaan:">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><small><b>4A.3 Kesediaan</b> (0 = tidak bersedia, 1 = ragu, 2 = bersedia)</small></label>
                                    <label class="form-label d-block text-muted mb-1" style="font-size: 0.75rem;">Bersedia belajar keterampilan baru dan menerima bimbingan:</label>
                                    <select class="form-select form-select-sm calc-trigger" id="m4a-kesediaan" name="m4a_kesediaan">
                                        <option value="0">0 (tidak bersedia)</option>
                                        <option value="1">1 (ragu)</option>
                                        <option value="2">2 (bersedia)</option>
                                    </select>
                                </div>
                            </div>
                            <table class="table table-bordered mt-3 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">Poin 4A (4A.1 + 4A.2 + 4A.3) (Maks. 13)</td>
                                    <td class="fw-bold text-center align-middle fs-5" style="width: 25%;"><span id="m4a-total">0</span><input type="hidden" name="skor_4a" id="skor_4a"><input type="hidden" name="klaster_4a" id="klaster_4a"></td>
                                </tr>
                            </table>
                            
                            <div class="sub-title border-bottom pb-2 mt-4">4B. Motivasi dan kesiapan berubah (skor maks 25)</div>
                            <div class="alert alert-secondary py-2 px-3 mb-2" style="font-size: 0.85rem;">
                                <strong>Petunjuk Pengisian :</strong><br>
                                Bacakan setiap pernyataan. Jelaskan bahwa tidak ada jawaban benar atau salah dan bahwa jawaban jujur lebih berguna daripada jawaban yang dianggap baik.<br>
                                Butir pernyataan pada tabel dibawah ini terdiri atas : butir 1–4 = motivasi pulih; 5–8 = motivasi bekerja; 9–12 = harapan dan kesiapan berubah<br><br>
                                <strong>Cara Skoring :</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Poin 1, 2, 3, 5, 6, 7, 9, 10, 11 dan 12 skor : 1 = sangat tidak setuju, 2 = tidak setuju, 3 = setuju, 4 = sangat setuju.</li>
                                    <li>Poin 4 dan 8 skor : 1 = sangat setuju; 2 = setuju; 3 = tidak setuju, 4 = sangat tidak setuju.</li>
                                </ul>
                            </div>
                            <?php
                            $m4b_qs = [
                                "1. Saya ingin berhenti memakai narkotika.",
                                "2. Saya sadar bahwa pemakaian narkotika telah merugikan diri saya dan keluarga.",
                                "3. Saya bersedia mengikuti terapi dan tes urin sampai program selesai.",
                                "4. Saya merasa tidak perlu berubah; masalah saya bukan karena narkotika. (R)",
                                "5. Saya mau bekerja keras setiap hari, termasuk pekerjaan berat di luar ruangan.",
                                "6. Saya mau belajar keterampilan baru walaupun awalnya sulit.",
                                "7. Saya bersedia mengikuti aturan dan jadwal kerja walaupun tidak sesuai kemauan saya.",
                                "8. Saya ikut program ini terutama agar cepat keluar dari Lapas, bukan untuk belajar. (R)",
                                "9. Saya punya rencana yang jelas tentang apa yang akan saya kerjakan setelah bebas.",
                                "10. Saya tahu hal-hal yang membuat saya ingin memakai lagi dan saya punya cara menghindarinya.",
                                "11. Saya yakin saya bisa tetap bersih dari narkotika setelah program.",
                                "12. Keluarga atau orang terdekat mendukung saya mengikuti program ini."
                            ];
                            foreach($m4b_qs as $i => $q): 
                                $is_reversed = ($i == 3 || $i == 7);
                                $is_narkoba = in_array($i, [0, 1, 2, 3, 9, 10]); // Butir 1, 2, 3, 4, 10, 11
                            ?>
                            <div class="q-row d-flex justify-content-between align-items-center py-1 <?= $is_narkoba ? 'm4b-narkoba-item' : '' ?>" data-narkoba="<?= $is_narkoba ? '1' : '0' ?>">
                                <div style="padding-right: 15px;"><small><?= $q ?></small></div>
                                <div>
                                    <select class="form-select form-select-sm m4b-calc" name="m4b_q_<?= $i ?>" style="width:190px;" data-reversed="<?= $is_reversed ? '1' : '0' ?>">
                                        <?php if(!$is_reversed): ?>
                                            <option value="1">1 - Sangat Tidak Setuju</option>
                                            <option value="2">2 - Tidak Setuju</option>
                                            <option value="3" selected>3 - Setuju</option>
                                            <option value="4">4 - Sangat Setuju</option>
                                        <?php else: ?>
                                            <option value="1">1 - Sangat Setuju</option>
                                            <option value="2">2 - Setuju</option>
                                            <option value="3" selected>3 - Tidak Setuju</option>
                                            <option value="4">4 - Sangat Tidak Setuju</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="alert alert-light border py-2 px-3 mt-2 mb-2" style="font-size: 0.8rem;" id="m4b_konversi_banner">
                                <div id="m4b_konversi_rehab">
                                    <strong>Tabel Konversi Skor Mentah (12 Butir):</strong> 12–20 = 5 &nbsp;|&nbsp; 21–26 = 10 &nbsp;|&nbsp; 27–32 = 15 &nbsp;|&nbsp; 33–38 = 20 &nbsp;|&nbsp; 39–48 = 25
                                </div>
                                <div id="m4b_konversi_reedukasi" class="d-none">
                                    <strong>Tabel Konversi Skor Mentah (6 Butir Non-Narkotika):</strong> 6–10 = 5 &nbsp;|&nbsp; 11–13 = 10 &nbsp;|&nbsp; 14–16 = 15 &nbsp;|&nbsp; 17–19 = 20 &nbsp;|&nbsp; 20–24 = 25
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-12"><small class="fw-bold">Pertanyaan terbuka (tidak diskor, catat ringkas):</small></div>
                                <div class="col-12"><small>1. Ceritakan kegiatan kerja atau pembinaan yang paling Anda sukai dan alasannya!</small><input type="text" class="form-control form-control-sm mt-1" name="m4b_t1"></div>
                                <div class="col-12 m4b-narkoba-item" data-narkoba="1"><small>2. Apa yang biasanya membuat Anda ingin memakai lagi, dan apa yang Anda lakukan untuk menghindarinya!</small><input type="text" class="form-control form-control-sm mt-1" name="m4b_t2"></div>
                                <div class="col-12"><small>3. Apa rencana Anda dalam 6 bulan setelah bebas?</small><input type="text" class="form-control form-control-sm mt-1" name="m4b_t3"></div>
                                <div class="col-12"><small>Catatan:</small><input type="text" class="form-control form-control-sm mt-1" name="m4b_catatan"></div>
                                <div class="col-12">
                                    <small class="fw-bold">Konsistensi jawaban terbuka dengan skala:</small>
                                    <div class="d-flex gap-2 mt-1">
                                        <select class="form-select form-select-sm" name="m4b_konsisten" style="max-width:200px;">
                                            <option value="Konsisten">Konsisten</option>
                                            <option value="Ada ketidaksesuaian">Ada ketidaksesuaian</option>
                                        </select>
                                        <input type="text" class="form-control form-control-sm" name="m4b_ketidaksesuaian" placeholder="Ada ketidaksesuaian: (beri tanda/alasan)">
                                    </div>
                                </div>
                            </div>
                            <table class="table table-bordered mt-2 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">
                                        SKOR KONVERSI MODUL 4B (Maks. 25)<br>
                                        <small class="text-muted fw-normal">Skor Mentah: <span id="m4b-raw-disp">0</span> / <span id="m4b-raw-max">48</span></small>
                                        <input type="hidden" name="m4b_raw" id="m4b_raw">
                                    </td>
                                    <td class="fw-bold text-center align-middle fs-5" style="width: 25%;"><span id="m4b-total">5</span><input type="hidden" name="skor_4b" id="skor_4b"></td>
                                </tr>
                            </table>

                            <div class="sub-title border-bottom pb-2 mt-4">4C. Sikap kerja (skor maks 25)</div>
                            <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 0.85rem;">
                                <strong>Petunjuk pengisian :</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Penilaian pada aspek 4C Sikap Kerja ini dilakukan oleh dua penilai, yaitu <strong>wali pemasyarakatan (Penilai 1)</strong> dan <strong>pembina kegiatan kerja atau petugas blok yang mengenal napi (Penilai 2)</strong>, masing-masing secara terpisah;</li>
                                    <li>Nilai berdasarkan perilaku yang benar-benar teramati, bukan kesan umum. Setiap skor wajib disertai satu contoh kejadian nyata pada baris "Bukti". Skor 2 dan 4 dipakai bila perilaku berada di antara dua uraian.</li>
                                    <li>Skor akhir per dimensi = rata-rata dua penilai, dibulatkan ke bilangan terdekat (0,5 dibulatkan ke atas). Bila dua penilai berbeda 2 poin atau lebih, bahas bersama dan catat alasan.</li>
                                </ul>
                            </div>
                            <div class="row g-2 mb-3 mt-2">
                                <div class="col-md-8"><label class="form-label" style="font-size: 0.85rem;">Penilai 1 (wali):</label><input type="text" class="form-control form-control-sm" name="m4c_p1_nama"></div>
                                <div class="col-md-4"><label class="form-label" style="font-size: 0.85rem;">Lama mengamati (bulan):</label><input type="number" class="form-control form-control-sm" name="m4c_p1_lama"></div>
                                <div class="col-md-8"><label class="form-label" style="font-size: 0.85rem;">Penilai 2 (pembina kerja/petugas blok):</label><input type="text" class="form-control form-control-sm" name="m4c_p2_nama"></div>
                                <div class="col-md-4"><label class="form-label" style="font-size: 0.85rem;">Lama mengamati (bulan):</label><input type="number" class="form-control form-control-sm" name="m4c_p2_lama"></div>
                            </div>
                            <?php
                            $m4c_dims = [
                                1 => [
                                    'title' => '1. Disiplin dan ketepatan waktu',
                                    's1' => 'Sering terlambat atau tidak hadir pada apel, kegiatan kerja, atau pembinaan tanpa alasan; harus sering diingatkan.',
                                    's3' => 'Umumnya hadir tepat waktu; sesekali perlu diingatkan.',
                                    's5' => 'Selalu hadir tepat waktu tanpa diingatkan; menjadi contoh bagi yang lain.'
                                ],
                                2 => [
                                    'title' => '2. Tanggung jawab dan kerapian kerja',
                                    's1' => 'Tugas sering tidak selesai atau ditinggalkan; alat dan area kerja tidak dirawat.',
                                    's3' => 'Tugas umumnya selesai dengan hasil memadai; alat dan area kerja cukup terjaga.',
                                    's5' => 'Menyelesaikan tugas tuntas dan rapi, merawat alat, dan berinisiatif mengatasi masalah kecil.'
                                ],
                                3 => [
                                    'title' => '3. Kerja sama dan hubungan dengan orang lain',
                                    's1' => 'Sering berselisih, menghindari kerja kelompok, atau mengintimidasi.',
                                    's3' => 'Dapat bekerja dalam kelompok tanpa konflik berarti.',
                                    's5' => 'Aktif membantu dan menengahi; diterima baik oleh petugas dan sesama warga binaan.'
                                ],
                                4 => [
                                    'title' => '4. Ketahanan terhadap tekanan dan pengendalian emosi',
                                    's1' => 'Mudah marah atau menyerah saat ditegur atau menghadapi kerja berat; pernah bertindak agresif.',
                                    's3' => 'Umumnya tenang; kadang perlu waktu menenangkan diri, lalu kembali bekerja.',
                                    's5' => 'Tetap tenang dan melanjutkan kerja saat ditegur atau di bawah tekanan.'
                                ],
                                5 => [
                                    'title' => '5. Kepatuhan pada instruksi dan aturan',
                                    's1' => 'Sering menolak instruksi atau melanggar aturan; ada catatan teguran.',
                                    's3' => 'Umumnya mengikuti instruksi; pelanggaran kecil jarang terjadi.',
                                    's5' => 'Selalu mengikuti instruksi dan aturan; tanpa catatan teguran.'
                                ]
                            ];
                            foreach($m4c_dims as $n => $d): ?>
                            <div class="card mb-3 p-3 bg-light shadow-sm border">
                                <div class="fw-bold mb-2 text-primary" style="font-size: 0.9rem;"><?= $d['title'] ?></div>
                                <div class="row g-2 mb-2" style="font-size: 0.75rem; color: #555;">
                                    <div class="col-md-4"><span class="badge bg-secondary mb-1">Skor 1 (kurang)</span><br><?= $d['s1'] ?></div>
                                    <div class="col-md-4"><span class="badge bg-info text-dark mb-1">Skor 3 (cukup)</span><br><?= $d['s3'] ?></div>
                                    <div class="col-md-4"><span class="badge bg-success mb-1">Skor 5 (sangat baik)</span><br><?= $d['s5'] ?></div>
                                </div>
                                <div class="d-flex gap-3 mb-2 pt-2 border-top">
                                    <div style="flex:1"><label class="form-label mb-1 fw-bold" style="font-size: 0.8rem;">P1 (Penilai 1)</label><select class="form-select form-select-sm m4c-p1" name="m4c_d<?= $n ?>_p1" data-dim="<?= $n ?>"><option value="0">-</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select></div>
                                    <div style="flex:1"><label class="form-label mb-1 fw-bold" style="font-size: 0.8rem;">P2 (Penilai 2)</label><select class="form-select form-select-sm m4c-p2" name="m4c_d<?= $n ?>_p2" data-dim="<?= $n ?>"><option value="0">-</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select></div>
                                    <div style="flex:1"><label class="form-label mb-1 fw-bold text-success" style="font-size: 0.8rem;">Akhir (Rata-rata)</label><input type="number" class="form-control form-control-sm m4c-calc fw-bold" name="m4c_d<?= $n ?>_akhir" id="m4c_d<?= $n ?>_akhir" value="0"></div>
                                </div>
                                <div><input type="text" class="form-control form-control-sm" name="m4c_d<?= $n ?>_bukti" placeholder="Bukti perilaku (kejadian nyata):"></div>
                            </div>
                            <?php endforeach; ?>
                            <div class="col-12 mt-2 mb-2"><label class="form-label"><small><b>Pengamatan terbatas (kurang dari 3 bulan):</b></small></label>
                            <select class="form-select form-select-sm" name="m4c_terbatas"><option value="Tidak">Tidak</option><option value="Ya">Ya: beri tanda</option></select></div>
                            <table class="table table-bordered mt-2 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">TOTAL SKOR MODUL 4C (Maks. 25, rentang 5–25)</td>
                                    <td class="fw-bold text-center align-middle fs-5" style="width: 25%;"><span id="m4c-total">0</span><input type="hidden" name="skor_4c" id="skor_4c"></td>
                                </tr>
                            </table>

                            <div class="sub-title border-bottom pb-2 mt-4">4D. Kapasitas kerja (skor maks 6)</div>
                            <div class="alert alert-warning py-2 px-3 mb-2" style="font-size: 0.85rem;">
                                <strong>Petunjuk Pengisian :</strong> Hanya dilakukan bila Modul 2 berstatus Layak atau Layak bersyarat dan atas izin tenaga kesehatan. Gunakan tugas yang sama untuk semua napi di UPT yang sama.
                            </div>
                            <?php
                            $m4d_qs = [
                                "4D.1 Uji ketahanan fisik singkat di bawah pengawasan: berjalan cepat 10 menit dan memindahkan beban ±10 kg sejauh 10 meter, 3 kali" => [
                                    "Tidak mampu atau berhenti",
                                    "Mampu dengan jeda atau keluhan ringan",
                                    "Mampu lancar"
                                ],
                                "4D.2 Riwayat kegiatan kerja di Lapas/Rutan (bengkel, kebun, dapur, laundry, dan sebagainya), dari catatan pembinaan" => [
                                    "Tidak pernah ikut",
                                    "Ikut kurang dari 3 bulan atau tidak rutin",
                                    "Rutin 3 bulan atau lebih"
                                ],
                                "4D.3 Tugas praktik 10 menit dengan instruksi lisan satu kali (misalnya memilah dan mengemas ±30 bungkus bahan)" => [
                                    "Tidak selesai",
                                    "Selesai dengan banyak kesalahan atau perlu dibantu",
                                    "Selesai benar dan mandiri"
                                ]
                            ];
                            $dim = 1;
                            foreach($m4d_qs as $q => $opts): ?>
                            <div class="card mb-2 p-2 bg-light shadow-sm border-0">
                                <div class="fw-bold mb-2" style="font-size: 0.85rem;"><?= $q ?></div>
                                <div>
                                    <select class="form-select form-select-sm m4d-calc" name="m4d_<?= $dim ?>">
                                        <option value="0">0 - <?= $opts[0] ?></option>
                                        <option value="1">1 - <?= $opts[1] ?></option>
                                        <option value="2">2 - <?= $opts[2] ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php $dim++; endforeach; ?>
                            <table class="table table-bordered mt-2 border-dark">
                                <tr>
                                    <td class="bg-light fw-bold text-end align-middle" style="width: 75%;">TOTAL SKOR MODUL 4D (Maks. 6)</td>
                                    <td class="fw-bold text-center align-middle fs-5" style="width: 25%;"><span id="m4d-total">0</span><input type="hidden" name="skor_4d" id="skor_4d"></td>
                                </tr>
                            </table>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary px-4 py-2" onclick="prevTab('tab-modul3')"><i class="fas fa-arrow-left me-2"></i> Sebelumnya</button>
                                <button type="button" class="btn btn-primary px-4 py-2" onclick="nextTab('tab-rekap')">Selanjutnya <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- REKAP -->
                    <div class="tab-pane fade" id="pills-rekap">
                        <div class="form-section">
                            <h5 class="section-title">B.5 REKAP SKOR DAN KEPUTUSAN</h5>
                            
                            <table class="table table-bordered align-middle text-center mb-4">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%; text-align: left;">Komponen</th>
                                        <th style="width: 20%;">Sumber</th>
                                        <th style="width: 20%;">Maks</th>
                                        <th style="width: 20%;">Skor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="text-align: left;">Motivasi dan kesiapan berubah</td>
                                        <td>Modul 4B</td>
                                        <td>25</td>
                                        <td class="fw-bold" id="rekap_skor_4b">0</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left;">Sikap kerja</td>
                                        <td>Modul 4C</td>
                                        <td>25</td>
                                        <td class="fw-bold" id="rekap_skor_4c">0</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left;">Risiko dan stabilitas</td>
                                        <td>Modul 3</td>
                                        <td>20</td>
                                        <td class="fw-bold" id="rekap_skor_3">0</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left;">Minat dan bakat</td>
                                        <td>Modul 4A</td>
                                        <td>13</td>
                                        <td class="fw-bold" id="rekap_skor_4a">0</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left;">Kesiapan fisik (Layak 9 / Bersyarat 5 / Tunda 0)</td>
                                        <td>Modul 2</td>
                                        <td>9</td>
                                        <td class="fw-bold" id="rekap_skor_2">0</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left;">Kapasitas kerja (OPTIONAL)</td>
                                        <td>Modul 4D</td>
                                        <td>6</td>
                                        <td class="fw-bold" id="rekap_skor_4d">0</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td style="text-align: right; font-weight: bold;">JUMLAH</td>
                                        <td></td>
                                        <td class="fw-bold">98</td>
                                        <td class="fw-bold text-primary fs-5" id="display_total_skor">0</td>
                                    </tr>
                                </tbody>
                            </table>
                            <input type="hidden" name="total_skor" id="total_skor">

                            <div class="card mb-3 p-3 bg-light border">
                                <h6 class="fw-bold mb-2">Catatan :</h6>
                                <p class="mb-2" style="font-size: 0.85rem;">
                                    <strong>1. Khusus untuk narapidana yang berdasarkan hasil penilaian memiliki skor tinggi, terdapat beberapa catatan yang perlu ditindaklanjuti dengan check list sebagai berikut :</strong>
                                </p>
                                <div class="p-2 bg-white rounded border mb-2" style="font-size: 0.85rem;">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="catatan_m1_k2" value="1" id="chk_m1_k2">
                                        <label class="form-check-label" for="chk_m1_k2">Modul 1 Kategori 2</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="catatan_m2_bersyarat" value="1" id="chk_m2_bersyarat">
                                        <label class="form-check-label" for="chk_m2_bersyarat">Modul 2 Layak bersyarat</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="catatan_ketidaksesuaian" value="1" id="chk_ketidaksesuaian">
                                        <label class="form-check-label" for="chk_ketidaksesuaian">Ketidaksesuaian : poin 4B &ge; 20 tetapi poin 4C &le; 12 (jawaban mungkin diarahkan), atau ketidaksesuaian pada pertanyaan terbuka 4B</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="catatan_kekerasan" value="1" id="chk_kekerasan">
                                        <label class="form-check-label" for="chk_kekerasan">Catatan kekerasan 12 bulan terakhir (butir 3.4)</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="catatan_sikap_terbatas" value="1" id="chk_sikap_terbatas">
                                        <label class="form-check-label" for="chk_sikap_terbatas">Pengamatan sikap kerja terbatas (4C)</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="catatan_minat_rendah" value="1" id="chk_minat_rendah">
                                        <label class="form-check-label" for="chk_minat_rendah">Semua area minat &le; 2 (4A)</label>
                                    </div>
                                </div>
                                <small class="text-muted fst-italic mb-3 d-block" style="font-size: 0.8rem;">
                                    Jarak ke 2/3 masa pidana kurang dari 6 bulan (butir 0.7) tidak mengubah kategori; catat sebagai bahan pengaturan gelombang.
                                </small>

                                <p class="mb-2" style="font-size: 0.85rem;">
                                    <strong>2. Penetapan kategori (periksa berurutan dari atas; berhenti pada yang pertama terpenuhi)</strong>
                                </p>
                                <table class="table table-sm table-bordered bg-white mb-2" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Kategori</th>
                                            <th style="width: 70%;">Syarat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold">TIDAK MEMENUHI SYARAT</td>
                                            <td>Berhenti pada Modul 0 (kecuali butir 0.5 yang berstatus Ditunda).</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">RUJUK KE TPP</td>
                                            <td>Modul 1 Kategori 4. TPP mengklarifikasi; bila terindikasi pengedar : tidak ikut.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">DITUNDA</td>
                                            <td>Modul 2 Tunda, atau butir 0.5 menunggu keputusan pimpinan. Asesmen ulang setelah kondisi berubah atau keputusan terbit.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">TIDAK LAYAK</td>
                                            <td>Modul 1 Kategori 3; atau riwayat kabur (3.3); atau screening kategori tertinggi (3.2); atau skor di bawah 55. Berhak asesmen ulang pada gelombang berikut.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">LAYAK BERSYARAT</td>
                                            <td>Skor 55&ndash;69; atau skor &ge; 70 dengan satu <i>red flag</i> atau lebih.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">LAYAK</td>
                                            <td>Skor &ge; 70 dan tanpa <i>red flag</i>.</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <small class="text-muted fst-italic mb-3 d-block" style="font-size: 0.8rem;">
                                    Batas skor 70 dan 55 bersifat sementara dan akan dikalibrasi setelah gelombang I. Bila kuota Kanwil lebih kecil daripada jumlah yang Layak, urutkan berdasarkan skor, lalu jarak ke 2/3 masa pidana.
                                </small>

                                <div class="row g-3 p-2 bg-white rounded border mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold mb-1"><small>Kategori Hasil (Otomatis & Penetapan)</small></label>
                                        <select class="form-select form-select-sm fw-bold" name="kategori_hasil" id="kategori_hasil">
                                            <option value="TIDAK MEMENUHI SYARAT">Tidak memenuhi syarat</option>
                                            <option value="RUJUK KE TPP">Rujuk ke TPP</option>
                                            <option value="DITUNDA">Ditunda</option>
                                            <option value="TIDAK LAYAK">Tidak layak</option>
                                            <option value="LAYAK BERSYARAT">Layak bersyarat</option>
                                            <option value="LAYAK">Layak</option>
                                        </select>
                                        <div class="mt-2 text-center p-2 bg-light rounded border">
                                            <span class="fs-5 fw-bold" id="display_kategori_hasil">-</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold mb-1"><small>Rekomendasi Klaster</small></label>
                                        <select class="form-select form-select-sm mb-2" name="klaster_4a" id="klaster_4a">
                                            <option value="">Pilih Klaster</option>
                                            <option value="Kementan">Kementan</option>
                                            <option value="KKP">KKP</option>
                                            <option value="Re-edukasi non-lahan">Re-edukasi non-lahan</option>
                                        </select>
                                        <label class="form-label mb-1"><small>Dasar rekomendasi (area minat, pengalaman, hasil 4D):</small></label>
                                        <input type="text" class="form-control form-control-sm" name="dasar_rekomendasi" placeholder="Dasar rekomendasi...">
                                    </div>
                                    <div class="col-12 pt-2 border-top">
                                        <label class="form-label fw-bold mb-1"><small>Syarat dan Tindak Lanjut</small></label>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" name="tl_medis" placeholder="Medis (tanpa diagnosis)">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" name="tl_pembinaan" placeholder="Pembinaan">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <input type="text" class="form-control form-control-sm" name="alasan_tl" placeholder="Alasan bila Tidak layak, Ditunda, atau Rujuk">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3 p-3 bg-light border">
                                <h6 class="fw-bold mb-2">Keputusan Sidang TPP UPT</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label"><small>Keputusan</small></label>
                                        <select class="form-select form-select-sm" name="keputusan_tpp">
                                            <option value="">Pilih</option>
                                            <option value="Disetujui">Disetujui</option>
                                            <option value="Tidak disetujui">Tidak disetujui</option>
                                            <option value="Ditunda">Ditunda</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label"><small>Ketua TPP</small></label><input type="text" class="form-control form-control-sm" name="ketua_tpp"></div>
                                    <div class="col-md-4"><label class="form-label"><small>Tanggal Sidang</small></label><input type="date" class="form-control form-control-sm" name="tgl_sidang"></div>
                                    <div class="col-12"><label class="form-label"><small>Alasan (wajib bila berbeda dari kategori instrumen)</small></label><input type="text" class="form-control form-control-sm" name="alasan_tpp" placeholder="Alasan bila berbeda dari kategori instrumen:"></div>
                                    <div class="col-12"><label class="form-label"><small>Catatan Tambahan</small></label><textarea class="form-control form-control-sm" name="catatan" rows="2"></textarea></div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary px-4 py-2" onclick="prevTab('tab-modul4')"><i class="fas fa-arrow-left me-2"></i> Sebelumnya</button>
                                <div>
                                    <button type="button" class="btn btn-outline-secondary px-4 py-2 me-2" onclick="window.print()"><i class="fas fa-print me-2"></i>Cetak</button>
                                    <button type="submit" class="btn btn-primary px-4 py-2"><i class="fas fa-save me-2"></i>Simpan Asesmen</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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

        function nextTab(targetTabId, checkModul0 = false) {
            if(checkModul0) {
                let m0_stop = false;
                let v1 = document.querySelector('select[name="modul0_1"]').value;
                let v2 = document.querySelector('select[name="modul0_2"]').value;
                let v3 = document.querySelector('select[name="modul0_3"]').value;
                let v4 = document.querySelector('select[name="modul0_4"]').value;
                let v6 = document.querySelector('select[name="modul0_6"]').value;
                let v7 = document.querySelector('select[name="modul0_7"]').value;
                let v8 = document.querySelector('select[name="modul0_8"]').value;
                
                if (v1 === 'tidak' || v2 === 'tidak' || v3 === 'tidak' || v4 === 'tidak' || v6 === 'lainnya' || v7 === 'luar_batas' || v8 === 'tidak') {
                    m0_stop = true;
                }
                
                if (v6 === 'ringan' && v3 !== 'ya') {
                    m0_stop = true;
                }

                if(m0_stop) {
                    alert("Tidak dapat melanjutkan! Hasil skrinning administratif menyatakan narapidana BERHENTI (Tidak Memenuhi Syarat).");
                    return;
                }
            }
            let btn = document.getElementById(targetTabId);
            btn.disabled = false; // Enable the target tab
            btn.click();
            window.scrollTo(0, 0);
        }

        function prevTab(targetTabId) {
            document.getElementById(targetTabId).click();
            window.scrollTo(0, 0);
        }

        function goToNextFromModul0() {
            let isReedukasi = document.querySelector('input[name="jalur_asesmen"]:checked')?.value === 'reedukasi';
            if (isReedukasi) {
                nextTab('tab-modul2', true);
            } else {
                nextTab('tab-modul1', true);
            }
        }

        function goToPrevFromModul2() {
            let isReedukasi = document.querySelector('input[name="jalur_asesmen"]:checked')?.value === 'reedukasi';
            if (isReedukasi) {
                prevTab('tab-modul0');
            } else {
                prevTab('tab-modul1');
            }
        }

        function handleJalurChange() {
            let isReedukasi = document.querySelector('input[name="jalur_asesmen"]:checked')?.value === 'reedukasi';
            
            // Tab pill
            let tabM1 = document.getElementById('tab-modul1');
            if (tabM1) {
                if (isReedukasi) {
                    tabM1.innerHTML = 'Modul 1 <small class="badge bg-secondary ms-1">Dilewati</small>';
                } else {
                    tabM1.innerText = 'Modul 1';
                }
            }

            // Info alert in Identitas
            let infoRe = document.getElementById('jalur_info_reedukasi');
            if (infoRe) {
                if (isReedukasi) infoRe.classList.remove('d-none');
                else infoRe.classList.add('d-none');
            }

            // Modul 1 skip notice & content
            let m1Notice = document.getElementById('m1_skip_notice');
            let m1Content = document.getElementById('m1_content_wrapper');
            if (m1Notice && m1Content) {
                if (isReedukasi) {
                    m1Notice.classList.remove('d-none');
                    m1Content.classList.add('opacity-50');
                    m1Content.querySelectorAll('input, select').forEach(el => el.disabled = true);
                } else {
                    m1Notice.classList.add('d-none');
                    m1Content.classList.remove('opacity-50');
                    m1Content.querySelectorAll('input, select').forEach(el => el.disabled = false);
                    toggleTglUrin();
                }
            }

            // Modul 4B items
            document.querySelectorAll('.m4b-narkoba-item').forEach(el => {
                if (isReedukasi) {
                    el.style.display = 'none';
                    el.querySelectorAll('select, input').forEach(inp => inp.disabled = true);
                } else {
                    el.style.display = '';
                    el.querySelectorAll('select, input').forEach(inp => inp.disabled = false);
                }
            });

            // Modul 4B Konversi Banner & max display
            let bRehab = document.getElementById('m4b_konversi_rehab');
            let bReedukasi = document.getElementById('m4b_konversi_reedukasi');
            let rawMax = document.getElementById('m4b-raw-max');
            if (bRehab && bReedukasi) {
                if (isReedukasi) {
                    bRehab.classList.add('d-none');
                    bReedukasi.classList.remove('d-none');
                    if (rawMax) rawMax.innerText = '24';
                } else {
                    bRehab.classList.remove('d-none');
                    bReedukasi.classList.add('d-none');
                    if (rawMax) rawMax.innerText = '48';
                }
            }

            calculateAll();
        }

        function calculateAll() {
            let isReedukasi = document.querySelector('input[name="jalur_asesmen"]:checked')?.value === 'reedukasi';

            // Modul 0
            let m0_stop = false; let m0_tunda = false;
            let v1 = document.querySelector('select[name="modul0_1"]').value;
            let v2 = document.querySelector('select[name="modul0_2"]').value;
            let v3 = document.querySelector('select[name="modul0_3"]').value;
            let v4 = document.querySelector('select[name="modul0_4"]').value;
            let v5 = document.querySelector('select[name="modul0_5"]').value;
            let v6 = document.querySelector('select[name="modul0_6"]').value;
            let v7 = document.querySelector('select[name="modul0_7"]').value;
            let v8 = document.querySelector('select[name="modul0_8"]').value;
            
            if (v1 === 'tidak' || v2 === 'tidak' || v3 === 'tidak' || v4 === 'tidak' || v6 === 'lainnya' || v7 === 'luar_batas' || v8 === 'tidak') {
                m0_stop = true;
            }
            if (v6 === 'ringan' && v3 !== 'ya') {
                m0_stop = true;
            }
            
            if (v5 === 'termasuk') {
                m0_tunda = true;
            }

            let res0 = isReedukasi ? "Lanjut Modul 2 (Non-Narkoba)" : "Lanjut Modul 1";
            if(m0_stop) res0 = "Berhenti (Tidak Memenuhi Syarat)";
            else if(m0_tunda) res0 = "Ditunda";
            document.getElementById('hasil_modul0_display').value = res0;
            document.getElementById('hasil_modul0').value = res0;

            // Modul 1
            let m1_kat = "";
            let total_rf = 0;
            if (isReedukasi) {
                m1_kat = "Dilewati (Pintu Re-edukasi Non-Narkotika)";
                document.getElementById('kategori_modul1_display').value = m1_kat;
                document.getElementById('kategori_modul1').value = m1_kat;
            } else {
                let m1_score = 0;
                document.querySelectorAll('.m1-calc').forEach(e => { m1_score += parseInt(e.value||0); });
                
                let rf_kuat = document.querySelectorAll('.rf-kuat:checked').length;
                let rf_pend = document.querySelectorAll('.rf-pend:checked').length;
                total_rf = rf_kuat + rf_pend;

                let m1_tat = document.querySelector('select[name="m1_tat"]').value;
                let m1_tri = document.querySelector('select[name="m1_tri_1"]').value;
                
                let is_tat_pengguna = (m1_tat === 'Pengguna');
                let is_tat_bukan = (m1_tat === 'Bukan Pengguna');

                let is_kat4 = (rf_kuat > 0 || rf_pend >= 2);

                if (is_kat4) {
                    m1_kat = "Kategori 4: Indikasi Pengedar (Rujuk TPP)";
                } else if ((m1_score <= 1 && !is_tat_pengguna) || is_tat_bukan) {
                    m1_kat = "Kategori 3: Tidak Terverifikasi (Tidak Layak)";
                } else {
                    let is_konsisten = (m1_tri === 'Konsisten' || m1_tri === 'Sebagian');
                    let is_kat1 = ((m1_score >= 4 || is_tat_pengguna) && is_konsisten && total_rf === 0);
                    
                    if (is_kat1) {
                        m1_kat = "Kategori 1: Pengguna Terverifikasi (Lanjut)";
                    } else {
                        m1_kat = "Kategori 2: Indikasi Sedang/Belum Lengkap (Lanjut Bersyarat)";
                    }
                }

                document.getElementById('kategori_modul1_display').value = m1_kat + ` (Gejala: ${m1_score})`;
                document.getElementById('kategori_modul1').value = m1_kat;
            }

            // Modul 2
            let m2_is_tunda = false;
            let m2_is_bersyarat = false;
            document.querySelectorAll('.m2-calc').forEach(e => {
                if (e.value === 'tunda') m2_is_tunda = true;
                if (e.value === 'bersyarat') m2_is_bersyarat = true;
            });
            
            let m2_score = 9;
            let m2_val = 'layak';
            let m2_text = "LAYAK (Poin: 9)";
            
            if (m2_is_tunda) {
                m2_score = 0;
                m2_val = 'tunda';
                m2_text = "TUNDA (Poin: 0)";
            } else if (m2_is_bersyarat) {
                m2_score = 5;
                m2_val = 'layak_bersyarat';
                m2_text = "LAYAK BERSYARAT (Poin: 5)";
            }
            
            document.getElementById('kesimpulan_modul2_display').value = m2_text;
            document.getElementById('kesimpulan_modul2').value = m2_val;

            // Modul 3
            let m3_score = 0;
            let m3_2_val = document.querySelector('select[name="modul3_2"]').value;
            let m3_3_val = document.querySelector('select[name="modul3_3"]').value;
            document.querySelectorAll('.m3-calc').forEach(e => { 
                let v = parseInt(e.value||0);
                if(v >= 0) m3_score += v; 
            });
            document.getElementById('m3-total').innerText = m3_score;

            // Modul 4A
            let m4a_cats = ['kementan','kkp','mekanik','produksi','jasa'];
            let max_cat_score = 0; let best_cat = "";
            m4a_cats.forEach(c => {
                let c_score = 0;
                document.querySelectorAll('.m4a-'+c).forEach(e => { c_score += parseInt(e.value||0); });
                let scEl = document.getElementById('score-'+c);
                if(scEl) scEl.innerText = c_score;
                let scInp = document.getElementById('score_'+c+'_input');
                if(scInp) scInp.value = c_score;
                if(c_score > max_cat_score) { max_cat_score = c_score; best_cat = c; }
            });
            document.getElementById('m4a1-poin').innerText = max_cat_score;
            document.getElementById('m4a1-area').innerText = max_cat_score > 0 ? best_cat.toUpperCase() : '-';
            let m4a1_poin_inp = document.getElementById('m4a_poin_minat');
            if(m4a1_poin_inp) m4a1_poin_inp.value = max_cat_score;

            let m4a_pengalaman_val = parseInt(document.getElementById('m4a-pengalaman').value || 0);
            let m4a_kesediaan_val = parseInt(document.getElementById('m4a-kesediaan').value || 0);
            let m4a_total = max_cat_score + m4a_pengalaman_val + m4a_kesediaan_val;
            document.getElementById('m4a-total').innerText = m4a_total;
            document.getElementById('skor_4a').value = m4a_total;
            document.getElementById('klaster_4a').value = best_cat ? (best_cat === 'kementan' ? 'Kementan' : (best_cat === 'kkp' ? 'KKP' : 'Re-edukasi non-lahan')) : '';

            // Modul 4B
            let m4b_raw = 0;
            document.querySelectorAll('.m4b-calc').forEach(e => { 
                let isNarkoba = e.closest('.m4b-narkoba-item') !== null;
                if (isReedukasi && isNarkoba) return; // skip butir narkoba jika non-narkoba
                m4b_raw += parseInt(e.value || 0);
            });
            let m4b_conv = 5;
            if (isReedukasi) {
                // Konversi 6-24 ke skala 25
                if (m4b_raw >= 20) m4b_conv = 25;
                else if (m4b_raw >= 17) m4b_conv = 20;
                else if (m4b_raw >= 14) m4b_conv = 15;
                else if (m4b_raw >= 11) m4b_conv = 10;
                else m4b_conv = 5;
            } else {
                // Konversi 12-48 ke skala 25
                if (m4b_raw >= 39) m4b_conv = 25;
                else if (m4b_raw >= 33) m4b_conv = 20;
                else if (m4b_raw >= 27) m4b_conv = 15;
                else if (m4b_raw >= 21) m4b_conv = 10;
                else m4b_conv = 5;
            }
            let m4b_raw_disp = document.getElementById('m4b-raw-disp');
            if(m4b_raw_disp) m4b_raw_disp.innerText = m4b_raw;
            let m4b_raw_inp = document.getElementById('m4b_raw');
            if(m4b_raw_inp) m4b_raw_inp.value = m4b_raw;
            document.getElementById('m4b-total').innerText = m4b_conv;
            document.getElementById('skor_4b').value = m4b_conv;

            // Modul 4C
            let m4c_score = 0;
            for(let i=1; i<=5; i++) {
                let p1 = parseInt(document.querySelector('select[name="m4c_d'+i+'_p1"]').value || 0);
                let p2 = parseInt(document.querySelector('select[name="m4c_d'+i+'_p2"]').value || 0);
                let akhir = 0;
                if(p1 > 0 && p2 > 0) {
                    akhir = Math.round((p1 + p2) / 2);
                } else if(p1 > 0) {
                    akhir = p1;
                } else if(p2 > 0) {
                    akhir = p2;
                }
                document.getElementById('m4c_d'+i+'_akhir').value = akhir;
                m4c_score += akhir;
            }
            document.getElementById('m4c-total').innerText = m4c_score;
            document.getElementById('skor_4c').value = m4c_score;

            // Modul 4D
            let m4d_score = 0;
            document.querySelectorAll('.m4d-calc').forEach(e => { m4d_score += parseInt(e.value||0); });
            document.getElementById('m4d-total').innerText = m4d_score;
            document.getElementById('skor_4d').value = m4d_score;

            // Update Rekap table elements
            if(document.getElementById('rekap_skor_4b')) document.getElementById('rekap_skor_4b').innerText = m4b_conv;
            if(document.getElementById('rekap_skor_4c')) document.getElementById('rekap_skor_4c').innerText = m4c_score;
            if(document.getElementById('rekap_skor_3')) document.getElementById('rekap_skor_3').innerText = m3_score;
            if(document.getElementById('rekap_skor_4a')) document.getElementById('rekap_skor_4a').innerText = m4a_total;
            if(document.getElementById('rekap_skor_2')) document.getElementById('rekap_skor_2').innerText = m2_score;
            if(document.getElementById('rekap_skor_4d')) document.getElementById('rekap_skor_4d').innerText = m4d_score;

            // FINAL
            let total = m2_score + m3_score + m4a_total + m4b_conv + m4c_score + m4d_score;
            document.getElementById('display_total_skor').innerText = total;
            document.getElementById('total_skor').value = total;

            // Catatan 1 Checklist automated checks
            let chk_m1_k2 = document.getElementById('chk_m1_k2');
            if (chk_m1_k2) {
                if (isReedukasi) {
                    chk_m1_k2.checked = false;
                    chk_m1_k2.disabled = true;
                } else {
                    chk_m1_k2.disabled = false;
                    if (m1_kat.includes("Kategori 2")) chk_m1_k2.checked = true;
                }
            }

            let chk_m2_bersyarat = document.getElementById('chk_m2_bersyarat');
            if (chk_m2_bersyarat && m2_val === 'layak_bersyarat') chk_m2_bersyarat.checked = true;

            let m4b_konsisten = document.querySelector('select[name="m4b_konsisten"]')?.value;
            let chk_ketidaksesuaian = document.getElementById('chk_ketidaksesuaian');
            if (chk_ketidaksesuaian && ((m4b_conv >= 20 && m4c_score <= 12) || m4b_konsisten === 'Ada ketidaksesuaian')) {
                chk_ketidaksesuaian.checked = true;
            }

            let m3_4_val = document.querySelector('select[name="modul3_4"]')?.value;
            let chk_kekerasan = document.getElementById('chk_kekerasan');
            if (chk_kekerasan && m3_4_val === '0') chk_kekerasan.checked = true;

            let m4c_terbatas = document.querySelector('select[name="m4c_terbatas"]')?.value;
            let chk_sikap_terbatas = document.getElementById('chk_sikap_terbatas');
            if (chk_sikap_terbatas && m4c_terbatas === 'Ya') chk_sikap_terbatas.checked = true;

            let chk_minat_rendah = document.getElementById('chk_minat_rendah');
            if (chk_minat_rendah && (max_cat_score <= 2 && m4a_total > 0)) chk_minat_rendah.checked = true;

            // Catatan 2: Penetapan kategori (periksa berurutan dari atas; berhenti pada yang pertama terpenuhi)
            let result = "TIDAK LAYAK"; let resClass = "text-danger";
            if (m0_stop) {
                result = "TIDAK MEMENUHI SYARAT";
                resClass = "text-danger";
            } else if (!isReedukasi && m1_kat.includes("Kategori 4")) {
                result = "RUJUK KE TPP";
                resClass = "text-danger";
            } else if (m2_val === 'tunda' || m0_tunda) {
                result = "DITUNDA";
                resClass = "text-warning";
            } else if ((!isReedukasi && m1_kat.includes("Kategori 3")) || m3_2_val === '-99' || m3_3_val === '-99' || total < 55) {
                result = "TIDAK LAYAK";
                resClass = "text-danger";
            } else if ((total >= 55 && total <= 69) || (total >= 70 && ((!isReedukasi && (total_rf > 0 || m1_kat.includes("Kategori 2"))) || (isReedukasi && (m3_2_val === '-99' || m3_3_val === '-99'))))) {
                result = "LAYAK BERSYARAT";
                resClass = "text-info";
            } else if (total >= 70) {
                result = "LAYAK";
                resClass = "text-success";
            }
            
            let resEl = document.getElementById('display_kategori_hasil');
            if (resEl) {
                resEl.innerText = result;
                resEl.className = "fs-5 fw-bold " + resClass;
            }
            let katHasilInp = document.getElementById('kategori_hasil');
            if (katHasilInp) {
                katHasilInp.value = result;
            }
        }

        function toggleTglUrin() {
            var val = document.getElementById('m1_urin')?.value;
            var tglInput = document.getElementById('m1_tgl_urin');
            if(tglInput) {
                if(val === 'Positif' || val === 'Negatif') {
                    tglInput.disabled = false;
                } else {
                    tglInput.disabled = true;
                    tglInput.value = '';
                }
            }
        }

        document.querySelectorAll('select, input').forEach(el => { el.addEventListener('change', calculateAll); });
        handleJalurChange();
        toggleTglUrin();
    </script>
    
    <?php if(!empty($edit_data)): ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const editData = <?= json_encode($edit_data) ?>;
        for(const key in editData) {
            const els = document.querySelectorAll(`[name="${key}"]`);
            els.forEach(el => {
                if(el.type === 'checkbox' || el.type === 'radio') {
                    if(el.value == editData[key]) el.checked = true;
                } else {
                    el.value = editData[key];
                }
            });
        }
        
        const gejalaRaw = editData['gejala_json'];
        if(gejalaRaw) {
            try {
                const gejala = JSON.parse(gejalaRaw);
                for(const key in gejala) {
                    if(key === 'extra_data') continue;
                    const els = document.querySelectorAll(`[name="${key}"]`);
                    els.forEach(el => {
                        if(el.type === 'checkbox' || el.type === 'radio') {
                            if(el.value == gejala[key]) el.checked = true;
                        } else {
                            el.value = gejala[key];
                        }
                    });
                }
                const extra = gejala.extra_data || {};
                for(const key in extra) {
                    const els = document.querySelectorAll(`[name="${key}"]`);
                    els.forEach(el => {
                        if(el.type === 'checkbox' || el.type === 'radio') {
                            if(el.value == extra[key]) el.checked = true;
                        } else {
                            el.value = extra[key];
                        }
                    });
                }
            } catch(e) {}
        }
        
        setTimeout(() => {
            handleJalurChange();
            calculateAll();
            toggleTglUrin();
        }, 500);
    });
    </script>
    <?php endif; ?>
</body>
</html>






