<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abuse_dids');
    }
};
