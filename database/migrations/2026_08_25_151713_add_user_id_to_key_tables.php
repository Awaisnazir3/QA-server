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
        Schema::table('call_logs', function (Blueprint $table) {
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('admin_users')->onDelete('cascade');
        });

        Schema::table('bulk_dids', function (Blueprint $table) {
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('admin_users')->onDelete('cascade');
        });

        Schema::table('call_histories', function (Blueprint $table) {
            $table->integer('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('admin_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('bulk_dids', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('call_histories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
