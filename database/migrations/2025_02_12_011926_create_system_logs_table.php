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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('logged_at');
            
            // Log Classification
            $table->enum('level', [
                'DEBUG', 'INFO', 'NOTICE', 
                'WARNING', 'ERROR', 'CRITICAL', 
                'ALERT', 'EMERGENCY'
            ]);
            $table->string('component', 50);  // Which part of the system generated the log
            
            // Log Details
            $table->string('event', 100);     // Short description of what happened
            $table->text('message');          // Detailed message
            $table->json('context')->nullable(); // Additional contextual data
            
            // Related Entities
            $table->unsignedBigInteger('trading_pair_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            
            // System Information
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->json('system_metrics')->nullable(); // CPU, memory usage, etc.
            
            // Error Tracking
            $table->string('exception_class')->nullable();
            $table->text('stack_trace')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('logged_at');
            $table->index(['level', 'component']);
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
