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
        'caller_id',
        'status',
        'source_ip',
        'route_destination',
        'call_datetime',
        'duration',
        'checked_channels',
        'caller_name',
        'is_bulk',
        'user_id',
    ];

    protected $casts = [
        'checked_channels' => 'integer',
        'duration' => 'integer',
        'call_datetime' => 'datetime',
        'is_bulk' => 'boolean',
    ];

    protected static bool $columnsVerified = false;

    /**
     * Ensure route_destination, caller_id, call_datetime, and duration columns exist in database
     */
    public static function ensureTableColumnsExist(): void
    {
        if (static::$columnsVerified) return;
        static::$columnsVerified = true;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('call_logs')) {
                \Illuminate\Support\Facades\Schema::table('call_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('call_logs', 'route_destination')) {
                        $table->string('route_destination', 50)->nullable()->after('source_ip');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('call_logs', 'caller_id')) {
                        $table->string('caller_id', 50)->nullable()->after('phone_number');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('call_logs', 'call_datetime')) {
                        $table->dateTime('call_datetime')->nullable()->after('route_destination');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('call_logs', 'duration')) {
                        $table->integer('duration')->nullable()->after('call_datetime');
                    }
                });
            }
        } catch (\Throwable $e) {
            // Ignore if already exists or permission denied
        }
    }

    /**
     * Smart Accessor: Caller ID with fallbacks to CDRs
     */
    public function getDisplayCallerIdAttribute(): string
    {
        if (!empty($this->attributes['caller_id'])) {
            return $this->attributes['caller_id'];
        }
        if (!empty($this->attributes['caller_name']) && $this->attributes['caller_name'] !== 'channel_test_active') {
            return $this->attributes['caller_name'];
        }
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone_number);
            $latestCdr = Cdr::where('destination', $this->phone_number)
                ->orWhere('destination', $cleanPhone)
                ->latest('start_time')
                ->first();
            if ($latestCdr && !empty($latestCdr->caller_id)) {
                return $latestCdr->caller_id;
            }
            $latestTest = $this->channelTestCdrs()->latest()->first();
            if ($latestTest && !empty($latestTest->caller_id)) {
                return $latestTest->caller_id;
            }
        } catch (\Throwable $e) {}
        return '—';
    }

    /**
     * Smart Accessor: Call Date/Time formatted
     */
    public function getDisplayDateTimeAttribute(): string
    {
        if (!empty($this->attributes['call_datetime'])) {
            return \Carbon\Carbon::parse($this->attributes['call_datetime'])->format('Y-m-d H:i:s');
        }
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone_number);
            $latestCdr = Cdr::where('destination', $this->phone_number)
                ->orWhere('destination', $cleanPhone)
                ->latest('start_time')
                ->first();
            if ($latestCdr && !empty($latestCdr->start_time)) {
                return $latestCdr->start_time->format('Y-m-d H:i:s');
            }
            $latestTest = $this->channelTestCdrs()->latest()->first();
            if ($latestTest && !empty($latestTest->created_at)) {
                return $latestTest->created_at->format('Y-m-d H:i:s');
            }
        } catch (\Throwable $e) {}
        return '—';
    }

    /**
     * Smart Accessor: Duration formatted as MM:SS
     */
    public function getDisplayDurationAttribute(): string
    {
        if (isset($this->attributes['duration']) && $this->attributes['duration'] !== null && $this->attributes['duration'] !== '') {
            $sec = (int)$this->attributes['duration'];
            return sprintf('%02d:%02d', floor($sec / 60), $sec % 60);
        }
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone_number);
            $latestCdr = Cdr::where('destination', $this->phone_number)
                ->orWhere('destination', $cleanPhone)
                ->latest('start_time')
                ->first();
            if ($latestCdr && isset($latestCdr->duration)) {
                $sec = (int)$latestCdr->duration;
                return sprintf('%02d:%02d', floor($sec / 60), $sec % 60);
            }
        } catch (\Throwable $e) {}
        return '—';
    }

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
