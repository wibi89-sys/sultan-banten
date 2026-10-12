<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'isi',
        'kategori',
        'tipe',
        'upt_id',
        'gambar',
        'aktif',
        'tampilkan_marquee',
        'tanggal_mulai',
        'tanggal_selesai',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'tampilkan_marquee' => 'boolean',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
