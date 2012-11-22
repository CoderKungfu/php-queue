#PHP-Queue#

A unified front-end for different queuing backends. Includes a REST server, CLI interface and daemon runners.

## Why PHP-Queue? ##

The pains of implementing a queueing system (eg. Beanstalk, Amazon SQS, RabbitMQ) for your applciation:

* Which one is most effecient?
* Learning curve to learn and effectively implement the queue backend.
* Time taken to develop the application codes.
* Vendor locked in, making it impossible to switch.
* Not flexible when use case for the queue changes.

PHP-Queue hopes to serve as an abstract layer between your application code and the implementation of the queue.

## Benefits ##

* **Job Queue is Backend agnostic**

	Just refer to the queue by name, what it runs on is independent of the application code. Your code just asks for the next item in the `PHPQueue\JobQueue`, and you'll get a `PHPQueue\Job` object with the `data` and `jobId`.

* **Flexible Job Queue implementation**

	You can decide whether each `PHPQueue\JobQueue` only carries 1 type of work or multiple types of target workers. You control the retrieval of the job data from the Queue Backend and how it is instantiated as a `PHPQueue\Job` object. Each `PHPQueue\Job` object carries information on which workers it is targeted for.

* **Independent Workers**

	Workers are independent of Job Queues. All it needs to worry about is processing the input data and return the resulting data. The queue despatcher will handle the rest. Workers will also be chainable.

---
## Installation ##

**Installating via Composer**

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

	```
<?php
require_once '/path/to/sdk/vendor/autoload.php';
?>
```

## Getting Started ##

You can have a look at the **Demo App** inside `.\vendor\coderkungfu\phpqueue\src\demo\` folder for a recommended folder structure.

* htdocs
	* .htaccess
	* index.php
* queues
	* \<QueueNameInCamelCase\>Queue.php
* workers
	* \<WorkerNameInCamelCase\>Worker.php

I would also recommend putting the autoloader statement and your app configs inside a separate `config.php` file.

**Recommended `config.php` file content:**

```
<?php
require_once '/path/to/sdk/vendor/autoload.php';
PHPQueue\Base::$queue_path = __DIR__ . '/queues/';
PHPQueue\Base::$worker_path = __DIR__ . '/workers/';
?>
```
---
## License ##

This software is released under the MIT License.

Copyright (C) 2012 Michael Cheng Chi Mun

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.