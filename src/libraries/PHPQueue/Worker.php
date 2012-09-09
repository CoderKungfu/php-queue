<?php
namespace PHPQueue;
abstract class Worker
{
	public $inputData;
	public $resultData;

	public function __construct(){}

	public function beforeJob($inputData)
	{
		$this->inputData = $inputData;
		$this->resultData = null;
	}
	/**
	 * @param \PHPQueue\Job $jobObject
	 */
	public function runJob($jobObject){}
	public function afterJob(){}

	public function onSuccess(){}
	public function onError(\Exception $ex){}
}
?>