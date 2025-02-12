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
        Schema::create('sentiment_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->timestamp('analyzed_at');
            
            // Source Information
            $table->enum('source_type', ['NEWS', 'TWITTER', 'REDDIT', 'TELEGRAM', 'OTHER']);
            $table->string('source_url')->nullable();
            $table->string('source_author')->nullable();
            
            // Content
            $table->text('content');
            $table->string('language', 10)->default('en');
            $table->integer('reach')->nullable();  // Number of views/likes/retweets
            
            // Sentiment Analysis
            $table->decimal('sentiment_score', 5, 4);  // Range: -1 to 1
            $table->decimal('confidence_score', 5, 4); // Range: 0 to 1
            $table->enum('sentiment_label', ['VERY_NEGATIVE', 'NEGATIVE', 'NEUTRAL', 'POSITIVE', 'VERY_POSITIVE']);
            
            // Additional Metrics
            $table->decimal('impact_score', 5, 4)->nullable();  // Estimated market impact
            $table->json('entity_mentions')->nullable();        // Referenced entities/tokens
            $table->json('topic_classification')->nullable();   // Categorized topics
            $table->json('keyword_extraction')->nullable();     // Important keywords
            
            // Metadata
            $table->json('raw_analysis_data')->nullable();     // Complete analysis results
            $table->string('analyzer_version')->nullable();     // Version of sentiment analyzer
            
            $table->timestamps();
            
            // Indexes
            $table->index(['trading_pair_id', 'analyzed_at']);
            $table->index(['source_type', 'sentiment_label']);
            $table->index('sentiment_score');
            $table->index('impact_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentiment_data');
    }
};
