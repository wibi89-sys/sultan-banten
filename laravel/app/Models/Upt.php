<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upt extends Model
{
    use HasFactory;

    protected $table = 'upt';

    protected $fillable = [
        'nama_upt',
        'alamat',
        'kapasitas',
        'latitude',
        'longitude',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'upt_id');
    }

    public function dataPenghuni()
    {
        return $this->hasMany(DataPenghuni::class, 'upt_id');
    }

    public function dataOverstaying()
    {
        return $this->hasMany(DataOverstaying::class, 'upt_id');
    }

    public function dataBebasHarian()
    {
        return $this->hasMany(DataBebasHarian::class, 'upt_id');
    }

    public function dataIntegrasi()
    {
        return $this->hasMany(DataIntegrasi::class, 'upt_id');
    }
}
