#!/usr/bin/php
<?php
#
# BeanstalkSampleDaemon    Starts the PHP-Queue runner for BeanstalkSample
#
# chkconfig:    - 91 91
# description:    Runner for PHP-Queue
#

#require_once '/absolute/path/to/php-queue/src/demo/config.php';
require_once dirname(__DIR__) . '/config.php';
$pid_file = __DIR__ . '/process.pid';
if (empty($argv[1]))
{
	Clio\Console::output("Unknown action.");
	die();
}
switch($argv[1])
{
	case 'start':
		Clio\Console::stdout('Starting... ');
		try
		{
			Clio\Daemon::work(array(
					'pid' => $pid_file,
				),
				function($stdin, $stdout, $sterr)
				{
					class BeanstalkSample extends PHPQueue\Runner{}
					$runner = new BeanstalkSample('BeanstalkSample', array('logPath'=>__DIR__ . '/logs/'));
					$runner->run();
				}
			);
			Clio\Console::output('%g[OK]%n');
		}
		catch (Exception $ex)
		{
			Clio\Console::output('%r[FAILED]%n');
		}
		break;
	case 'stop':
		Clio\Console::stdout('Stopping... ');
		try
		{
			Clio\Daemon::kill($pid_file, true);
			Clio\Console::output('%g[OK]%n');
		}
		catch (Exception $ex)
		{
			Clio\Console::output('%r[FAILED]%n');
		}
		break;
	default:
		Clio\Console::output("Unknown action.");
		break;
}
?>