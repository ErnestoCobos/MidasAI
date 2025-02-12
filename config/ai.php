<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DeepSeek AI Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the DeepSeek AI service.
    | The API key should be set in your environment file.
    |
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-reasoner'),
        'cache' => [
            'enabled' => true,
            'ttl' => 300, // 5 minutes
        ],
        'logging' => [
            'enabled' => true,
            'channel' => 'ai',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Performance Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for tracking AI performance metrics and decisions.
    |
    */
    'tracking' => [
        'enabled' => true,
        'metrics' => [
            'accuracy_threshold' => 0.75,
            'confidence_threshold' => 0.8,
            'performance_window' => 24, // hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Market Analysis Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for market analysis parameters and thresholds.
    |
    */
    'analysis' => [
        'timeframes' => ['1m', '5m', '15m', '1h', '4h', '1d'],
        'indicators' => [
            'rsi_period' => 14,
            'macd_fast' => 12,
            'macd_slow' => 26,
            'macd_signal' => 9,
            'bb_period' => 20,
            'bb_deviation' => 2,
        ],
        'sentiment' => [
            'min_confidence' => 0.6,
            'update_interval' => 300, // 5 minutes
        ],
    ],
];
