<?php
/**
 * Cetak Laporan Asesmen (Format Blanko Asli)
 */

require_once 'includes/functions.php';
require_once 'auth_asesmen.php';

$id = $_GET['id'] ?? null;
if(!$id) die("ID Asesmen tidak ditemukan.");

$stmt = $pdo->prepare("SELECT * FROM data_asesmen WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$data) die("Data Asesmen tidak ditemukan.");

$gejala = json_decode($data['gejala_json'], true) ?: [];
$ex = $gejala['extra_data'] ?? [];
$jalur = $ex['jalur_asesmen'] ?? 'rehabilitasi';

$total_gejala = 0;
for($i=0; $i<=10; $i++){
    if(!empty($gejala["gejala_$i"])) $total_gejala++;
}

// Menghitung IMT (Indeks Massa Tubuh) dari bb dan tb jika ada
$imt = '';
if(!empty($data['bb']) && !empty($data['tb'])) {
    $bb = (float)$data['bb'];
    $tb_m = (float)$data['tb'] / 100; // ubah cm ke meter
    if($tb_m > 0) {
        $imt = round($bb / ($tb_m * $tb_m), 1);
    }
}
if(empty($ex['m2_imt'])) {
    $ex['m2_imt'] = $imt;
}

function cb($val, $expected) {
    if(is_array($expected)) {
        return in_array(strtolower((string)$val), array_map('strtolower', $expected)) ? '&#9745;' : '&#9744;';
    }
    return (strtolower((string)$val) === strtolower((string)$expected)) ? '&#9745;' : '&#9744;';
}

function uline($val, $width = '100%') {
    $v = empty($val) ? '&nbsp;' : htmlspecialchars($val);
    return "<div style='display:inline-block; border-bottom: 1px solid #000; min-width: $width;'>$v</div>";
}

function fdate($val) {
    if (empty($val) || $val === '0000-00-00') return '';
    if (is_numeric($val) && (float)$val > 1000 && (float)$val < 100000) {
        $unixTimestamp = ((float)$val - 25569) * 86400;
        return gmdate('d-m-Y', (int)$unixTimestamp);
    }
    $time = strtotime($val);
    if ($time === false || $time <= 0) return htmlspecialchars($val);
    return date('d-m-Y', $time);
}

function calcDateDiff($start, $end) {
    if(empty($start) || empty($end) || $start === '0000-00-00' || $end === '0000-00-00') return '____ Th, ____ Bl, ____ hr';
    try {
        if (is_numeric($start) && (float)$start > 1000 && (float)$start < 100000) {
            $start = gmdate('Y-m-d', ((float)$start - 25569) * 86400);
        }
        if (is_numeric($end) && (float)$end > 1000 && (float)$end < 100000) {
            $end = gmdate('Y-m-d', ((float)$end - 25569) * 86400);
        }
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        if($d1 > $d2) return '0 Th, 0 Bl, 0 hr';
        $diff = $d1->diff($d2);
        return $diff->y . ' Th, ' . $diff->m . ' Bl, ' . $diff->d . ' hr';
    } catch(Exception $e) {
        return '____ Th, ____ Bl, ____ hr';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>IKP-R Draf 1 - <?= htmlspecialchars($data['nama']) ?></title>
    <style>
        *, *:before, *:after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; background: #e0e0e0; margin: 0; padding: 20px; }
        .page { background: #fff; width: 21cm; padding: 1cm; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
        .title { font-size: 14pt; color: #1F3A2E; text-align: center; font-weight: bold; margin-bottom: 20px; }
        .section-title { font-size: 11.5pt; color: #2F5D46; font-weight: bold; margin-top: 20px; margin-bottom: 10px; border: none; background: transparent; padding: 0; text-transform: none; }
        .sub-title { font-size: 10pt; color: #333333; font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        .tbl-border { width: 100%; border-collapse: collapse; margin-bottom: 15px; word-wrap: break-word; }
        .tbl-border th, .tbl-border td { border: 1px solid #8C8C8C; padding: 5px; vertical-align: top; }
        .tbl-border th { background: #1F3A2E; color: white; text-align: center; font-weight: bold; font-size: 9pt; }
        .tbl-border td { font-size: 9pt; }
        .tbl-identitas td { background: #E8EEE9; font-weight: bold; width: 25%; }
        .tbl-identitas td:nth-child(even) { background: #fff; font-weight: normal; }
        .text-center { text-align: center; }
        .mt-10 { margin-top: 10px; }
        .petunjuk { font-size: 9pt; font-style: italic; margin-bottom: 5px; }
        .btn-print { text-align: center; margin-bottom: 20px; }
        .btn-print button { padding: 10px 20px; font-size: 16px; cursor: pointer; background: #2F5D46; color: white; border: none; border-radius: 4px; }
        .page-break { margin-top: 15px; }
        @media print {
            @page { size: A4; margin: 1cm; }
            body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { margin: 0; padding: 0; box-shadow: none; width: 100%; max-width: 100%; }
            .btn-print { display: none; }
            .page-break { margin-top: 0; page-break-after: auto; }
            .petunjuk, .sub-title { page-break-inside: avoid; break-inside: avoid; }
            table { page-break-inside: avoid; width: 100%; max-width: 100%; }
            tr, td, th { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            .title, .sub-title, .petunjuk { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="btn-print">
        <button onclick="window.print()">Cetak Dokumen</button>
    </div>

    <div class="page">
        <!-- HEADER -->
        <div class="title">INSTRUMEN KELAYAKAN DAN PENEMPATAN REHABILITASI DAN RE-EDUKASI NARAPIDANA PENGGUNA NAPZA BERBASIS KETAHANAN PANGAN</div>
        
        <!-- MODUL 0 -->
        <div class="title" style="font-size:13pt; margin-top:0;">IDENTITAS DAN MODUL 0. SKRINNING AWAL (SELEKSI ADMINISTRATIF)</div>
        <table class="tbl-border tbl-identitas">
            <tr><td>Nama</td><td><?= uline($data['nama']) ?></td><td>No. register / ID SDP</td><td><?= uline($data['no_register']) ?></td></tr>
            <tr><td>UPT / Kanwil</td><td><?= uline($data['upt'] . ' / Banten') ?></td><td>Tempat, tgl lahir</td><td><?= uline($data['ttl']) ?></td></tr>
            <tr><td>Usia saat asesmen</td><td><?= uline($data['usia'] . ' tahun') ?></td><td>Tanggal asesmen</td><td><?= uline(fdate($data['tgl_asesmen'])) ?></td></tr>
            <tr><td>Pasal dan perkara</td><td colspan="3"><?= uline($ex['m0_pasal'] ?? '') ?></td></tr>
            <tr><td>Lama pidana</td><td><?= uline($ex['m0_lama_pidana'] ?? '') ?></td><td>Tgl mulai pidana</td><td><?= uline(fdate($ex['m0_tgl_mulai'] ?? '')) ?></td></tr>
            <tr><td>Tgl 2/3 masa pidana</td><td><?= uline(fdate($ex['m0_tgl_23'] ?? '')) ?></td><td>Tgl bebas</td><td><?= uline(fdate($ex['m0_tgl_bebas'] ?? '')) ?></td></tr>
            <tr><td>Nama asesor</td><td><?= uline($ex['m0_nama_asesor'] ?? '') ?></td><td>Nama asesor kedua / reviu</td><td><?= uline($ex['m0_nama_reviu'] ?? '') ?></td></tr>
            <tr>
                <td style="background:#1F3A2E; color:#fff; font-weight:bold;">Pintu Asesmen</td>
                <td colspan="3" style="font-weight:bold; color: <?= $jalur === 'reedukasi' ? '#1b5e20' : '#0d47a1' ?>;">
                    <?= $jalur === 'reedukasi' ? '&#9745; Pintu Re-edukasi (Narapidana Non-Narkotika)' : '&#9745; Pintu Rehabilitasi (Narapidana Pengguna Narkotika)' ?>
                </td>
            </tr>
        </table>

        <div class="petunjuk">PETUNJUK PENGISIAN :<br>Isi butir 0.1 sampai 0.8 dari SDP dan berkas sebelum bertemu napi. Satu jawaban yang menghentikan sudah cukup untuk berhenti; tidak perlu melanjutkan ke modul berikutnya.</div>
        
        <table class="tbl-border">
            <tr><th width="5%">No</th><th width="55%">Syarat</th><th width="15%">Sumber</th><th width="25%">Hasil</th></tr>
            <tr><td class="text-center">0.1</td><td>Usia pada tanggal asesmen 18-45 tahun (P1)</td><td>SDP</td><td><?= cb($data['modul0_1'], 'ya') ?> Ya &nbsp; <?= cb($data['modul0_1'], 'tidak') ?> Tidak: <b>berhenti</b></td></tr>
            <tr><td class="text-center">0.2</td><td>Bukan pidana mati atau pidana seumur hidup</td><td>Putusan, SDP</td><td><?= cb($data['modul0_2'], 'ya') ?> Ya &nbsp; <?= cb($data['modul0_2'], 'tidak') ?> Tidak: <b>berhenti</b></td></tr>
            <tr><td class="text-center">0.3</td><td>Putusan tidak menyatakan secara eksplisit peran bandar, produsen, atau pengendali jaringan peredaran</td><td>Putusan</td><td><?= cb($data['modul0_3'], 'ya') ?> Ya &nbsp; <?= cb($data['modul0_3'], 'tidak') ?> Tidak: <b>berhenti</b></td></tr>
            <tr><td class="text-center">0.4</td><td>Bukan kelompok yang dikecualikan (korupsi, napiter kategori merah, Anak Binaan). Napiter kategori hijau hanya dengan rekomendasi BNPT</td><td>SDP, BNPT</td><td><?= cb($data['modul0_4'], 'ya') ?> Ya &nbsp; <?= cb($data['modul0_4'], 'tidak') ?> Tidak: <b>berhenti</b></td></tr>
            <tr><td class="text-center">0.5</td><td>Perkara bukan perlindungan anak, kekerasan seksual, atau kesusilaan (P5)</td><td>Putusan</td><td><?= cb($data['modul0_5'], 'bukan') ?> Bukan <br><?= cb($data['modul0_5'], 'termasuk') ?> Termasuk : status <b>DITUNDA</b></td></tr>
            <tr><td class="text-center">0.6</td><td>Register F (P3)</td><td>Register F, SDP</td>
                <td>
                    <?= cb($data['modul0_6'], 'tidak') ?> Tidak pernah tercatat<br>
                    <?= cb($data['modul0_6'], 'ringan') ?> Pernah, ringan, lebih dari 12 bulan lalu (lanjut hanya bila P3 melonggarkan; jika tidak, berhenti)<br>
                    <?= cb($data['modul0_6'], 'lainnya') ?> Pernah, lainnya: berhenti
                </td>
            </tr>
            <tr><td class="text-center">0.7</td><td>Sisa pidana (P4)<br>Jarak dari tanggal asesmen ke 2/3 masa pidana: <?= calcDateDiff($data['tgl_asesmen'], $ex['m0_tgl_23'] ?? '') ?><br>Sisa pidana total: <?= calcDateDiff($data['tgl_asesmen'], $ex['m0_tgl_bebas'] ?? '') ?><br>batas min-maks: ____ s.d. ____</td><td>SDP</td>
                <td>
                    <?= cb($ex['modul0_7']??'', 'ya') ?> Jarak &ge; 6 bulan<br>
                    <?= cb($ex['modul0_7']??'', 'tidak') ?> Jarak < 6 bulan : catat untuk pengaturan gelombang<br>
                    <?= cb($ex['modul0_7']??'', 'dalam_batas') ?> Di dalam batas P4<br>
                    <?= cb($ex['modul0_7']??'', 'luar_batas') ?> Di luar batas: berhenti
                </td>
            </tr>
            <tr><td class="text-center">0.8</td><td>Tidak sedang menjalani proses hukum lain (tersangka/terdakwa perkara lain) dan tidak ada penetapan lain yang menghalangi</td><td>SDP, Kejaksaan</td><td><?= cb($ex['modul0_8']??'', 'ya') ?> Ya &nbsp; <?= cb($ex['modul0_8']??'', 'tidak') ?> Tidak: <b>berhenti</b></td></tr>

        </table>
        
        <table class="tbl-border mt-10">
            <tr>
                <td width="25%" style="background:#E8EEE9;"><b>Hasil Modul 0</b></td>
                <td colspan="3">
                    <?= cb($data['hasil_modul0'], ['Lanjut Modul 1', 'Lanjut ke Modul 1', 'Lanjut']) ?> Lanjut ke Modul 1<br>
                    <?= cb($data['hasil_modul0'], ['Berhenti (Tidak Memenuhi Syarat)', 'Berhenti']) ?> Berhenti: tidak memenuhi syarat, butir ke-____, alasan : ______________________________<br>
                    <?= cb($data['hasil_modul0'], ['Ditunda']) ?> Ditunda menunggu keputusan pimpinan, butir ke-____
                </td>
            </tr>
        </table>

        <div style="background:#F4F4F4; padding: 10px; font-size: 9pt; border: 1px solid #8C8C8C; margin-bottom: 20px;">
            <b>Pernyataan kesediaan.</b> Saya telah mendapat penjelasan tentang tujuan, isi, lamanya, dan aturan program, termasuk kewajiban bekerja, mengikuti terapi dan tes urin, serta sanksi bila melanggar. Saya mengikuti asesmen dan program ini dengan sukarela dan memberikan keterangan sebenarnya. Saya memahami bahwa hasil asesmen menjadi bahan sidang TPP dan tidak menjamin saya diterima.<br><br>
            Nama dan tanda tangan napi: _________________________    Tanggal: ____________<br><br>
            Nama dan tanda tangan asesor: _______________________    Tanggal: ____________
        </div>

        <div class="page-break"></div>

        <!-- MODUL 1 -->
        <div class="page-break"></div>
        <div class="title" style="font-size:13pt; margin-top:0;">MODUL 1. VERIFIKASI STATUS PENGGUNA NARKOTIKA</div>
        <?php if($jalur === 'reedukasi'): ?>
        <div style="background:#E8EEE9; border: 2px dashed #2F5D46; padding: 25px; text-align: center; margin-bottom: 25px; border-radius: 6px;">
            <b style="font-size: 11pt; color: #1F3A2E;">MODUL 1 DILEWATI (PINTU RE-EDUKASI NON-NARKOTIKA)</b><br><br>
            <p style="font-size: 9.5pt; margin: 0; line-height: 1.6; color: #222;">
                Instrumen Modul 1 hanya diperuntukkan bagi narapidana pengguna narkotika pada pintu rehabilitasi.<br>
                Untuk <b>Pintu Re-edukasi (Narapidana Non-Narkotika)</b>, Modul 1 <b>tidak dipakai</b> dan penilaian kelayakan langsung menggunakan Modul 2, 3, dan 4.
            </p>
        </div>
        <?php else: ?>
        <div class="petunjuk">Modul ini menjawab satu pertanyaan : apakah napi benar pengguna berdasarkan riwayat, bukan hanya berdasarkan pasal. Hasil TAT BNN menjadi rujukan utama. Modul ini tidak diberi skor; hasilnya berupa Kategori 1 sampai 4.</div>

        <div class="sub-title">1.1 Data dokumen (diisi sebelum wawancara)</div>
        <table class="tbl-border tbl-identitas">
            <tr><td>Pasal didakwa dan diputus</td><td><?= uline($ex['m1_pasal'] ?? '') ?></td></tr>
            <tr><td>Jenis dan berat barang bukti (gram/butir)</td><td><?= uline($ex['m1_bb'] ?? '') ?></td></tr>
            <tr><td>Peran dalam putusan</td><td>
                <?= cb($ex['m1_peran']??'', 'Pemakai') ?> Pemakai &nbsp; 
                <?= cb($ex['m1_peran']??'', 'Pembeli untuk dipakai') ?> Pembeli &nbsp; 
                <?= cb($ex['m1_peran']??'', 'Kurir/perantara') ?> Kurir &nbsp; 
                <?= cb($ex['m1_peran']??'', 'Lainnya') ?> Lainnya: ______
            </td></tr>
            <tr><td>Hasil TAT BNN / ASSIST/ asesmen rehabilitasi sebelumnya</td><td>
                <?= cb($ex['m1_tat']??'', 'Belum ada') ?> Belum ada &nbsp; 
                <?= cb($ex['m1_tat']??'', 'Pengguna') ?> Ada, menyatakan pengguna &nbsp; 
                <?= cb($ex['m1_tat']??'', 'Bukan Pengguna') ?> Ada, menyatakan bukan pengguna
                <br><br>Rekomendasi: <?= uline($ex['m1_rekomendasi'] ?? '', '50%') ?>
            </td></tr>
            <tr><td>Hasil tes urin di UPT (bila ada)</td><td>
                <?= cb($ex['m1_urin']??'', 'Positif') ?> Positif &nbsp; 
                <?= cb($ex['m1_urin']??'', 'Negatif') ?> Negatif &nbsp; 
                <?= cb($ex['m1_urin']??'', 'Belum dites') ?> Belum dites &nbsp; Tgl: <?= uline(fdate($ex['m1_tgl_urin'] ?? ''), '30%') ?>
            </td></tr>
        </table>

        <div class="sub-title">1.2 Wawancara riwayat penggunaan</div>
        <table class="tbl-border tbl-identitas">
            <tr><td>a. Zat utama</td><td><?= uline($data['zat_utama']) ?></td></tr>
            <tr><td>b. Usia pertama kali memakai</td><td><?= htmlspecialchars($data['usia_pertama_memakai']) ?> tahun</td></tr>
            <tr><td>c. Lama pemakaian rutin</td><td><?= htmlspecialchars($data['lama_pemakaian']) ?> tahun</td></tr>
            <tr><td>d. Frekuensi dalam 3 bln sblm ditahan</td><td>
                <?= cb($ex['m1_frekuensi']??'', 'Harian') ?> Harian &nbsp; 
                <?= cb($ex['m1_frekuensi']??'', 'Mingguan') ?> Mingguan &nbsp; 
                <?= cb($ex['m1_frekuensi']??'', 'Bulanan') ?> Bulanan &nbsp; 
                <?= cb($ex['m1_frekuensi']??'', 'Tidak memakai') ?> Tidak memakai
            </td></tr>
            <tr><td>e. Cara pakai</td><td>
                <?= cb($ex['m1_cara']??'', 'Dihisap') ?> Dihisap/ditelan &nbsp; 
                <?= cb($ex['m1_cara']??'', 'Suntik') ?> Suntik
            </td></tr>
            <tr><td>f. Pemakaian terakhir</td><td><?= uline(fdate($ex['m1_terakhir'] ?? '')) ?></td></tr>
            <tr>
                <td>g. Pernah menjalani rehabilitasi, terapi, atau konseling</td>
                <td>
                    <?= cb($ex['m1_rehab']??'', 'Tidak') ?> Tidak &nbsp; 
                    <?= cb($ex['m1_rehab']??'', 'Ya') ?> Ya<br><br>
                    Di mana, kapan, hasilnya: <?= uline($ex['m1_rehab_ket']??'', '100%') ?>
                </td>
            </tr>
            <tr>
                <td>h. Pernah mencoba berhenti sendiri</td>
                <td>
                    <?= cb($ex['m1_berhenti']??'', 'Tidak') ?> Tidak &nbsp; 
                    <?= cb($ex['m1_berhenti']??'', 'Ya') ?> Ya, <?= uline($ex['m1_berhenti_kali']??'', '40px') ?> kali<br><br>
                    Penyebab kambuh: <?= uline($ex['m1_berhenti_ket']??'', '100%') ?>
                </td>
            </tr>
        </table>

        <div class="sub-title">1.3 Daftar gejala gangguan penggunaan zat (12 bulan sebelum ditahan)</div>
        <div class="petunjuk">Tanyakan satu per satu; centang "Ya" bila napi membenarkan dengan contoh yang jelas.</div>
        <table class="tbl-border">
            <tr><th width="5%">No</th><th width="75%">Gejala</th><th width="10%">Ya</th><th width="10%">Tidak</th></tr>
            <tr><td class="text-center">1</td><td>Memakai lebih banyak atau lebih lama daripada yang direncanakan</td><td class="text-center"><?= cb($gejala['gejala_0']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_0']??0, 0) ?></td></tr>
            <tr><td class="text-center">2</td><td>Ingin atau sudah mencoba mengurangi atau berhenti, tetapi gagal</td><td class="text-center"><?= cb($gejala['gejala_1']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_1']??0, 0) ?></td></tr>
            <tr><td class="text-center">3</td><td>Banyak waktu tersita untuk mencari, memakai, atau pulih dari efek zat</td><td class="text-center"><?= cb($gejala['gejala_2']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_2']??0, 0) ?></td></tr>
            <tr><td class="text-center">4</td><td>Dorongan atau keinginan kuat untuk memakai (craving)</td><td class="text-center"><?= cb($gejala['gejala_3']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_3']??0, 0) ?></td></tr>
            <tr><td class="text-center">5</td><td>Gagal menjalankan kewajiban di pekerjaan, sekolah, atau rumah karena pemakaian</td><td class="text-center"><?= cb($gejala['gejala_4']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_4']??0, 0) ?></td></tr>
            <tr><td class="text-center">6</td><td>Tetap memakai walaupun menimbulkan masalah dengan keluarga atau orang lain</td><td class="text-center"><?= cb($gejala['gejala_5']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_5']??0, 0) ?></td></tr>
            <tr><td class="text-center">7</td><td>Meninggalkan atau mengurangi kegiatan penting (pekerjaan, hobi, pergaulan) karena pemakaian</td><td class="text-center"><?= cb($gejala['gejala_6']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_6']??0, 0) ?></td></tr>
            <tr><td class="text-center">8</td><td>Memakai dalam situasi berbahaya (misalnya sambil berkendara)</td><td class="text-center"><?= cb($gejala['gejala_7']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_7']??0, 0) ?></td></tr>
            <tr><td class="text-center">9</td><td>Tetap memakai walaupun tahu menimbulkan masalah fisik atau kejiwaan</td><td class="text-center"><?= cb($gejala['gejala_8']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_8']??0, 0) ?></td></tr>
            <tr><td class="text-center">10</td><td>Toleransi: perlu dosis makin banyak untuk efek yang sama</td><td class="text-center"><?= cb($gejala['gejala_9']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_9']??0, 0) ?></td></tr>
            <tr><td class="text-center">11</td><td>Gejala putus zat saat berhenti, atau memakai zat untuk menghilangkan gejala itu</td><td class="text-center"><?= cb($gejala['gejala_10']??0, 1) ?></td><td class="text-center"><?= cb($gejala['gejala_10']??0, 0) ?></td></tr>
            <tr>
                <td colspan="2" style="background:#E8EEE9; font-weight:bold; text-align:right;">Jumlah jawaban "Ya"</td>
                <td colspan="2" class="text-center" style="font-weight:bold;"><?= htmlspecialchars($total_gejala??'') ?> dari 11</td>
            </tr>
        </table>
        
        <div style="font-size:9pt; margin-top:5px; margin-bottom:15px;">
            <b>SKOR :</b> 0–1: tidak memenuhi ; 2–3: ringan ; 4–5: sedang ; &ge;6: berat
        </div>

        <div class="sub-title">1.4 Triangulasi (cek silang)</div>
        <table class="tbl-border">
            <tr>
                <td width="50%">
                    Cerita napi dibandingkan putusan/BAP<br><br>
                    <?= cb($ex['m1_tri_1']??'', 'Konsisten') ?> Konsisten &nbsp; 
                    <?= cb($ex['m1_tri_1']??'', 'Sebagian') ?> Sebagian &nbsp; 
                    <?= cb($ex['m1_tri_1']??'', 'Tidak konsisten') ?> Tidak konsisten
                </td>
                <td width="50%">
                    Keterangan keluarga atau catatan lain (bila ada)<br><br>
                    <?= uline($ex['m1_tri_2']??'') ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    Keterkaitan dengan jaringan peredaran (putusan, informasi intelijen Lapas)<br><br>
                    <?= cb($ex['m1_tri_3']??'', 'Tidak ada') ?> Tidak ada &nbsp; 
                    <?= cb($ex['m1_tri_3']??'', 'Ada') ?> Ada: <?= uline($ex['m1_tri_3_ket']??'', '150px') ?>
                </td>
            </tr>
        </table>

        <div class="sub-title">1.5 Tanda bahaya peredaran (red flag)</div>
        <table class="tbl-border">
            <tr>
                <td width="50%" style="vertical-align:top;">
                    <b>Kuat</b> (satu saja berarti rujuk ke TPP, bukan otomatis lolos) :<br><br>
                    <div style="margin-bottom:5px;"><?= cb($ex['m1_rf_kuat1']??'', '1') ?> Barang bukti melebihi ambang pemakaian SEMA 4/2010, tanpa riwayat ketergantungan yang sejalan</div>
                    <div style="margin-bottom:5px;"><?= cb($ex['m1_rf_kuat2']??'', '1') ?> Putusan atau BAP memuat peran menjual, mengedarkan, mengendalikan, atau menerima transaksi</div>
                    <div style="margin-bottom:5px;"><?= cb($ex['m1_rf_kuat3']??'', '1') ?> Informasi intelijen atau catatan tentang peredaran/transaksi narkotika di dalam Lapas/Rutan</div>
                    <div><?= cb($ex['m1_rf_kuat4']??'', '1') ?> Keterlibatan jaringan terorganisir (pengendali, bandar, kurir jaringan)</div>
                </td>
                <td width="50%" style="vertical-align:top;">
                    <b>Pendukung</b> (dua atau lebih berarti rujuk ke TPP):<br><br>
                    <div style="margin-bottom:5px;"><?= cb($ex['m1_rf_dukung1']??'', '1') ?> Ditemukan timbangan, plastik klip dalam jumlah banyak, atau alat pengemas</div>
                    <div style="margin-bottom:5px;"><?= cb($ex['m1_rf_dukung2']??'', '1') ?> Uang dalam jumlah tidak wajar atau catatan transaksi</div>
                    <div style="margin-bottom:5px;"><?= cb($ex['m1_rf_dukung3']??'', '1') ?> Daftar gejala pada tabel/ poin 1.3 berjumlah 0–1 padahal perkara peredaran</div>
                    <div><?= cb($ex['m1_rf_dukung4']??'', '1') ?> Pernah dipidana perkara narkotika dengan peran peredaran</div>
                </td>
            </tr>
        </table>

        <div class="sub-title">1.6 Kesimpulan Modul 1 (pilih satu)</div>
        <table class="tbl-border">
            <tr><th width="20%">Kategori</th><th width="80%">Kriteria dan tindak lanjut</th></tr>
            <tr>
                <td><b><?= (strpos($data['kategori_modul1']??'', 'Kategori 1') !== false) ? '&#9745;' : '&#9744;' ?> Kategori 1</b><br>Pengguna terverifikasi</td>
                <td>Gejala 1.3 &ge; 4, atau TAT BNN/ ASSIST menyatakan pengguna dengan rekomendasi rehabilitasi; cerita konsisten atau sebagian; tanpa red flag. <b>Lanjut.</b></td>
            </tr>
            <tr>
                <td><b><?= (strpos($data['kategori_modul1']??'', 'Kategori 2') !== false) ? '&#9745;' : '&#9744;' ?> Kategori 2</b><br>Pengguna, indikasi sedang atau belum lengkap</td>
                <td>Gejala 1.3 berjumlah 2–3, atau Kategori 1 tetapi cerita tidak konsisten dengan dokumen; tanpa red flag. <b>Lanjut dengan syarat</b> (minta TAT BNN/ ASSIST atau tes urin); beri tanda pada B.5.</td>
            </tr>
            <tr>
                <td><b><?= (strpos($data['kategori_modul1']??'', 'Kategori 3') !== false) ? '&#9745;' : '&#9744;' ?> Kategori 3</b><br>Tidak terverifikasi</td>
                <td>Gejala 1.3 berjumlah 0–1 dan tidak ada TAT yang menyatakan pengguna, atau TAT menyatakan bukan pengguna. <b>Tidak layak</b>; dapat asesmen ulang bila ada data baru.</td>
            </tr>
            <tr>
                <td><b><?= (strpos($data['kategori_modul1']??'', 'Kategori 4') !== false) ? '&#9745;' : '&#9744;' ?> Kategori 4</b><br>Indikasi pengedar</td>
                <td>Ada red flag kuat atau dua red flag pendukung. <b>Rujuk ke TPP.</b> Bila terindikasi pengedar setelah klarifikasi: <b>tidak ikut</b> dan kembali ke UPT asal.</td>
            </tr>
            <tr>
                <td colspan="2">Catatan asesor: <?= uline($ex['m1_catatan']??'') ?></td>
            </tr>
        </table>
        <?php endif; ?>
        
        <div class="page-break"></div>
        <div class="title" style="font-size:13pt; margin-top:0;">MODUL 2. KESIAPAN FISIK DAN MENTAL (DIISI TENAGA KESEHATAN)</div>
        <div style="background:#FBF1D6; padding: 10px; font-size: 9pt; border: 1px solid #8C8C8C; margin-bottom: 20px;">
            <b>Kerahasiaan.</b> Bagian 2.1 disimpan pada rekam medis tertutup UPT. Hanya Bagian 2.2 (kesimpulan kemampuan bekerja, tanpa diagnosis) yang diserahkan ke asesor dan dibaca TPP.
        </div>

        <div class="sub-title">2.1 Pemeriksaan (rekam medis tertutup)</div>
        <table class="tbl-border tbl-identitas">
            <tr>
                <td width="30%">Tanda vital dan antropometri</td>
                <td>
                    TD <?= uline($data['td'], '50px') ?> &nbsp; Nadi <?= uline($data['nadi'], '40px') ?> &nbsp; Suhu <?= uline($data['suhu'], '40px') ?> &nbsp; BB <?= uline($data['bb'], '40px') ?> &nbsp; TB <?= uline($data['tb'], '40px') ?> &nbsp; IMT <?= uline($ex['m2_imt']??'', '40px') ?>
                </td>
            </tr>
            <tr>
                <td>Skrining TB (gejala dan pemeriksaan sesuai prosedur)</td>
                <td>
                    <?= cb($ex['m2_tb']??'', 'aman') ?> Tidak ada gejala &nbsp; 
                    <?= cb($ex['m2_tb']??'', 'tunda') ?> Perlu pemeriksaan lanjut &nbsp; 
                    <?= cb($ex['m2_tb']??'', 'bersyarat') ?> TB dalam pengobatan
                </td>
            </tr>
            <tr>
                <td>Skrining HIV dan hepatitis (sesuai prosedur dan persetujuan)</td>
                <td>
                    <?= cb($ex['m2_hiv']??'', 'aman') ?> Tidak diperiksa &nbsp; 
                    <?= cb($ex['m2_hiv']??'', ['bersyarat', 'tunda']) ?> Dilakukan, hasil di rekam medis
                </td>
            </tr>
            <tr>
                <td>Penyakit kronis atau keterbatasan gerak</td>
                <td>
                    <?= cb($ex['m2_kronis']??'', 'aman') ?> Tidak ada &nbsp; 
                    <?= cb($ex['m2_kronis']??'', 'bersyarat') ?> Ada, terkontrol &nbsp; 
                    <?= cb($ex['m2_kronis']??'', 'tunda') ?> Ada, tidak terkontrol
                </td>
            </tr>
            <tr>
                <td>Tanda intoksikasi atau putus zat saat ini</td>
                <td>
                    <?= cb($ex['m2_intoksikasi']??'', 'aman') ?> Tidak ada &nbsp; 
                    <?= cb($ex['m2_intoksikasi']??'', 'tunda') ?> Ada
                </td>
            </tr>
            <tr>
                <td>Skrining kesehatan jiwa SRQ-20 (batas sesuai pedoman Kemenkes; umumnya &ge; 6) (optional/ jika ada)</td>
                <td>
                    Skor: <?= uline($ex['m2_srq']??'', '50px') ?> &nbsp; 
                    <?= cb($ex['m2_srq_batas']??'', 'bawah') ?> Di bawah batas &nbsp; 
                    <?= cb($ex['m2_srq_batas']??'', 'atas') ?> Di atas batas
                </td>
            </tr>
            <tr>
                <td>Riwayat gangguan jiwa berat</td>
                <td>
                    <?= cb($ex['m2_jiwa']??'', 'aman') ?> Tidak ada &nbsp; 
                    <?= cb($ex['m2_jiwa']??'', 'bersyarat') ?> Ada, terkontrol &nbsp; 
                    <?= cb($ex['m2_jiwa']??'', 'tunda') ?> Ada, tidak terkontrol
                </td>
            </tr>
        </table>

        <div class="sub-title">2.2 Kesimpulan kemampuan bekerja (diserahkan ke asesor)</div>
        <table class="tbl-border">
            <tr><th width="20%">Kesimpulan</th><th width="70%">Kriteria</th><th width="10%">Poin</th></tr>
            <tr>
                <td><b><?= cb($data['kesimpulan_modul2'], 'layak') ?> LAYAK</b></td>
                <td>Tidak ada kondisi yang menghalangi kerja fisik ringan sampai sedang di luar ruangan; skrining jiwa di bawah batas.</td>
                <td class="text-center">9</td>
            </tr>
            <tr>
                <td><b><?= cb($data['kesimpulan_modul2'], 'layak_bersyarat') ?> LAYAK BERSYARAT</b></td>
                <td>Ada kondisi terkontrol atau perlu tindak lanjut selama program (misalnya pengobatan rutin, kontrol berkala, evaluasi psikolog), namun aman bekerja.<br>Syarat: <?= uline($ex['m2_syarat']??'') ?></td>
                <td class="text-center">5</td>
            </tr>
            <tr>
                <td><b><?= cb($data['kesimpulan_modul2'], 'tunda') ?> TUNDA</b></td>
                <td>Ada kondisi yang menurut tenaga kesehatan belum aman untuk bekerja atau dimobilisasi saat ini (misalnya penyakit menular aktif, gangguan jiwa berat tidak terkontrol, penyakit berat yang membatasi kerja, intoksikasi atau putus zat akut). Status DITUNDA; dapat diasesmen ulang setelah pulih.</td>
                <td class="text-center">0</td>
            </tr>
            <tr>
                <td colspan="3">
                    Kebutuhan layanan lanjutan (tanpa menyebut diagnosis): <br><br>
                    <?= cb($ex['m2_layanan1']??'', '1') ?> Obat rutin &nbsp; 
                    <?= cb($ex['m2_layanan2']??'', '1') ?> Kontrol berkala &nbsp; 
                    <?= cb($ex['m2_layanan3']??'', '1') ?> Rujuk RS &nbsp; 
                    <?= cb($ex['m2_layanan4']??'', '1') ?> Evaluasi psikolog &nbsp; 
                    <?= cb($ex['m2_layanan5']??'', '1') ?> Stabilisasi/detoksifikasi (BNN)
                </td>
            </tr>
            <tr>
                <td colspan="3" style="border:none; padding-top:15px; background:#fff;">
                    Nama, jabatan, paraf tenaga kesehatan: <?= uline($ex['m2_nakes_nama'] ?? '', '200px') ?> &nbsp;&nbsp;&nbsp; Tanggal: <?= uline(fdate($ex['m2_nakes_tgl'] ?? ''), '100px') ?>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="border:none; padding-top:10px; background:#fff;">
                    <div style="background:#FBF1D6; padding: 10px; font-size: 9pt; border: 1px solid #8C8C8C;">
                        <b>Catatan :</b> Pemeriksaan fisik pada Modul 4D hanya dilakukan bila Modul 2 berstatus Layak atau Layak bersyarat dan atas izin tenaga kesehatan.
                    </div>
                </td>
            </tr>
        </table>

        <div class="page-break"></div>
        <div class="title" style="font-size:13pt; margin-top:0;">MODUL 3. RISIKO DAN STABILITAS (DATA SDP, SKOR MAKS 20)</div>
        <div class="petunjuk">Seluruh butir diambil dari SDP dan register; tidak ada wawancara.</div>
        <table class="tbl-border">
            <tr><th width="5%">No</th><th width="45%">Indikator</th><th width="15%">Sumber</th><th width="35%">Pilihan dan poin</th></tr>
            <tr><td class="text-center">3.1</td><td>Kategori risiko residivisme pada Risiko Residivisme Indonesia (RRI)</td><td class="text-center">SDP</td><td><?= $data['modul3_1'] == 7 ? '&#9745;' : '&#9744;' ?> Rendah (7) &nbsp; <?= $data['modul3_1'] == 5 ? '&#9745;' : '&#9744;' ?> Sedang (5) <br><?= $data['modul3_1'] == 3 ? '&#9745;' : '&#9744;' ?> Tinggi (3) &nbsp; <?= $data['modul3_1'] == 0 ? '&#9745;' : '&#9744;' ?> Sangat Tinggi (0)</td></tr>
            <tr><td class="text-center">3.2</td><td>Hasil screening penempatan (risiko keamanan, keselamatan, stabilitas, pemasyarakatan)</td><td class="text-center">SDP</td><td><?= $data['modul3_2'] == 6 ? '&#9745;' : '&#9744;' ?> Minimum (6) &nbsp; <?= $data['modul3_2'] == 3 ? '&#9745;' : '&#9744;' ?> Medium (3) <br><?= $data['modul3_2'] == 0 ? '&#9745;' : '&#9744;' ?> Maksimum (0) <br><?= $data['modul3_2'] == -99 ? '&#9745;' : '&#9744;' ?> Super maksimum (risiko pelarian/kekerasan sangat tinggi) : Berhenti, Tidak Layak</td></tr>
            <tr><td class="text-center">3.3</td><td>Riwayat kabur atau percobaan kabur</td><td class="text-center">SDP</td><td><?= $data['modul3_3'] == 0 ? '&#9745;' : '&#9744;' ?> Tidak pernah (0) &nbsp; <?= $data['modul3_3'] == -99 ? '&#9745;' : '&#9744;' ?> Pernah : Tidak Layak</td></tr>
            <tr><td class="text-center">3.4</td><td>Catatan kekerasan 12 bulan terakhir</td><td class="text-center">Register</td><td><?= $data['modul3_4'] == 4 ? '&#9745;' : '&#9744;' ?> Tidak ada (4) &nbsp; <?= $data['modul3_4'] == 0 ? '&#9745;' : '&#9744;' ?> Ada (0)</td></tr>
            <tr><td class="text-center">3.5</td><td>Catatan pembinaan 12 bulan terakhir</td><td class="text-center">Register</td><td><?= $data['modul3_5'] == 3 ? '&#9745;' : '&#9744;' ?> Tanpa teguran (3) &nbsp; <?= $data['modul3_5'] == 1 ? '&#9745;' : '&#9744;' ?> Teguran Lisan/Tertulis tanpa register F (1)</td></tr>
            <tr><td colspan="3" style="background:#E8EEE9; font-weight:bold;">Jumlah poin Modul 3</td><td style="font-weight:bold;" class="text-center"><?= ($data['modul3_1']+$data['modul3_2']+$data['modul3_3']+$data['modul3_4']+$data['modul3_5']) ?> dari 20</td></tr>
            <tr>
                <td colspan="4">
                    Kebutuhan kriminogenik tinggi (tidak diskor, untuk rencana pembinaan)<br><br>
                    <?= uline($ex['m3_kriminogenik'] ?? '', '100%') ?>
                </td>
            </tr>
        </table>

        <div class="page-break"></div>
        <div class="title" style="font-size:13pt; margin-top:0;">MODUL 4. MINAT, BAKAT, MOTIVASI, DAN SIKAP KERJA</div>
        
        <div class="sub-title">4A. Minat dan bakat (skor maks 13)</div>
        <div class="petunjuk">0 = tidak tertarik; 1 = biasa saja atau mau mencoba; 2 = sangat tertarik.</div>
        <table class="tbl-border">
            <tr><th width="85%">Klaster / Kegiatan</th><th width="5%">0</th><th width="5%">1</th><th width="5%">2</th></tr>
            <tr><td colspan="4" style="background:#E8EEE9; font-weight:bold;">A. Pertanian (klaster Kementan)</td></tr>
            <tr>
                <td>A1. Mengolah tanah, menanam, dan merawat padi atau tanaman di sawah/ladang</td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_0'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_0'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_0'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>A2. Merawat tanaman pangan, sayur, atau kebun</td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_1'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_1'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_1'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>A3. Mengoperasikan atau merawat alat pertanian (Traktor Tangan, Pompa Air)</td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_2'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_2'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kementan_2'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr><td colspan="4" style="background:#E8EEE9; font-weight:bold;">B. Perikanan dan kelautan (klaster KKP)</td></tr>
            <tr>
                <td>B1. Bekerja di tambak: memberi pakan, mengatur air, memanen</td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_0'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_0'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_0'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>B2. Bekerja sebagai awak kapal ikan dan tinggal di kapal</td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_1'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_1'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_1'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>B3. Mengolah hasil ikan atau garam : membersihkan, mengolah, mengemas.</td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_2'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_2'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_kkp_2'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr><td colspan="4" style="background:#E8EEE9; font-weight:bold;">C. Mekanik dan teknik (non-lahan)</td></tr>
            <tr>
                <td>C1. Memperbaiki mesin, kendaraan, atau peralatan</td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_0'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_0'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_0'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>C2. Pertukangan: bangunan, las, listrik, pipa</td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_1'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_1'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_1'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>C3. Mengoperasikan mesin atau peralatan kerja</td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_2'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_2'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_mekanik_2'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr><td colspan="4" style="background:#E8EEE9; font-weight:bold;">D. Pengolahan dan produksi (non-lahan)</td></tr>
            <tr>
                <td>D1. Memasak atau membuat makanan, roti, atau olahan pangan</td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_0'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_0'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_0'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>D2. Memilah, menimbang, mengemas, atau mengelola stok gudang</td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_1'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_1'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_1'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>D3. Menjahit, membuat kerajinan, atau memproduksi barang</td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_2'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_2'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_produksi_2'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr><td colspan="4" style="background:#E8EEE9; font-weight:bold;">E. Jasa dan administrasi (non-lahan)</td></tr>
            <tr>
                <td>E1. Mencatat dan merapikan data (hasil panen, stok, kehadiran)</td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_0'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_0'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_0'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>E2. Pekerjaan jasa: kebersihan, laundry, cukur rambut, layanan</td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_1'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_1'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_1'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>E3. Belajar komputer atau mengikuti pelatihan kelas dan sertifikasi</td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_2'] ?? '') === '0') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_2'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4a_item_jasa_2'] ?? '') === '2') ? '&#10003;' : '' ?></td>
            </tr>
        </table>
        
        <div class="sub-title mt-10">Rekap minat dan rekomendasi klaster</div>
        <table class="tbl-border text-center">
            <tr><th width="20%"></th><th width="16%">A</th><th width="16%">B</th><th width="16%">C</th><th width="16%">D</th><th width="16%">E</th></tr>
            <tr>
                <td style="background:#E8EEE9; font-weight:bold; text-align:left;">Jumlah skor area (0-6)</td>
                <td class="fw-bold"><?= htmlspecialchars($ex['score_kementan'] ?? '') ?></td>
                <td class="fw-bold"><?= htmlspecialchars($ex['score_kkp'] ?? '') ?></td>
                <td class="fw-bold"><?= htmlspecialchars($ex['score_mekanik'] ?? '') ?></td>
                <td class="fw-bold"><?= htmlspecialchars($ex['score_produksi'] ?? '') ?></td>
                <td class="fw-bold"><?= htmlspecialchars($ex['score_jasa'] ?? '') ?></td>
            </tr>
            <tr><td style="background:#E8EEE9; font-weight:bold; text-align:left;">Klaster</td><td>Kementan</td><td>KKP</td><td>Re-edukasi non-lahan</td><td>Re-edukasi non-lahan</td><td>Re-edukasi non-lahan</td></tr>
        </table>
        <div class="petunjuk">Catatan : Poin minat = skor area tertinggi (0–6). Bila dua area sama tinggi, pilih berdasarkan pengalaman kerja (4A.2), hasil 4D, dan kuota klaster. Bila semua area &le; 2, rekomendasikan re-edukasi non-lahan dan beri tanda.</div>
        
        <table class="tbl-border mt-10">
            <tr>
                <td width="30%"><b>4A.1 Poin minat (skor area tertinggi)</b></td>
                <td><?= htmlspecialchars($ex['m4a_poin_minat'] ?? '') ?> dari 6 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Area terpilih: <?= uline($data['klaster_4a'] ?? '', '30%') ?></td>
            </tr>
            <tr>
                <td><b>4A.2 Pengalaman kerja relevan</b></td>
                <td>
                    <?= cb($ex['m4a_pengalaman'] ?? '', '0') ?> Tidak ada atau tidak relevan (0) &nbsp;&nbsp; <?= cb($ex['m4a_pengalaman'] ?? '', '2') ?> Informal atau kurang dari 1 tahun (2)<br>
                    <?= cb($ex['m4a_pengalaman'] ?? '', '3') ?> 1 tahun atau lebih (3) &nbsp;&nbsp; <?= cb($ex['m4a_pengalaman'] ?? '', '5') ?> Punya sertifikat atau keahlian terbukti, termasuk pelatihan kerja di Lapas (5)<br>
                    Bidang/jenis pekerjaan: <?= uline($ex['m4a_bidang_kerja'] ?? '', '250px') ?>
                </td>
            </tr>
            <tr>
                <td><b>4A.3 Kesediaan</b><br><small>(0 = tidak bersedia, 1 = ragu, 2 = bersedia)</small></td>
                <td>Bersedia belajar keterampilan baru dan menerima bimbingan: &nbsp;&nbsp; <?= cb($ex['m4a_kesediaan'] ?? '', '0') ?> 0 &nbsp;&nbsp; <?= cb($ex['m4a_kesediaan'] ?? '', '1') ?> 1 &nbsp;&nbsp; <?= cb($ex['m4a_kesediaan'] ?? '', '2') ?> 2</td>
            </tr>
            <tr>
                <td style="background:#E8EEE9;"><b>Poin 4A (4A.1 + 4A.2 + 4A.3)</b></td>
                <td style="font-weight:bold;"><?= htmlspecialchars($data['skor_4a']) ?> dari 13</td>
            </tr>
        </table>

        <div class="page-break"></div>
        <div class="sub-title">4B. Motivasi dan kesiapan berubah (skor maks 25)</div>
        <div class="petunjuk" style="font-style:normal;">
            <b>Petunjuk Pengisian :</b><br>
            <?php if($jalur === 'reedukasi'): ?>
            Bacakan setiap pernyataan. Jelaskan bahwa tidak ada jawaban benar atau salah dan bahwa jawaban jujur lebih berguna daripada jawaban yang dianggap baik.<br>
            Untuk <b>Pintu Re-edukasi (Non-Narkotika)</b>, butir yang menyangkut narkotika (butir 1, 2, 3, 4, 10, 11) dilewati. Dinilai 6 butir non-narkotika (butir 5, 6, 7, 8, 9, 12).<br><br>
            <b>Cara Skoring :</b>
            <ul style="margin-top:2px; margin-bottom:5px; padding-left:20px;">
                <li>Poin 5, 6, 7, 9, dan 12 skor : 1 = sangat tidak setuju, 2 = tidak setuju, 3 = setuju, 4 = sangat setuju.</li>
                <li>Poin 8 skor : 1 = sangat setuju; 2 = setuju; 3 = tidak setuju, 4 = sangat tidak setuju. (R)</li>
            </ul>
            <?php else: ?>
            Bacakan setiap pernyataan. Jelaskan bahwa tidak ada jawaban benar atau salah dan bahwa jawaban jujur lebih berguna daripada jawaban yang dianggap baik.<br>
            Butir pernyataan pada tabel dibawah ini terdiri atas : butir 1&ndash;4 = motivasi pulih; 5&ndash;8 = motivasi bekerja; 9&ndash;12 = harapan dan kesiapan berubah<br><br>
            <b>Cara Skoring :</b>
            <ul style="margin-top:2px; margin-bottom:5px; padding-left:20px;">
                <li>Poin 1, 2, 3, 5, 6, 7, 9, 10, 11 dan 12 skor : 1 = sangat tidak setuju, 2 = tidak setuju, 3 = setuju, 4 = sangat setuju.</li>
                <li>Poin 4 dan 8 skor : 1 = sangat setuju; 2 = setuju; 3 = tidak setuju, 4 = sangat tidak setuju.</li>
            </ul>
            <?php endif; ?>
        </div>
        <table class="tbl-border mt-10">
            <tr><th width="80%">Pernyataan</th><th width="5%">1</th><th width="5%">2</th><th width="5%">3</th><th width="5%">4</th></tr>
            <?php if($jalur !== 'reedukasi'): ?>
            <tr>
                <td>1. Saya ingin berhenti memakai narkotika.</td>
                <td class="text-center"><?= (($ex['m4b_q_0'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_0'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_0'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_0'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>2. Saya sadar bahwa pemakaian narkotika telah merugikan diri saya dan keluarga.</td>
                <td class="text-center"><?= (($ex['m4b_q_1'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_1'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_1'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_1'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>3. Saya bersedia mengikuti terapi dan tes urin sampai program selesai.</td>
                <td class="text-center"><?= (($ex['m4b_q_2'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_2'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_2'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_2'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>4. Saya merasa tidak perlu berubah; masalah saya bukan karena narkotika. (R)</td>
                <td class="text-center"><?= (($ex['m4b_q_3'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_3'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_3'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_3'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>5. Saya mau bekerja keras setiap hari, termasuk pekerjaan berat di luar ruangan.</td>
                <td class="text-center"><?= (($ex['m4b_q_4'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_4'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_4'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_4'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>6. Saya mau belajar keterampilan baru walaupun awalnya sulit.</td>
                <td class="text-center"><?= (($ex['m4b_q_5'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_5'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_5'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_5'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>7. Saya bersedia mengikuti aturan dan jadwal kerja walaupun tidak sesuai kemauan saya.</td>
                <td class="text-center"><?= (($ex['m4b_q_6'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_6'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_6'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_6'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>8. Saya ikut program ini terutama agar cepat keluar dari Lapas, bukan untuk belajar. (R)</td>
                <td class="text-center"><?= (($ex['m4b_q_7'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_7'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_7'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_7'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>9. Saya punya rencana yang jelas tentang apa yang akan saya kerjakan setelah bebas.</td>
                <td class="text-center"><?= (($ex['m4b_q_8'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_8'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_8'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_8'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <?php if($jalur !== 'reedukasi'): ?>
            <tr>
                <td>10. Saya tahu hal-hal yang membuat saya ingin memakai lagi dan saya punya cara menghindarinya.</td>
                <td class="text-center"><?= (($ex['m4b_q_9'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_9'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_9'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_9'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr>
                <td>11. Saya yakin saya bisa tetap bersih dari narkotika setelah program.</td>
                <td class="text-center"><?= (($ex['m4b_q_10'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_10'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_10'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_10'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>12. Keluarga atau orang terdekat mendukung saya mengikuti program ini.</td>
                <td class="text-center"><?= (($ex['m4b_q_11'] ?? '') === '1') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_11'] ?? '') === '2') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_11'] ?? '') === '3') ? '&#10003;' : '' ?></td>
                <td class="text-center"><?= (($ex['m4b_q_11'] ?? '') === '4') ? '&#10003;' : '' ?></td>
            </tr>
            <tr><td style="background:#E8EEE9; font-weight:bold; text-align:right;">Poin 4B (Konversi)</td><td colspan="4" style="font-weight:bold;" class="text-center"><?= htmlspecialchars($data['skor_4b']) ?> dari 25</td></tr>
        </table>

        <div style="font-size:9pt; margin-top:10px; margin-bottom:5px;">
            <b>Catatan :</b> Konversikan nilai total pada tabel di atas dengan tabel konversi berikut ini :
        </div>
        <?php if($jalur === 'reedukasi'): ?>
        <table class="tbl-border text-center" style="margin-bottom:15px; width:100%;">
            <tr>
                <td style="background:#E8EEE9; font-weight:bold;">Skor mentah (6 butir)</td>
                <td>6–10</td>
                <td>11–13</td>
                <td>14–16</td>
                <td>17–19</td>
                <td>20–24</td>
            </tr>
            <tr>
                <td style="background:#E8EEE9; font-weight:bold;">Konversi (Poin 4B)</td>
                <td>5</td>
                <td>10</td>
                <td>15</td>
                <td>20</td>
                <td>25</td>
            </tr>
        </table>
        <div style="font-size:9pt; margin-bottom:15px;">
            <b>Skor mentah / Poin 4B:</b> <?= uline($ex['m4b_raw'] ?? '', '50px') ?> (rentang 6–24) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Poin Konversi: <b><?= htmlspecialchars($data['skor_4b']) ?></b> dari 25
        </div>
        <?php else: ?>
        <table class="tbl-border text-center" style="margin-bottom:15px; width:100%;">
            <tr>
                <td style="background:#E8EEE9; font-weight:bold;">Skor mentah (12 butir)</td>
                <td>12–20</td>
                <td>21–26</td>
                <td>27–32</td>
                <td>33–38</td>
                <td>39–48</td>
            </tr>
            <tr>
                <td style="background:#E8EEE9; font-weight:bold;">Konversi (Poin 4B)</td>
                <td>5</td>
                <td>10</td>
                <td>15</td>
                <td>20</td>
                <td>25</td>
            </tr>
        </table>
        <div style="font-size:9pt; margin-bottom:15px;">
            <b>Skor mentah / Poin 4B:</b> <?= uline($ex['m4b_raw'] ?? '', '50px') ?> (rentang 12–48) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Poin Konversi: <b><?= htmlspecialchars($data['skor_4b']) ?></b> dari 25
        </div>
        <?php endif; ?>

        <div style="font-size:9pt; margin-bottom:5px; font-weight:bold;">Pertanyaan terbuka (tidak diskor, catat ringkas)</div>
        <table class="tbl-border" style="margin-bottom:15px;">
            <tr>
                <td width="5%" class="text-center">1.</td>
                <td width="95%">Ceritakan kegiatan kerja atau pembinaan yang paling Anda sukai dan alasannya!<br><?= uline($ex['m4b_t1']??'', '100%') ?></td>
            </tr>
            <?php if($jalur !== 'reedukasi'): ?>
            <tr>
                <td class="text-center">2.</td>
                <td>Apa yang biasanya membuat Anda ingin memakai lagi, dan apa yang Anda lakukan untuk menghindarinya!<br><?= uline($ex['m4b_t2']??'', '100%') ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="text-center"><?= $jalur === 'reedukasi' ? '2.' : '3.' ?></td>
                <td>Apa rencana Anda dalam 6 bulan setelah bebas?<br><?= uline($ex['m4b_t3']??'', '100%') ?></td>
            </tr>
            <tr>
                <td colspan="2">Catatan: <?= uline($ex['m4b_catatan'] ?? '', '100%') ?></td>
            </tr>
        </table>

        <div style="font-size:9pt; margin-bottom:20px;">
            <b>Konsistensi jawaban terbuka dengan skala</b><br><br>
            <?= cb($ex['m4b_konsisten']??'', 'Konsisten') ?> Konsisten &nbsp;&nbsp;&nbsp;&nbsp; 
            <?= cb($ex['m4b_konsisten']??'', 'Ada ketidaksesuaian') ?> Ada ketidaksesuaian: <?= uline($ex['m4b_ketidaksesuaian'] ?? '', '200px') ?> (beri tanda)
        </div>

        <div class="sub-title">4C. Sikap kerja (skor maks 25)</div>
        <div class="petunjuk" style="background:#FBF1D6; padding: 10px; font-style:normal; border: 1px solid #8C8C8C;">
            <b>Petunjuk pengisian :</b>
            <ul style="margin-top:5px; margin-bottom:0; padding-left:20px;">
                <li>Penilaian pada aspek 4C Sikap Kerja ini dilakukan oleh dua penilai, yaitu <b>wali pemasyarakatan (Penilai 1)</b> dan <b>pembina kegiatan kerja atau petugas blok yang mengenal napi (Penilai 2)</b>, masing-masing secara terpisah;</li>
                <li>Nilai berdasarkan perilaku yang benar-benar teramati, bukan kesan umum. Setiap skor wajib disertai satu contoh kejadian nyata pada baris "Bukti". Skor 2 dan 4 dipakai bila perilaku berada di antara dua uraian.</li>
                <li>Skor akhir per dimensi = rata-rata dua penilai, dibulatkan ke bilangan terdekat (0,5 dibulatkan ke atas). Bila dua penilai berbeda 2 poin atau lebih, bahas bersama dan catat alasan.</li>
            </ul>
        </div>
        <table class="tbl-border mt-10">
            <tr>
                <td width="50%">Penilai 1 (wali): <?= uline($ex['m4c_p1_nama']??'', '50%') ?></td>
                <td width="50%">Lama mengamati: <?= uline($ex['m4c_p1_lama']??'', '30px') ?> bulan</td>
            </tr>
            <tr>
                <td>Penilai 2 (pembina kerja/petugas blok): <?= uline($ex['m4c_p2_nama']??'', '50%') ?></td>
                <td>Lama mengamati: <?= uline($ex['m4c_p2_lama']??'', '30px') ?> bulan</td>
            </tr>
        </table>
        
        <table class="tbl-border mt-10" style="table-layout: fixed;">
            <tr><th width="20%">Dimensi</th><th width="20%">Skor 1 (kurang)</th><th width="20%">Skor 3 (cukup)</th><th width="20%">Skor 5 (sangat baik)</th><th width="6%">P1</th><th width="6%">P2</th><th width="8%">Akhir</th></tr>
            <tr>
                <td><b>1. Disiplin dan ketepatan waktu</b></td>
                <td>Sering terlambat atau tidak hadir pada apel, kegiatan kerja, atau pembinaan tanpa alasan; harus sering diingatkan.</td>
                <td>Umumnya hadir tepat waktu; sesekali perlu diingatkan.</td>
                <td>Selalu hadir tepat waktu tanpa diingatkan; menjadi contoh bagi yang lain.</td>
                <td class="text-center"><?= $ex['m4c_d1_p1'] > 0 ? htmlspecialchars($ex['m4c_d1_p1']) : '' ?></td>
                <td class="text-center"><?= $ex['m4c_d1_p2'] > 0 ? htmlspecialchars($ex['m4c_d1_p2']) : '' ?></td>
                <td class="text-center fw-bold"><?= $ex['m4c_d1_akhir'] > 0 ? htmlspecialchars($ex['m4c_d1_akhir']) : '' ?></td>
            </tr>
            <tr>
                <td colspan="7">Bukti perilaku (kejadian nyata): <?= uline($ex['m4c_d1_bukti']??'', '100%') ?></td>
            </tr>
            <tr>
                <td><b>2. Tanggung jawab dan kerapian kerja</b></td>
                <td>Tugas sering tidak selesai atau ditinggalkan; alat dan area kerja tidak dirawat.</td>
                <td>Tugas umumnya selesai dengan hasil memadai; alat dan area kerja cukup terjaga.</td>
                <td>Menyelesaikan tugas tuntas dan rapi, merawat alat, dan berinisiatif mengatasi masalah kecil.</td>
                <td class="text-center"><?= $ex['m4c_d2_p1'] > 0 ? htmlspecialchars($ex['m4c_d2_p1']) : '' ?></td>
                <td class="text-center"><?= $ex['m4c_d2_p2'] > 0 ? htmlspecialchars($ex['m4c_d2_p2']) : '' ?></td>
                <td class="text-center fw-bold"><?= $ex['m4c_d2_akhir'] > 0 ? htmlspecialchars($ex['m4c_d2_akhir']) : '' ?></td>
            </tr>
            <tr>
                <td colspan="7">Bukti perilaku (kejadian nyata): <?= uline($ex['m4c_d2_bukti']??'', '100%') ?></td>
            </tr>
            <tr>
                <td><b>3. Kerja sama dan hubungan dengan orang lain</b></td>
                <td>Sering berselisih, menghindari kerja kelompok, atau mengintimidasi.</td>
                <td>Dapat bekerja dalam kelompok tanpa konflik berarti.</td>
                <td>Aktif membantu dan menengahi; diterima baik oleh petugas dan sesama warga binaan.</td>
                <td class="text-center"><?= $ex['m4c_d3_p1'] > 0 ? htmlspecialchars($ex['m4c_d3_p1']) : '' ?></td>
                <td class="text-center"><?= $ex['m4c_d3_p2'] > 0 ? htmlspecialchars($ex['m4c_d3_p2']) : '' ?></td>
                <td class="text-center fw-bold"><?= $ex['m4c_d3_akhir'] > 0 ? htmlspecialchars($ex['m4c_d3_akhir']) : '' ?></td>
            </tr>
            <tr>
                <td colspan="7">Bukti perilaku (kejadian nyata): <?= uline($ex['m4c_d3_bukti']??'', '100%') ?></td>
            </tr>
            <tr>
                <td><b>4. Ketahanan terhadap tekanan dan pengendalian emosi</b></td>
                <td>Mudah marah atau menyerah saat ditegur atau menghadapi kerja berat; pernah bertindak agresif.</td>
                <td>Umumnya tenang; kadang perlu waktu menenangkan diri, lalu kembali bekerja.</td>
                <td>Tetap tenang dan melanjutkan kerja saat ditegur atau di bawah tekanan.</td>
                <td class="text-center"><?= $ex['m4c_d4_p1'] > 0 ? htmlspecialchars($ex['m4c_d4_p1']) : '' ?></td>
                <td class="text-center"><?= $ex['m4c_d4_p2'] > 0 ? htmlspecialchars($ex['m4c_d4_p2']) : '' ?></td>
                <td class="text-center fw-bold"><?= $ex['m4c_d4_akhir'] > 0 ? htmlspecialchars($ex['m4c_d4_akhir']) : '' ?></td>
            </tr>
            <tr>
                <td colspan="7">Bukti perilaku (kejadian nyata): <?= uline($ex['m4c_d4_bukti']??'', '100%') ?></td>
            </tr>
            <tr>
                <td><b>5. Kepatuhan pada instruksi dan aturan</b></td>
                <td>Sering menolak instruksi atau melanggar aturan; ada catatan teguran.</td>
                <td>Umumnya mengikuti instruksi; pelanggaran kecil jarang terjadi.</td>
                <td>Selalu mengikuti instruksi dan aturan; tanpa catatan teguran.</td>
                <td class="text-center"><?= $ex['m4c_d5_p1'] > 0 ? htmlspecialchars($ex['m4c_d5_p1']) : '' ?></td>
                <td class="text-center"><?= $ex['m4c_d5_p2'] > 0 ? htmlspecialchars($ex['m4c_d5_p2']) : '' ?></td>
                <td class="text-center fw-bold"><?= $ex['m4c_d5_akhir'] > 0 ? htmlspecialchars($ex['m4c_d5_akhir']) : '' ?></td>
            </tr>
            <tr>
                <td colspan="7">Bukti perilaku (kejadian nyata): <?= uline($ex['m4c_d5_bukti']??'', '100%') ?></td>
            </tr>
        </table>
        
        <table class="tbl-border mt-10">
            <tr>
                <td width="40%"><b>Poin 4C (jumlah skor akhir 5 dimensi)</b></td>
                <td width="60%"><?= uline(htmlspecialchars($data['skor_4c']), '40px') ?> dari 25 (rentang 5–25)</td>
            </tr>
            <tr>
                <td><b>Pengamatan terbatas (kurang dari 3 bulan)</b></td>
                <td><?= cb($ex['m4c_terbatas']??'', 'Tidak') ?> Tidak &nbsp;&nbsp; <?= cb($ex['m4c_terbatas']??'', 'Ya') ?> Ya: beri tanda</td>
            </tr>
        </table>

        <div class="sub-title">4D. Kapasitas kerja (skor maks 6)</div>
        <div class="petunjuk" style="background:#FBF1D6; padding: 10px; font-style:normal; border: 1px solid #8C8C8C;">
            <b>Petunjuk Pengisian :</b>
            <p style="margin-top:5px; margin-bottom:0;">Hanya dilakukan bila Modul 2 berstatus Layak atau Layak bersyarat dan atas izin tenaga kesehatan. Gunakan tugas yang sama untuk semua napi di UPT yang sama.</p>
        </div>
        <table class="tbl-border mt-10" style="table-layout: fixed;">
            <tr><th width="8%">No</th><th width="44%">Butir</th><th width="14%">0</th><th width="14%">1</th><th width="14%">2</th><th width="6%">Skor</th></tr>
            <tr>
                <td class="text-center"><b>4D.1</b></td>
                <td>Uji ketahanan fisik singkat di bawah pengawasan: berjalan cepat 10 menit dan memindahkan beban ±10 kg sejauh 10 meter, 3 kali</td>
                <td>Tidak mampu atau berhenti</td>
                <td>Mampu dengan jeda atau keluhan ringan</td>
                <td>Mampu lancar</td>
                <td class="text-center fw-bold"><?= $ex['m4d_1'] !== '' ? htmlspecialchars($ex['m4d_1']) : '' ?></td>
            </tr>
            <tr>
                <td class="text-center"><b>4D.2</b></td>
                <td>Riwayat kegiatan kerja di Lapas/Rutan (bengkel, kebun, dapur, laundry, dan sebagainya), dari catatan pembinaan</td>
                <td>Tidak pernah ikut</td>
                <td>Ikut kurang dari 3 bulan atau tidak rutin</td>
                <td>Rutin 3 bulan atau lebih</td>
                <td class="text-center fw-bold"><?= $ex['m4d_2'] !== '' ? htmlspecialchars($ex['m4d_2']) : '' ?></td>
            </tr>
            <tr>
                <td class="text-center"><b>4D.3</b></td>
                <td>Tugas praktik 10 menit dengan instruksi lisan satu kali (misalnya memilah dan mengemas ±30 bungkus bahan)</td>
                <td>Tidak selesai</td>
                <td>Selesai dengan banyak kesalahan atau perlu dibantu</td>
                <td>Selesai benar dan mandiri</td>
                <td class="text-center fw-bold"><?= $ex['m4d_3'] !== '' ? htmlspecialchars($ex['m4d_3']) : '' ?></td>
            </tr>
        </table>
        <table class="tbl-border mt-10">
            <tr>
                <td width="40%"><b>Poin 4D</b></td>
                <td width="60%"><?= uline(htmlspecialchars($data['skor_4d']), '40px') ?> dari 6</td>
            </tr>
        </table>

        <div class="page-break"></div>
        <div class="title" style="font-size:13pt; margin-top:0;">B.5 REKAP SKOR DAN KEPUTUSAN</div>
        <table class="tbl-border">
            <tr><th width="40%">Komponen</th><th width="20%">Sumber</th><th width="20%">Maks</th><th width="20%">Skor</th></tr>
            <tr><td>Motivasi dan kesiapan berubah</td><td class="text-center">Modul 4B</td><td class="text-center">25</td><td class="text-center"><?= htmlspecialchars($data['skor_4b']) ?></td></tr>
            <tr><td>Sikap kerja</td><td class="text-center">Modul 4C</td><td class="text-center">25</td><td class="text-center"><?= htmlspecialchars($data['skor_4c']) ?></td></tr>
            <?php
            $m3_total_skor = 0;
            foreach(['modul3_1', 'modul3_2', 'modul3_3', 'modul3_4', 'modul3_5'] as $m3k) {
                $v = (int)($data[$m3k] ?? 0);
                if($v > 0) $m3_total_skor += $v;
            }
            ?>
            <tr><td>Risiko dan stabilitas</td><td class="text-center">Modul 3</td><td class="text-center">20</td><td class="text-center"><?= $m3_total_skor ?></td></tr>
            <tr><td>Minat dan bakat</td><td class="text-center">Modul 4A</td><td class="text-center">13</td><td class="text-center"><?= htmlspecialchars($data['skor_4a']) ?></td></tr>
            <tr><td>Kesiapan fisik (Layak 9 / Bersyarat 5 / Tunda 0)</td><td class="text-center">Modul 2</td><td class="text-center">9</td><td class="text-center"><?= htmlspecialchars($data['kesimpulan_modul2'] === 'layak' ? 9 : ($data['kesimpulan_modul2'] === 'layak_bersyarat' ? 5 : 0)) ?></td></tr>
            <tr><td>Kapasitas kerja (OPTIONAL)</td><td class="text-center">Modul 4D</td><td class="text-center">6</td><td class="text-center"><?= htmlspecialchars($data['skor_4d']) ?></td></tr>
            <tr><td colspan="2" style="background:#E8EEE9; text-align:right; font-weight:bold;">JUMLAH</td><td style="background:#E8EEE9;" class="text-center"><b>98</b></td><td style="background:#E8EEE9;" class="text-center"><b><?= htmlspecialchars($data['total_skor']) ?></b></td></tr>
        </table>

        <div style="font-size:9pt; margin-top:10px; margin-bottom:5px;">
            <b>Catatan :</b><br>
            1. Khusus untuk narapidana yang berdasarkan hasil penilaian memiliki skor tinggi, terdapat beberapa catatan yang perlu ditindaklanjuti dengan check list sebagai berikut :
        </div>
        <table class="tbl-border" style="font-size:9pt; margin-bottom:5px;">
            <tr><td><?= ($jalur !== 'reedukasi' && (!empty($ex['catatan_m1_k2']) || strpos($data['kategori_modul1'] ?? '', 'Kategori 2') !== false)) ? '&#9745;' : '&#9744;' ?> Modul 1 Kategori 2 <?= $jalur === 'reedukasi' ? '<span style="color:#666; font-size:8pt;">(Dilewati - Non-Narkotika)</span>' : '' ?></td></tr>
            <tr><td><?= (!empty($ex['catatan_m2_bersyarat']) || ($data['kesimpulan_modul2'] ?? '') === 'layak_bersyarat') ? '&#9745;' : '&#9744;' ?> Modul 2 Layak bersyarat</td></tr>
            <tr><td><?= (!empty($ex['catatan_ketidaksesuaian']) || (($data['skor_4b'] ?? 0) >= 20 && ($data['skor_4c'] ?? 0) <= 12) || ($ex['m4b_konsisten'] ?? '') === 'Ada ketidaksesuaian') ? '&#9745;' : '&#9744;' ?> Ketidaksesuaian : poin 4B &ge; 20 tetapi poin 4C &le; 12 (jawaban mungkin diarahkan), atau ketidaksesuaian pada pertanyaan terbuka 4B</td></tr>
            <tr><td><?= (!empty($ex['catatan_kekerasan']) || ($data['modul3_4'] ?? 4) == 0) ? '&#9745;' : '&#9744;' ?> Catatan kekerasan 12 bulan terakhir (butir 3.4)</td></tr>
            <tr><td><?= (!empty($ex['catatan_sikap_terbatas']) || ($ex['m4c_terbatas'] ?? '') === 'Ya') ? '&#9745;' : '&#9744;' ?> Pengamatan sikap kerja terbatas (4C)</td></tr>
            <tr><td><?= (!empty($ex['catatan_minat_rendah']) || ((float)($ex['m4a_poin_minat'] ?? 0) <= 2 && ($data['skor_4a'] ?? 0) > 0)) ? '&#9745;' : '&#9744;' ?> Semua area minat &le; 2 (4A)</td></tr>
        </table>
        <div style="font-size:8.5pt; font-style:italic; margin-bottom:10px;">
            Jarak ke 2/3 masa pidana kurang dari 6 bulan (butir 0.7) tidak mengubah kategori; catat sebagai bahan pengaturan gelombang.
        </div>

        <div style="font-size:9pt; margin-bottom:5px;">
            2. Penetapan kategori (periksa berurutan dari atas; berhenti pada yang pertama terpenuhi)
        </div>
        <table class="tbl-border" style="font-size:8.5pt;">
            <tr><th width="28%">Kategori</th><th width="72%">Syarat</th></tr>
            <tr>
                <td><b>TIDAK MEMENUHI SYARAT</b></td>
                <td>Berhenti pada Modul 0 (kecuali butir 0.5 yang berstatus Ditunda).</td>
            </tr>
            <tr>
                <td><b>RUJUK KE TPP</b></td>
                <td>Modul 1 Kategori 4. TPP mengklarifikasi; bila terindikasi pengedar : tidak ikut.</td>
            </tr>
            <tr>
                <td><b>DITUNDA</b></td>
                <td>Modul 2 Tunda, atau butir 0.5 menunggu keputusan pimpinan. Asesmen ulang setelah kondisi berubah atau keputusan terbit.</td>
            </tr>
            <tr>
                <td><b>TIDAK LAYAK</b></td>
                <td>Modul 1 Kategori 3; atau riwayat kabur (3.3); atau screening kategori tertinggi (3.2); atau skor di bawah 55. Berhak asesmen ulang pada gelombang berikut.</td>
            </tr>
            <tr>
                <td><b>LAYAK BERSYARAT</b></td>
                <td>Skor 55&ndash;69; atau skor &ge; 70 dengan satu <i>red flag</i> atau lebih.</td>
            </tr>
            <tr>
                <td><b>LAYAK</b></td>
                <td>Skor &ge; 70 dan tanpa <i>red flag</i>.</td>
            </tr>
        </table>
        <div style="font-size:8.5pt; font-style:italic; margin-bottom:10px;">
            Batas skor 70 dan 55 bersifat sementara dan akan dikalibrasi setelah gelombang I. Bila kuota Kanwil lebih kecil daripada jumlah yang Layak, urutkan berdasarkan skor, lalu jarak ke 2/3 masa pidana.
        </div>

        <table class="tbl-border mt-10">
            <tr>
                <td width="22%" style="background:#E8EEE9; vertical-align:middle;"><b>Kategori hasil</b></td>
                <td width="78%">
                    <?= cb($data['kategori_hasil'], ['tidak memenuhi syarat', 'tidak memenuhi syarat (m0)']) ?> Tidak memenuhi syarat &nbsp;&nbsp;
                    <?= cb($data['kategori_hasil'], 'rujuk ke tpp') ?> Rujuk ke TPP &nbsp;&nbsp;
                    <?= cb($data['kategori_hasil'], 'ditunda') ?> Ditunda &nbsp;&nbsp;
                    <?= cb($data['kategori_hasil'], 'tidak layak') ?> Tidak layak &nbsp;&nbsp;
                    <?= cb($data['kategori_hasil'], 'layak bersyarat') ?> Layak bersyarat &nbsp;&nbsp;
                    <?= cb($data['kategori_hasil'], 'layak') ?> Layak
                </td>
            </tr>
            <tr>
                <td style="background:#E8EEE9;"><b>Rekomendasi klaster</b></td>
                <td>
                    <?= cb($data['klaster_4a'], 'Kementan') ?> Kementan &nbsp;&nbsp;
                    <?= cb($data['klaster_4a'], 'KKP') ?> KKP &nbsp;&nbsp;
                    <?= cb($data['klaster_4a'], 'Re-edukasi non-lahan') ?> Re-edukasi non-lahan<br><br>
                    Dasar rekomendasi (area minat, pengalaman, hasil 4D): <?= uline($ex['dasar_rekomendasi'] ?? '', '300px') ?>
                </td>
            </tr>
            <tr>
                <td style="background:#E8EEE9;"><b>Syarat dan tindak lanjut</b></td>
                <td>
                    Medis (tanpa diagnosis): <?= uline($ex['tl_medis'] ?? ($ex['m2_syarat'] ?? ''), '180px') ?> &nbsp;&nbsp;&nbsp; Pembinaan: <?= uline($ex['tl_pembinaan'] ?? '', '180px') ?><br><br>
                    Alasan bila Tidak layak, Ditunda, atau Rujuk: <?= uline($ex['alasan_tl'] ?? ($data['catatan'] ?? ''), '350px') ?>
                </td>
            </tr>
        </table>

        <div style="font-weight:bold; font-size:10pt; margin-top:15px; margin-bottom:5px;">Pengesahan</div>
        <div style="background:#F4F4F4; padding: 8px 10px; font-size: 8.5pt; border: 1px solid #8C8C8C; margin-bottom: 5px;">
            <b>Pernyataan asesor.</b> Saya melaksanakan asesmen ini sesuai pedoman, tidak menerima imbalan dalam bentuk apa pun, dan tidak memiliki hubungan kepentingan dengan warga binaan yang dinilai.
        </div>

        <table class="tbl-border" style="text-align:center;">
            <tr><th width="25%">Asesor</th><th width="25%">Reviu (asesor kedua/supervisor)</th><th width="25%">Wali pemasyarakatan</th><th width="25%">Tenaga kesehatan (Modul 2)</th></tr>
            <tr><td style="height:60px;"></td><td></td><td></td><td></td></tr>
            <tr><td>Nama/tgl: <?= htmlspecialchars($ex['m0_nama_asesor']??'') ?></td><td>Nama/tgl: <?= htmlspecialchars($ex['m0_nama_reviu']??'') ?></td><td>Nama/tgl: <?= htmlspecialchars($ex['m4c_p1_nama']??'') ?></td><td>Nama/tgl: <?= htmlspecialchars($ex['m2_nakes_nama']??'') ?></td></tr>
        </table>

        <table class="tbl-border mt-10">
            <tr>
                <td width="22%" style="background:#E8EEE9; vertical-align:middle;"><b>Keputusan sidang TPP UPT</b></td>
                <td width="78%">
                    <?= cb($data['keputusan_tpp'] ?? $ex['keputusan_tpp'] ?? '', 'Disetujui') ?> Disetujui &nbsp;&nbsp; <?= cb($data['keputusan_tpp'] ?? $ex['keputusan_tpp'] ?? '', 'Tidak disetujui') ?> Tidak disetujui &nbsp;&nbsp; <?= cb($data['keputusan_tpp'] ?? $ex['keputusan_tpp'] ?? '', 'Ditunda') ?> Ditunda<br><br>
                    Alasan (wajib bila berbeda dari kategori instrumen): <?= uline($ex['alasan_tpp'] ?? '', '350px') ?><br><br>
                    Ketua TPP: <?= uline($ex['ketua_tpp']??'', '180px') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Tanggal sidang: <?= uline(fdate($ex['tgl_sidang']??''), '120px') ?>
                </td>
            </tr>
        </table>

    </div>
</body>
</html>

