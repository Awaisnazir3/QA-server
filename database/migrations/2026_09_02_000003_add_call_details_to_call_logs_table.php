<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('call_logs')) {
            Schema::table('call_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('call_logs', 'caller_id')) {
                    $table->string('caller_id', 50)->nullable()->after('phone_number');
                }
                if (!Schema::hasColumn('call_logs', 'call_datetime')) {
                    $table->dateTime('call_datetime')->nullable()->after('route_destination');
                }
                if (!Schema::hasColumn('call_logs', 'duration')) {
                    $table->integer('duration')->nullable()->after('call_datetime');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('call_logs')) {
            Schema::table('call_logs', function (Blueprint $table) {
                if (Schema::hasColumn('call_logs', 'duration')) {
                    $table->dropColumn('duration');
                }
                if (Schema::hasColumn('call_logs', 'call_datetime')) {
                    $table->dropColumn('call_datetime');
                }
                if (Schema::hasColumn('call_logs', 'caller_id')) {
                    $table->dropColumn('caller_id');
                }
            });
        }
    }
};
