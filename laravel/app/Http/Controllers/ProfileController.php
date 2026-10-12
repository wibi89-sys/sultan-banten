<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Instansi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $instansi = Instansi::getFirst();

        return view('profile.index', compact('user', 'instansi'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $user->nama_lengkap = $request->input('nama_lengkap');
        if ($request->filled('no_wa')) {
            $user->no_wa = $request->input('no_wa');
        }
        $user->save();

        ActivityLog::log('edit', 'profil', "User {$user->nama_lengkap} memperbarui data profil");

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ], [
            'current_password.required' => 'Password saat ini harus diisi!',
            'new_password.required' => 'Password baru harus diisi!',
            'new_password.min' => 'Password baru minimal 6 karakter!',
            'confirm_password.same' => 'Konfirmasi password tidak cocok!',
        ]);

        /** @var User $user */
        $user = auth()->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->with('error', 'Password saat ini tidak valid!');
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        ActivityLog::log('edit', 'profil', "User {$user->nama_lengkap} mengubah password");

        return back()->with('success', 'Password berhasil diubah!');
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'photo.required' => 'Pilih file foto terlebih dahulu!',
            'photo.image' => 'File harus berupa gambar!',
            'photo.mimes' => 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WebP.',
            'photo.max' => 'Ukuran file maksimal 2MB!',
        ]);

        /** @var User $user */
        $user = auth()->user();

        $uploadDir = public_path('uploads/profile');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension();
        $filename = 'user_' . $user->id . '_' . time() . '.' . $extension;
        $file->move($uploadDir, $filename);
        $relativePath = 'uploads/profile/' . $filename;

        if (!empty($user->foto) && File::exists(public_path($user->foto))) {
            File::delete(public_path($user->foto));
        }

        $user->foto = $relativePath;
        $user->save();

        ActivityLog::log('edit', 'profil', "User {$user->nama_lengkap} mengupload foto profil baru");

        return back()->with('success', 'Foto profil berhasil diupload!');
    }

    public function deletePhoto()
    {
        /** @var User $user */
        $user = auth()->user();

        if (!empty($user->foto) && File::exists(public_path($user->foto))) {
            File::delete(public_path($user->foto));
        }

        $user->foto = null;
        $user->save();

        ActivityLog::log('edit', 'profil', "User {$user->nama_lengkap} menghapus foto profil");

        return back()->with('success', 'Foto profil berhasil dihapus!');
    }
}
