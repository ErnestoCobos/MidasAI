<?php

namespace App\Services\AI\DeepSeek;

use App\Services\AI\DeepSeek\Chat\ChatService;

class DeepSeekManager
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function chat()
    {
        return new ChatService();
    }
}
