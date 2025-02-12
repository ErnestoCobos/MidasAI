<?php

namespace App\Services\AI\DeepSeek\Chat;

class ChatService
{
    public function create(array $params)
    {
        $response = $this->makeRequest($params);
        return (object)[
            'content' => $response['choices'][0]['message']['content']
        ];
    }

    protected function makeRequest(array $params)
    {
        // In a real implementation, this would make an HTTP request to the DeepSeek API
        // For testing purposes, we'll return a mock response
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Mock response from DeepSeek API'
                    ]
                ]
            ]
        ];
    }
}
