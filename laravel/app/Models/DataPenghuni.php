<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPenghuni extends Model
{
    use HasFactory;

    protected $table = 'data_penghuni';

    protected $fillable = [
        'upt_id',
        'klasifikasi_pidana',
        'tahanan_dewasa_laki',
        'tahanan_dewasa_perempuan',
        'tahanan_anak_laki',
        'tahanan_anak_perempuan',
        'narapidana_dewasa_laki',
        'narapidana_dewasa_perempuan',
        'narapidana_anak_laki',
        'narapidana_anak_perempuan',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahanan_dewasa_laki' => 'integer',
            'tahanan_dewasa_perempuan' => 'integer',
            'tahanan_anak_laki' => 'integer',
            'tahanan_anak_perempuan' => 'integer',
            'narapidana_dewasa_laki' => 'integer',
            'narapidana_dewasa_perempuan' => 'integer',
            'narapidana_anak_laki' => 'integer',
            'narapidana_anak_perempuan' => 'integer',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function getTotalTahananAttribute(): int
    {
        if (array_key_exists('total_tahanan', $this->attributes) && $this->attributes['total_tahanan'] !== null) {
            return (int) $this->attributes['total_tahanan'];
        }
        return (int)(($this->tahanan_dewasa_laki ?? 0) + ($this->tahanan_dewasa_perempuan ?? 0) + ($this->tahanan_anak_laki ?? 0) + ($this->tahanan_anak_perempuan ?? 0));
    }

    public function getTotalNarapidanaAttribute(): int
    {
        if (array_key_exists('total_narapidana', $this->attributes) && $this->attributes['total_narapidana'] !== null) {
            return (int) $this->attributes['total_narapidana'];
        }
        return (int)(($this->narapidana_dewasa_laki ?? 0) + ($this->narapidana_dewasa_perempuan ?? 0) + ($this->narapidana_anak_laki ?? 0) + ($this->narapidana_anak_perempuan ?? 0));
    }

    public function getTotalPenghuniAttribute(): int
    {
        if (array_key_exists('total_penghuni', $this->attributes) && $this->attributes['total_penghuni'] !== null) {
            return (int) $this->attributes['total_penghuni'];
        }
        return $this->total_tahanan + $this->total_narapidana;
    }
}
