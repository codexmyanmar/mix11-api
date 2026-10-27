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
        Schema::create('live22_processed_transactions', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED
            
            // Core Transaction Data
            $table->string('transaction_id', 50)->index()->comment('BetId, ResultId, or TranId');
            $table->string('type', 20)->comment('bet, result, rollback, bonus, betnsettle');
            $table->unsignedBigInteger('user_id');
            $table->string('player_id', 100)->nullable()->comment('Player ID from Live22');
            $table->bigInteger('round_id')->nullable()->comment('Round ID from Live22');
            
            // Game Details
            $table->string('game_code', 50)->nullable();
            $table->string('game_type', 50)->nullable()->comment('Game Type (Slots, etc)');
            
            // Financial Data
            $table->decimal('bet_amount', 20, 2)->nullable()->default(0.00);
            $table->decimal('payout', 20, 2)->nullable()->default(0.00);
            $table->decimal('net_change', 20, 2)->nullable()->default(0.00)->comment('payout - bet');
            $table->decimal('exchange_rate', 10, 4)->nullable()->default(1.0000);
            $table->string('currency', 10)->nullable()->default('THB');
            $table->decimal('old_balance', 20, 2)->nullable()->default(0.00);
            $table->decimal('new_balance', 20, 2)->nullable()->default(0.00);
            
            // Provider Time Tracking
            $table->dateTime('tran_date_time')->nullable()->comment('Transaction date time from Live22');
            $table->dateTime('request_date_time')->nullable()->comment('Request date time from Live22');
            $table->string('provider_time_zone', 100)->nullable();
            $table->dateTime('provider_tran_dt')->nullable()->comment('Provider transaction datetime');
            
            // Security & Integration
            $table->text('auth_token')->nullable()->comment('Auth token from Live22');
            $table->string('signature', 100)->nullable();
            $table->longText('raw_data')->nullable()->comment('Complete raw JSON data');
            $table->string('operator_id', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            
            // Laravel Timestamps
            $table->timestamps();
            
            // Indexing for performance
            $table->index(['user_id', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live22_processed_transactions');
    }
};