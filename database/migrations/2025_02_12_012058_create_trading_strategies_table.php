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
        Schema::create('trading_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Strategy Configuration
            $table->json('indicators')->nullable();  // Technical indicators used
            $table->json('parameters')->nullable();  // Strategy parameters
            $table->json('risk_settings')->nullable(); // Risk management settings
            
            // Trading Rules
            $table->json('entry_rules')->nullable();  // Entry conditions
            $table->json('exit_rules')->nullable();   // Exit conditions
            $table->json('position_sizing_rules')->nullable();
            
            // Time Settings
            $table->json('trading_hours')->nullable();  // Active trading hours
            $table->string('timeframe', 20);  // e.g., '1m', '5m', '1h', '1d'
            
            // Performance Settings
            $table->decimal('max_positions', 5, 2)->default(1);  // Maximum concurrent positions
            $table->decimal('max_drawdown', 5, 2)->nullable();   // Maximum allowed drawdown
            $table->decimal('profit_target', 5, 2)->nullable();  // Take profit target
            $table->decimal('stop_loss', 5, 2)->nullable();      // Stop loss percentage
            
            // Backtesting Results
            $table->json('backtest_results')->nullable();
            $table->decimal('sharpe_ratio', 8, 4)->nullable();
            $table->decimal('sortino_ratio', 8, 4)->nullable();
            $table->decimal('win_rate', 5, 2)->nullable();
            
            // Version Control
            $table->string('version')->default('1.0.0');
            $table->json('change_history')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('name');
            $table->index(['is_active', 'timeframe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_strategies');
    }
};
