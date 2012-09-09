<?php
require __DIR__ . '/../vendor/autoload.php';
class SampleQueue extends PHPQueue\JobQueue
{
	public function getJob()
	{
		parent::getJob();
	}
}
?>