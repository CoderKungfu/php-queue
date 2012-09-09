<?php
namespace PHPQueue;
class Cli
{
	public $options;
	private $queue;
	static private $worker_path;

	public function __construct($options=array())
	{
		self::$worker_path = dirname(dirname(__DIR__)) . '/workers/';
		$this->options = $options;
		if ( !empty($options['queue']) )
		{
			$this->queue = $options['queue'];
		}
	}

	public function add($payload=array())
	{
		$queue = \PHPQueue\Base::getQueue($this->queue, $this->options);
		return \PHPQueue\Base::addJob($queue, $payload);
	}

	public function work()
	{
		$queue = \PHPQueue\Base::getQueue($this->queue, $this->options);
		$newJob = \PHPQueue\Base::getJob($queue);
		$newWorker = $newJob->worker;
		require_once self::$worker_path . '/' . $newWorker . 'Worker.php';
		$workerClassName = $newWorker . 'Worker';
		$workerClass = new $workerClassName($newJob->data);
		$workerClass->beforeJob();
		$workerClass->runJob();
		$workerClass->afterJob();

		$resultData = $workerClass->resultData;
		$queue->beforeUpdate();
		$queue->updateJob($resultData);
		$queue->afterUpdate();
	}
}
?>