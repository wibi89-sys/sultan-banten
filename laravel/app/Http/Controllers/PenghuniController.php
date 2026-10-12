<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataPenghuni;
use App\Models\Upt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenghuniController extends Controller
{
    private $klasifikasiKeys = [
        'Pidana Umum'           => 'pidana_umum',
        'Tipikor'               => 'tipikor',
        'Narkoba Bandar'        => 'narkoba_bandar',
        'Narkoba Pengguna'      => 'narkoba_pengguna',
        'Teroris'               => 'teroris',
        'Trafficking'           => 'trafficking',
        'Ilegal Logging'        => 'ilegal_logging',
        'Money Laundering'      => 'money_laundering',
        'Genosida'              => 'genosida',
        'UU ITE'                => 'uu_ite',
        'WNA'                   => 'wna',
        'Sakit Berkepanjangan'  => 'sakit_berkepanjangan',
        'Lansia >70 tahun'      => 'lansia_70_tahun',
        'Pidana Mati'           => 'pidana_mati',
        'Seumur Hidup'          => 'seumur_hidup'
    ];

    private $klasifikasiUmum = ['Pidana Umum'];
    private $klasifikasiKhusus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];
    private $klasifikasiUUITE = ['UU ITE'];
    private $klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('penghuni')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $selectedTab = $request->query('tab', 'all');
        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DataPenghuni::with('upt')->orderByDesc('tanggal')->orderByDesc('id');

        if ($tanggalMulai && $tanggalSampai) {
            $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSampai]);
        } elseif ($tanggalMulai) {
            $query->where('tanggal', '>=', $tanggalMulai);
        } elseif ($tanggalSampai) {
            $query->where('tanggal', '<=', $tanggalSampai);
        }

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        $klasifikasiUtama = array_merge($this->klasifikasiUmum, $this->klasifikasiKhusus, $this->klasifikasiUUITE);
        $allKlasifikasi = array_merge($klasifikasiUtama, $this->klasifikasiLainnya);

        if ($selectedTab === 'utama') {
            $query->whereIn('klasifikasi_pidana', $klasifikasiUtama);
        } elseif ($selectedTab === 'lainnya') {
            $query->whereIn('klasifikasi_pidana', $this->klasifikasiLainnya);
        } elseif ($selectedTab !== 'all' && in_array($selectedTab, $allKlasifikasi)) {
            $query->where('klasifikasi_pidana', $selectedTab);
        }

        $penghuniList = $query->paginate(50)->withQueryString();
        $klasifikasiKeys = $this->klasifikasiKeys;
        $klasifikasiUmum = $this->klasifikasiUmum;
        $klasifikasiKhusus = $this->klasifikasiKhusus;
        $klasifikasiUUITE = $this->klasifikasiUUITE;
        $klasifikasiLainnya = $this->klasifikasiLainnya;

        $timeRestriction = getTimeRestriction();
        $timeCheck = isInputTimeAllowed();
        $canInput = $timeCheck['allowed'];
        $timeRestrictionMessage = $timeCheck['message'];

        return view('penghuni.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'selectedTab',
            'tanggalMulai',
            'tanggalSampai',
            'penghuniList',
            'klasifikasiKeys',
            'klasifikasiUmum',
            'klasifikasiKhusus',
            'klasifikasiUUITE',
            'klasifikasiLainnya',
            'klasifikasiUtama',
            'timeRestriction',
            'canInput',
            'timeRestrictionMessage'
        ));
    }

    public function getPreviousData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('penghuni')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $uptId = (int) $request->query('upt_id');
        $tanggal = $request->query('tanggal', date('Y-m-d'));

        if (!$uptId) {
            return response()->json(['success' => false, 'has_data' => false, 'message' => 'Pilih UPT terlebih dahulu.']);
        }

        // Input data tidak ada hari libur: ambil tepat data hari kemarin (H-1)
        $previousDate = date('Y-m-d', strtotime($tanggal . ' - 1 day'));

        $records = DataPenghuni::where('upt_id', $uptId)
            ->whereDate('tanggal', $previousDate)
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => true,
                'has_data' => false,
                'previous_date' => $previousDate,
                'formatted_yesterday' => formatIndonesianDate($previousDate),
                'message' => 'Data hari kemarin (' . formatIndonesianDate($previousDate) . ') belum diinput untuk UPT ini. Silakan input data hari kemarin terlebih dahulu atau input manual dari 0.'
            ]);
        }

        $data = [];
        foreach ($records as $r) {
            $key = $this->klasifikasiKeys[$r->klasifikasi_pidana] ?? null;
            if ($key) {
                $data[$key] = [
                    'tahanan_dl' => (int) $r->tahanan_dewasa_laki,
                    'tahanan_dp' => (int) $r->tahanan_dewasa_perempuan,
                    'tahanan_al' => (int) $r->tahanan_anak_laki,
                    'tahanan_ap' => (int) $r->tahanan_anak_perempuan,
                    'narapidana_dl' => (int) $r->narapidana_dewasa_laki,
                    'narapidana_dp' => (int) $r->narapidana_dewasa_perempuan,
                    'narapidana_al' => (int) $r->narapidana_anak_laki,
                    'narapidana_ap' => (int) $r->narapidana_anak_perempuan,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'has_data' => count($data) > 0,
            'previous_date' => $previousDate,
            'formatted_date' => formatIndonesianDate($previousDate),
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('penghuni')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (!$timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $request->validate([
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => 'required|date',
        ]);

        $uptId = (int) $request->input('upt_id');
        $tanggal = $request->input('tanggal');

        // Cegah duplikasi
        if (DataPenghuni::where('upt_id', $uptId)->where('tanggal', $tanggal)->exists()) {
            return back()->with('error', 'Data penghuni untuk UPT ini pada tanggal ' . formatDate($tanggal) . ' sudah ada. Silakan gunakan tombol edit.');
        }

        DB::beginTransaction();
        try {
            $inserted = 0;
            foreach ($this->klasifikasiKeys as $klasifikasi => $key) {
                DataPenghuni::create([
                    'upt_id' => $uptId,
                    'klasifikasi_pidana' => $klasifikasi,
                    'tahanan_dewasa_laki' => (int) $request->input('tahanan_dl_' . $key, 0),
                    'tahanan_dewasa_perempuan' => (int) $request->input('tahanan_dp_' . $key, 0),
                    'tahanan_anak_laki' => (int) $request->input('tahanan_al_' . $key, 0),
                    'tahanan_anak_perempuan' => (int) $request->input('tahanan_ap_' . $key, 0),
                    'narapidana_dewasa_laki' => (int) $request->input('narapidana_dl_' . $key, 0),
                    'narapidana_dewasa_perempuan' => (int) $request->input('narapidana_dp_' . $key, 0),
                    'narapidana_anak_laki' => (int) $request->input('narapidana_al_' . $key, 0),
                    'narapidana_anak_perempuan' => (int) $request->input('narapidana_ap_' . $key, 0),
                    'tanggal' => $tanggal,
                    'keterangan' => $request->input('keterangan', ''),
                ]);
                $inserted++;
            }

            DB::commit();

            $upt = Upt::find($uptId);
            ActivityLog::log('tambah', 'penghuni', "Menambahkan {$inserted} data penghuni untuk UPT {$upt->nama_upt} tanggal {$tanggal}", ['upt_id' => $uptId, 'tanggal' => $tanggal]);

            return back()->with('success', "Berhasil menyimpan {$inserted} data penghuni.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('penghuni')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (!$timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $penghuni = DataPenghuni::findOrFail($id);
        $request->validate([
            'klasifikasi_pidana' => 'required',
            'tanggal' => 'required|date',
        ]);

        $penghuni->update([
            'klasifikasi_pidana' => $request->input('klasifikasi_pidana'),
            'tahanan_dewasa_laki' => (int) $request->input('tahanan_dewasa_laki', 0),
            'tahanan_dewasa_perempuan' => (int) $request->input('tahanan_dewasa_perempuan', 0),
            'tahanan_anak_laki' => (int) $request->input('tahanan_anak_laki', 0),
            'tahanan_anak_perempuan' => (int) $request->input('tahanan_anak_perempuan', 0),
            'narapidana_dewasa_laki' => (int) $request->input('narapidana_dewasa_laki', 0),
            'narapidana_dewasa_perempuan' => (int) $request->input('narapidana_dewasa_perempuan', 0),
            'narapidana_anak_laki' => (int) $request->input('narapidana_anak_laki', 0),
            'narapidana_anak_perempuan' => (int) $request->input('narapidana_anak_perempuan', 0),
            'tanggal' => $request->input('tanggal'),
            'keterangan' => $request->input('keterangan', ''),
        ]);

        ActivityLog::log('edit', 'penghuni', "Mengubah data penghuni ID {$id} ({$penghuni->klasifikasi_pidana})", $penghuni->toArray());

        return back()->with('success', 'Data penghuni berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('penghuni')) {
            abort(403);
        }

        $penghuni = DataPenghuni::findOrFail($id);
        $old = $penghuni->toArray();
        $penghuni->delete();

        ActivityLog::log('hapus', 'penghuni', "Menghapus data penghuni ID {$id}", $old);

        return back()->with('success', 'Data penghuni berhasil dihapus.');
    }
}
