<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class ManageLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:manage 
        {action : Action to perform (view/clean/filter)} 
        {--level= : Filter by log level} 
        {--component= : Filter by component}
        {--start= : Start date for filtering}
        {--end= : End date for filtering}
        {--limit=50 : Limit number of results}
        {--export= : Export logs to file (json/csv)}
        {--days=30 : Number of days to keep when cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage system logs - view, clean, filter and export logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'view':
                $this->viewLogs();
                break;
                
            case 'clean':
                $this->cleanLogs();
                break;
                
            case 'filter':
                $this->filterLogs();
                break;
                
            default:
                $this->error('Invalid action. Use view, clean, or filter.');
                return 1;
        }
    }

    /**
     * View recent logs with optional filtering
     */
    protected function viewLogs()
    {
        $query = $this->buildFilteredQuery();
        $logs = $query->latest()->limit($this->option('limit'))->get();
        
        if ($logs->isEmpty()) {
            $this->info('No logs found.');
            return;
        }

        if ($export = $this->option('export')) {
            $this->exportLogs($logs, $export);
            return;
        }

        $this->displayLogsTable($logs);
    }

    /**
     * Clean old logs based on retention days
     */
    protected function cleanLogs()
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days);
        
        $count = SystemLog::where('logged_at', '<', $cutoff)->delete();
        
        $this->info("Cleaned {$count} logs older than {$days} days.");
    }

    /**
     * Filter and display logs based on criteria
     */
    protected function filterLogs()
    {
        $query = $this->buildFilteredQuery();
        $logs = $query->latest()->limit($this->option('limit'))->get();
        
        if ($logs->isEmpty()) {
            $this->info('No logs found matching the filters.');
            return;
        }

        if ($export = $this->option('export')) {
            $this->exportLogs($logs, $export);
            return;
        }

        $this->displayLogsTable($logs);
    }

    /**
     * Build query with applied filters
     */
    protected function buildFilteredQuery()
    {
        $query = SystemLog::query();

        if ($level = $this->option('level')) {
            $query->where('level', strtoupper($level));
        }

        if ($component = $this->option('component')) {
            $query->where('component', $component);
        }

        if ($start = $this->option('start')) {
            $query->where('logged_at', '>=', Carbon::parse($start));
        }

        if ($end = $this->option('end')) {
            $query->where('logged_at', '<=', Carbon::parse($end));
        }

        return $query;
    }

    /**
     * Display logs in a formatted table
     */
    protected function displayLogsTable($logs)
    {
        $headers = ['Time', 'Level', 'Component', 'Event', 'Message'];
        
        $rows = $logs->map(function ($log) {
            return [
                $log->logged_at->format('Y-m-d H:i:s'),
                $log->level,
                $log->component,
                $log->event,
                $this->truncateMessage($log->message)
            ];
        });

        $this->table($headers, $rows);
        
        if ($logs->count() === (int)$this->option('limit')) {
            $this->info("Showing first {$this->option('limit')} results. Use --limit option to see more.");
        }
    }

    /**
     * Export logs to file
     */
    protected function exportLogs($logs, $format)
    {
        $filename = 'logs_export_' . now()->format('Y-m-d_His') . '.' . $format;
        
        if ($format === 'json') {
            File::put(storage_path($filename), $logs->toJson(JSON_PRETTY_PRINT));
        } elseif ($format === 'csv') {
            $handle = fopen(storage_path($filename), 'w');
            
            // Headers
            fputcsv($handle, ['Time', 'Level', 'Component', 'Event', 'Message', 'Context']);
            
            // Data
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->logged_at->format('Y-m-d H:i:s'),
                    $log->level,
                    $log->component,
                    $log->event,
                    $log->message,
                    json_encode($log->context)
                ]);
            }
            
            fclose($handle);
        } else {
            $this->error('Invalid export format. Use json or csv.');
            return;
        }

        $this->info("Logs exported to storage/{$filename}");
    }

    /**
     * Truncate long messages for display
     */
    protected function truncateMessage($message, $length = 100)
    {
        if (strlen($message) <= $length) {
            return $message;
        }
        
        return substr($message, 0, $length - 3) . '...';
    }
}
