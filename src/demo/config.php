<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
PHPQueue\Base::$queuePath = __DIR__ . '/queues/';
PHPQueue\Base::$workerPath = __DIR__ . '/workers/';
?>