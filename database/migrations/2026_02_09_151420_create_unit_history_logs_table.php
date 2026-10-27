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
        Schema::create('unit_history_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
            ->nullable()
            ->constrained('users') // Explicitly reference the 'users' table
            ->onDelete('set null');

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');
            $table->integer("amount");
            $table->integer("user_before_amount")->nullable();
            $table->integer("user_after_amount")->nullable();
            $table->integer("agent_before_amount")->nullable();
            $table->integer("agent_after_amount")->nullable();
            $table->integer("type")->default(0); // 0 withdraw , 1 topup 
            $table->integer("status")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_history_logs');
    }
};
