<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataOverstaying;
use App\Models\Upt;
use Illuminate\Http\Request;

class OverstayingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('overstaying')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DataOverstaying::with('upt')->orderByDesc('tanggal')->orderByDesc('id');

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

        $overstayingList = $query->paginate(50)->withQueryString();

        $timeRestriction = getTimeRestriction();
        $timeCheck = isInputTimeAllowed();
        $canInput = $timeCheck['allowed'];
        $timeRestrictionMessage = $timeCheck['message'];

        return view('overstaying.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'tanggalMulai',
            'tanggalSampai',
            'overstayingList',
            'timeRestriction',
            'canInput',
            'timeRestrictionMessage'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('overstaying')) {
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

        if (DataOverstaying::where('upt_id', $uptId)->where('tanggal', $tanggal)->exists()) {
            return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal ' . formatDate($tanggal) . ' sudah ada. Silakan edit data yang ada.');
        }

        $data = DataOverstaying::create([
            'upt_id' => $uptId,
            'tahanan_ai' => (int) $request->input('tahanan_ai', 0),
            'tahanan_aii' => (int) $request->input('tahanan_aii', 0),
            'tahanan_aiii' => (int) $request->input('tahanan_aiii', 0),
            'tahanan_aiv' => (int) $request->input('tahanan_aiv', 0),
            'tahanan_av' => (int) $request->input('tahanan_av', 0),
            'overstaying_ai' => (int) $request->input('overstaying_ai', 0),
            'overstaying_aii' => (int) $request->input('overstaying_aii', 0),
            'overstaying_aiii' => (int) $request->input('overstaying_aiii', 0),
            'overstaying_aiv' => (int) $request->input('overstaying_aiv', 0),
            'overstaying_av' => (int) $request->input('overstaying_av', 0),
            'tanggal' => $tanggal,
            'keterangan' => $request->input('keterangan', ''),
        ]);

        $upt = Upt::find($uptId);
        ActivityLog::log('tambah', 'overstaying', "Menambahkan data overstaying UPT {$upt->nama_upt} tanggal {$tanggal}", ['upt_id' => $uptId, 'tanggal' => $tanggal]);

        return back()->with('success', 'Data overstaying berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('overstaying')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (!$timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $data = DataOverstaying::findOrFail($id);
        $request->validate([
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => 'required|date',
        ]);

        $data->update([
            'upt_id' => (int) $request->input('upt_id'),
            'tahanan_ai' => (int) $request->input('tahanan_ai', 0),
            'tahanan_aii' => (int) $request->input('tahanan_aii', 0),
            'tahanan_aiii' => (int) $request->input('tahanan_aiii', 0),
            'tahanan_aiv' => (int) $request->input('tahanan_aiv', 0),
            'tahanan_av' => (int) $request->input('tahanan_av', 0),
            'overstaying_ai' => (int) $request->input('overstaying_ai', 0),
            'overstaying_aii' => (int) $request->input('overstaying_aii', 0),
            'overstaying_aiii' => (int) $request->input('overstaying_aiii', 0),
            'overstaying_aiv' => (int) $request->input('overstaying_aiv', 0),
            'overstaying_av' => (int) $request->input('overstaying_av', 0),
            'tanggal' => $request->input('tanggal'),
            'keterangan' => $request->input('keterangan', ''),
        ]);

        ActivityLog::log('edit', 'overstaying', "Mengubah data overstaying ID {$id}", $data->toArray());

        return back()->with('success', 'Data overstaying berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('overstaying')) {
            abort(403);
        }

        $data = DataOverstaying::findOrFail($id);
        $old = $data->toArray();
        $data->delete();

        ActivityLog::log('hapus', 'overstaying', "Menghapus data overstaying ID {$id}", $old);

        return back()->with('success', 'Data overstaying berhasil dihapus.');
    }
}
