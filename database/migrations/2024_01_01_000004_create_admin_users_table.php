<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role', 20)->default('admin'); // admin, operator
            $table->timestamps();
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
