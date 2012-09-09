<?php
require __DIR__ . '/../vendor/autoload.php';
class SampleQueue extends PHPQueue\JobQueue
{
	private $jobs = array();
	public function addJob($newJob)
	{
		parent::addJob($newJob);
		array_unshift($this->jobs, $newJob);
		return true;
	}

	public function getJob()
	{
		parent::getJob();
		if ( empty($this->jobs) )
		{
			throw new \PHPQueue\Exception("No more jobs.");
		}
		$jobData = array_pop($this->jobs);
		$nextJob = new \PHPQueue\Job();
		$nextJob->data = $jobData;
		$nextJob->worker = 'Sample';
		return $nextJob;
	}

	public function getQueueSize()
	{
		parent::getQueueSize();
		return count($this->jobs);
	}
}
?>