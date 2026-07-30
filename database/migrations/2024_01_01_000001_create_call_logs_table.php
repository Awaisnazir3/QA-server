<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 50);
            $table->string('status', 20)->default('pending'); // pending, pass, fail, route
            $table->string('source_ip')->nullable();
            $table->integer('checked_channels')->nullable();
            $table->string('caller_name')->nullable();
            $table->index('phone_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
