<?php
namespace PHPQueue\Backend;
class Beanstalkd extends Base
{
	public $server_uri;
	public $tube;

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
		return $this->connection->useTube($this->tube)->put(json_encode($data));
	}

	/**
	 * @return array
	 */
	public function get()
	{
		$this->beforeGet();
		$newJob = $this->connection->watch($this->tube)->reserve();
		$this->lastJob = $newJob;
		$this->lastJobId = $newJob->getId();
		$this->afterGet();
		return json_decode($newJob->getData(), true);
	}

	public function clear($jobId=null)
	{
		$this->beforeClear();
		$this->isJobOpen($jobId);
		$theJob = $this->open_items[$jobId];
		$this->connection->delete($theJob);
		$this->lastJobId = $jobId;
		$this->afterClearRelease();
	}

	public function release($jobId=null)
	{
		$this->beforeRelease();
		$this->isJobOpen($jobId);
		$theJob = $this->open_items[$jobId];
		$this->connection->release($theJob);
		$this->lastJobId = $jobId;
		$this->afterClearRelease();
	}
}
?>