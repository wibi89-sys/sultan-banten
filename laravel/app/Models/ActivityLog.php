<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'aksi',
        'modul',
        'deskripsi',
        'detail',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'detail' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(string $aksi, string $modul, string $deskripsi, $detail = null): static
    {
        $user = auth()->user();

        return static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->nama_lengkap ?? $user?->username ?? 'Sistem',
            'aksi' => $aksi,
            'modul' => $modul,
            'deskripsi' => $deskripsi,
            'detail' => is_array($detail) ? json_encode($detail) : $detail,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
