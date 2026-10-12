<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataBebasHarian;
use App\Models\Instansi;
use App\Models\Upt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BebasHarianController extends Controller
{
    private $klasifikasiList = [
        'Pidana Umum'       => 'pidana_umum',
        'Tipikor'           => 'tipikor',
        'Narkoba Bandar'    => 'narkoba_bandar',
        'Narkoba Pengguna'  => 'narkoba_pengguna',
        'Teroris'           => 'teroris',
        'Trafficking'       => 'trafficking',
        'Ilegal Logging'    => 'ilegal_logging',
        'Money Laundering'  => 'money_laundering',
        'Genosida'          => 'genosida',
        'UU ITE'            => 'uu_ite'
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('bebas_harian')) {
            abort(403, 'Akses ditolak.');
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DB::table('data_bebas_harian as dbh')
            ->join('upt as u', 'dbh.upt_id', '=', 'u.id')
            ->select(
                'dbh.upt_id',
                'dbh.tanggal',
                'u.nama_upt',
                DB::raw('SUM(dbh.bebas_murni) as total_bm'),
                DB::raw('SUM(dbh.pb) as total_pb'),
                DB::raw('SUM(dbh.cb) as total_cb'),
                DB::raw('SUM(dbh.cmb) as total_cmb'),
                DB::raw('SUM(dbh.asimilasi) as total_asimilasi'),
                DB::raw('SUM(dbh.bdh) as total_bdh'),
                DB::raw('SUM(dbh.lainnya) as total_lainnya'),
                DB::raw('SUM(dbh.bebas_murni + dbh.pb + dbh.cb + dbh.cmb + dbh.asimilasi + dbh.bdh + dbh.lainnya) as grand_total'),
                DB::raw('MAX(dbh.keterangan) as keterangan'),
                DB::raw('MAX(dbh.created_at) as created_at')
            )
            ->groupBy('dbh.upt_id', 'dbh.tanggal', 'u.nama_upt')
            ->orderByDesc('dbh.tanggal')
            ->orderBy('u.nama_upt');

        if ($tanggalMulai && $tanggalSampai) {
            $query->whereBetween('dbh.tanggal', [$tanggalMulai, $tanggalSampai]);
        } elseif ($tanggalMulai) {
            $query->where('dbh.tanggal', '>=', $tanggalMulai);
        } elseif ($tanggalSampai) {
            $query->where('dbh.tanggal', '<=', $tanggalSampai);
        }

        if ($filterUpt > 0) {
            $query->where('dbh.upt_id', $filterUpt);
        }

        $dataList = $query->get();
        $klasifikasiList = $this->klasifikasiList;

        return view('bebas_harian.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'tanggalMulai',
            'tanggalSampai',
            'dataList',
            'klasifikasiList'
        ));
    }

    public function detail(Request $request)
    {
        $uptId = (int) $request->query('upt_id');
        $tanggal = $request->query('tanggal');

        $details = DataBebasHarian::where('upt_id', $uptId)
            ->where('tanggal', $tanggal)
            ->get();

        return response()->json($details);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('bebas_harian')) {
            abort(403);
        }

        $request->validate([
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => 'required|date',
        ]);

        $uptId = (int) $request->input('upt_id');
        $tanggal = $request->input('tanggal');
        $keteranganGlobal = $request->input('keterangan_global', '');

        // Cegah duplikasi
        if (DataBebasHarian::where('upt_id', $uptId)->where('tanggal', $tanggal)->exists()) {
            return back()->with('error', 'Data Bebas Harian untuk UPT ini pada tanggal tersebut sudah ada. Silakan gunakan tombol edit.');
        }

        DB::beginTransaction();
        try {
            foreach ($this->klasifikasiList as $klasifikasi => $key) {
                DataBebasHarian::create([
                    'upt_id' => $uptId,
                    'tanggal' => $tanggal,
                    'klasifikasi_pidana' => $klasifikasi,
                    'bebas_murni' => (int) $request->input('bebas_murni_' . $key, 0),
                    'pb' => (int) $request->input('pb_' . $key, 0),
                    'cb' => (int) $request->input('cb_' . $key, 0),
                    'cmb' => (int) $request->input('cmb_' . $key, 0),
                    'asimilasi' => (int) $request->input('asimilasi_' . $key, 0),
                    'bdh' => (int) $request->input('bdh_' . $key, 0),
                    'lainnya' => (int) $request->input('lainnya_' . $key, 0),
                    'ket_lainnya' => $request->input('ket_lainnya_' . $key, ''),
                    'keterangan' => $keteranganGlobal,
                ]);
            }

            DB::commit();

            $upt = Upt::find($uptId);
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->nama_lengkap ?? $user->username,
                'aksi' => 'tambah',
                'modul' => 'bebas_harian',
                'deskripsi' => "Menambahkan data bebas harian UPT {$upt->nama_upt} tanggal {$tanggal}",
                'data_lama' => null,
                'data_baru' => json_encode(['upt_id' => $uptId, 'tanggal' => $tanggal]),
            ]);

            return back()->with('success', 'Data bebas harian berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('bebas_harian')) {
            abort(403);
        }

        $request->validate([
            'edit_upt_id' => 'required|exists:upt,id',
            'edit_tanggal' => 'required|date',
        ]);

        $uptId = (int) $request->input('edit_upt_id');
        $tanggal = $request->input('edit_tanggal');
        $keteranganGlobal = $request->input('edit_keterangan_global', '');

        DB::beginTransaction();
        try {
            // Delete and re-insert for consistency
            DataBebasHarian::where('upt_id', $uptId)->where('tanggal', $tanggal)->delete();

            foreach ($this->klasifikasiList as $klasifikasi => $key) {
                DataBebasHarian::create([
                    'upt_id' => $uptId,
                    'tanggal' => $tanggal,
                    'klasifikasi_pidana' => $klasifikasi,
                    'bebas_murni' => (int) $request->input('edit_bebas_murni_' . $key, 0),
                    'pb' => (int) $request->input('edit_pb_' . $key, 0),
                    'cb' => (int) $request->input('edit_cb_' . $key, 0),
                    'cmb' => (int) $request->input('edit_cmb_' . $key, 0),
                    'asimilasi' => (int) $request->input('edit_asimilasi_' . $key, 0),
                    'bdh' => (int) $request->input('edit_bdh_' . $key, 0),
                    'lainnya' => (int) $request->input('edit_lainnya_' . $key, 0),
                    'ket_lainnya' => $request->input('edit_ket_lainnya_' . $key, ''),
                    'keterangan' => $keteranganGlobal,
                ]);
            }

            DB::commit();

            $upt = Upt::find($uptId);
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->nama_lengkap ?? $user->username,
                'aksi' => 'edit',
                'modul' => 'bebas_harian',
                'deskripsi' => "Memperbarui data bebas harian UPT {$upt->nama_upt} tanggal {$tanggal}",
                'data_lama' => null,
                'data_baru' => json_encode(['upt_id' => $uptId, 'tanggal' => $tanggal]),
            ]);

            return back()->with('success', 'Data bebas harian berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('bebas_harian')) {
            abort(403);
        }

        $uptId = (int) $request->input('del_upt_id');
        $tanggal = $request->input('del_tanggal');

        try {
            DataBebasHarian::where('upt_id', $uptId)->where('tanggal', $tanggal)->delete();

            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->nama_lengkap ?? $user->username,
                'aksi' => 'hapus',
                'modul' => 'bebas_harian',
                'deskripsi' => "Menghapus data bebas harian UPT ID {$uptId} tanggal {$tanggal}",
                'data_lama' => json_encode(['upt_id' => $uptId, 'tanggal' => $tanggal]),
                'data_baru' => null,
            ]);

            return back()->with('success', 'Data bebas harian berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
