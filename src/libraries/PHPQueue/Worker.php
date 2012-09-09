<?php
namespace PHPQueue;
abstract class Worker
{
	public function __construct(){}
	public function beforeJob(){}
	public function runJob(){}
	public function afterJob(){}
	public function onSuccess(){}
	public function onError(){}

}
?>