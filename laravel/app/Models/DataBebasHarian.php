<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataBebasHarian extends Model
{
    use HasFactory;

    protected $table = 'data_bebas_harian';

    protected $fillable = [
        'upt_id',
        'tanggal',
        'klasifikasi_pidana',
        'bebas_murni',
        'pb',
        'cb',
        'cmb',
        'asimilasi',
        'bdh',
        'lainnya',
        'ket_lainnya',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'bebas_murni' => 'integer',
            'pb' => 'integer',
            'cb' => 'integer',
            'cmb' => 'integer',
            'asimilasi' => 'integer',
            'bdh' => 'integer',
            'lainnya' => 'integer',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function getTotalBebasAttribute(): int
    {
        return $this->bebas_murni + $this->pb + $this->cb + $this->cmb + $this->asimilasi + $this->bdh + $this->lainnya;
    }
}
