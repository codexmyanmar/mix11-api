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
        // 1. KingKong Transactions
        Schema::create('kingkong_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('bet_id', 255);
            $table->string('settle_id', 255)->nullable();
            $table->string('round_id', 255);
            $table->string('game_code', 255);
            $table->decimal('bet_amount', 10, 2)->default(0.00);
            $table->decimal('win_amount', 10, 2)->nullable();
            $table->string('status', 255)->default('pending');
            $table->timestamps();

            // Recommended indexes
            $table->index('user_id');
            $table->index('bet_id');
            $table->index('round_id');
        });

        // 2. API Logs
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api_name', 100)->nullable();
            $table->string('method', 10)->nullable();
            $table->text('url');
            $table->longText('request_payload')->nullable();
            $table->longText('response')->nullable();
            $table->string('status', 50)->nullable()->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 3. AWC Players
        Schema::create('awc_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('awc_user_id', 21)->nullable();
            $table->char('currency', 4)->nullable()->default('THB');
            $table->string('language', 10)->nullable()->default('en');
            $table->string('user_name', 20)->nullable();
            $table->string('status', 20)->nullable()->default('active');
            $table->longText('bet_limit')->nullable(); // Set as longText to match utf8mb4_bin
            $table->tinyInteger('is_jackpot_enabled')->nullable()->default(0);
            $table->longText('awc_response')->nullable();
            $table->timestamps();

            // Index for user lookup
            $table->index('user_id');
            $table->index('awc_user_id');
        });
        
        
        // 4. AWC Transactions
        Schema::create('awc_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('platform_tx_id', 50)->index();
            $table->string('settlement_platform_tx_id', 255)->nullable();
            $table->string('ref_platform_tx_id', 50)->nullable();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('awc_user_id', 21);
            $table->string('platform', 20);
            $table->string('game_type', 10);
            $table->string('game_code', 50);
            $table->string('game_name', 50)->nullable();
            $table->string('bet_type', 50)->nullable();
            $table->string('round_id', 50)->nullable();
            $table->decimal('bet_amount', 20, 4)->default(0.0000);
            $table->decimal('win_amount', 20, 4)->default(0.0000);
            $table->decimal('jackpot_bet_amount', 20, 4)->default(0.0000);
            $table->decimal('jackpot_win_amount', 20, 4)->default(0.0000);
            $table->decimal('turnover', 20, 4)->default(0.0000);
            $table->decimal('original_balance', 20, 4)->nullable();
            $table->decimal('balance_after', 20, 4)->nullable();
            $table->enum('status', ['bet', 'settle', 'void', 'cancelled', 'refund'])->default('bet');
            $table->integer('void_type')->nullable();
            $table->string('type', 20)->nullable();
            $table->dateTime('bet_time');
            $table->dateTime('tx_time')->nullable();
            $table->dateTime('update_time')->nullable();
            $table->dateTime('settle_time')->nullable();
            $table->dateTime('cancel_time')->nullable();
            $table->dateTime('void_time')->nullable();
            $table->longText('game_info')->nullable();
            $table->longText('tip_info')->nullable();
            $table->tinyInteger('is_premium')->default(0);
            $table->string('currency', 4)->default('THB');
            $table->timestamps();
        });

        // 5. Bole Raw Logs
        Schema::create('bole_raw_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 50);
            $table->string('player_account', 100)->nullable();
            $table->string('report_id', 255)->nullable();
            $table->integer('type')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('gain_gold', 15, 2)->nullable();
            $table->decimal('bet_num', 15, 2)->nullable();
            $table->string('game_code', 100)->nullable();
            $table->longText('raw_payload');
            $table->tinyInteger('processed')->default(0);
            $table->timestamps();
        });

        // 6. Bole Transactions
        Schema::create('bole_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('transaction_id', 255)->unique();
            $table->string('order_id', 255)->nullable();
            $table->string('type', 50);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('status', 50)->default('completed');
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
        
        
        // 7. Joker Transactions
        Schema::create('joker_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 255);
            $table->decimal('amount', 15, 2);
            $table->decimal('old_balance', 15, 2);
            $table->decimal('new_balance', 15, 2);
            $table->string('reference_id', 255)->nullable();
            $table->string('round_id', 255)->nullable();
            $table->string('game_code', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('provider', 255)->nullable()->default('joker');
            $table->longText('metadata')->nullable(); // Matches utf8mb4_bin
            $table->string('request_hash', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Indexes for lookup speed
            $table->index('reference_id');
            $table->index('round_id');
        });

        // 8. Pending Bet Cancellations
        Schema::create('pending_bet_cancellations', function (Blueprint $table) {
            $table->id();
            $table->string('platform_tx_id', 50)->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
        });

        // 9. Rich88 Transfers
        Schema::create('rich88_transfers', function (Blueprint $table) {
            $table->id(); // Note: SQL says bigint(20), matches standard id()
            $table->unsignedBigInteger('user_id')->index();
            $table->string('transfer_no', 255)->nullable();
            $table->string('transfer_type', 255)->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('status', 225)->nullable()->default('pending');
            $table->decimal('balance_before', 20, 2)->nullable();
            $table->decimal('balance_after', 20, 2)->nullable();
            $table->string('currency', 255)->nullable();
            $table->string('round_id', 255)->nullable();
            $table->string('game_code', 255)->nullable();
            $table->timestamps();
            
            // Index for transfer tracking
            $table->index('transfer_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awc_players');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('kingkong_transactions');
        Schema::dropIfExists('awc_transactions');
        Schema::dropIfExists('bole_raw_logs');
        Schema::dropIfExists('bole_transactions');
        Schema::dropIfExists('joker_transactions');
        Schema::dropIfExists('pending_bet_cancellations');
        Schema::dropIfExists('rich88_transfers');
    }
};