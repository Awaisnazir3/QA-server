<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AbuseDid extends Model
{
    protected $table = 'abuse_dids';

    protected $fillable = [
        'user_id',
        'phone_number',
        'source_trunk',
        'hits_count',
        'status',
        'first_hit_at',
        'last_hit_at',
        'last_call_id',
        'raw_log',
    ];

    protected $casts = [
        'first_hit_at' => 'datetime',
        'last_hit_at' => 'datetime',
        'hits_count' => 'integer',
    ];

    /**
     * Helper to auto-create table if missing and enforce unique constraint
     */
    public static function ensureTableExists(): void
    {
        try {
            if (!Schema::hasTable('abuse_dids')) {
                Schema::create('abuse_dids', function (Blueprint $table) {
                    $table->id();
                    $table->integer('user_id')->nullable();
                    $table->string('phone_number', 50)->unique();
                    $table->string('source_trunk', 100)->nullable();
                    $table->unsignedInteger('hits_count')->default(1);
                    $table->string('status', 30)->default('rejected');
                    $table->timestamp('first_hit_at')->nullable();
                    $table->timestamp('last_hit_at')->nullable();
                    $table->string('last_call_id', 100)->nullable();
                    $table->text('raw_log')->nullable();
                    $table->timestamps();

                    $table->index('hits_count');
                    $table->index('last_hit_at');
                });
            } else {
                // Ensure duplicate rows are consolidated if any exist
                try {
                    $duplicates = \Illuminate\Support\Facades\DB::select("
                        SELECT phone_number, COUNT(*) as cnt, SUM(hits_count) as total_hits, MAX(last_hit_at) as max_last_hit, MIN(first_hit_at) as min_first_hit
                        FROM abuse_dids 
                        GROUP BY phone_number 
                        HAVING cnt > 1
                    ");

                    foreach ($duplicates as $dup) {
                        $records = static::where('phone_number', $dup->phone_number)->orderBy('id', 'asc')->get();
                        if ($records->count() > 1) {
                            $primary = $records->first();
                            $primary->hits_count = $dup->total_hits;
                            $primary->first_hit_at = $dup->min_first_hit ?: $primary->first_hit_at;
                            $primary->last_hit_at = $dup->max_last_hit ?: $primary->last_hit_at;
                            $primary->save();

                            // Delete subsequent duplicate records
                            static::where('phone_number', $dup->phone_number)
                                ->where('id', '!=', $primary->id)
                                ->delete();
                        }
                    }

                    // Enforce UNIQUE index on phone_number if not already present
                    $uniqueIndexes = \Illuminate\Support\Facades\DB::select("
                        SHOW INDEX FROM abuse_dids WHERE Column_name = 'phone_number' AND Non_unique = 0
                    ");

                    if (empty($uniqueIndexes)) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE abuse_dids ADD UNIQUE KEY abuse_dids_phone_number_unique (phone_number)");
                    }
                } catch (\Throwable $e) {
                    // Ignore if already unique or schema query not supported
                }
            }
        } catch (\Throwable $e) {
            // Ignore if table already exists or permission issue
        }
    }
}
