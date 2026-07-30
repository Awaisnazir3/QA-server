<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallHistory extends Model
{
    protected $fillable = [
        'caller_id',
        'callee_number',
        'direction',
        'status',
        'route_id',
        'duration',
        'start_time',
        'end_time',
        'recording_url',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
    ];
}
