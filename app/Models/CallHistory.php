<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class CallHistory extends Model
{
    use BelongsToUser;

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
