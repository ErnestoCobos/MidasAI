<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\WebSocketBinance::class,
        Commands\MonitorWebSocket::class,
        Commands\CleanupMarketData::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Horizon Snapshots
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // Market Data Management
        $schedule->command('market:cleanup --days=30')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        // WebSocket Management
        $schedule->command('websocket:monitor')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Horizon Management
        $schedule->command('horizon:purge')
            ->dailyAt('00:30')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
