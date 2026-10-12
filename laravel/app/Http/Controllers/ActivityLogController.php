<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    private $aksiLabels = [
        'login'   => ['label' => 'Login',   'icon' => 'fa-sign-in-alt',        'color' => 'success'],
        'logout'  => ['label' => 'Logout',  'icon' => 'fa-sign-out-alt',       'color' => 'secondary'],
        'tambah'  => ['label' => 'Tambah',  'icon' => 'fa-plus',               'color' => 'primary'],
        'edit'    => ['label' => 'Edit',    'icon' => 'fa-edit',               'color' => 'warning'],
        'hapus'   => ['label' => 'Hapus',   'icon' => 'fa-trash',              'color' => 'danger'],
        'view'    => ['label' => 'Lihat',   'icon' => 'fa-eye',                'color' => 'info'],
        'export'  => ['label' => 'Export',  'icon' => 'fa-download',           'color' => 'success'],
        'import'  => ['label' => 'Import',  'icon' => 'fa-upload',             'color' => 'info'],
        'setting' => ['label' => 'Setting', 'icon' => 'fa-cog',                'color' => 'secondary'],
        'error'   => ['label' => 'Error',   'icon' => 'fa-exclamation-triangle','color' => 'danger'],
    ];

    private $modulLabels = [
        'auth'        => 'Autentikasi',
        'penghuni'    => 'Data Penghuni',
        'overstaying' => 'Data Overstaying',
        'integrasi'   => 'Data Integrasi',
        'bebas_harian'=> 'Bebas Harian',
        'upt'         => 'Data UPT',
        'user'        => 'Manajemen User',
        'instansi'    => 'Pengaturan Instansi',
        'pengumuman'  => 'Pengumuman',
        'laporan'     => 'Laporan',
        'profil'      => 'Profil',
        'wa'          => 'WhatsApp',
        'wa_pimpinan' => 'WA Pimpinan',
        'system'      => 'Sistem',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('activity_log')) {
            abort(403, 'Akses ditolak.');
        }

        $filterAksi = $request->query('aksi', '');
        $filterModul = $request->query('modul', '');
        $filterUserId = $request->query('user_id', '');
        $filterTanggalMulai = $request->query('tanggal_mulai', '');
        $filterTanggalAkhir = $request->query('tanggal_akhir', '');
        $filterSearch = $request->query('search', '');

        $query = ActivityLog::orderByDesc('created_at');

        if ($filterAksi) {
            $query->where('aksi', $filterAksi);
        }
        if ($filterModul) {
            $query->where('modul', $filterModul);
        }
        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        }
        if ($filterTanggalMulai) {
            $query->whereDate('created_at', '>=', $filterTanggalMulai);
        }
        if ($filterTanggalAkhir) {
            $query->whereDate('created_at', '<=', $filterTanggalAkhir);
        }
        if ($filterSearch) {
            $query->where(function ($q) use ($filterSearch) {
                $q->where('deskripsi', 'like', "%{$filterSearch}%")
                  ->orWhere('user_name', 'like', "%{$filterSearch}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        // Statistics
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();

        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', $today)->count(),
            'this_week' => ActivityLog::where('created_at', '>=', $startOfWeek)->count(),
            'top_users' => ActivityLog::select('user_name', DB::raw('COUNT(*) as total'))
                ->whereNotNull('user_name')
                ->groupBy('user_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->toArray(),
            'by_action' => ActivityLog::select('aksi', DB::raw('COUNT(*) as total'))
                ->groupBy('aksi')
                ->orderByDesc('total')
                ->get()
                ->toArray(),
        ];

        $allUsers = User::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'username']);

        return view('activity_log.index', [
            'logs' => $logs,
            'stats' => $stats,
            'allUsers' => $allUsers,
            'filterAksi' => $filterAksi,
            'filterModul' => $filterModul,
            'filterUserId' => $filterUserId,
            'filterTanggalMulai' => $filterTanggalMulai,
            'filterTanggalAkhir' => $filterTanggalAkhir,
            'filterSearch' => $filterSearch,
            'aksiLabels' => $this->aksiLabels,
            'modulLabels' => $this->modulLabels,
        ]);
    }
}
