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
        Schema::create('call_histories', function (Blueprint $table) {
            $table->id();
            $table->string('caller_id');
            $table->string('callee_number');
            $table->string('direction');
            $table->string('status');
            $table->unsignedBigInteger('route_id')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign key constraint (optional - only if routes table exists)
            // $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_histories');
    }
};
