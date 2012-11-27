<?php
namespace PHPQueue;
class Logger extends \Monolog\Logger
{
    static public $all_logs = array();

    /**
     * @param string $logName
     * @param int $logLevel
     * @param string $logPath
     * @return \PHPQueue\Logger
     */
    static public function createLogger($logName=null, $logLevel = Logger::WARNING, $logPath=null)
    {
        if (empty(self::$all_logs[$logName]))
        {
            $logger = new Logger($logName);
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($logPath, $logLevel));
            self::$all_logs[$logName] = $logger;
        }
        return self::$all_logs[$logName];
    }
}
?>
