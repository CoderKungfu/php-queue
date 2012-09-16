<?php
namespace PHPQueue;
class Logger extends \Monolog\Logger
{
	static public $logJobs = array();

	/**
	 * @param string $logName
	 * @param int $logLevel
	 * @param string $logPath
	 * @return \PHPQueue\Logger
	 */
	static public function startLogger($logName=null, $logLevel = Logger::WARNING, $logPath=null)
	{
		if (empty(self::$logJobs[$logName]))
		{
			$logger = new Logger($logName);
			$logger->pushHandler(new \Monolog\Handler\StreamHandler($logPath, $logLevel));
			self::$logJobs[$logName] = $logger;
		}
		return self::$logJobs[$logName];
	}
}
?>
