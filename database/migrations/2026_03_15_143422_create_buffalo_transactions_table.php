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
        // 1. Buffalo Transactions Table
        Schema::create('buffalo_transactions', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED
            $table->string('uid', 255)->comment('User ID from Buffalo format (UID_xxx)');
            $table->string('bet_uid', 255)->comment('Unique bet transaction ID from Buffalo');
            $table->string('token', 255)->comment('Authentication token');
            
            // Amounts in Cents (Integer as per your request)
            $table->integer('changemoney')->comment('Net amount change in cents');
            $table->integer('bet')->comment('Bet amount in cents');
            $table->integer('win')->comment('Win amount in cents');
            
            $table->string('room_id', 50)->nullable();
            $table->string('game_id', 50)->nullable();
            $table->string('status', 50)->default('pending');
            
            // Raw data from provider
            $table->longText('raw_request')->nullable(); 
            
            $table->timestamps();

            // Indexes for speed
            $table->index('uid');
            $table->index('bet_uid');
        });

        // 2. Buffalo Logs Table
        Schema::create('buffalo_logs', function (Blueprint $table) {
            $table->id(); // int(10) UNSIGNED (Standard Laravel ID is bigint, but this matches your request)
            $table->string('uid', 255)->nullable();
            $table->string('bet_uid', 255)->nullable();
            $table->string('token', 255)->nullable();
            
            $table->longText('request_data')->nullable();
            $table->longText('response_data')->nullable();
            $table->string('endpoint', 255)->nullable();
            $table->string('status', 255)->default('pending');
            
            $table->timestamps();

            // Indexes for logs
            $table->index('bet_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buffalo_logs');
        Schema::dropIfExists('buffalo_transactions');
    }
};