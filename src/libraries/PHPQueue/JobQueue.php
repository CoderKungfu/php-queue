<?php
namespace PHPQueue;
abstract class JobQueue
{
	public $lastJobId;

	public function __construct(){}

	public function beforeAdd(){}
	public function addJob($newJob){}
	public function afterAdd(){}

	public function beforeGet(){}
	public function getJob($jobId=null)
	{
		return null;
	}
	public function afterGet(){}

	public function beforeUpdate(){}
	public function updateJob($jobId=null, $resultData=null){}
	public function releaseJob($jobId=null){}
	public function clearJob($jobId=null){}
	public function afterUpdate(){}

	public function onError(){}
}
?>