<?php

namespace App\Services\Analysis;

use App\Models\SentimentData;
use App\Models\TradingPair;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class SentimentAnalysisService
{
    protected array $newsApiEndpoints = [
        'https://newsapi.org/v2/everything',
        'https://cryptopanic.com/api/v1/posts/',
        'https://min-api.cryptocompare.com/data/v2/news/',
    ];

    protected array $socialPlatforms = [
        'twitter',
        'reddit',
        'telegram'
    ];

    /**
     * Analyze sentiment for a trading pair
     */
    public function analyzeSentiment(TradingPair $tradingPair): void
    {
        try {
            // Collect data from various sources
            $newsData = $this->collectNewsData($tradingPair);
            $socialData = $this->collectSocialData($tradingPair);
            
            // Analyze each piece of content
            $this->processSentimentData($tradingPair, array_merge($newsData, $socialData));
        } catch (Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'SentimentAnalysisService',
                'event' => 'SENTIMENT_ANALYSIS_FAILED',
                'message' => $e->getMessage(),
                'context' => [
                    'trading_pair' => $tradingPair->symbol,
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Collect news data from various sources
     */
    protected function collectNewsData(TradingPair $tradingPair): array
    {
        $news = [];
        $searchTerms = $this->getSearchTerms($tradingPair);

        foreach ($this->newsApiEndpoints as $endpoint) {
            try {
                $response = Http::get($endpoint, [
                    'q' => implode(' OR ', $searchTerms),
                    'sortBy' => 'publishedAt',
                    'language' => 'en',
                    'apiKey' => config('services.news.api_key')
                ]);

                if ($response->successful()) {
                    $articles = $response->json()['articles'] ?? [];
                    foreach ($articles as $article) {
                        $news[] = [
                            'source_type' => 'NEWS',
                            'source_url' => $article['url'],
                            'source_author' => $article['author'] ?? null,
                            'content' => $article['title'] . ' ' . ($article['description'] ?? ''),
                            'reach' => null
                        ];
                    }
                }
            } catch (Exception $e) {
                // Log error but continue with other sources
                SystemLog::create([
                    'level' => 'WARNING',
                    'component' => 'SentimentAnalysisService',
                    'event' => 'NEWS_COLLECTION_FAILED',
                    'message' => "Failed to collect news from {$endpoint}",
                    'context' => ['error' => $e->getMessage()]
                ]);
            }
        }

        return $news;
    }

    /**
     * Collect social media data
     */
    protected function collectSocialData(TradingPair $tradingPair): array
    {
        $social = [];
        $searchTerms = $this->getSearchTerms($tradingPair);

        foreach ($this->socialPlatforms as $platform) {
            try {
                $method = "collect{$platform}Data";
                if (method_exists($this, $method)) {
                    $platformData = $this->$method($searchTerms);
                    $social = array_merge($social, $platformData);
                }
            } catch (Exception $e) {
                SystemLog::create([
                    'level' => 'WARNING',
                    'component' => 'SentimentAnalysisService',
                    'event' => 'SOCIAL_COLLECTION_FAILED',
                    'message' => "Failed to collect data from {$platform}",
                    'context' => ['error' => $e->getMessage()]
                ]);
            }
        }

        return $social;
    }

    /**
     * Process and store sentiment data
     */
    protected function processSentimentData(TradingPair $tradingPair, array $data): void
    {
        foreach ($data as $item) {
            try {
                // Analyze sentiment using VADER
                $sentiment = $this->analyzeSentimentScore($item['content']);
                
                // Calculate impact score based on reach and source
                $impactScore = $this->calculateImpactScore(
                    $sentiment['compound'],
                    $item['reach'] ?? null,
                    $item['source_type']
                );

                // Store sentiment data
                SentimentData::create([
                    'trading_pair_id' => $tradingPair->id,
                    'analyzed_at' => now(),
                    'source_type' => $item['source_type'],
                    'source_url' => $item['source_url'] ?? null,
                    'source_author' => $item['source_author'] ?? null,
                    'content' => $item['content'],
                    'language' => 'en',
                    'reach' => $item['reach'] ?? null,
                    'sentiment_score' => $sentiment['compound'],
                    'confidence_score' => $sentiment['confidence'],
                    'sentiment_label' => $this->getSentimentLabel($sentiment['compound']),
                    'impact_score' => $impactScore,
                    'entity_mentions' => $this->extractEntities($item['content']),
                    'topic_classification' => $this->classifyTopics($item['content']),
                    'keyword_extraction' => $this->extractKeywords($item['content'])
                ]);
            } catch (Exception $e) {
                SystemLog::create([
                    'level' => 'ERROR',
                    'component' => 'SentimentAnalysisService',
                    'event' => 'SENTIMENT_PROCESSING_FAILED',
                    'message' => $e->getMessage(),
                    'context' => [
                        'content' => $item['content'],
                        'error' => $e->getMessage()
                    ]
                ]);
            }
        }
    }

    /**
     * Analyze sentiment score using VADER
     */
    protected function analyzeSentimentScore(string $text): array
    {
        $analyzer = new \Sentiment\Analyzer();
        $result = $analyzer->getSentiment($text);
        
        return [
            'compound' => $result['compound'],
            'confidence' => abs($result['compound']), // Use absolute value as confidence
            'pos' => $result['pos'],
            'neu' => $result['neu'],
            'neg' => $result['neg']
        ];
    }

    /**
     * Calculate impact score
     */
    protected function calculateImpactScore(?float $sentimentScore, ?int $reach, string $sourceType): float
    {
        // Base impact from sentiment strength
        $impact = abs($sentimentScore ?? 0);
        
        // Adjust based on reach if available
        if ($reach) {
            $reachFactor = min(log10($reach) / 5, 1); // Normalize large numbers
            $impact *= (1 + $reachFactor);
        }
        
        // Adjust based on source type
        $sourceWeights = [
            'NEWS' => 1.2,
            'TWITTER' => 1.0,
            'REDDIT' => 0.8,
            'TELEGRAM' => 0.7,
            'OTHER' => 0.5
        ];
        
        $impact *= ($sourceWeights[$sourceType] ?? 1.0);
        
        return min(max($impact, 0), 1); // Ensure between 0 and 1
    }

    /**
     * Get sentiment label based on score
     */
    protected function getSentimentLabel(float $score): string
    {
        if ($score <= -0.75) return SentimentData::SENTIMENT_VERY_NEGATIVE;
        if ($score <= -0.25) return SentimentData::SENTIMENT_NEGATIVE;
        if ($score >= 0.75) return SentimentData::SENTIMENT_VERY_POSITIVE;
        if ($score >= 0.25) return SentimentData::SENTIMENT_POSITIVE;
        return SentimentData::SENTIMENT_NEUTRAL;
    }

    /**
     * Extract entities from text
     */
    protected function extractEntities(string $text): array
    {
        // Simple keyword-based entity extraction
        $entities = [];
        $patterns = [
            'prices' => '/\$[0-9,]+(\.[0-9]{2})?/',
            'percentages' => '/[0-9]+%/',
            'cryptocurrencies' => '/\b(BTC|ETH|USDT|BNB)\b/i',
            'urls' => '/https?:\/\/\S+/i'
        ];

        foreach ($patterns as $type => $pattern) {
            preg_match_all($pattern, $text, $matches);
            if (!empty($matches[0])) {
                $entities[$type] = array_values(array_unique($matches[0]));
            }
        }

        return $entities;
    }

    /**
     * Classify topics in text
     */
    protected function classifyTopics(string $text): array
    {
        $topics = [];
        $categories = [
            'price_movement' => ['price', 'increase', 'decrease', 'up', 'down', 'bull', 'bear'],
            'technology' => ['blockchain', 'protocol', 'upgrade', 'fork', 'development'],
            'regulation' => ['sec', 'regulation', 'law', 'compliance', 'government'],
            'adoption' => ['adoption', 'partnership', 'integration', 'mainstream'],
            'trading' => ['trade', 'volume', 'exchange', 'market', 'position'],
        ];

        $text = strtolower($text);
        foreach ($categories as $category => $keywords) {
            $count = 0;
            foreach ($keywords as $keyword) {
                $count += substr_count($text, $keyword);
            }
            if ($count > 0) {
                $topics[$category] = $count;
            }
        }

        return $topics;
    }

    /**
     * Extract keywords from text
     */
    protected function extractKeywords(string $text): array
    {
        // Remove common words and extract significant terms
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'in', 'a', 'an', 'and'];
        $words = str_word_count(strtolower($text), 1);
        $words = array_diff($words, $stopWords);
        
        $frequencies = array_count_values($words);
        arsort($frequencies);
        
        return array_slice($frequencies, 0, 10, true);
    }

    /**
     * Get search terms for a trading pair
     */
    protected function getSearchTerms(TradingPair $tradingPair): array
    {
        $terms = [
            $tradingPair->base_asset,
            str_replace('/', '', $tradingPair->symbol)
        ];

        // Add common variations
        if ($tradingPair->base_asset === 'BTC') {
            $terms[] = 'Bitcoin';
        } elseif ($tradingPair->base_asset === 'ETH') {
            $terms[] = 'Ethereum';
        }

        return array_unique($terms);
    }

    /**
     * Get aggregated sentiment for a trading pair
     */
    public function getAggregateSentiment(TradingPair $tradingPair, int $hours = 24): array
    {
        $cacheKey = "sentiment_{$tradingPair->id}_{$hours}";
        
        return Cache::remember($cacheKey, 60, function () use ($tradingPair, $hours) {
            $data = SentimentData::where('trading_pair_id', $tradingPair->id)
                ->where('analyzed_at', '>=', now()->subHours($hours))
                ->get();

            if ($data->isEmpty()) {
                return [
                    'score' => 0,
                    'confidence' => 0,
                    'impact' => 0,
                    'count' => 0
                ];
            }

            $weightedScore = 0;
            $totalWeight = 0;
            $totalImpact = 0;
            $totalConfidence = 0;

            foreach ($data as $item) {
                $weight = $item->impact_score * $item->confidence_score;
                $weightedScore += $item->sentiment_score * $weight;
                $totalWeight += $weight;
                $totalImpact += $item->impact_score;
                $totalConfidence += $item->confidence_score;
            }

            $count = $data->count();
            return [
                'score' => $totalWeight > 0 ? $weightedScore / $totalWeight : 0,
                'confidence' => $count > 0 ? $totalConfidence / $count : 0,
                'impact' => $count > 0 ? $totalImpact / $count : 0,
                'count' => $count
            ];
        });
    }
}
