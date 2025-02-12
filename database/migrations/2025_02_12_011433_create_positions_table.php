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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->enum('side', ['LONG', 'SHORT']);
            $table->enum('status', ['OPEN', 'CLOSED']);
            
            // Position Details
            $table->decimal('quantity', 18, 8);
            $table->decimal('entry_price', 18, 8);
            $table->decimal('current_price', 18, 8);
            $table->decimal('liquidation_price', 18, 8)->nullable();
            
            // Risk Management
            $table->decimal('stop_loss', 18, 8)->nullable();
            $table->decimal('take_profit', 18, 8)->nullable();
            $table->decimal('trailing_stop', 18, 8)->nullable();
            
            // Performance Tracking
            $table->decimal('realized_pnl', 18, 8)->default(0);
            $table->decimal('unrealized_pnl', 18, 8)->default(0);
            $table->decimal('commission_paid', 18, 8)->default(0);
            
            // Strategy Information
            $table->string('strategy_name')->nullable();
            $table->json('strategy_parameters')->nullable();
            $table->json('entry_signals')->nullable();
            $table->json('exit_signals')->nullable();
            
            // Timestamps
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['trading_pair_id', 'status']);
            $table->index('opened_at');
            $table->index('strategy_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
