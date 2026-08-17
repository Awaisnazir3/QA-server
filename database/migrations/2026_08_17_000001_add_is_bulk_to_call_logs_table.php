<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('call_logs') && !Schema::hasColumn('call_logs', 'is_bulk')) {
            Schema::table('call_logs', function (Blueprint $table) {
                $table->boolean('is_bulk')->default(false);
                $table->index('is_bulk');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('call_logs') && Schema::hasColumn('call_logs', 'is_bulk')) {
            Schema::table('call_logs', function (Blueprint $table) {
                $table->dropColumn('is_bulk');
            });
        }
    }
};
