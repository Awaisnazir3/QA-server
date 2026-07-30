<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_test_cdrs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('did_id');
            $table->string('phone_number', 50);
            $table->string('caller_id', 50);
            $table->string('call_status', 20)->default('Answered');
            $table->timestamps();
            $table->foreign('did_id')->references('id')->on('call_logs')->onDelete('cascade');
            $table->index(['phone_number', 'created_at']);
            $table->index('call_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_test_cdrs');
    }
};
