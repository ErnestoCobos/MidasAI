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
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->timestamp('timestamp');
            // OHLCV data
            $table->decimal('open', 18, 8);
            $table->decimal('high', 18, 8);
            $table->decimal('low', 18, 8);
            $table->decimal('close', 18, 8);
            $table->decimal('volume', 18, 8);
            // Additional market data
            $table->decimal('quote_volume', 18, 8);
            $table->integer('number_of_trades');
            $table->decimal('taker_buy_volume', 18, 8);
            $table->decimal('taker_buy_quote_volume', 18, 8);
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['trading_pair_id', 'timestamp']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};
