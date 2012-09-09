<?php
require __DIR__ . '/../vendor/autoload.php';
class SampleWorker extends PHPQueue\Worker
{
	public function runJob()
	{
		parent::runJob();
	}
}
?>