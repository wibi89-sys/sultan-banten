<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instansi extends Model
{
    use HasFactory;

    protected $table = 'instansi';

    protected $fillable = [
        'nama_instansi',
        'alamat',
        'telepon',
        'email',
        'website',
        'batas_jam_aktif',
        'jam_mulai',
        'jam_selesai',
        'logo',
        'running_text',
        'welcome_banner',
        'favicon',
        'kop_image',
        'latitude',
        'longitude',
        'logo_kop',
        'ttd_image',
        'stempel_image',
    ];

    public static function getFirst()
    {
        return static::first() ?? new static([
            'nama_instansi' => 'KANTOR WILAYAH KEMENTERIAN HUKUM DAN HAM BANTEN',
            'jam_mulai' => '06:00',
            'jam_selesai' => '08:00',
        ]);
    }
}
