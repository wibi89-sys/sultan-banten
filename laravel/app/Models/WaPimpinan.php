<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaPimpinan extends Model
{
    protected $table = 'wa_pimpinan';

    protected $fillable = [
        'nama',
        'jabatan',
        'no_wa',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }
}
