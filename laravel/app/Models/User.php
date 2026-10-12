<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'foto',
        'no_wa',
        'role',
        'upt_id',
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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'akses_dashboard' => 'boolean',
            'akses_penghuni' => 'boolean',
            'akses_overstaying' => 'boolean',
            'akses_integrasi' => 'boolean',
            'akses_bebas_harian' => 'boolean',
            'akses_upt' => 'boolean',
            'akses_user' => 'boolean',
            'akses_instansi' => 'boolean',
            'akses_laporan' => 'boolean',
            'akses_pengumuman' => 'boolean',
            'akses_wa_settings' => 'boolean',
            'akses_wa_schedule' => 'boolean',
            'akses_activity_log' => 'boolean',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function hasAccess(string $menu): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $property = 'akses_' . $menu;
        return (bool) ($this->{$property} ?? false);
    }

    public function getAvatarHtml(int $size = 32): string
    {
        $name = htmlspecialchars($this->nama_lengkap ?? $this->username ?? 'User');
        $initial = strtoupper(substr($name, 0, 1));
        $fontSize = (int) floor($size * 0.45);

        if (!empty($this->foto) && file_exists(public_path($this->foto))) {
            return '<img src="' . asset($this->foto) . '" alt="' . $name . '" style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        }

        return '<div style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: ' . $fontSize . 'px; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-shadow: 0 1px 2px rgba(0,0,0,0.2);">' . $initial . '</div>';
    }

    public function getUserUptFilter(): ?int
    {
        if ($this->isAdmin()) {
            return null;
        }
        return $this->upt_id ? (int) $this->upt_id : null;
    }
}
