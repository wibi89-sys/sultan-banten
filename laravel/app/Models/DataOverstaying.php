<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOverstaying extends Model
{
    use HasFactory;

    protected $table = 'data_overstaying';

    protected $fillable = [
        'upt_id',
        'tahanan_ai',
        'tahanan_aii',
        'tahanan_aiii',
        'tahanan_aiv',
        'tahanan_av',
        'overstaying_ai',
        'overstaying_aii',
        'overstaying_aiii',
        'overstaying_aiv',
        'overstaying_av',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahanan_ai' => 'integer',
            'tahanan_aii' => 'integer',
            'tahanan_aiii' => 'integer',
            'tahanan_aiv' => 'integer',
            'tahanan_av' => 'integer',
            'overstaying_ai' => 'integer',
            'overstaying_aii' => 'integer',
            'overstaying_aiii' => 'integer',
            'overstaying_aiv' => 'integer',
            'overstaying_av' => 'integer',
        ];
    }

    public function upt()
    {
        return $this->belongsTo(Upt::class, 'upt_id');
    }

    public function getTotalTahananAttribute(): int
    {
        return $this->tahanan_ai + $this->tahanan_aii + $this->tahanan_aiii + $this->tahanan_aiv + $this->tahanan_av;
    }

    public function getTotalOverstayingAttribute(): int
    {
        return $this->overstaying_ai + $this->overstaying_aii + $this->overstaying_aiii + $this->overstaying_aiv + $this->overstaying_av;
    }
}
