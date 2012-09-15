<?php
require __DIR__ . '/../vendor/autoload.php';
class SampleRunner extends PHPQueue\Runner
{
	public function setup()
	{
		$this->queue_name = 'Sample';
		$this->queue_options = array();
	}
}
$runner = new SampleRunner();
$runner->run();
?>