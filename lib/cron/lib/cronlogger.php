<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class CronLogger implements LoggerInterface
{
    protected $logFileName;
    public function __construct(string $logFileName)
    {
        $this->logFileName = trim($logFileName);
        $logDir = dirname($logFileName);
        if (!file_exists($logDir)) {
            $oldUmask = umask(0);
            mkdir($logDir, 0750, true);
            umask($oldUmask);
        }
    }

    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }


    public function log($level, $message, array $context = array())
    {
        $message = trim($message);
        $time = date('d-m-Y H:i:s');
        $msg = "[{$time}] {$level}: {$message}" . PHP_EOL;
        file_put_contents($this->logFileName, $msg, FILE_APPEND);
    }
}
