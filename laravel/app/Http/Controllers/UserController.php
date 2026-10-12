<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private $permissionFields = [
        'akses_dashboard',
        'akses_penghuni',
        'akses_overstaying',
        'akses_integrasi',
        'akses_bebas_harian',
        'akses_upt',
        'akses_user',
        'akses_instansi',
        'akses_laporan',
        'akses_pengumuman',
        'akses_wa_settings',
        'akses_wa_schedule',
        'akses_activity_log',
    ];

    public function index()
    {
        $user = auth()->user();
        if (!$user->hasAccess('user')) {
            abort(403);
        }

        $users = User::with('upt')->orderBy('username')->get();
        $uptList = Upt::orderBy('nama_upt')->get();

        return view('users.index', compact('users', 'uptList'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('user')) {
            abort(403);
        }

        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,operator,viewer',
        ]);

        $data = [
            'username' => $request->input('username'),
            'nama_lengkap' => $request->input('nama_lengkap'),
            'password' => Hash::make($request->input('password')),
            'no_wa' => $request->input('no_wa'),
            'role' => $request->input('role'),
            'upt_id' => $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
        ];

        foreach ($this->permissionFields as $field) {
            $data[$field] = $request->has($field);
        }

        $newUser = User::create($data);
        ActivityLog::log('tambah', 'user', "Menambahkan user baru: {$newUser->nama_lengkap} ({$newUser->username})", $newUser->toArray());

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('user')) {
            abort(403);
        }

        $targetUser = User::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'role' => 'required|in:admin,operator,viewer',
        ]);

        $data = [
            'nama_lengkap' => $request->input('nama_lengkap'),
            'no_wa' => $request->input('no_wa'),
            'role' => $request->input('role'),
            'upt_id' => $request->filled('upt_id') ? (int) $request->input('upt_id') : null,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $data['password'] = Hash::make($request->input('password'));
        }

        foreach ($this->permissionFields as $field) {
            $data[$field] = $request->has($field);
        }

        $targetUser->update($data);
        ActivityLog::log('edit', 'user', "Mengubah data user: {$targetUser->nama_lengkap}", $targetUser->toArray());

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('user')) {
            abort(403);
        }

        if ($id == $user->id) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $targetUser = User::findOrFail($id);
        $old = $targetUser->toArray();
        $targetUser->delete();

        ActivityLog::log('hapus', 'user', "Menghapus user: {$targetUser->username}", $old);

        return back()->with('success', 'User berhasil dihapus.');
    }
}
