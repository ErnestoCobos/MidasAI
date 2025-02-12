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
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('snapshot_time');
            
            // Total Portfolio Value
            $table->decimal('total_value_usdt', 18, 8);
            $table->decimal('free_usdt', 18, 8);
            $table->decimal('locked_usdt', 18, 8);
            
            // Performance Metrics
            $table->decimal('daily_pnl', 18, 8);
            $table->decimal('daily_pnl_percentage', 8, 4);
            $table->decimal('total_pnl', 18, 8);
            $table->decimal('total_pnl_percentage', 8, 4);
            
            // Risk Metrics
            $table->decimal('daily_drawdown', 8, 4);
            $table->decimal('max_drawdown', 8, 4);
            
            // Trading Statistics
            $table->integer('total_trades');
            $table->integer('winning_trades');
            $table->integer('losing_trades');
            $table->decimal('win_rate', 8, 4);
            $table->decimal('profit_factor', 8, 4);
            $table->decimal('average_win', 18, 8);
            $table->decimal('average_loss', 18, 8);
            
            // Asset Allocation
            $table->json('asset_distribution');  // Distribution across different cryptocurrencies
            $table->json('strategy_allocation'); // Distribution across different strategies
            
            // Market Conditions
            $table->decimal('market_volatility', 8, 4);
            $table->decimal('market_trend', 8, 4);  // Overall market trend indicator
            
            $table->timestamps();
            
            // Indexes
            $table->index('snapshot_time');
            $table->index(['snapshot_time', 'total_value_usdt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
