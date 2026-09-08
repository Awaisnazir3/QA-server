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
        if (Schema::hasTable('abuse_dids')) {
            if (!Schema::hasColumn('abuse_dids', 'source_ip')) {
                Schema::table('abuse_dids', function (Blueprint $table) {
                    $table->string('source_ip', 100)->nullable()->after('source_trunk');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('abuse_dids')) {
            if (Schema::hasColumn('abuse_dids', 'source_ip')) {
                Schema::table('abuse_dids', function (Blueprint $table) {
                    $table->dropColumn('source_ip');
                });
            }
        }
    }
};
