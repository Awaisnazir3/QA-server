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
        if (!Schema::hasTable('call_histories')) {
            Schema::create('call_histories', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable();
                $table->string('caller_id');
                $table->string('callee_number');
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_histories');
    }
};
