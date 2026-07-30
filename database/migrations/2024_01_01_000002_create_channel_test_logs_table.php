<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_test_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('did_id');
            $table->string('phone_number', 50);
            $table->integer('calls_requested')->default(5);
            $table->integer('channels_detected')->default(0);
            $table->string('status', 20)->default('completed');
            $table->timestamps();
            $table->foreign('did_id')->references('id')->on('call_logs')->onDelete('cascade');
            $table->index(['phone_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_test_logs');
    }
};
