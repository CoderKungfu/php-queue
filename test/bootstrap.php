<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
PHPQueue\Base::$queue_path = dirname(__DIR__) . '/demo/queues/';
PHPQueue\Base::$worker_path = dirname(__DIR__) . '/demo/workers/';
