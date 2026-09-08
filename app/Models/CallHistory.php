<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CallHistory extends Model
{
    use BelongsToUser;

    protected $table = 'call_histories';

    protected $fillable = [
        'user_id',
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

    protected static bool $tableVerified = false;

    protected static function booted(): void
    {
        static::ensureTableExists();
    }

    /**
     * Auto-create call_histories table or missing columns if not present
     */
    public static function ensureTableExists(): void
    {
        if (static::$tableVerified) {
            return;
        }

        try {
            if (\Illuminate\Support\Facades\Cache::get('call_histories_table_verified')) {
                static::$tableVerified = true;
                return;
            }

            if (!Schema::hasTable('call_histories')) {
                Schema::create('call_histories', function (Blueprint $table) {
                    $table->id();
                    $table->integer('user_id')->nullable();
                    $table->string('caller_id')->nullable();
                    $table->string('callee_number')->nullable();
                    $table->string('direction')->default('outbound');
                    $table->string('status')->default('pending');
                    $table->unsignedBigInteger('route_id')->nullable();
                    $table->integer('duration')->nullable();
                    $table->timestamp('start_time')->nullable();
                    $table->timestamp('end_time')->nullable();
                    $table->string('recording_url')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamps();

                    $table->index('direction');
                    $table->index('status');
                    $table->index('created_at');
                });
            } else {
                Schema::table('call_histories', function (Blueprint $table) {
                    if (!Schema::hasColumn('call_histories', 'caller_id')) {
                        $table->string('caller_id')->nullable()->first();
                    }
                    if (!Schema::hasColumn('call_histories', 'callee_number')) {
                        $table->string('callee_number')->nullable()->after('caller_id');
                    }
                    if (!Schema::hasColumn('call_histories', 'user_id')) {
                        $table->integer('user_id')->nullable()->after('id');
                    }
                    if (!Schema::hasColumn('call_histories', 'direction')) {
                        $table->string('direction')->default('outbound')->after('callee_number');
                    }
                    if (!Schema::hasColumn('call_histories', 'status')) {
                        $table->string('status')->default('pending')->after('direction');
                    }
                    if (!Schema::hasColumn('call_histories', 'route_id')) {
                        $table->unsignedBigInteger('route_id')->nullable()->after('status');
                    }
                    if (!Schema::hasColumn('call_histories', 'duration')) {
                        $table->integer('duration')->nullable()->after('route_id');
                    }
                    if (!Schema::hasColumn('call_histories', 'start_time')) {
                        $table->timestamp('start_time')->nullable()->after('duration');
                    }
                    if (!Schema::hasColumn('call_histories', 'end_time')) {
                        $table->timestamp('end_time')->nullable()->after('start_time');
                    }
                    if (!Schema::hasColumn('call_histories', 'recording_url')) {
                        $table->string('recording_url')->nullable()->after('end_time');
                    }
                    if (!Schema::hasColumn('call_histories', 'notes')) {
                        $table->text('notes')->nullable()->after('recording_url');
                    }
                });
            }

            static::$tableVerified = true;
            \Illuminate\Support\Facades\Cache::put('call_histories_table_verified', true, 86400);
        } catch (\Throwable $e) {
            // Ignore schema manipulation errors
        }
    }
}
