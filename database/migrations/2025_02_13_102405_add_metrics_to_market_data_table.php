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
        Schema::table('market_data', function (Blueprint $table) {
            $table->decimal('daily_volatility', 18, 8)->nullable()->after('taker_buy_quote_volume');
            $table->decimal('buy_sell_ratio', 18, 8)->nullable()->after('daily_volatility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_data', function (Blueprint $table) {
            $table->dropColumn('daily_volatility');
            $table->dropColumn('buy_sell_ratio');
        });
    }
};
