<?php
namespace PHPQueue;
abstract class Runner
{
	public $queue_options;
	public $queue_name;
	private $queue;
	public $log;
	static private $worker_path;

	public function __construct($queue='', $options=array())
	{
		self::$worker_path = dirname(dirname(__DIR__)) . '/workers/';
		$this->log = array();
		if (!empty($queue))
		{
			$this->queue_name = $queue;
		}
		else
		{
			$className = __CLASS__;
			$this->queue_name = str_replace('Runner', '', $className);
		}
		if (!empty($options))
		{
			$this->queue_options = $options;
		}
		return $this;
	}

	public function run()
	{
		$this->setup();
		$this->beforeLoop();
		while (true)
		{
			$this->loop();
		}
	}

	public function setup(){}

	protected function beforeLoop()
	{
		if (empty($this->queue_name))
		{
			throw new \PHPQueue\Exception('Queue name is invalid');
		}
		$this->queue = \PHPQueue\Base::getQueue($this->queue, $this->queue_options);
	}

	protected function loop()
	{
		$newJob = null;
		try
		{
			$newJob = \PHPQueue\Base::getJob($this->queue);
		}
		catch (Exception $ex)
		{
			$this->log[] = "Error: " . $ex->getMessage();
		}
		if ( empty($newJob) )
		{
			$this->log[] = "Notice: No Job found.";
			return;
		}
		try
		{
			$worker = \PHPQueue\Base::getWorker($newJob->worker);
			\PHPQueue\Base::workJob($worker, $newJob);
			return \PHPQueue\Base::updateJob($queue, $newJob->jobId, $worker->resultData);
		}
		catch (Exception $ex)
		{
			$this->queue->releaseJob($newJob->jobId);
			throw $ex;
		}
	}
}
?>