<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MonitorWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor WebSocket connection and restart if necessary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $lastMessage = Cache::get('binance_websocket_last_message');
            
            if (!$lastMessage || $lastMessage->diffInMinutes(now()) > 5) {
                // WebSocket might be down, attempt to restart
                $this->restartWebSocket();

                SystemLog::create([
                    'level' => 'WARNING',
                    'component' => 'WebSocketMonitor',
                    'event' => 'WEBSOCKET_RESTART',
                    'message' => 'WebSocket connection appears to be down, attempting restart',
                    'context' => [
                        'last_message' => $lastMessage ? $lastMessage->toDateTimeString() : 'never',
                        'minutes_since_last_message' => $lastMessage ? $lastMessage->diffInMinutes(now()) : null
                    ]
                ]);

                $this->error('WebSocket connection appears to be down, attempting restart');
                return 1;
            }

            // Log healthy status
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'WebSocketMonitor',
                'event' => 'WEBSOCKET_HEALTHY',
                'message' => 'WebSocket connection is healthy',
                'context' => [
                    'last_message' => $lastMessage->toDateTimeString(),
                    'seconds_since_last_message' => $lastMessage->diffInSeconds(now())
                ]
            ]);

            $this->info('WebSocket connection is healthy');
            return 0;

        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'WebSocketMonitor',
                'event' => 'MONITOR_ERROR',
                'message' => $e->getMessage()
            ]);

            $this->error("Error monitoring WebSocket: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Restart the WebSocket connection
     */
    protected function restartWebSocket()
    {
        // Kill existing websocket process
        $pid = Cache::get('websocket_pid');
        if ($pid) {
            exec("kill -9 {$pid}");
        }

        // Start new websocket process
        $command = 'php artisan websocket:binance';
        if (config('services.binance.testnet')) {
            $command .= ' --testnet';
        }

        exec($command . ' > /dev/null 2>&1 & echo $!', $output);
        $newPid = $output[0];

        // Store new PID
        Cache::put('websocket_pid', $newPid, now()->addDay());

        SystemLog::create([
            'level' => 'INFO',
            'component' => 'WebSocketMonitor',
            'event' => 'WEBSOCKET_RESTARTED',
            'message' => 'WebSocket process restarted',
            'context' => [
                'old_pid' => $pid,
                'new_pid' => $newPid
            ]
        ]);
    }
}
