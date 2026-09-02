<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('call_logs') && !Schema::hasColumn('call_logs', 'route_destination')) {
            Schema::table('call_logs', function (Blueprint $table) {
                $table->string('route_destination', 50)->nullable()->after('source_ip');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('call_logs') && Schema::hasColumn('call_logs', 'route_destination')) {
            Schema::table('call_logs', function (Blueprint $table) {
                $table->dropColumn('route_destination');
            });
        }
    }
};
