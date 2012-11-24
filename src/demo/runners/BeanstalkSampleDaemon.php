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
	fwrite(STDOUT, "Unknown action." . PHP_EOL);
	die();
}
switch($argv[1])
{
	case 'start':
		fwrite(STDOUT, "Starting... ");
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
		fwrite(STDOUT, "[OK]" . PHP_EOL);
		break;
	case 'stop':
		fwrite(STDOUT, "Stopping... ");
		Clio\Daemon::kill($pid_file, true);
		fwrite(STDOUT, "[OK]" . PHP_EOL);
		break;
	default:
		fwrite(STDOUT, "Unknown action." . PHP_EOL);
		break;
}
?>