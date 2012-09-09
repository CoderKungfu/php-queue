<?php
require __DIR__ . '/../vendor/autoload.php';
class SampleWorker extends PHPQueue\Worker
{
	/**
	 * @param \PHPQueue\Job $jobObject
	 */
	public function runJob($jobObject)
	{
		parent::runJob($jobObject);
		$jobObject->onSuccessful();
		$this->resultData = array('var1'=>"Hello Back!");
	}
}
?>