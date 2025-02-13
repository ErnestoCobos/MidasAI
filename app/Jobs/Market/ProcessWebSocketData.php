<?php

namespace App\Jobs\Market;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemLog;

class ProcessWebSocketData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('market-data');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (isset($this->data['e'])) {
                match ($this->data['e']) {
                    'kline' => ProcessKlineData::dispatch($this->data),
                    'trade' => ProcessTradeData::dispatch($this->data),
                    'ticker' => Process24hTickerData::dispatch($this->data),
                    default => null
                };
            }
        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'ProcessWebSocketData',
                'event' => 'WEBSOCKET_DATA_PROCESSING_ERROR',
                'message' => $e->getMessage(),
                'context' => [
                    'data' => $this->data,
                    'error' => $e->getMessage()
                ]
            ]);

            throw $e;
        }
    }
}
