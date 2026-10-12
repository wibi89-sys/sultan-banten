<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pengumuman;
use App\Models\Upt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('pengumuman')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $query = Pengumuman::with('upt')->orderByDesc('id');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'aktif') $query->where('aktif', 1);
            if ($status === 'nonaktif') $query->where('aktif', 0);
        }

        $pengumumanList = $query->paginate(20)->withQueryString();

        return view('pengumuman.index', compact('pengumumanList', 'uptList'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('pengumuman')) {
            abort(403);
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'kategori' => 'required|string',
        ]);

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $uploadPath = public_path('uploads/pengumuman');
            if (!File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            $file = $request->file('gambar');
            $ext = $file->getClientOriginalExtension();
            $filename = 'pengumuman_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($uploadPath, $filename);
            $gambar = "uploads/pengumuman/{$filename}";
        }

        $p = Pengumuman::create([
            'judul' => $request->input('judul'),
            'isi' => $request->input('isi'),
            'kategori' => $request->input('kategori'),
            'tipe' => $request->input('tipe', 'Global'),
            'upt_id' => $request->input('tipe') === 'UPT' && $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
            'gambar' => $gambar,
            'aktif' => $request->has('aktif'),
            'tampilkan_marquee' => $request->has('tampilkan_marquee'),
            'tanggal_mulai' => $request->input('tanggal_mulai') ?: null,
            'tanggal_selesai' => $request->input('tanggal_selesai') ?: null,
            'created_by' => $user->id,
        ]);

        ActivityLog::log('tambah', 'pengumuman', "Menambahkan pengumuman baru: {$p->judul}", $p->toArray());

        return back()->with('success', 'Pengumuman berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('pengumuman')) {
            abort(403);
        }

        $p = Pengumuman::findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'kategori' => 'required|string',
        ]);

        $gambar = $p->gambar;
        if ($request->has('delete_gambar')) {
            if ($gambar && File::exists(public_path($gambar))) {
                File::delete(public_path($gambar));
            }
            $gambar = null;
        }

        if ($request->hasFile('gambar')) {
            $uploadPath = public_path('uploads/pengumuman');
            if (!File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            $file = $request->file('gambar');
            $ext = $file->getClientOriginalExtension();
            $filename = 'pengumuman_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($uploadPath, $filename);

            if ($gambar && File::exists(public_path($gambar))) {
                File::delete(public_path($gambar));
            }
            $gambar = "uploads/pengumuman/{$filename}";
        }

        $p->update([
            'judul' => $request->input('judul'),
            'isi' => $request->input('isi'),
            'kategori' => $request->input('kategori'),
            'tipe' => $request->input('tipe', 'Global'),
            'upt_id' => $request->input('tipe') === 'UPT' && $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
            'gambar' => $gambar,
            'aktif' => $request->has('aktif'),
            'tampilkan_marquee' => $request->has('tampilkan_marquee'),
            'tanggal_mulai' => $request->input('tanggal_mulai') ?: null,
            'tanggal_selesai' => $request->input('tanggal_selesai') ?: null,
        ]);

        ActivityLog::log('edit', 'pengumuman', "Mengubah pengumuman: {$p->judul}", $p->toArray());

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('pengumuman')) {
            abort(403);
        }

        $p = Pengumuman::findOrFail($id);
        if ($p->gambar && File::exists(public_path($p->gambar))) {
            File::delete(public_path($p->gambar));
        }
        $old = $p->toArray();
        $p->delete();

        ActivityLog::log('hapus', 'pengumuman', "Menghapus pengumuman: {$p->judul}", $old);

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }
}
