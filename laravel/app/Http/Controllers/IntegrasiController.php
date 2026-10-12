<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataIntegrasi;
use App\Models\Upt;
use Illuminate\Http\Request;

class IntegrasiController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('integrasi')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DataIntegrasi::with('upt')->orderByDesc('tanggal')->orderByDesc('id');

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

        $integrasiList = $query->paginate(50)->withQueryString();

        $timeRestriction = getTimeRestriction();
        $timeCheck = isInputTimeAllowed();
        $canInput = $timeCheck['allowed'];
        $timeRestrictionMessage = $timeCheck['message'];

        return view('integrasi.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'tanggalMulai',
            'tanggalSampai',
            'integrasiList',
            'timeRestriction',
            'canInput',
            'timeRestrictionMessage'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('integrasi')) {
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

        if (DataIntegrasi::where('upt_id', $uptId)->where('tanggal', $tanggal)->exists()) {
            return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal ' . formatDate($tanggal) . ' sudah ada. Silakan edit data yang ada.');
        }

        $data = DataIntegrasi::create([
            'upt_id' => $uptId,
            'usulan_pb' => (int) $request->input('usulan_pb', 0),
            'usulan_cb' => (int) $request->input('usulan_cb', 0),
            'usulan_cmb' => (int) $request->input('usulan_cmb', 0),
            'usulan_cmk' => (int) $request->input('usulan_cmk', 0),
            'usulan_asimilasi' => (int) $request->input('usulan_asimilasi', 0),
            'sk_terbit_pb' => (int) $request->input('sk_terbit_pb', 0),
            'sk_terbit_cb' => (int) $request->input('sk_terbit_cb', 0),
            'sk_terbit_cmb' => (int) $request->input('sk_terbit_cmb', 0),
            'sk_terbit_cmk' => (int) $request->input('sk_terbit_cmk', 0),
            'sk_terbit_asimilasi' => (int) $request->input('sk_terbit_asimilasi', 0),
            'tanggal' => $tanggal,
            'keterangan' => $request->input('keterangan', ''),
        ]);

        $upt = Upt::find($uptId);
        ActivityLog::log('tambah', 'integrasi', "Menambahkan data integrasi UPT {$upt->nama_upt} tanggal {$tanggal}", ['upt_id' => $uptId, 'tanggal' => $tanggal]);

        return back()->with('success', 'Data integrasi berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('integrasi')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (!$timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $data = DataIntegrasi::findOrFail($id);
        $request->validate([
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => 'required|date',
        ]);

        $data->update([
            'upt_id' => (int) $request->input('upt_id'),
            'usulan_pb' => (int) $request->input('usulan_pb', 0),
            'usulan_cb' => (int) $request->input('usulan_cb', 0),
            'usulan_cmb' => (int) $request->input('usulan_cmb', 0),
            'usulan_cmk' => (int) $request->input('usulan_cmk', 0),
            'usulan_asimilasi' => (int) $request->input('usulan_asimilasi', 0),
            'sk_terbit_pb' => (int) $request->input('sk_terbit_pb', 0),
            'sk_terbit_cb' => (int) $request->input('sk_terbit_cb', 0),
            'sk_terbit_cmb' => (int) $request->input('sk_terbit_cmb', 0),
            'sk_terbit_cmk' => (int) $request->input('sk_terbit_cmk', 0),
            'sk_terbit_asimilasi' => (int) $request->input('sk_terbit_asimilasi', 0),
            'tanggal' => $request->input('tanggal'),
            'keterangan' => $request->input('keterangan', ''),
        ]);

        ActivityLog::log('edit', 'integrasi', "Mengubah data integrasi ID {$id}", $data->toArray());

        return back()->with('success', 'Data integrasi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('integrasi')) {
            abort(403);
        }

        $data = DataIntegrasi::findOrFail($id);
        $old = $data->toArray();
        $data->delete();

        ActivityLog::log('hapus', 'integrasi', "Menghapus data integrasi ID {$id}", $old);

        return back()->with('success', 'Data integrasi berhasil dihapus.');
    }
}
