<?php
namespace PHPQueue;
class Logger extends \Monolog\Logger
{
    public static $all_logs = array();

    /**
     * @param  string           $logName
     * @param  int              $logLevel
     * @param  string           $logPath
     * @return \PHPQueue\Logger
     */
    public static function createLogger($logName=null, $logLevel = Logger::WARNING, $logPath=null)
    {
        if (empty(self::$all_logs[$logName])) {
            $logger = new self($logName);
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($logPath, $logLevel));
            self::$all_logs[$logName] = $logger;
        }

        return self::$all_logs[$logName];
    }

    public static function cycleLog($logName, $logLevel = Logger::WARNING, $logPath=null)
    {
        if (!empty(self::$all_logs[$logName])) {
            unset(self::$all_logs[$logName]);
            self::createLogger($logName, $logLevel, $logPath);
        }

        return self::$all_logs[$logName];
    }
}
