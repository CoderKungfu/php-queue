<?php
require_once dirname(__DIR__) . '/config.php';
class BeanstalkSample extends PHPQueue\Runner{}
$runner = new BeanstalkSample('BeanstalkSample', array('logPath'=>__DIR__ . '/logs/'));
$runner->run();
?>