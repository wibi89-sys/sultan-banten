<?php

namespace App\Http\Controllers;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\Instansi;
use App\Models\Pengumuman;
use App\Models\Upt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $instansi = Instansi::getFirst();

        $filterTanggal = $request->query('tanggal', date('Y-m-d'));
        $filterUpt = (int) $request->query('upt_id', 0);
        $uptList = Upt::orderBy('nama_upt')->get();

        $klasifikasiPidum = ['Pidana Umum'];
        $klasifikasiPidsus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];
        $klasifikasiUUITE = ['UU ITE'];
        $klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

        // UPT with Data query
        $uptDataQuery = DB::table('data_penghuni')->where('tanggal', $filterTanggal)
            ->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))
            ->select('upt_id')
            ->union(DB::table('data_overstaying')->where('tanggal', $filterTanggal)->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))->select('upt_id'))
            ->union(DB::table('data_integrasi')->where('tanggal', $filterTanggal)->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))->select('upt_id'))
            ->union(DB::table('data_bebas_harian')->where('tanggal', $filterTanggal)->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))->select('upt_id'))
            ->distinct()
            ->pluck('upt_id');

        $totalUptWithData = $uptDataQuery->count();

        // Total Kapasitas
        $totalKapasitas = $filterUpt > 0
            ? (int) Upt::where('id', $filterUpt)->sum('kapasitas')
            : (int) Upt::whereIn('id', $uptDataQuery)->sum('kapasitas');

        // Penghuni Base Query
        $penghuniBase = DataPenghuni::where('tanggal', $filterTanggal)
            ->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt));

        // Main stats (excluding lainnya)
        $mainPenghuni = DB::table('data_penghuni')
            ->where('tanggal', $filterTanggal)
            ->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))
            ->whereNotIn('klasifikasi_pidana', $klasifikasiLainnya)
            ->selectRaw('
                COALESCE(SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan), 0) as total_tahanan,
                COALESCE(SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan), 0) as total_narapidana
            ')
            ->first();

        $totalTahanan = (int) ($mainPenghuni->total_tahanan ?? 0);
        $totalNarapidana = (int) ($mainPenghuni->total_narapidana ?? 0);
        $totalPenghuni = $totalTahanan + $totalNarapidana;

        $persenOverkapasitas = $totalKapasitas > 0
            ? round((($totalPenghuni - $totalKapasitas) / $totalKapasitas) * 100, 1)
            : 0;

        $tingkatHunian = $totalKapasitas > 0
            ? round(($totalPenghuni / $totalKapasitas) * 100, 1)
            : 0;

        // Overstaying Total
        $totalOverstaying = (int) DataOverstaying::where('tanggal', $filterTanggal)
            ->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))
            ->sum(DB::raw('overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av'));

        // Helper Klasifikasi Total
        $getKlasifikasiTotal = function($list) use ($penghuniBase) {
            return (int) (clone $penghuniBase)
                ->whereIn('klasifikasi_pidana', $list)
                ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));
        };

        $totalPidum = $getKlasifikasiTotal($klasifikasiPidum);
        $totalPidsus = $getKlasifikasiTotal($klasifikasiPidsus);
        $totalUuIte = $getKlasifikasiTotal($klasifikasiUUITE);
        $totalWna = $getKlasifikasiTotal(['WNA']);
        $totalSakit = $getKlasifikasiTotal(['Sakit Berkepanjangan']);
        $totalLansia = $getKlasifikasiTotal(['Lansia >70 tahun']);
        $totalPidanaMati = $getKlasifikasiTotal(['Pidana Mati']);
        $totalSeumurHidup = $getKlasifikasiTotal(['Seumur Hidup']);

        // Bebas Harian Stats
        $bebasRes = DataBebasHarian::where('tanggal', $filterTanggal)
            ->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))
            ->selectRaw('
                COALESCE(SUM(bebas_murni), 0) as bm,
                COALESCE(SUM(pb), 0) as pb,
                COALESCE(SUM(cb), 0) as cb,
                COALESCE(SUM(cmb), 0) as cmb,
                COALESCE(SUM(asimilasi), 0) as asim,
                COALESCE(SUM(bdh), 0) as bdh,
                COALESCE(SUM(lainnya), 0) as lny,
                COALESCE(SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya), 0) as total
            ')
            ->first();

        $stats = [
            'total_upt' => $totalUptWithData,
            'total_kapasitas' => $totalKapasitas,
            'total_tahanan' => $totalTahanan,
            'total_narapidana' => $totalNarapidana,
            'total_penghuni' => $totalPenghuni,
            'persen_overkapasitas' => $persenOverkapasitas,
            'tingkat_hunian' => $tingkatHunian,
            'total_overstaying' => $totalOverstaying,
            'total_pidum' => $totalPidum,
            'total_pidsus' => $totalPidsus,
            'total_uu_ite' => $totalUuIte,
            'total_wna' => $totalWna,
            'total_sakit' => $totalSakit,
            'total_lansia' => $totalLansia,
            'total_pidana_mati' => $totalPidanaMati,
            'total_seumur_hidup' => $totalSeumurHidup,
            'bebas_murni' => (int) ($bebasRes->bm ?? 0),
            'bebas_pb' => (int) ($bebasRes->pb ?? 0),
            'bebas_cb' => (int) ($bebasRes->cb ?? 0),
            'bebas_cmb' => (int) ($bebasRes->cmb ?? 0),
            'bebas_asimilasi' => (int) ($bebasRes->asim ?? 0),
            'bebas_bdh' => (int) ($bebasRes->bdh ?? 0),
            'bebas_lainnya' => (int) ($bebasRes->lny ?? 0),
            'total_bebas' => (int) ($bebasRes->total ?? 0),
        ];

        // Overstaying Stats per Kategori (AI - AV) for Donut Charts
        $kategoriOverstaying = ['ai' => 'AI', 'aii' => 'AII', 'aiii' => 'AIII', 'aiv' => 'AIV', 'av' => 'AV'];
        $overstayingStats = [];
        foreach ($kategoriOverstaying as $key => $label) {
            $overstayingStats[$key] = ['tahanan' => 0, 'overstaying' => 0, 'normal' => 0];
        }

        $overstayingData = DataOverstaying::where('tanggal', $filterTanggal)
            ->when($filterUpt > 0, fn($q) => $q->where('upt_id', $filterUpt))
            ->selectRaw('
                SUM(tahanan_ai) as tahanan_ai, SUM(tahanan_aii) as tahanan_aii, 
                SUM(tahanan_aiii) as tahanan_aiii, SUM(tahanan_aiv) as tahanan_aiv, SUM(tahanan_av) as tahanan_av,
                SUM(overstaying_ai) as overstaying_ai, SUM(overstaying_aii) as overstaying_aii, 
                SUM(overstaying_aiii) as overstaying_aiii, SUM(overstaying_aiv) as overstaying_aiv, SUM(overstaying_av) as overstaying_av
            ')
            ->first();

        if ($overstayingData) {
            foreach ($kategoriOverstaying as $key => $label) {
                $tahanan = (int) ($overstayingData->{'tahanan_' . $key} ?? 0);
                $overstaying = (int) ($overstayingData->{'overstaying_' . $key} ?? 0);
                $overstayingStats[$key] = [
                    'tahanan' => $tahanan,
                    'overstaying' => $overstaying,
                    'normal' => max(0, $tahanan - $overstaying),
                ];
            }
        }

        // Data per UPT for Overkapasitas Table
        $uptDataTable = Upt::select('upt.id', 'upt.nama_upt', 'upt.kapasitas')
            ->when($filterUpt > 0, fn($q) => $q->where('upt.id', $filterUpt))
            ->selectRaw("
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan ELSE 0 END), 0) as total_tahanan,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') 
                    THEN dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as total_narapidana,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup') 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as total_penghuni
            ")
            ->leftJoin('data_penghuni as dp', function($join) use ($filterTanggal) {
                $join->on('upt.id', '=', 'dp.upt_id')->where('dp.tanggal', '=', $filterTanggal);
            })
            ->groupBy('upt.id', 'upt.nama_upt', 'upt.kapasitas')
            ->orderBy('upt.nama_upt')
            ->get();

        // Chart Klasifikasi Bar Data
        $penghuniByKlasifikasi = (clone $penghuniBase)
            ->whereNotIn('klasifikasi_pidana', $klasifikasiLainnya)
            ->select('klasifikasi_pidana')
            ->selectRaw('SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total')
            ->groupBy('klasifikasi_pidana')
            ->orderByDesc('total')
            ->get();

        // UPT Locations for Leaflet Map
        $uptLocations = Upt::select('upt.id', 'upt.nama_upt', 'upt.alamat', 'upt.kapasitas', 'upt.latitude', 'upt.longitude')
            ->whereNotNull('upt.latitude')
            ->whereNotNull('upt.longitude')
            ->when($filterUpt > 0, fn($q) => $q->where('upt.id', $filterUpt))
            ->selectRaw("
                COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni
            ")
            ->leftJoin('data_penghuni as dp', function($join) use ($filterTanggal) {
                $join->on('upt.id', '=', 'dp.upt_id')
                    ->where('dp.tanggal', '=', $filterTanggal)
                    ->whereNotIn('dp.klasifikasi_pidana', ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup']);
            })
            ->groupBy('upt.id', 'upt.nama_upt', 'upt.alamat', 'upt.kapasitas', 'upt.latitude', 'upt.longitude')
            ->get();

        // Data Penghuni Berdasarkan Jenis Kejahatan Table
        $jenisKejahatanData = Upt::select('upt.id', 'upt.nama_upt')
            ->when($filterUpt > 0, fn($q) => $q->where('upt.id', $filterUpt))
            ->selectRaw("
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Pidana Umum' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as pidum,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Tipikor' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as tipikor,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Narkoba Bandar' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as narkoba_bandar,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Narkoba Pengguna' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as narkoba_pengguna,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Teroris' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as teroris,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Trafficking' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as trafficking,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Ilegal Logging' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as ilegal_logging,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Money Laundering' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as money_laundering,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Genosida' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as genosida,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'UU ITE' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as uu_ite,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'WNA' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as wna,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Sakit Berkepanjangan' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as sakit,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Lansia >70 tahun' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as lansia,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Pidana Mati' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as pidana_mati,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Seumur Hidup' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as seumur_hidup,
                COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                             dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total_penghuni
            ")
            ->leftJoin('data_penghuni as dp', function($join) use ($filterTanggal) {
                $join->on('upt.id', '=', 'dp.upt_id')->where('dp.tanggal', '=', $filterTanggal);
            })
            ->groupBy('upt.id', 'upt.nama_upt')
            ->orderBy('upt.nama_upt')
            ->get();

        // Data Pidana Mati & Seumur Hidup per UPT
        $pmshData = Upt::select('upt.id', 'upt.nama_upt')
            ->when($filterUpt > 0, fn($q) => $q->where('upt.id', $filterUpt))
            ->selectRaw("
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Pidana Mati' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as pidana_mati,
                COALESCE(SUM(CASE WHEN dp.klasifikasi_pidana = 'Seumur Hidup' 
                    THEN dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + 
                    dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan ELSE 0 END), 0) as seumur_hidup
            ")
            ->leftJoin('data_penghuni as dp', function($join) use ($filterTanggal) {
                $join->on('upt.id', '=', 'dp.upt_id')->where('dp.tanggal', '=', $filterTanggal);
            })
            ->groupBy('upt.id', 'upt.nama_upt')
            ->orderBy('upt.nama_upt')
            ->get();

        // Data Bebas Harian per UPT
        $bebasHarianUptData = Upt::select('upt.id', 'upt.nama_upt')
            ->when($filterUpt > 0, fn($q) => $q->where('upt.id', $filterUpt))
            ->selectRaw('
                COALESCE(SUM(dbh.bebas_murni), 0) as bebas_murni,
                COALESCE(SUM(dbh.pb), 0) as pb,
                COALESCE(SUM(dbh.cb), 0) as cb,
                COALESCE(SUM(dbh.cmb), 0) as cmb,
                COALESCE(SUM(dbh.asimilasi), 0) as asimilasi,
                COALESCE(SUM(dbh.bdh), 0) as bdh,
                COALESCE(SUM(dbh.lainnya), 0) as lainnya,
                COALESCE(SUM(dbh.bebas_murni + dbh.pb + dbh.cb + dbh.cmb + dbh.asimilasi + dbh.bdh + dbh.lainnya), 0) as total_bebas
            ')
            ->leftJoin('data_bebas_harian as dbh', function($join) use ($filterTanggal) {
                $join->on('upt.id', '=', 'dbh.upt_id')->where('dbh.tanggal', '=', $filterTanggal);
            })
            ->groupBy('upt.id', 'upt.nama_upt')
            ->orderBy('upt.nama_upt')
            ->get();

        // Data Umur dan Gender
        $umurGenderData = (clone $penghuniBase)
            ->whereNotIn('klasifikasi_pidana', $klasifikasiLainnya)
            ->selectRaw('
                COALESCE(SUM(tahanan_dewasa_laki + narapidana_dewasa_laki), 0) as dewasa_laki,
                COALESCE(SUM(tahanan_dewasa_perempuan + narapidana_dewasa_perempuan), 0) as dewasa_perempuan,
                COALESCE(SUM(tahanan_anak_laki + narapidana_anak_laki), 0) as anak_laki,
                COALESCE(SUM(tahanan_anak_perempuan + narapidana_anak_perempuan), 0) as anak_perempuan
            ')
            ->first();

        // Active Pengumuman for Popup (if user logged in)
        $pengumumanPopup = collect();
        if ($user) {
            $today = date('Y-m-d');
            $pQuery = Pengumuman::with('upt')
                ->where('aktif', true)
                ->where(function ($q) use ($today) {
                    $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today);
                });

            if ($user->upt_id) {
                $pQuery->where(function ($q) use ($user) {
                    $q->where('tipe', 'Global')->orWhere('upt_id', $user->upt_id);
                });
            }

            $pengumumanPopup = $pQuery->orderByRaw("
                CASE WHEN kategori = 'Urgent' THEN 1 
                     WHEN kategori = 'Pengumuman' THEN 2 
                     WHEN kategori = 'Berita' THEN 3 
                     ELSE 4 END, created_at DESC
            ")->get();
        }

        // Marquee Announcements
        $today = date('Y-m-d');
        $marqueeAnnouncements = Pengumuman::where('aktif', true)
            ->where('tampilkan_marquee', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today);
            })
            ->when($user && $user->upt_id, function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('tipe', 'Global')->orWhere('upt_id', $user->upt_id);
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $globalRunningText = $instansi->running_text ?? '';

        return view('dashboard', compact(
            'user',
            'instansi',
            'filterTanggal',
            'filterUpt',
            'uptList',
            'stats',
            'overstayingStats',
            'uptDataTable',
            'penghuniByKlasifikasi',
            'uptLocations',
            'jenisKejahatanData',
            'pmshData',
            'bebasHarianUptData',
            'umurGenderData',
            'pengumumanPopup',
            'marqueeAnnouncements',
            'globalRunningText'
        ));
    }
}
