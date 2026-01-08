<?php

namespace Velocix\Support;

class Logger
{
    protected $logPath;
    protected $channel = 'velocix';
    protected $maxFiles = 30; // Keep logs for 30 days
    
    // Log levels
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public function __construct($logPath = null)
    {
        $this->logPath = $logPath ?: $this->getDefaultLogPath();
        $this->ensureLogDirectoryExists();
    }

    /**
     * Get default log path
     */
    protected function getDefaultLogPath()
    {
        $basePath = dirname(dirname(dirname(dirname(__DIR__))));
        return $basePath . '/storage/logs';
    }

    /**
     * Ensure log directory exists
     */
    protected function ensureLogDirectoryExists()
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Set log channel
     */
    public function channel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Log emergency message
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert message
     */
    public function alert($message, array $context = [])
    {
        return $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical($message, array $context = [])
    {
        return $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, array $context = [])
    {
        return $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning($message, array $context = [])
    {
        return $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice message
     */
    public function notice($message, array $context = [])
    {
        return $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info message
     */
    public function info($message, array $context = [])
    {
        return $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug message
     */
    public function debug($message, array $context = [])
    {
        return $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log a message to the logs
     */
    public function log($level, $message, array $context = [])
    {
        $logFile = $this->getLogFile();
        $formattedMessage = $this->formatMessage($level, $message, $context);
        
        // Write to log file
        file_put_contents(
            $logFile,
            $formattedMessage . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        // Clean old logs
        $this->cleanOldLogs();

        return true;
    }

    /**
     * Get log file path
     */
    protected function getLogFile()
    {
        $date = date('Y-m-d');
        return $this->logPath . '/' . $this->channel . '-' . $date . '.log';
    }

    /**
     * Format log message
     */
    protected function formatMessage($level, $message, array $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        // Replace placeholders in message with context values
        $message = $this->interpolate($message, $context);
        
        // Build log line
        $logLine = "[{$timestamp}] {$this->channel}.{$level}: {$message}";
        
        // Add context if present
        if (!empty($context)) {
            $logLine .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        return $logLine;
    }

    /**
     * Interpolates context values into the message placeholders
     */
    protected function interpolate($message, array $context = [])
    {
        // Build replacement array with braces around keys
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Clean old log files
     */
    protected function cleanOldLogs()
    {
        $files = glob($this->logPath . '/' . $this->channel . '-*.log');
        
        if (count($files) <= $this->maxFiles) {
            return;
        }

        // Sort files by modification time
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Delete oldest files
        $filesToDelete = array_slice($files, 0, count($files) - $this->maxFiles);
        foreach ($filesToDelete as $file) {
            @unlink($file);
        }
    }

    /**
     * Get all log files
     */
    public function getLogFiles()
    {
        return glob($this->logPath . '/*.log');
    }

    /**
     * Read log file
     */
    public function read($filename, $lines = 100)
    {
        $file = $this->logPath . '/' . $filename;
        
        if (!file_exists($file)) {
            return [];
        }

        $content = file($file);
        return array_slice($content, -$lines);
    }

    /**
     * Clear all logs
     */
    public function clear()
    {
        $files = $this->getLogFiles();
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    /**
     * Clear logs for specific channel
     */
    public function clearChannel($channel)
    {
        $files = glob($this->logPath . '/' . $channel . '-*.log');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
}