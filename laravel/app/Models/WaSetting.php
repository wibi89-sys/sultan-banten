<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaSetting extends Model
{
    protected $table = 'wa_settings';

    protected $fillable = [
        'gateway_name',
        'api_key',
        'api_url',
        'account_sid',
        'sender_number',
        'group_id',
        'group_id_laporan',
        'jam_reminder_pagi',
        'jam_reminder_siang',
        'aktif',
        'template_pagi',
        'template_siang',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public static function getFirst(): ?self
    {
        return static::find(1) ?? static::first();
    }
}
