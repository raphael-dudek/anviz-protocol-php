<?php

namespace Anviz\SDK\Utils;

/**
 * Einfacher Logger für Debugging
 */
class Logger
{
    private static $logFile;
    private static $logLevel = 'INFO'; // DEBUG, INFO, WARN, ERROR

    public static function setLogFile(string $file): void
    {
        self::$logFile = $file;
    }

    public static function setLogLevel(string $level): void
    {
        self::$logLevel = $level;
    }

    public static function debug(string $message): void
    {
        self::log('DEBUG', $message);
    }

    public static function info(string $message): void
    {
        self::log('INFO', $message);
    }

    public static function warn(string $message): void
    {
        self::log('WARN', $message);
    }

    public static function error(string $message): void
    {
        self::log('ERROR', $message);
    }

    private static function log(string $level, string $message): void
    {
        if (self::shouldLog($level)) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [$level] $message\n";

            if (self::$logFile) {
                file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
            } else {
                echo $logMessage;
            }
        }
    }

    private static function shouldLog(string $level): bool
    {
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARN' => 2, 'ERROR' => 3];
        return $levels[$level] >= $levels[self::$logLevel];
    }
}
