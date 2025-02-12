<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentimentData extends Model
{
    protected $fillable = [
        'trading_pair_id',
        'analyzed_at',
        'source_type',
        'source_url',
        'source_author',
        'content',
        'language',
        'reach',
        'sentiment_score',
        'confidence_score',
        'sentiment_label',
        'impact_score',
        'entity_mentions',
        'topic_classification',
        'keyword_extraction',
        'raw_analysis_data',
        'analyzer_version',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'reach' => 'integer',
        'sentiment_score' => 'decimal:4',
        'confidence_score' => 'decimal:4',
        'impact_score' => 'decimal:4',
        'entity_mentions' => 'json',
        'topic_classification' => 'json',
        'keyword_extraction' => 'json',
        'raw_analysis_data' => 'json',
    ];

    // Source Types
    const SOURCE_NEWS = 'NEWS';
    const SOURCE_TWITTER = 'TWITTER';
    const SOURCE_REDDIT = 'REDDIT';
    const SOURCE_TELEGRAM = 'TELEGRAM';
    const SOURCE_OTHER = 'OTHER';

    // Sentiment Labels
    const SENTIMENT_VERY_NEGATIVE = 'VERY_NEGATIVE';
    const SENTIMENT_NEGATIVE = 'NEGATIVE';
    const SENTIMENT_NEUTRAL = 'NEUTRAL';
    const SENTIMENT_POSITIVE = 'POSITIVE';
    const SENTIMENT_VERY_POSITIVE = 'VERY_POSITIVE';

    // Relationships
    public function tradingPair()
    {
        return $this->belongsTo(TradingPair::class);
    }

    // Scopes
    public function scopeTimeRange($query, $start, $end)
    {
        return $query->whereBetween('analyzed_at', [$start, $end]);
    }

    public function scopeSource($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopePositive($query)
    {
        return $query->whereIn('sentiment_label', [
            self::SENTIMENT_POSITIVE,
            self::SENTIMENT_VERY_POSITIVE
        ]);
    }

    public function scopeNegative($query)
    {
        return $query->whereIn('sentiment_label', [
            self::SENTIMENT_NEGATIVE,
            self::SENTIMENT_VERY_NEGATIVE
        ]);
    }

    public function scopeHighConfidence($query, $threshold = 0.8)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeHighImpact($query, $threshold = 0.7)
    {
        return $query->where('impact_score', '>=', $threshold);
    }

    // Helper Methods
    public function isPositive()
    {
        return in_array($this->sentiment_label, [
            self::SENTIMENT_POSITIVE,
            self::SENTIMENT_VERY_POSITIVE
        ]);
    }

    public function isNegative()
    {
        return in_array($this->sentiment_label, [
            self::SENTIMENT_NEGATIVE,
            self::SENTIMENT_VERY_NEGATIVE
        ]);
    }

    public function isNeutral()
    {
        return $this->sentiment_label === self::SENTIMENT_NEUTRAL;
    }

    public function isHighConfidence($threshold = 0.8)
    {
        return $this->confidence_score >= $threshold;
    }

    public function isHighImpact($threshold = 0.7)
    {
        return $this->impact_score >= $threshold;
    }

    public function getEntities()
    {
        return json_decode($this->entity_mentions, true) ?? [];
    }

    public function getTopics()
    {
        return json_decode($this->topic_classification, true) ?? [];
    }

    public function getKeywords()
    {
        return json_decode($this->keyword_extraction, true) ?? [];
    }

    public function hasEntity($entity)
    {
        $entities = $this->getEntities();
        return in_array($entity, array_column($entities, 'name'));
    }

    public function hasTopic($topic)
    {
        $topics = $this->getTopics();
        return isset($topics[$topic]);
    }

    public function getTopicConfidence($topic)
    {
        $topics = $this->getTopics();
        return $topics[$topic] ?? 0;
    }

    public function getSentimentColor()
    {
        return match($this->sentiment_label) {
            self::SENTIMENT_VERY_POSITIVE => '#28a745',
            self::SENTIMENT_POSITIVE => '#98d8a0',
            self::SENTIMENT_NEUTRAL => '#6c757d',
            self::SENTIMENT_NEGATIVE => '#dc3545',
            self::SENTIMENT_VERY_NEGATIVE => '#881c26',
            default => '#6c757d',
        };
    }

    public function getSourceIcon()
    {
        return match($this->source_type) {
            self::SOURCE_NEWS => '📰',
            self::SOURCE_TWITTER => '🐦',
            self::SOURCE_REDDIT => '👽',
            self::SOURCE_TELEGRAM => '✈️',
            default => '📱',
        };
    }
}
