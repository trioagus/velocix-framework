<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class LogClearCommand extends Command
{
    protected $signature = 'log:clear';
    protected $description = 'Clear application log files';

    public function handle($args = [])
    {
        $basePath = getcwd();
        $logPath = $basePath . '/storage/logs';

        if (!is_dir($logPath)) {
            $this->error('Log directory does not exist.');
            return;
        }

        // Get channel from argument
        $channel = $args[0] ?? null;

        if ($channel) {
            // Clear specific channel
            $this->clearChannelLogs($logPath, $channel);
        } else {
            // Clear all logs
            $this->clearAllLogs($logPath);
        }
    }

    protected function clearAllLogs($logPath)
    {
        $files = glob($logPath . '/*.log');
        $count = count($files);

        if ($count === 0) {
            $this->info('No log files to clear.');
            return;
        }

        $this->line("Found {$count} log file(s). Clearing...");

        foreach ($files as $file) {
            unlink($file);
        }

        $this->info('✓ All log files cleared successfully!');
    }

    protected function clearChannelLogs($logPath, $channel)
    {
        $files = glob($logPath . '/' . $channel . '-*.log');
        $count = count($files);

        if ($count === 0) {
            $this->info("No log files found for channel: {$channel}");
            return;
        }

        $this->line("Found {$count} log file(s) for channel '{$channel}'. Clearing...");

        foreach ($files as $file) {
            unlink($file);
        }

        $this->info("✓ Logs cleared for channel: {$channel}");
    }
}