<?php

namespace App\Services;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\Instansi;
use App\Models\Upt;
use Illuminate\Support\Facades\DB;

class PdfReportService
{
    public static function generate(string $reportType, ?int $uptId, string $tanggalMulai, string $tanggalAkhir, array $signData = [])
    {
        $tcpdfPath = app_path('Libraries/tcpdf/tcpdf.php');
        if (!file_exists($tcpdfPath)) {
            $tcpdfPath = base_path('../tcpdf/tcpdf.php');
        }
        require_once $tcpdfPath;

        $instansi = Instansi::getFirst();
        $user = auth()->user();

        $reportTitles = [
            'penghuni' => 'LAPORAN DATA PENGHUNI & OVERKAPASITAS',
            'overstaying' => 'LAPORAN OVERSTAYING TAHANAN',
            'integrasi' => 'LAPORAN DATA INTEGRASI',
            'bebas_harian' => 'LAPORAN PENGELUARAN / BEBAS HARIAN',
            'rekapitulasi' => 'LAPORAN REKAPITULASI'
        ];

        $orientation = in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian']) ? 'P' : 'L';

        // Instantiate TCPDF
        $pdf = new class($orientation, 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {
            public $instansiModel = null;
            public $reportType = 'penghuni';

            public function Header() {}

            public function Footer() {
                $this->SetY(-20);
                $y = $this->GetY();
                $qrText = "✅ Dokumen Valid\ndicetak melalui :\nSULTAN BANTEN @Ditjenpas Banten";
                $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0,0,0], 'bgcolor' => false];
                $this->write2DBarcode($qrText, 'QRCODE,L', 15, $y, 15, 15, $style, 'N');

                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 7);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
                $this->SetTextColor(0, 0, 0);
            }
        };

        $pdf->instansiModel = $instansi;
        $pdf->reportType = $reportType;
        $pdf->SetCreator('Sultan Banten');
        $pdf->SetAuthor($user->nama_lengkap ?? 'Admin');
        $pdf->SetTitle($reportTitles[$reportType] ?? 'Laporan');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        // Header / Kop
        if (in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian'])) {
            $logoKop = null;
            if (!empty($instansi?->logo_kop) && file_exists(public_path($instansi->logo_kop))) {
                $logoKop = public_path($instansi->logo_kop);
            } elseif (!empty($instansi?->logo) && file_exists(public_path($instansi->logo))) {
                $logoKop = public_path($instansi->logo);
            }

            if ($logoKop) {
                $pdf->Image($logoKop, 15, 10, 25);
            }

            $wKop = 155;
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
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(5);

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, $reportTitles[$reportType] ?? 'LAPORAN', 0, 1, 'C');
            $pdf->Cell(0, 6, 'UPT PEMASYARAKATAN KANTOR WILAYAH BANTEN', 0, 1, 'C');
            $pdf->Ln(2);
        } else {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, strtoupper($instansi?->nama_instansi ?? 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN'), 0, 1, 'C');
            if (!empty($instansi?->alamat)) {
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(0, 5, $instansi->alamat, 0, 1, 'C');
            }
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, $reportTitles[$reportType] ?? 'Laporan', 0, 1, 'C');
            $pdf->Ln(2);
        }

        // Fetch Data & Draw Table
        self::renderReportContent($pdf, $reportType, $uptId, $tanggalMulai, $tanggalAkhir, $instansi, $signData);

        $filename = "Laporan_{$reportType}_" . date('Ymd_His') . ".pdf";
        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private static function renderReportContent($pdf, $reportType, $uptId, $tanggalMulai, $tanggalAkhir, $instansi, $signData)
    {
        // Date String
        $hariTanggalStr = 'Semua Periode';
        if (!empty($tanggalMulai) && !empty($tanggalAkhir)) {
            if ($tanggalMulai == $tanggalAkhir) {
                $hariTanggalStr = formatIndonesianDate($tanggalMulai, true);
            } else {
                $hariTanggalStr = formatIndonesianDate($tanggalMulai, false) . ' s/d ' . formatIndonesianDate($tanggalAkhir, false);
            }
        } elseif (!empty($tanggalMulai)) {
            $hariTanggalStr = 'Mulai ' . formatIndonesianDate($tanggalMulai, false);
        } elseif (!empty($tanggalAkhir)) {
            $hariTanggalStr = 'Sampai ' . formatIndonesianDate($tanggalAkhir, false);
        }

        if ($reportType === 'penghuni') {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Hari/Tanggal :', 0, 1, 'L');
            $pdf->Cell(0, 5, $hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            $widths = [10, 65, 18, 18, 20, 20, 20, 19];
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[0], 8, 'No', 1, 0, 'C', true);
            $pdf->Cell($widths[1], 8, 'Unit Pelaksana Teknis', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 8, 'Kapasitas', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 8, 'Tahanan', 1, 0, 'C', true);
            $pdf->Cell($widths[4], 8, 'Narapidana', 1, 0, 'C', true);
            $pdf->Cell($widths[5], 8, 'Total', 1, 0, 'C', true);

            $x = $pdf->GetX(); $y = $pdf->GetY();
            $pdf->MultiCell($widths[6], 8, "Tingkat\nHunian", 1, 'C', true, 0, $x, $y, true, 0, false, true, 8, 'M');
            $pdf->Cell($widths[7], 8, '% Over', 1, 1, 'C', true);

            // Fetch data
            $exclude = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];
            $q = DB::table('upt as u')
                ->join('data_penghuni as dp', 'u.id', '=', 'dp.upt_id')
                ->whereNotIn('dp.klasifikasi_pidana', $exclude);

            if ($uptId) $q->where('u.id', $uptId);
            if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('dp.tanggal', [$tanggalMulai, $tanggalAkhir]);

            $data = $q->select(
                'u.nama_upt', 'u.kapasitas', 'dp.tanggal',
                DB::raw('COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan), 0) as total_tahanan'),
                DB::raw('COALESCE(SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_narapidana'),
                DB::raw('COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni')
            )->groupBy('u.id', 'u.nama_upt', 'u.kapasitas', 'dp.tanggal')
            ->orderBy('u.nama_upt')->orderBy('dp.tanggal')->get();

            $no = 1;
            $gKapasitas = 0; $gTahanan = 0; $gNapi = 0; $gTotal = 0;
            $pdf->SetFont('helvetica', '', 8);

            foreach ($data as $r) {
                $kap = (int) $r->kapasitas;
                $tah = (int) $r->total_tahanan;
                $nap = (int) $r->total_narapidana;
                $tot = (int) $r->total_penghuni;

                $gKapasitas += $kap; $gTahanan += $tah; $gNapi += $nap; $gTotal += $tot;
                $persen = $kap > 0 ? round((($tot - $kap) / $kap) * 100, 1) : 0;
                $tingkat = $kap > 0 ? round(($tot / $kap) * 100, 1) : 0;

                $textUPT = $r->nama_upt;
                $numLines = $pdf->getNumLines($textUPT, $widths[1]);
                $rowH = max(7, $numLines * 3.8);

                $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
                $x = $pdf->GetX(); $y = $pdf->GetY();
                $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
                $pdf->Cell($widths[2], $rowH, number_format($kap), 1, 0, 'C');
                $pdf->Cell($widths[3], $rowH, number_format($tah), 1, 0, 'C');
                $pdf->Cell($widths[4], $rowH, number_format($nap), 1, 0, 'C');
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell($widths[5], $rowH, number_format($tot), 1, 0, 'C');

                $fillColor = $persen <= 0 ? [40, 167, 69] : ($persen <= 20 ? [23, 162, 184] : ($persen <= 50 ? [255, 193, 7] : [220, 53, 69]));
                $textColor = ($persen > 20 && $persen <= 50) ? [0, 0, 0] : [255, 255, 255];

                $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->Cell($widths[6], $rowH, $tingkat . '%', 1, 0, 'C', true);
                $pdf->Cell($widths[7], $rowH, ($persen > 0 ? '+' : '') . $persen . '%', 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('helvetica', '', 8);
            }

            if ($tanggalMulai === $tanggalAkhir && !empty($tanggalMulai)) {
                $totPersen = $gKapasitas > 0 ? round((($gTotal - $gKapasitas) / $gKapasitas) * 100, 1) : 0;
                $totHunian = $gKapasitas > 0 ? round(($gTotal / $gKapasitas) * 100, 1) : 0;

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(220, 220, 220);
                $pdf->Cell($widths[0] + $widths[1], 7, 'TOTAL', 1, 0, 'C', true);
                $pdf->Cell($widths[2], 7, number_format($gKapasitas), 1, 0, 'C', true);
                $pdf->Cell($widths[3], 7, number_format($gTahanan), 1, 0, 'C', true);
                $pdf->Cell($widths[4], 7, number_format($gNapi), 1, 0, 'C', true);
                $pdf->Cell($widths[5], 7, number_format($gTotal), 1, 0, 'C', true);
                $pdf->Cell($widths[6], 7, $totHunian . '%', 1, 0, 'C', true);
                $pdf->Cell($widths[7], 7, ($totPersen > 0 ? '+' : '') . $totPersen . '%', 1, 1, 'C', true);
            }

            self::renderSignature($pdf, $instansi, $signData);

        } elseif ($reportType === 'bebas_harian') {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Hari/Tanggal : ' . $hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            $widths = [8, 42, 12, 12, 12, 12, 12, 12, 14, 14, 40];
            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

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

            $q = DB::table('data_bebas_harian as dbh')->join('upt as u', 'dbh.upt_id', '=', 'u.id');
            if ($uptId) $q->where('dbh.upt_id', $uptId);
            if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('dbh.tanggal', [$tanggalMulai, $tanggalAkhir]);

            $data = $q->select(
                'u.nama_upt',
                DB::raw('SUM(dbh.bebas_murni) as total_bm'),
                DB::raw('SUM(dbh.pb) as total_pb'),
                DB::raw('SUM(dbh.cb) as total_cb'),
                DB::raw('SUM(dbh.cmb) as total_cmb'),
                DB::raw('SUM(dbh.asimilasi) as total_asimilasi'),
                DB::raw('SUM(dbh.bdh) as total_bdh'),
                DB::raw('SUM(dbh.lainnya) as total_lainnya'),
                DB::raw('SUM(dbh.bebas_murni + dbh.pb + dbh.cb + dbh.cmb + dbh.asimilasi + dbh.bdh + dbh.lainnya) as total_bebas'),
                DB::raw('GROUP_CONCAT(DISTINCT NULLIF(dbh.ket_lainnya, "") SEPARATOR "; ") as rincian_lainnya'),
                DB::raw('MAX(dbh.keterangan) as keterangan')
            )->groupBy('u.id', 'u.nama_upt')->orderBy('u.nama_upt')->get();

            $no = 1;
            $tBm = 0; $tPb = 0; $tCb = 0; $tCmb = 0; $tAsim = 0; $tBdh = 0; $tLain = 0; $gTotal = 0;
            $pdf->SetFont('helvetica', '', 7.5);

            foreach ($data as $r) {
                $bm = (int)$r->total_bm; $pb = (int)$r->total_pb; $cb = (int)$r->total_cb;
                $cmb = (int)$r->total_cmb; $asim = (int)$r->total_asimilasi; $bdh = (int)$r->total_bdh;
                $lain = (int)$r->total_lainnya; $sub = (int)$r->total_bebas;

                $tBm += $bm; $tPb += $pb; $tCb += $cb; $tCmb += $cmb; $tAsim += $asim; $tBdh += $bdh; $tLain += $lain; $gTotal += $sub;

                $textUPT = $r->nama_upt;
                $textKet = trim(($r->keterangan ?? '') . (!empty($r->rincian_lainnya) ? ' (' . $r->rincian_lainnya . ')' : ''));
                if (empty($textKet)) $textKet = '-';

                $nUPT = $pdf->getNumLines($textUPT, $widths[1]);
                $nKet = $pdf->getNumLines($textKet, $widths[10]);
                $rowH = max(6, max($nUPT, $nKet) * 4.5);

                $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
                $x = $pdf->GetX(); $y = $pdf->GetY();
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
                $x = $pdf->GetX(); $y = $pdf->GetY();
                $pdf->MultiCell($widths[10], $rowH, $textKet, 1, 'L', false, 1, $x, $y, true, 0, false, true, $rowH, 'M');
            }

            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell($widths[0] + $widths[1], 6, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 6, $tBm, 1, 0, 'C', true);
            $pdf->Cell($widths[3], 6, $tPb, 1, 0, 'C', true);
            $pdf->Cell($widths[4], 6, $tCb, 1, 0, 'C', true);
            $pdf->Cell($widths[5], 6, $tCmb, 1, 0, 'C', true);
            $pdf->Cell($widths[6], 6, $tAsim, 1, 0, 'C', true);
            $pdf->Cell($widths[7], 6, $tBdh, 1, 0, 'C', true);
            $pdf->Cell($widths[8], 6, $tLain, 1, 0, 'C', true);
            $pdf->Cell($widths[9], 6, $gTotal, 1, 0, 'C', true);
            $pdf->Cell($widths[10], 6, '', 1, 1, 'C', true);

            self::renderSignature($pdf, $instansi, $signData);

        } elseif ($reportType === 'overstaying') {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Hari/Tanggal : ' . $hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            $widths = [8, 55, 13, 13, 13, 13, 13, 15, 47];
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[0], 7, 'NO', 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, 'NAMA UPT', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, 'AI', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 7, 'AII', 1, 0, 'C', true);
            $pdf->Cell($widths[4], 7, 'AIII', 1, 0, 'C', true);
            $pdf->Cell($widths[5], 7, 'AIV', 1, 0, 'C', true);
            $pdf->Cell($widths[6], 7, 'AV', 1, 0, 'C', true);
            $pdf->Cell($widths[7], 7, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[8], 7, 'KETERANGAN', 1, 1, 'C', true);

            $q = DB::table('data_overstaying as do')->join('upt as u', 'do.upt_id', '=', 'u.id');
            if ($uptId) $q->where('do.upt_id', $uptId);
            if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('do.tanggal', [$tanggalMulai, $tanggalAkhir]);

            $data = $q->select('do.*', 'u.nama_upt')->orderBy('u.nama_upt')->orderBy('do.tanggal')->get();

            $no = 1;
            $tAI = 0; $tAII = 0; $tAIII = 0; $tAIV = 0; $tAV = 0; $gTotal = 0;
            $pdf->SetFont('helvetica', '', 8);

            foreach ($data as $r) {
                $sub = $r->overstaying_ai + $r->overstaying_aii + $r->overstaying_aiii + $r->overstaying_aiv + $r->overstaying_av;
                $tAI += $r->overstaying_ai; $tAII += $r->overstaying_aii; $tAIII += $r->overstaying_aiii;
                $tAIV += $r->overstaying_aiv; $tAV += $r->overstaying_av; $gTotal += $sub;

                $textUPT = $r->nama_upt;
                $textKet = !empty($r->keterangan) ? $r->keterangan : 'Nihil';

                $nUPT = $pdf->getNumLines($textUPT, $widths[1]);
                $nKet = $pdf->getNumLines($textKet, $widths[8]);
                $rowH = max(6, max($nUPT, $nKet) * 4);

                $pdf->Cell($widths[0], $rowH, $no++, 1, 0, 'C');
                $x = $pdf->GetX(); $y = $pdf->GetY();
                $pdf->MultiCell($widths[1], $rowH, $textUPT, 1, 'L', false, 0, $x, $y, true, 0, false, true, $rowH, 'M');
                $pdf->Cell($widths[2], $rowH, $r->overstaying_ai, 1, 0, 'C');
                $pdf->Cell($widths[3], $rowH, $r->overstaying_aii, 1, 0, 'C');
                $pdf->Cell($widths[4], $rowH, $r->overstaying_aiii, 1, 0, 'C');
                $pdf->Cell($widths[5], $rowH, $r->overstaying_aiv, 1, 0, 'C');
                $pdf->Cell($widths[6], $rowH, $r->overstaying_av, 1, 0, 'C');
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell($widths[7], $rowH, $sub, 1, 0, 'C');
                $pdf->SetFont('helvetica', '', 8);
                $x = $pdf->GetX(); $y = $pdf->GetY();
                $pdf->MultiCell($widths[8], $rowH, $textKet, 1, 'L', false, 1, $x, $y, true, 0, false, true, $rowH, 'M');
            }

            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell($widths[0] + $widths[1], 6, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 6, $tAI, 1, 0, 'C', true);
            $pdf->Cell($widths[3], 6, $tAII, 1, 0, 'C', true);
            $pdf->Cell($widths[4], 6, $tAIII, 1, 0, 'C', true);
            $pdf->Cell($widths[5], 6, $tAIV, 1, 0, 'C', true);
            $pdf->Cell($widths[6], 6, $tAV, 1, 0, 'C', true);
            $pdf->Cell($widths[7], 6, $gTotal, 1, 0, 'C', true);
            $pdf->Cell($widths[8], 6, '', 1, 1, 'C', true);

            self::renderSignature($pdf, $instansi, $signData);

        } else {
            // Landscape reports (Integrasi, Rekapitulasi)
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Periode: ' . $hariTanggalStr, 0, 1, 'L');
            $pdf->Ln(2);

            if ($reportType === 'rekapitulasi') {
                $headers = ['No', 'UPT', 'Kapasitas', 'Tahanan', 'Narapidana', 'Total', '% Kapasitas', 'Overstaying', 'Integrasi'];
                $widths = [10, 85, 24, 24, 24, 24, 24, 24, 24];

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($headers as $i => $h) {
                    $pdf->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
                }
                $pdf->Ln();

                $upts = Upt::orderBy('nama_upt')->get();
                $no = 1;
                $gKap = 0; $gTah = 0; $gNap = 0; $gTot = 0; $gOvs = 0; $gInt = 0;
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetTextColor(0, 0, 0);

                foreach ($upts as $u) {
                    $qP = DataPenghuni::where('upt_id', $u->id)
                        ->whereNotIn('klasifikasi_pidana', ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup']);
                    if ($tanggalMulai && $tanggalAkhir) $qP->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);

                    $tah = (int) (clone $qP)->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan'));
                    $nap = (int) (clone $qP)->sum(DB::raw('narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));
                    $tot = $tah + $nap;
                    $kap = (int) $u->kapasitas;
                    $persen = $kap > 0 ? round(($tot / $kap) * 100, 1) : 0;

                    $qO = DataOverstaying::where('upt_id', $u->id);
                    if ($tanggalMulai && $tanggalAkhir) $qO->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                    $ovs = (int) $qO->sum(DB::raw('overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av'));

                    $qI = DataIntegrasi::where('upt_id', $u->id);
                    if ($tanggalMulai && $tanggalAkhir) $qI->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                    $int = (int) $qI->sum(DB::raw('usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi'));

                    $gKap += $kap; $gTah += $tah; $gNap += $nap; $gTot += $tot; $gOvs += $ovs; $gInt += $int;

                    $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
                    $pdf->Cell($widths[1], 6, $u->nama_upt, 1, 0, 'L');
                    $pdf->Cell($widths[2], 6, number_format($kap), 1, 0, 'C');
                    $pdf->Cell($widths[3], 6, number_format($tah), 1, 0, 'C');
                    $pdf->Cell($widths[4], 6, number_format($nap), 1, 0, 'C');
                    $pdf->Cell($widths[5], 6, number_format($tot), 1, 0, 'C');
                    $pdf->Cell($widths[6], 6, $persen . '%', 1, 0, 'C');
                    $pdf->Cell($widths[7], 6, number_format($ovs), 1, 0, 'C');
                    $pdf->Cell($widths[8], 6, number_format($int), 1, 1, 'C');
                }

                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($widths[0] + $widths[1], 7, 'TOTAL', 1, 0, 'C', true);
                $pdf->Cell($widths[2], 7, number_format($gKap), 1, 0, 'C', true);
                $pdf->Cell($widths[3], 7, number_format($gTah), 1, 0, 'C', true);
                $pdf->Cell($widths[4], 7, number_format($gNapi ?? $gNap), 1, 0, 'C', true);
                $pdf->Cell($widths[5], 7, number_format($gTot), 1, 0, 'C', true);
                $totPersen = $gKap > 0 ? round(($gTot / $gKap) * 100, 1) : 0;
                $pdf->Cell($widths[6], 7, $totPersen . '%', 1, 0, 'C', true);
                $pdf->Cell($widths[7], 7, number_format($gOvs), 1, 0, 'C', true);
                $pdf->Cell($widths[8], 7, number_format($gInt), 1, 1, 'C', true);
            }
        }
    }

    private static function renderSignature($pdf, $instansi, $signData)
    {
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Demikian laporan ini disampaikan, atas perhatiannya diucapkan terima kasih.', 0, 1, 'L');
        $pdf->Ln(4);

        $tempatTtd = $signData['tempat_ttd'] ?? 'Serang';
        $jabatanTtd = $signData['jabatan_ttd'] ?? 'Kepala Bidang Pelayanan dan Pembinaan';
        $namaTtd = $signData['nama_ttd'] ?? 'Surya Dharma';
        $tglTtd = formatIndonesianDate(date('Y-m-d'), false);

        $sigX = 130;
        $pdf->SetX($sigX);
        $pdf->Cell(70, 5, $tempatTtd . ', ' . $tglTtd, 0, 1, 'C');
        $pdf->SetX($sigX);
        $pdf->Cell(70, 5, $jabatanTtd, 0, 1, 'C');
        $pdf->Ln(2);

        $yBeforeSig = $pdf->GetY();
        if (!empty($instansi?->ttd_image) && file_exists(public_path($instansi->ttd_image))) {
            $pdf->Image(public_path($instansi->ttd_image), $sigX + 15, $yBeforeSig - 2, 40, 0);
        }
        if (!empty($instansi?->stempel_image) && file_exists(public_path($instansi->stempel_image))) {
            $pdf->Image(public_path($instansi->stempel_image), $sigX + 5, $yBeforeSig - 5, 35, 0);
        }

        $pdf->Ln(20);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX($sigX);
        $pdf->Cell(70, 5, $namaTtd, 0, 1, 'C');
    }
}
