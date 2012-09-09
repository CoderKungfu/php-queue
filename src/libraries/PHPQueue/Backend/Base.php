<?php
namespace PHPQueue\Backend;
abstract class Base
{
	public $last_job;
	public $last_job_id;
	private $open_items = array();

	public function __construct(){}

	public function beforeAdd($data=null){}
	public function add($data=null){}
	public function afterAdd(){}

	public function beforeGet($jobId=null)
	{
		if (!empty($jobId))
		{
			$this->last_job_id = $jobId;
		}
	}
	public function get($jobId=null){}
	public function afterGet()
	{
		$id = $this->last_job_id;
		$this->open_items[$id] = $this->last_job;
	}

	public function clear($jobId=null)
	{
		if (!empty($jobId))
		{
			$this->last_job_id = $jobId;
		}
	}
	public function release($jobId=null)
	{
		if (!empty($jobId))
		{
			$this->last_job_id = $jobId;
		}
	}
	public function afterClearRelease()
	{
		$id = $this->last_job_id;
		unset($this->open_items[$id]);
	}

	public function onError($ex){}
}
?>