<?php
namespace PHPQueue;
class Job
{
	public $worker;
	public $jobId;
	public $data;
	public function __construct($json=null, $jobId=null)
	{
		$this->jobId = $jobId;
		$raw_data = json_decode($json, true);
		$this->worker = $raw_data['worker'];
		$this->data = $raw_data['data'];
	}
}
?>