<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelTestCdr extends Model
{
    protected $table = 'channel_test_cdrs';

    protected $fillable = [
        'did_id',
        'phone_number',
        'caller_id',
        'call_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'did_id');
    }
}
