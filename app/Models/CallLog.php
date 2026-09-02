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
        'user_id',
    ];

    protected $casts = [
        'checked_channels' => 'integer',
        'is_bulk' => 'boolean',
    ];

    /**
     * Consolidate duplicate phone numbers in call_logs table
     */
    public static function deduplicate(): void
    {
        try {
            $duplicates = static::selectRaw('phone_number, COUNT(*) as cnt')
                ->groupBy('phone_number')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                $records = static::where('phone_number', $dup->phone_number)
                    ->orderByRaw("FIELD(status, 'route', 'pass', 'fail', 'pending') ASC")
                    ->orderBy('id', 'desc')
                    ->get();

                if ($records->count() > 1) {
                    $primary = $records->first();

                    foreach ($records->slice(1) as $secondary) {
                        if (empty($primary->source_ip) && !empty($secondary->source_ip)) {
                            $primary->source_ip = $secondary->source_ip;
                        }
                        if ($primary->checked_channels === null && $secondary->checked_channels !== null) {
                            $primary->checked_channels = $secondary->checked_channels;
                        }
                        if (empty($primary->user_id) && !empty($secondary->user_id)) {
                            $primary->user_id = $secondary->user_id;
                        }

                        try {
                            ChannelTestLog::where('did_id', $secondary->id)->update(['did_id' => $primary->id]);
                            ChannelTestCdr::where('did_id', $secondary->id)->update(['did_id' => $primary->id]);
                        } catch (\Throwable $e) {
                            // ignore foreign key updates if table missing
                        }

                        $secondary->delete();
                    }

                    $primary->save();
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    public function channelTestLogs(): HasMany
    {
        return $this->hasMany(ChannelTestLog::class, 'did_id');
    }

    public function channelTestCdrs(): HasMany
    {
        return $this->hasMany(ChannelTestCdr::class, 'did_id');
    }
}
