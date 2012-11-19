<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
PHPQueue\Base::$queuePath = dirname(__DIR__) . '/src/demo/queues/';
PHPQueue\Base::$workerPath = dirname(__DIR__) . '/src/demo/workers/';
?>