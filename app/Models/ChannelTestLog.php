<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelTestLog extends Model
{
    protected $fillable = [
        'did_id',
        'phone_number',
        'calls_requested',
        'channels_detected',
        'status',
    ];

    protected $casts = [
        'calls_requested' => 'integer',
        'channels_detected' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'did_id');
    }
}
