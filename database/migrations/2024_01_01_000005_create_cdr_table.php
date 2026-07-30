<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdr', function (Blueprint $table) {
            $table->id();
            $table->string('caller_id', 50);
            $table->string('destination', 50);
            $table->integer('duration')->default(0);
            $table->integer('billsec')->default(0);
            $table->string('disposition', 20)->default('NO ANSWER'); // ANSWERED, NO ANSWER, etc
            $table->timestamp('start_time')->nullable();
            $table->timestamps();
            $table->index(['caller_id', 'start_time']);
            $table->index(['destination', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdr');
    }
};
