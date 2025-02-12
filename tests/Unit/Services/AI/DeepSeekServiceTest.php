<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\DeepSeekService;
use App\Models\TradingPair;
use App\Models\TechnicalIndicator;
use App\Models\MarketData;
use App\Models\SentimentData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

class DeepSeekServiceTest extends TestCase
{
    protected $service;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the OpenAI client
        $this->mockClient = Mockery::mock('OpenAI\Client');
        $this->mockClient->shouldReceive('chat')->andReturn($this->mockClient);

        // Create the service with mocked client
        $this->service = new DeepSeekService();
        $this->setProtectedProperty($this->service, 'client', $this->mockClient);

        // Set up test configuration
        Config::set('ai.deepseek.cache.enabled', true);
        Config::set('ai.deepseek.cache.ttl', 300);
        Config::set('ai.deepseek.logging.enabled', true);
    }

    /** @test */
    public function it_can_analyze_market_conditions()
    {
        // Create test data
        $pair = $this->createTestTradingPair();

        // Mock the API response
        $this->mockClient->shouldReceive('create')
            ->once()
            ->andReturn((object)[
                'choices' => [(object)[
                    'message' => (object)[
                        'content' => $this->getMockAnalysisResponse()
                    ]
                ]]
            ]);

        // Perform analysis
        $result = $this->service->analyzeMarket($pair);

        // Assert response structure
        $this->assertArrayHasKey('raw_analysis', $result);
        $this->assertArrayHasKey('parsed', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertTrue($result['parsed']);
    }

    /** @test */
    public function it_caches_market_analysis_results()
    {
        $pair = $this->createTestTradingPair();
        $cacheKey = "market_analysis_{$pair->symbol}";

        // First call should hit the API
        $this->mockClient->shouldReceive('create')
            ->once()
            ->andReturn((object)[
                'choices' => [(object)[
                    'message' => (object)[
                        'content' => $this->getMockAnalysisResponse()
                    ]
                ]]
            ]);

        // First analysis
        $result1 = $this->service->analyzeMarket($pair);

        // Second analysis should use cache
        $result2 = $this->service->analyzeMarket($pair);

        // Results should be identical
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        $pair = $this->createTestTradingPair();

        // Mock an API error
        $this->mockClient->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $this->expectException(\Exception::class);
        $this->service->analyzeMarket($pair);
    }

    protected function createTestTradingPair()
    {
        $pair = new TradingPair();
        $pair->symbol = 'BTCUSDT';

        // Create and associate technical indicators
        $technical = new TechnicalIndicator();
        $technical->rsi = 55;
        $technical->macd_line = 0.5;
        $technical->macd_signal = 0.3;
        $technical->macd_histogram = 0.2;
        $technical->bb_upper = 45000;
        $technical->bb_middle = 44000;
        $technical->bb_lower = 43000;
        $pair->setRelation('technicalIndicator', $technical);

        // Create and associate market data
        $market = new MarketData();
        $market->price = 44500;
        $market->volume_24h = 1000000;
        $market->price_change_24h = 2.5;
        $market->volume_change_24h = 1.5;
        $pair->setRelation('marketData', $market);

        // Create and associate sentiment data
        $sentiment = new SentimentData();
        $sentiment->score = 0.7;
        $sentiment->news_sentiment = 0.8;
        $sentiment->social_sentiment = 0.6;
        $sentiment->fear_greed_index = 65;
        $pair->setRelation('sentimentData', collect([$sentiment]));

        return $pair;
    }

    protected function getMockAnalysisResponse()
    {
        return <<<EOT
Market Analysis for BTCUSDT:

1. Market Regime: Trending (Bullish)
   - Strong upward momentum with consistent higher highs
   - Volume supporting price action
   - RSI showing moderate bullish conditions

2. Risk Level: 6/10
   - Elevated due to recent volatility
   - Above average trading volume
   - Positive sentiment might indicate FOMO risk

3. Key Levels:
   Support:
   - Major: $43,000 (Lower BB)
   - Minor: $43,500
   Resistance:
   - Major: $45,000 (Upper BB)
   - Minor: $44,750

4. Trading Opportunities:
   - Long position with tight stops below $43,000
   - Potential breakout above $45,000
   - Scale-in approach recommended

5. Risk Factors:
   - Overbought conditions approaching
   - High social sentiment might indicate crowd euphoria
   - Watch for volume divergence

6. Position Sizing:
   - Recommended: 2-3% of portfolio
   - Scale in: 1% initial, add 1% above $45,000
   - Maximum position: 5% of portfolio
EOT;
    }

    protected function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
