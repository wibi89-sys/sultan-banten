<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Instansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InstansiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->hasAccess('instansi')) {
            abort(403);
        }

        $instansi = Instansi::getFirst();

        return view('instansi.index', compact('instansi'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('instansi')) {
            abort(403);
        }

        $instansi = Instansi::first();
        if (!$instansi) {
            $instansi = new Instansi();
        }

        $instansi->nama_instansi = $request->input('nama_instansi');
        $instansi->alamat = $request->input('alamat');
        $instansi->telepon = $request->input('telepon');
        $instansi->email = $request->input('email');
        $instansi->website = $request->input('website');
        $instansi->running_text = $request->input('running_text');

        // Batas Jam Input
        $instansi->batas_jam_aktif = $request->has('batas_jam_aktif');
        $instansi->jam_mulai = $request->input('jam_mulai', '06:00:00');
        $instansi->jam_selesai = $request->input('jam_selesai', '08:00:00');

        // File uploads
        $imageFields = ['logo', 'favicon', 'welcome_banner', 'kop_image', 'logo_kop', 'ttd_image', 'stempel_image'];
        $uploadPath = public_path('uploads');
        if (!File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        foreach ($imageFields as $field) {
            // Delete request
            if ($request->has("delete_{$field}")) {
                if ($instansi->{$field} && File::exists(public_path($instansi->{$field}))) {
                    File::delete(public_path($instansi->{$field}));
                }
                $instansi->{$field} = null;
            }

            // Upload request
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension();
                $filename = "{$field}_" . time() . '_' . uniqid() . '.' . $ext;
                $file->move($uploadPath, $filename);

                // Remove previous file
                if ($instansi->{$field} && File::exists(public_path($instansi->{$field}))) {
                    File::delete(public_path($instansi->{$field}));
                }
                $instansi->{$field} = "uploads/{$filename}";
            }
        }

        $instansi->save();
        ActivityLog::log('edit', 'instansi', 'Memperbarui data dan pengaturan instansi', $instansi->toArray());

        return back()->with('success', 'Pengaturan instansi berhasil diperbarui.');
    }
}
