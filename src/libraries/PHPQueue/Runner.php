<?php
namespace PHPQueue;
abstract class Runner
{
	const RUN_USLEEP = 1000000;
	public $queueOptions;
	public $queueName;
	private $queue;
	public $logger;
	public $logPath;

	public function __construct($queue='', $options=array())
	{
		if (!empty($queue))
		{
			$this->queueName = $queue;
		}
		if (!empty($options))
		{
			$this->queueOptions = $options;
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

	public function setup()
	{
		$this->logPath = dirname(dirname(__DIR__)) . '/runners/logs/' . $this->queueName . '-' . date('Ymd') . '.log';
		$this->logger = new \Monolog\Logger($this->queueName);
		$this->logger->pushHandler(new \Monolog\Handler\StreamHandler($this->logPath, \Monolog\Logger::INFO));
	}

	protected function beforeLoop()
	{
		if (empty($this->queueName))
		{
			throw new \PHPQueue\Exception('Queue name is invalid');
		}
		$this->queue = \PHPQueue\Base::getQueue($this->queueName, $this->queueOptions);
	}

	protected function loop()
	{
		$sleepTime = self::RUN_USLEEP;
		$newJob = null;
		try
		{
			$newJob = \PHPQueue\Base::getJob($this->queue);
		}
		catch (Exception $ex)
		{
			$this->logger->addError($ex->getMessage());
			$sleepTime = self::RUN_USLEEP * 5;
		}
		if (empty($newJob))
		{
			$this->logger->addNotice("No Job found.");
			$sleepTime = self::RUN_USLEEP * 10;
		}
		else
		{
			try
			{
				$worker = \PHPQueue\Base::getWorker($newJob->worker);
				\PHPQueue\Base::workJob($worker, $newJob);
				return \PHPQueue\Base::updateJob($this->queue, $newJob->jobId, $worker->resultData);
			}
			catch (Exception $ex)
			{
				$this->queue->releaseJob($newJob->jobId);
				throw $ex;
			}
		}
		$this->logger->addInfo('Sleeping ' . ceil($sleepTime / 1000000) . ' seconds.');
		usleep($sleepTime);
	}
}
?>