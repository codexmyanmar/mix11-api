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
        Schema::create('refer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')
            ->constrained('users')
            ->onDelete('cascade');

            // user who was referred
            $table->foreignId('referred_user_id')
            ->constrained('users')
            ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refer_logs');
    }
};
