<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataIntegrasi extends Model
{
    use HasFactory;

    protected $table = 'data_integrasi';

    protected $fillable = [
        'upt_id',
        'usulan_pb',
        'usulan_cb',
        'usulan_cmb',
        'usulan_cmk',
        'usulan_asimilasi',
        'sk_terbit_pb',
        'sk_terbit_cb',
        'sk_terbit_cmb',
        'sk_terbit_cmk',
        'sk_terbit_asimilasi',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'usulan_pb' => 'integer',
            'usulan_cb' => 'integer',
            'usulan_cmb' => 'integer',
            'usulan_cmk' => 'integer',
            'usulan_asimilasi' => 'integer',
            'sk_terbit_pb' => 'integer',
            'sk_terbit_cb' => 'integer',
            'sk_terbit_cmb' => 'integer',
            'sk_terbit_cmk' => 'integer',
            'sk_terbit_asimilasi' => 'integer',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function getTotalUsulanAttribute(): int
    {
        return $this->usulan_pb + $this->usulan_cb + $this->usulan_cmb + $this->usulan_cmk + $this->usulan_asimilasi;
    }

    public function getTotalSkTerbitAttribute(): int
    {
        return $this->sk_terbit_pb + $this->sk_terbit_cb + $this->sk_terbit_cmb + $this->sk_terbit_cmk + $this->sk_terbit_asimilasi;
    }
}
