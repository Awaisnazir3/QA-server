<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToUser;

class CallLog extends Model
{
    use BelongsToUser;

    protected $table = 'call_logs';

    public $timestamps = false;

    protected $fillable = [
        'phone_number',
        'status',
        'source_ip',
        'checked_channels',
        'caller_name',
        'is_bulk',
    ];

    protected $casts = [
        'checked_channels' => 'integer',
        'is_bulk' => 'boolean',
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
