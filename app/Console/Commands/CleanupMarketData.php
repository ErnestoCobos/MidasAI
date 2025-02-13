<?php

namespace App\Console\Commands;

use App\Models\MarketData;
use App\Models\SystemLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupMarketData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:cleanup
                          {--days=30 : Number of days of data to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old market data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $days = $this->option('days');
            $cutoffDate = Carbon::now()->subDays($days);

            $this->info("Cleaning up market data older than {$cutoffDate}...");

            // Get count before deletion
            $countBefore = MarketData::count();

            // Delete old records
            $deleted = MarketData::where('timestamp', '<', $cutoffDate)->delete();

            // Get count after deletion
            $countAfter = MarketData::count();

            // Log the cleanup
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'MarketDataCleanup',
                'event' => 'DATA_CLEANUP',
                'message' => "Cleaned up market data older than {$days} days",
                'context' => [
                    'records_before' => $countBefore,
                    'records_after' => $countAfter,
                    'records_deleted' => $deleted,
                    'cutoff_date' => $cutoffDate->toDateTimeString()
                ]
            ]);

            $this->info("Cleanup complete. Deleted {$deleted} records.");
            $this->info("Records before: {$countBefore}");
            $this->info("Records after: {$countAfter}");

            return 0;

        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'MarketDataCleanup',
                'event' => 'CLEANUP_ERROR',
                'message' => $e->getMessage()
            ]);

            $this->error("Error cleaning up market data: {$e->getMessage()}");
            return 1;
        }
    }
}
