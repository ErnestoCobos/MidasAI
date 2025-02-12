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
        Schema::create('technical_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->timestamp('timestamp');
            // RSI
            $table->decimal('rsi', 8, 4)->nullable();
            // MACD
            $table->decimal('macd_line', 18, 8)->nullable();
            $table->decimal('macd_signal', 18, 8)->nullable();
            $table->decimal('macd_histogram', 18, 8)->nullable();
            // Bollinger Bands
            $table->decimal('bb_upper', 18, 8)->nullable();
            $table->decimal('bb_middle', 18, 8)->nullable();
            $table->decimal('bb_lower', 18, 8)->nullable();
            // ATR and Volatility
            $table->decimal('atr', 18, 8)->nullable();
            $table->decimal('volatility', 8, 4)->nullable();
            // Moving Averages
            $table->decimal('sma_20', 18, 8)->nullable();
            $table->decimal('ema_20', 18, 8)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['trading_pair_id', 'timestamp']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_indicators');
    }
};
