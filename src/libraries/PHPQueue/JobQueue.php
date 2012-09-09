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
	public function getJob(){}
	public function afterGet(){}

	public function beforeUpdate(){}
	public function updateJob($resultData){}
	public function afterUpdate(){}

	public function onError(){}
}
?>