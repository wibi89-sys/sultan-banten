<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaScheduledReport extends Model
{
    protected $table = 'wa_scheduled_reports';

    protected $fillable = [
        'nama_laporan',
        'group_id',
        'jam_kirim',
        'hari',
        'template_pesan',
        'kirim_ke_pimpinan',
        'aktif',
        'last_sent',
        'next_send',
    ];

    protected function casts(): array
    {
        return [
            'kirim_ke_pimpinan' => 'boolean',
            'aktif' => 'boolean',
            'last_sent' => 'datetime',
            'next_send' => 'datetime',
        ];
    }
}
