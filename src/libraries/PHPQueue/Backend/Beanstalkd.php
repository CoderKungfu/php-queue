<?php
namespace PHPQueue\Backend;
class Beanstalkd extends Base
{
	public $server_uri;
	public $tube;
	static $reserve_timeout = 1;

	public function __construct($options=array())
	{
		parent::__construct();
		if (!empty($options['server']))
		{
			$this->server_uri = $options['server'];
		}
		if (!empty($options['tube']))
		{
			$this->tube = $options['tube'];
		}
	}

	public function connect()
	{
		$this->connection = new \Pheanstalk\Pheanstalk($this->server_uri);
	}

	/**
	 * @param array $data
	 * @return boolean Status of saving
	 */
	public function add($data=array())
	{
		$this->beforeAdd();
		return $this->getConnection()->useTube($this->tube)->put(json_encode($data));
	}

	/**
	 * @return array
	 */
	public function get()
	{
		$this->beforeGet();
		$newJob = $this->getConnection()->watch($this->tube)->reserve(self::$reserve_timeout);
		if ($newJob == false)
		{
			throw new \PHPQueue\Exception("No job found.");
		}
		$this->last_job = $newJob;
		$this->last_job_id = $newJob->getId();
		$this->afterGet();
		return json_decode($newJob->getData(), true);
	}

	public function clear($jobId=null)
	{
		$this->beforeClear($jobId);
		$this->isJobOpen($jobId);
		$theJob = $this->open_items[$jobId];
		$this->getConnection()->delete($theJob);
		$this->last_job_id = $jobId;
		$this->afterClearRelease();
	}

	public function release($jobId=null)
	{
		$this->beforeRelease($jobId);
		$this->isJobOpen($jobId);
		$theJob = $this->open_items[$jobId];
		$this->getConnection()->release($theJob);
		$this->last_job_id = $jobId;
		$this->afterClearRelease();
	}
}
?>