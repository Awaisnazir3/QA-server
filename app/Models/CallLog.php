<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallLog extends Model
{

        protected $table = 'call_logs';

    public $timestamps = false;

    protected $fillable = [
        'phone_number',
        'status',
        'source_ip',
        'checked_channels',
        'caller_name',
    ];

    protected $casts = [
        'checked_channels' => 'integer',
    ];

    public function channelTestLogs(): HasMany
    {
        return $this->hasMany(ChannelTestLog::class, 'did_id');
    }

    public function channelTestCdrs(): HasMany
    {
        return $this->hasMany(ChannelTestCdr::class, 'did_id');
    }
}
