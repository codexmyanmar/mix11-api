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
        Schema::create('games', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED
            
            $table->string("provider_game_id")->nullable();
            $table->string("game_name");
            $table->string("game_type");
            $table->string("game_code");
            $table->string("game_provider");
            $table->string("game_ggr_fee")->default('10');
            $table->string("game_alias")->nullable();
            
            // Using text() as per your SQL 'text' type
            $table->text("game_specail")->nullable();
            
            $table->integer("free_spin")->default(0);
            $table->integer("order")->default(0);
            $table->integer("status")->default(0);
            
            $table->string("image1")->nullable();
            $table->string("image2")->nullable();
            
            $table->integer("jili_jp")->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};