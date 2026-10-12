<?php

namespace App\Http\Controllers;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\Upt;
use App\Services\PdfReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    private $reportTitles = [
        'penghuni' => 'Laporan Data Penghuni',
        'overstaying' => 'Laporan Data Overstaying',
        'integrasi' => 'Laporan Data Integrasi',
        'bebas_harian' => 'Laporan Data Bebas Harian',
        'rekapitulasi' => 'Laporan Rekapitulasi'
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('laporan')) {
            abort(403, 'Akses ditolak.');
        }

        $reportType = $request->query('type', 'penghuni');
        if (!array_key_exists($reportType, $this->reportTitles)) {
            $reportType = 'penghuni';
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();
        $defaultStartDate = date('Y-m-01');
        $defaultEndDate = date('Y-m-t');

        return view('laporan.index', [
            'reportType' => $reportType,
            'reportTitles' => $this->reportTitles,
            'uptList' => $uptList,
            'userUptFilter' => $userUptFilter,
            'defaultStartDate' => $defaultStartDate,
            'defaultEndDate' => $defaultEndDate,
        ]);
    }

    public function pdf(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('laporan')) {
            abort(403, 'Akses ditolak.');
        }

        $reportType = $request->query('type', 'penghuni');
        $userUptFilter = $user->getUserUptFilter();
        $uptId = $userUptFilter ?: ($request->query('upt_id') ? (int) $request->query('upt_id') : null);
        $tanggalMulai = $request->query('tanggal_mulai', date('Y-m-01'));
        $tanggalAkhir = $request->query('tanggal_akhir', date('Y-m-t'));

        $signData = [
            'tempat_ttd' => $request->query('tempat_ttd', 'Serang'),
            'jabatan_ttd' => $request->query('jabatan_ttd', 'Kepala Bidang Pelayanan dan Pembinaan'),
            'nama_ttd' => $request->query('nama_ttd', 'Surya Dharma'),
        ];

        return PdfReportService::generate($reportType, $uptId, $tanggalMulai, $tanggalAkhir, $signData);
    }

    public function excel(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('laporan')) {
            abort(403, 'Akses ditolak.');
        }

        $reportType = $request->query('type', 'penghuni');
        $userUptFilter = $user->getUserUptFilter();
        $uptId = $userUptFilter ?: ($request->query('upt_id') ? (int) $request->query('upt_id') : null);
        $tanggalMulai = $request->query('tanggal_mulai', date('Y-m-01'));
        $tanggalAkhir = $request->query('tanggal_akhir', date('Y-m-t'));

        $filename = "Laporan_{$reportType}_" . date('Ymd_His') . ".xls";

        return response()->stream(function () use ($reportType, $uptId, $tanggalMulai, $tanggalAkhir) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';

            $title = $this->reportTitles[$reportType] ?? 'Laporan';
            $colspan = match ($reportType) {
                'overstaying' => 11,
                'integrasi' => 15,
                'bebas_harian' => 15,
                'rekapitulasi' => 9,
                default => 10,
            };

            echo '<tr><td colspan="' . $colspan . '" align="center" style="font-size: 16px; font-weight: bold;">' . htmlspecialchars($title) . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" align="center">Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' s/d ' . date('d/m/Y', strtotime($tanggalAkhir)) . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '"></td></tr>';

            if ($reportType === 'bebas_harian') {
                $q = DataBebasHarian::with('upt')->orderBy('tanggal', 'desc');
                if ($uptId) $q->where('upt_id', $uptId);
                if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                $data = $q->get();

                echo '<tr bgcolor="#34495e" style="color: white; font-weight: bold;"><td>No</td><td>Tanggal</td><td>UPT</td><td>Klasifikasi Pidana</td><td>Bebas Murni</td><td>PB</td><td>CB</td><td>CMB</td><td>Asimilasi</td><td>BDH</td><td>Lainnya</td><td>Rincian Lainnya</td><td>Total</td><td>Catatan</td><td>Waktu Input</td></tr>';
                $no = 1;
                $totBm = 0; $totPb = 0; $totCb = 0; $totCmb = 0; $totAsim = 0; $totBdh = 0; $totLain = 0; $grandTotal = 0;

                foreach ($data as $r) {
                    $bm = (int)$r->bebas_murni; $pb = (int)$r->pb; $cb = (int)$r->cb; $cmb = (int)$r->cmb;
                    $asim = (int)$r->asimilasi; $bdh = (int)$r->bdh; $lain = (int)$r->lainnya;
                    $sub = $bm + $pb + $cb + $cmb + $asim + $bdh + $lain;

                    $totBm += $bm; $totPb += $pb; $totCb += $cb; $totCmb += $cmb;
                    $totAsim += $asim; $totBdh += $bdh; $totLain += $lain; $grandTotal += $sub;

                    $tglStr = $r->tanggal ? date('d/m/Y', strtotime($r->tanggal)) : '-';
                    $createdStr = $r->created_at ? date('d/m/Y H:i:s', strtotime($r->created_at)) : '-';

                    echo "<tr><td align=\"center\">{$no}</td><td align=\"center\">{$tglStr}</td><td>{$r->upt?->nama_upt}</td><td>{$r->klasifikasi_pidana}</td><td align=\"center\">{$bm}</td><td align=\"center\">{$pb}</td><td align=\"center\">{$cb}</td><td align=\"center\">{$cmb}</td><td align=\"center\">{$asim}</td><td align=\"center\">{$bdh}</td><td align=\"center\">{$lain}</td><td>{$r->ket_lainnya}</td><td align=\"center\"><b>{$sub}</b></td><td>{$r->keterangan}</td><td align=\"center\">{$createdStr}</td></tr>";
                    $no++;
                }

                echo '<tr bgcolor="#34495e" style="color: white; font-weight: bold;"><td colspan="4" align="center">TOTAL</td><td align="center">' . $totBm . '</td><td align="center">' . $totPb . '</td><td align="center">' . $totCb . '</td><td align="center">' . $totCmb . '</td><td align="center">' . $totAsim . '</td><td align="center">' . $totBdh . '</td><td align="center">' . $totLain . '</td><td></td><td align="center">' . $grandTotal . '</td><td colspan="2"></td></tr>';

            } elseif ($reportType === 'overstaying') {
                $q = DataOverstaying::with('upt')->orderBy('tanggal', 'desc');
                if ($uptId) $q->where('upt_id', $uptId);
                if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                $data = $q->get();

                echo '<tr bgcolor="#dcdcdc" style="color: black; font-weight: bold;"><td>No</td><td>Tanggal</td><td>Nama UPT</td><td>AI</td><td>AII</td><td>AIII</td><td>AIV</td><td>AV</td><td>Total Overstay</td><td>Keterangan</td><td>Waktu Input</td></tr>';
                $no = 1;
                $totAI = 0; $totAII = 0; $totAIII = 0; $totAIV = 0; $totAV = 0; $grandTot = 0;

                foreach ($data as $r) {
                    $sub = $r->overstaying_ai + $r->overstaying_aii + $r->overstaying_aiii + $r->overstaying_aiv + $r->overstaying_av;
                    $totAI += $r->overstaying_ai; $totAII += $r->overstaying_aii; $totAIII += $r->overstaying_aiii;
                    $totAIV += $r->overstaying_aiv; $totAV += $r->overstaying_av; $grandTot += $sub;

                    $tglStr = $r->tanggal ? date('d/m/Y', strtotime($r->tanggal)) : '-';
                    $createdStr = $r->created_at ? date('d/m/Y H:i:s', strtotime($r->created_at)) : '-';

                    echo "<tr><td align=\"center\">{$no}</td><td align=\"center\">{$tglStr}</td><td>{$r->upt?->nama_upt}</td><td align=\"center\">{$r->overstaying_ai}</td><td align=\"center\">{$r->overstaying_aii}</td><td align=\"center\">{$r->overstaying_aiii}</td><td align=\"center\">{$r->overstaying_aiv}</td><td align=\"center\">{$r->overstaying_av}</td><td align=\"center\"><b>{$sub}</b></td><td>{$r->keterangan}</td><td align=\"center\">{$createdStr}</td></tr>";
                    $no++;
                }

                echo '<tr bgcolor="#dcdcdc" style="color: black; font-weight: bold;"><td colspan="3" align="center">TOTAL</td><td align="center">' . $totAI . '</td><td align="center">' . $totAII . '</td><td align="center">' . $totAIII . '</td><td align="center">' . $totAIV . '</td><td align="center">' . $totAV . '</td><td align="center">' . $grandTot . '</td><td colspan="2"></td></tr>';

            } elseif ($reportType === 'integrasi') {
                $q = DataIntegrasi::with('upt')->orderBy('tanggal', 'desc');
                if ($uptId) $q->where('upt_id', $uptId);
                if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);
                $data = $q->get();

                echo '<tr bgcolor="#dcdcdc" style="color: black; font-weight: bold;"><td>No</td><td>Tanggal</td><td>Nama UPT</td><td>Usulan PB</td><td>Usulan CB</td><td>Usulan CMB</td><td>Usulan CMK</td><td>Usulan Asim</td><td>Total Usulan</td><td>SK PB</td><td>SK CB</td><td>SK CMB</td><td>SK CMK</td><td>SK Asim</td><td>Total SK</td></tr>';
                $no = 1;
                foreach ($data as $r) {
                    $totU = $r->usulan_pb + $r->usulan_cb + $r->usulan_cmb + $r->usulan_cmk + $r->usulan_asimilasi;
                    $totSK = $r->sk_terbit_pb + $r->sk_terbit_cb + $r->sk_terbit_cmb + $r->sk_terbit_cmk + $r->sk_terbit_asimilasi;
                    $tglStr = $r->tanggal ? date('d/m/Y', strtotime($r->tanggal)) : '-';

                    echo "<tr><td align=\"center\">{$no}</td><td align=\"center\">{$tglStr}</td><td>{$r->upt?->nama_upt}</td><td align=\"center\">{$r->usulan_pb}</td><td align=\"center\">{$r->usulan_cb}</td><td align=\"center\">{$r->usulan_cmb}</td><td align=\"center\">{$r->usulan_cmk}</td><td align=\"center\">{$r->usulan_asimilasi}</td><td align=\"center\"><b>{$totU}</b></td><td align=\"center\">{$r->sk_terbit_pb}</td><td align=\"center\">{$r->sk_terbit_cb}</td><td align=\"center\">{$r->sk_terbit_cmb}</td><td align=\"center\">{$r->sk_terbit_cmk}</td><td align=\"center\">{$r->sk_terbit_asimilasi}</td><td align=\"center\"><b>{$totSK}</b></td></tr>";
                    $no++;
                }

            } elseif ($reportType === 'rekapitulasi') {
                $upts = Upt::orderBy('nama_upt')->get();
                echo '<tr bgcolor="#34495e" style="color: white; font-weight: bold;"><td>No</td><td>UPT</td><td>Kapasitas</td><td>Tahanan</td><td>Narapidana</td><td>Total</td><td>% Kapasitas</td><td>Overstaying</td><td>Integrasi</td></tr>';

                $no = 1;
                $gKap = 0; $gTah = 0; $gNap = 0; $gTot = 0; $gOvs = 0; $gInt = 0;

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

                    echo "<tr><td align=\"center\">{$no}</td><td>{$u->nama_upt}</td><td align=\"center\">{$kap}</td><td align=\"center\">{$tah}</td><td align=\"center\">{$nap}</td><td align=\"center\"><b>{$tot}</b></td><td align=\"center\">{$persen}%</td><td align=\"center\">{$ovs}</td><td align=\"center\">{$int}</td></tr>";
                    $no++;
                }

                $totPersen = $gKap > 0 ? round(($gTot / $gKap) * 100, 1) : 0;
                echo '<tr bgcolor="#34495e" style="color: white; font-weight: bold;"><td colspan="2" align="center">TOTAL</td><td align="center">' . $gKap . '</td><td align="center">' . $gTah . '</td><td align="center">' . $gNap . '</td><td align="center">' . $gTot . '</td><td align="center">' . $totPersen . '%</td><td align="center">' . $gOvs . '</td><td align="center">' . $gInt . '</td></tr>';

            } else {
                // Penghuni
                $q = DB::table('data_penghuni as dp')
                    ->join('upt as u', 'dp.upt_id', '=', 'u.id')
                    ->select('u.nama_upt', 'u.kapasitas', 'dp.tanggal')
                    ->selectRaw('SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan) as total_tahanan')
                    ->selectRaw('SUM(dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan) as total_napi')
                    ->selectRaw('MAX(dp.created_at) as waktu_input')
                    ->whereNotIn('dp.klasifikasi_pidana', ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'])
                    ->groupBy('u.nama_upt', 'u.kapasitas', 'dp.tanggal')
                    ->orderBy('dp.tanggal', 'desc');

                if ($uptId) $q->where('dp.upt_id', $uptId);
                if ($tanggalMulai && $tanggalAkhir) $q->whereBetween('dp.tanggal', [$tanggalMulai, $tanggalAkhir]);
                $data = $q->get();

                echo '<tr bgcolor="#dcdcdc" style="color: black; font-weight: bold;"><td>No</td><td>Tanggal</td><td>Nama UPT</td><td>Kapasitas</td><td>Tahanan</td><td>Narapidana</td><td>Total Penghuni</td><td>Tingkat Hunian</td><td>% Over</td><td>Waktu Input</td></tr>';
                $no = 1;
                $gKap = 0; $gTah = 0; $gNap = 0; $gTot = 0;

                foreach ($data as $r) {
                    $tot = $r->total_tahanan + $r->total_napi;
                    $gKap += $r->kapasitas; $gTah += $r->total_tahanan; $gNap += $r->total_napi; $gTot += $tot;
                    $tingkat = $r->kapasitas > 0 ? round(($tot / $r->kapasitas) * 100, 1) : 0;
                    $persenOver = $r->kapasitas > 0 ? round((($tot - $r->kapasitas) / $r->kapasitas) * 100, 1) : 0;
                    $waktuStr = $r->waktu_input ? date('d/m/Y H:i:s', strtotime($r->waktu_input)) : '-';

                    echo "<tr><td align=\"center\">{$no}</td><td align=\"center\">" . date('d/m/Y', strtotime($r->tanggal)) . "</td><td>{$r->nama_upt}</td><td align=\"center\">{$r->kapasitas}</td><td align=\"center\">{$r->total_tahanan}</td><td align=\"center\">{$r->total_napi}</td><td align=\"center\"><b>{$tot}</b></td><td align=\"center\">{$tingkat}%</td><td align=\"center\">" . ($persenOver > 0 ? '+' : '') . "{$persenOver}%</td><td align=\"center\">{$waktuStr}</td></tr>";
                    $no++;
                }

                $gPersen = $gKap > 0 ? round((($gTot - $gKap) / $gKap) * 100, 1) : 0;
                $gTingkat = $gKap > 0 ? round(($gTot / $gKap) * 100, 1) : 0;
                echo '<tr bgcolor="#dcdcdc" style="color: black; font-weight: bold;"><td colspan="3" align="center">TOTAL</td><td align="center">' . $gKap . '</td><td align="center">' . $gTah . '</td><td align="center">' . $gNap . '</td><td align="center">' . $gTot . '</td><td align="center">' . $gTingkat . '%</td><td align="center">' . ($gPersen > 0 ? '+' : '') . $gPersen . '%</td><td></td></tr>';
            }

            echo '</table></body></html>';
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
