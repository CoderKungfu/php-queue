<?php
namespace PHPQueue;
abstract class JobQueue
{
	public function __construct(){}
	public function beforeAdd(){}
	public function addJob(){}
	public function afterAdd(){}

	public function beforeGet(){}
	public function getJob(){}
	public function afterGet(){}

	public function beforeUpdate(){}
	public function updateJob(){}
	public function afterUpdate(){}

	public function onError(){}
}
?>