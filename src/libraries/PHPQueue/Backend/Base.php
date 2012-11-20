<?php
namespace PHPQueue\Backend;
abstract class Base
{
	public $last_job;
	public $last_job_id;
	protected $open_items = array();
	protected $connection;

	public function __construct(){}
	public function connect(){}

	public function beforeAdd($data=null)
	{
		if (is_null($this->connection))
		{
			$this->connect();
		}
	}
	public function add($data=null){}
	public function afterAdd(){}

	public function beforeGet($jobId=null)
	{
		if (!empty($jobId))
		{
			$this->last_job_id = $jobId;
		}
		if (is_null($this->connection))
		{
			$this->connect();
		}
	}
	public function get($jobId=null){}
	public function afterGet()
	{
		$id = $this->last_job_id;
		$this->open_items[$id] = $this->last_job;
	}

	public function  beforeClear()
	{
		if (is_null($this->connection))
		{
			$this->connect();
		}
		if (!empty($jobId))
		{
			$this->last_job_id = $jobId;
		}
	}
	public function clear($jobId=null){}

	public function beforeRelease()
	{
		if (is_null($this->connection))
		{
			$this->connect();
		}
		if (!empty($jobId))
		{
			$this->last_job_id = $jobId;
		}
	}
	public function release($jobId=null){}
	public function afterClearRelease()
	{
		$id = $this->last_job_id;
		unset($this->open_items[$id]);
	}

	public function onError($ex){}

	public function isJobOpen($jobId)
	{
		if (empty($this->open_items[$jobId]))
		{
			throw new \PHPQueue\Exception("Job was not previously retrieved.");
		}
	}
}
?>