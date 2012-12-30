<?php
require_once dirname(__DIR__) . '/config.php';
class SampleRunner extends PHPQueue\Runner
{
    public $queue_name = 'Sample';
}
$runner = new SampleRunner();
$runner->run();
