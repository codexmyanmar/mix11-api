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
        Schema::create('users', function (Blueprint $table) {
            // ID and User Identifiers
            $table->id(); // bigint(20) UNSIGNED
            $table->string('user_id')->nullable(); 
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 225)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Status and Financials
            $table->string('status')->default('1');
            $table->decimal('commission', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('withdraw_able', 15, 2)->default(0.00);
            $table->integer('wallet_locked')->default(0);
            
            // Security and Tracking
            $table->rememberToken(); // varchar(100)
            $table->string('last_login_ip')->nullable();
            $table->string('refer_code', 225)->nullable();
            
            // API / External Integrations
            $table->string('kingkong_token', 225)->nullable();
            $table->string('pp_token')->nullable();
            $table->string('rich88_account', 225)->nullable();
            $table->string('rich88_session_id')->nullable();
            $table->string('rich88_pf_id', 225)->nullable();
            $table->timestamp('rich88_session_expires_at')->nullable();

            // Timestamps
            $table->timestamps(); 
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};