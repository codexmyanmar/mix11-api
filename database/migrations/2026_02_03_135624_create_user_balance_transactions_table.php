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
        Schema::create('user_balance_transactions', function (Blueprint $table) {
            // 1. Basic Transaction Fields
            $table->id(); // bigint(20) UNSIGNED
            $table->unsignedBigInteger('user_id')->nullable(); 
            $table->integer('type'); // int(11)
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->string('reason'); // varchar(255)
            $table->integer('status')->default(1);
            $table->text('comment')->nullable();

            // 2. Game & Provider Fields
            $table->string('remote_id')->nullable();
            $table->string('provider_code')->nullable();
            $table->string('game_code')->nullable();
            $table->string('game_name')->nullable();
            
            // 3. Financial Tracking
            $table->decimal('stake_amount', 16, 2)->default(0.00);
            $table->decimal('win_loss_amount', 16, 2)->default(0.00);
            
            // 4. Processing Flags
            $table->tinyInteger('is_processed')->default(0); // tinyint(1)
            $table->tinyInteger('is_approved')->default(0);  // tinyint(1)
            $table->string('which_type')->nullable();

            // 5. Data Logs (Using longText to match your longtext SQL type)
            $table->longText('meta_data')->nullable();
            $table->longText('approval_data')->nullable();

            // 6. Timestamps
            $table->timestamps(); 

            // Optional: If you want the foreign key constraint
             $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_balance_transactions');
    }
};