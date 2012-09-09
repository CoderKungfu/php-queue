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
		$newJob = null;
		$queue = \PHPQueue\Base::getQueue($this->queue, $this->queue_options);
		try
		{
			$newJob = \PHPQueue\Base::getJob($queue);
			echo "===========================================================\n";
			echo "Next Job:\n";
			var_dump($newJob);
		}
		catch (Exception $ex)
		{
			echo "Error: " . $ex->getMessage() . "\n";
		}

		if ( empty($newJob) )
		{
			echo "Notice: No Job found.\n";
			return;
		}
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