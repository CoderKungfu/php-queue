<?php
require __DIR__ . '/../config.php';
class SampleRunner extends PHPQueue\Runner{}
$runner = new SampleRunner('Sample');
$runner->run();
?>