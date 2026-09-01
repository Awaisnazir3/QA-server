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
     * Helper to auto-create table if missing
     */
    public static function ensureTableExists(): void
    {
        try {
            if (!Schema::hasTable('abuse_dids')) {
                Schema::create('abuse_dids', function (Blueprint $table) {
                    $table->id();
                    $table->integer('user_id')->nullable();
                    $table->string('phone_number', 50)->index();
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
            }
        } catch (\Throwable $e) {
            // Ignore if already exists or permission issue
        }
    }
}
