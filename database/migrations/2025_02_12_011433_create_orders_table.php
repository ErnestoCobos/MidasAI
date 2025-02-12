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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->string('binance_order_id')->unique();
            $table->enum('type', ['MARKET', 'LIMIT', 'STOP_LOSS', 'TAKE_PROFIT']);
            $table->enum('side', ['BUY', 'SELL']);
            $table->decimal('quantity', 18, 8);
            $table->decimal('price', 18, 8)->nullable(); // Null for market orders
            $table->decimal('executed_qty', 18, 8)->default(0);
            $table->decimal('executed_price', 18, 8)->nullable();
            $table->decimal('commission', 18, 8)->default(0);
            $table->string('commission_asset', 10)->nullable();
            $table->enum('status', [
                'NEW', 'PARTIALLY_FILLED', 'FILLED', 
                'CANCELED', 'REJECTED', 'EXPIRED'
            ]);
            $table->json('raw_data')->nullable(); // Store complete Binance response
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['trading_pair_id', 'status']);
            $table->index('binance_order_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
