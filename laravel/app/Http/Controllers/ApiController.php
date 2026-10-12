<?php

namespace App\Http\Controllers;

use App\Models\DataPenghuni;
use App\Models\Instansi;
use App\Models\Pengumuman;
use App\Models\Upt;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    private $apiKey = 'ditjenpas_banten_secret_key_2026';

    public function stats(Request $request)
    {
        $providedKey = $request->query('api_key') ?: $request->header('X-API-KEY');

        if ($providedKey !== $this->apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Valid API Key required.'
            ], 403);
        }

        $action = $request->query('action', 'stats');
        $uptId = $request->filled('upt_id') ? (int) $request->query('upt_id') : null;
        $tanggal = $request->query('tanggal', date('Y-m-d'));

        try {
            if ($action === 'list_upt') {
                $uptList = Upt::orderBy('nama_upt')->get()->map(function ($upt) {
                    return [
                        'id' => $upt->id,
                        'nama' => $upt->nama_upt
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => $uptList
                ]);
            }

            if ($action === 'by_klasifikasi') {
                $query = DataPenghuni::where('tanggal', $tanggal);
                if ($uptId) $query->where('upt_id', $uptId);

                $data = $query->select(
                    'klasifikasi_pidana',
                    DB::raw('SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan) as total')
                )->groupBy('klasifikasi_pidana')->get();

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            if ($action === 'by_upt') {
                $data = DB::table('upt as u')
                    ->leftJoin('data_penghuni as dp', function ($join) use ($tanggal) {
                        $join->on('u.id', '=', 'dp.upt_id')->where('dp.tanggal', '=', $tanggal);
                    })
                    ->select(
                        'u.id', 'u.nama_upt', 'u.kapasitas',
                        DB::raw('COALESCE(SUM(dp.tahanan_dewasa_laki + dp.tahanan_dewasa_perempuan + dp.tahanan_anak_laki + dp.tahanan_anak_perempuan + dp.narapidana_dewasa_laki + dp.narapidana_dewasa_perempuan + dp.narapidana_anak_laki + dp.narapidana_anak_perempuan), 0) as total')
                    )
                    ->groupBy('u.id', 'u.nama_upt', 'u.kapasitas')
                    ->orderBy('u.nama_upt')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            // Default: stats
            $reportData = WhatsAppService::getComprehensiveReportData($tanggal);
            $announcements = Pengumuman::with('upt')
                ->where('aktif', 1)
                ->where(function ($q) use ($tanggal) {
                    $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $tanggal);
                })
                ->where(function ($q) use ($tanggal) {
                    $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $tanggal);
                })
                ->when($uptId, fn($q) => $q->where(fn($sub) => $sub->where('tipe', 'Global')->orWhere('upt_id', $uptId)))
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $instansi = Instansi::getFirst();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $reportData,
                    'announcements' => $announcements,
                    'instansi' => $instansi,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
