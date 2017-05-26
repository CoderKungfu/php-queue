# PHP-Queue Runners #

## Basic CLI Runner ##

Here is a basic runner for the a JobQueue named "Simple":

```php
<?php
require_once dirname(__DIR__) . '/config.php';
class SampleRunner extends PHPQueue\Runner{}
$runner = new SampleRunner('Sample');
$runner->run();
?>
```

Just run this in console:

```
$ php SimpleRunner.php
```

The runner will check the queue for new jobs and work the jobs. After working on the job, it will sleep for 1 second before processing the next job. If there are no more jobs in the queue, it will rest for 10 seconds.

### Breakdown ###

1. Create a new PHP file named "SimpleRunner.php" (no naming convention here).
2. Include the config file:

```php
require_once '/path/to/your/config.php';
```

3. Create a new Runner class (extending `PHPQueue\Runner`). The queue name can be defined in the `$queue_name` attribute.

```php
class SampleRunner extends PHPQueue\Runner
{
    public $queue_name = 'Sample';
}
```

4. Instantiate the class and call the `run()` method.

```php
$runner = new SampleRunner();
$runner->run();
```

5. Run the Runner.

```
$ php SimpleRunner.php
```

## PHP Daemon ##

Here's a basic script to start a PHP Daemon (using [`Clio\Clio`](https://packagist.org/packages/clio/clio)).

```php
# BeanstalkSampleStart.php
require_once '/path/to/your/config.php';
Clio\Daemon::work(
	array(
			'pid' => $pid_file,
	),
	function($stdin, $stdout, $sterr)
	{
		class BeanstalkSample extends PHPQueue\Runner{}
		$runner = new BeanstalkSample(
					  'BeanstalkSample'
					, array('logPath'=>__DIR__ . '/logs/'));
		$runner->run();
	}
);
```

Here's a basic script to stop a PHP Daemon (using [`Clio\Clio`](https://packagist.org/packages/clio/clio)).

```php
# BeanstalkSampleStop.php
require_once '/path/to/your/config.php';
Clio\Daemon::kill($pid_file, true);
```

To start/stop the daemon:

```
$ php BeanstalkSampleStart.php
```

```
$ php BeanstalkSampleStop.php
```

*__Note:__ On CentOS, you will need to install `php-process` package: `sudo yum install php-process`*

### The Proper Way ###

All Linux background daemons run like this:

```
$ /etc/init.d/httpd start
```

You can make your PHP-Queue runner start the same way.

#### CentOS ####

1. Combine the start and stop scripts into 1 file. And use PHP's `$argv` ([reference](http://www.php.net/manual/en/reserved.variables.argv.php)) to switch between executing `start` or `stop`:

```php
require_once '/path/to/your/config.php';
$pid_file = '/path/to/process.pid';
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
```

*__Note:__ Some `echo` statements were added to provide some on-screen feedback.*

2. Make your runner executable by adding this to the first line of the PHP script:

```
#!/usr/bin/php
```
	and make the file executable under linux:
	
```
$ chmod a+x BeanstalkSampleDaemon.php
```

	That way, you can call the script directly without involving PHP:
	
```
$ ./BeanstalkSampleDaemon.php
```

3. Move the file to `/etc/init.d/` folder.

```
$ mv BeanstalkSampleDaemon.php /etc/init.d/BeanstalkSampleDaemon
```

	*Notice that I moved it without the `.php` extension. You do not need the extension to be there as this file is now executable on its own.*

4. You can now run this script like a normal daemon:

```
$ /etc/init.d/BeanstalkSampleDaemon start
```

**Optional:**

To make it into a service that starts on boot-up.

1. Add this to the top of your script:

```
# !/usr/bin/php
<?php
#
# BeanstalkSampleDaemon    Starts the PHP-Queue runner for BeanstalkSample
#
# chkconfig:    - 91 91
# description:    Runner for PHP-Queue
#
...
```

*__Note:__ Customize to your specific script.*

2. Run this to add this to the boot up process:

```
$ chkconfig --levels 235 BeanstalkSampleDaemon on
```

3. To delete it:

```
$ chkconfig --del BeanstalkSampleDaemon
```


## References: ##
* [http://superuser.com/questions/126106/how-to-execute-a-shell-script-on-startup](http://superuser.com/questions/126106/how-to-execute-a-shell-script-on-startup)
* [http://lists.centos.org/pipermail/centos/2009-December/086930.html](http://lists.centos.org/pipermail/centos/2009-December/086930.html)
* [http://en.wikipedia.org/wiki/Runlevel](http://en.wikipedia.org/wiki/Runlevel)
