<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_dids')) {
            Schema::create('bulk_dids', function (Blueprint $table) {
                $table->id();
                $table->string('phone_number', 50);
                $table->string('source_ip')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamp('last_tested_at')->nullable();
                $table->timestamps();
                $table->index('phone_number');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_dids');
    }
};
