# PHP-Queue #
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/CoderKungfu/php-queue?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

A unified front-end for different queuing backends. Includes a REST server, CLI interface and daemon runners.

[![Build Status](https://secure.travis-ci.org/CoderKungfu/php-queue.png?branch=master)](https://travis-ci.org/CoderKungfu/php-queue)

## Why PHP-Queue? ##

Implementing a queueing system (eg. Beanstalk, Amazon SQS, RabbitMQ) for your application can be painful:

* Which one is most efficient? Performant?
* Learning curve to effectively implement the queue backend & the libraries.
* Time taken to develop the application codes.
* Vendor locked in, making it impossible to switch.
* Requires massive code change (ie. not flexible) when use case for the queue changes.

PHP-Queue hopes to serve as an abstract layer between your application code and the implementation of the queue.

## Benefits ##

* **Job Queue is Backend agnostic**

	Just refer to the queue by name, what it runs on is independent of the application code. Your code just asks for the next item in the `PHPQueue\JobQueue`, and you'll get a `PHPQueue\Job` object with the `data` and `jobId`.

* **Flexible Job Queue implementation**

	You can decide whether each `PHPQueue\JobQueue` only carries 1 type of work or multiple types of target workers. You control the retrieval of the job data from the Queue Backend and how it is instantiated as a `PHPQueue\Job` object. Each `PHPQueue\Job` object carries information on which workers it is targeted for.

* **Independent Workers**

	Workers are independent of Job Queues. All it needs to worry about is processing the input data and return the resulting data. The queue despatcher will handle the rest. Workers will also be chainable.

* **Powerful**

	The framework is deliberately open-ended and can be adapted to your implementation. It doesn't get in the way of your queue system.
	
	We've build a simple REST server to let you post job data to your queue easily. We also included a CLI interface for adding and triggering workers. All of which you can sub-class and overwrite.
	
	You can also include our core library files into your application and do some powerful heavy lifting.
	
	Several backend drivers are bundled:
    * Memcache
    * Redis
    * MongoDB
    * CSV
    These can be used as the primary job queue server, or for abstract FIFO or key-value data access.

---
## Installation ##

**Installing via Composer**

[Composer](http://getcomposer.org) is a dependency management tool for PHP that allows you to declare the dependencies your project needs and installs them into your project. In order to use the [**PHP-Queue**](https://packagist.org/packages/coderkungfu/php-queue) through Composer, you must do the following:

1. Add `"coderkungfu/php-queue"` as a dependency in your project's `composer.json` file. Visit the [Packagist](https://packagist.org/packages/coderkungfu/php-queue) page for more details.

2. Download and install Composer.

```
curl -s "http://getcomposer.org/installer" | php
```
3. Install your dependencies.

```
php composer.phar install
```

4. All the dependencies should be downloaded into a `vendor` folder.

5. Require Composer's autoloader.

```php
<?php
require_once '/path/to/vendor/autoload.php';
?>
```

## Getting Started ##

You can have a look at the **Demo App** inside `.\vendor\coderkungfu\php-queue\src\demo\` folder for a recommended folder structure.

* `htdocs` folder
	* .htaccess
	* index.php
* `queues` folder
	* \<QueueNameInCamelCase\>Queue.php
* `workers` folder
	* \<WorkerNameInCamelCase\>Worker.php
* `runners` folder
* `cli.php` file
* `config.php` file

I would also recommend putting the autoloader statement and your app configs inside a separate `config.php` file.

**Recommended `config.php` file content:**

```php
<?php
require_once '/path/to/vendor/autoload.php';
PHPQueue\Base::$queue_path = __DIR__ . '/queues/';
PHPQueue\Base::$worker_path = __DIR__ . '/workers/';
?>
```
**Altenative `config.php` file:**

You can also declare your application's namespace for loading the Queues and Workers.

```php
<?php
require_once '/path/to/vendor/autoload.php';
PHPQueue\Base::$queue_namespace = '\MyFabulousApp\Queues';
PHPQueue\Base::$worker_namespace = '\MyFabulousApp\Workers';
?>
```
PHP-Queue will attempt to instantiate the `PHPQueue\JobQueue` and `PHPQueue\Worker` classes using your namespace - appended with the queue/worker name. (ie. `\MyFabulousApp\Queues\Facebook`). 

It might be advisable to use [Composer's Custom Autoloader](http://getcomposer.org/doc/01-basic-usage.md#autoloading) for this.

**Note:**<br/>
*If you declared `PHPQueue\Base::$queue_path` and/or `PHPQueue\Base::$worker_path` together with the namespace, the files will be loaded with `require_once` from those folder path __AND__ instantiated with the namespaced class names.*

## REST Server ##

The default REST server can be used to interface directly with the queues and workers.

Copy the `htdocs` folder in the **Demo App** into your installation. The `index.php` calls the `\PHPQueue\REST::defaultRoutes()` method - which prepares an instance of the `Respect\Rest` REST server. You might need to modify the path of `config.php` within the `index.php` file.

**Recomended installation:** _use a new virtual host and map the `htdocs` as the webroot._

1. Add new job.

```
# Form post
curl -XPOST http://localhost/<QueueName>/ -d "var1=foo&var2=bar"
```

```
# JSON post
curl -XPOST http://localhost/<QueueName>/ -H "Content-Type: application/json" -d '{"var1":"foo","var2":"bar"}'
```

2. Trigger next job.

```
curl -XPUT http://localhost/<QueueName>/
```

Read the [full documentation](https://github.com/Respect/Rest) on `Respect\Rest` to further customize to your application needs (eg. Basic Auth).

## Command Line Interface (CLI) ##

Copy the `cli.php` file from the **Demo App** into your installation. This file implements the `\PHPQueue\Cli` class. You might need to modify the path of `config.php` within the `cli.php` file.

1. Add new job.

```
$ php cli.php <QueueName> add --data '{"boo":"bar","foo":"car"}'
```

2. Trigger next job.

```
$ php cli.php <QueueName> work
```

You can extend the `PHPQueue\Cli` class to customize your own CLI batch jobs (eg. import data from a MySQL DB into a queue).

## Runners ##

You can read more about the [Runners here](https://github.com/CoderKungfu/php-queue/blob/master/demo/runners/README.md).

## Interfaces ##

The queue backends will support one or more of these interfaces:

* AtomicReadBuffer

This is the recommended way to consume messages.  AtomicReadBuffer provides the
popAtomic($callback) interface, which rolls back the popped record if the
callback returns by exception.  For example:
    $queue = new PHPQueue\Backend\PDO($options);

    $queue->popAtomic(function ($message) use ($processor) {
        $processor->churn($message);
    });

The message will only be popped if churn() returns successfully.

* FifoQueueStore

A first in first out queue accessed by push and pop.

---
## License ##

This software is released under the MIT License.

Copyright (C) 2012 Michael Cheng Chi Mun

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
