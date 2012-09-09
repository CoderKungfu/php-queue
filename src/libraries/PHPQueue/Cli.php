<?php
namespace PHPQueue;
class Cli
{
	public $queue_options;
	private $queue;
	static private $worker_path;

	public function __construct($options=array())
	{
		self::$worker_path = dirname(dirname(__DIR__)) . '/workers/';
		$this->queue_options = $options;
		if ( !empty($options['queue']) )
		{
			$this->queue = $options['queue'];
		}
	}

	public function add($payload=array())
	{
		$queue = \PHPQueue\Base::getQueue($this->queue, $this->queue_options);
		return \PHPQueue\Base::addJob($queue, $payload);
	}

	public function work()
	{
		$queue = \PHPQueue\Base::getQueue($this->queue, $this->queue_options);
		$newJob = \PHPQueue\Base::getJob($queue);
		try
		{
			$newWorker = \PHPQueue\Base::getWorker($newJob->worker);
			\PHPQueue\Base::workJob($newWorker, $newJob);
			return \PHPQueue\Base::updateJob($queue, $newJob->jobId, $newWorker->resultData);
		}
		catch (Exception $ex)
		{
			$queue->releaseJob($newJob->jobId);
			throw $ex;
		}
	}
}
?>