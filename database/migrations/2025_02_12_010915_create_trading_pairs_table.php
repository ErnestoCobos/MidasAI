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
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('base_asset', 10);
            $table->string('quote_asset', 10);
            $table->decimal('min_qty', 18, 8);
            $table->decimal('max_qty', 18, 8);
            $table->decimal('min_notional', 18, 8);
            $table->decimal('max_position_size', 18, 8);
            $table->decimal('maker_fee', 8, 4)->default(0.1);
            $table->decimal('taker_fee', 8, 4)->default(0.1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_pairs');
    }
};
