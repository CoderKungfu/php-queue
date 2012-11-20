<?php
namespace PHPQueue;
abstract class Runner
{
	const RUN_USLEEP = 1000000;
	public $queue_options;
	public $queue_name;
	private $queue;
	public $logger;
	public $log_path;
	public $log_level;

	public function __construct($queue='', $options=array())
	{
		if (!empty($queue))
		{
			$this->queue_name = $queue;
		}
		if (!empty($options))
		{
			$this->queue_options = $options;
		}
		if (
			   !empty($this->queue_options['logPath'])
			&& is_writable($this->queue_options['logPath'])
		)
		{
			$this->log_path = $this->queue_options['logPath'];
		}
		if ( !empty($this->queue_options['logLevel']) )
		{
			$this->log_level = $this->queue_options['logLevel'];
		}
		else
		{
			$this->log_level = Logger::INFO;
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
		if (empty($this->log_path))
		{
			$baseFolder = dirname(dirname(__DIR__));
			$this->log_path = sprintf(
								  '%s/demo/runners/logs/'
								, $baseFolder
							);
		}
		$logFileName = sprintf('%s-%s.log', $this->queue_name, date('Ymd'));
		$this->logger = \PHPQueue\Logger::createLogger(
							  $this->queue_name
							, $this->log_level
							, $this->log_path . $logFileName
						);
	}

	protected function beforeLoop()
	{
		if (empty($this->queue_name))
		{
			throw new \PHPQueue\Exception('Queue name is invalid');
		}
		$this->queue = \PHPQueue\Base::getQueue($this->queue_name, $this->queue_options);
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
			$this->logger->addInfo(sprintf("Running new job (%s) with worker: %s", $newJob->job_id, $newJob->worker));
			try
			{
				$worker = \PHPQueue\Base::getWorker($newJob->worker);
				\PHPQueue\Base::workJob($worker, $newJob);
				$this->logger->addInfo(sprintf('Worker is done. Updating job (%s). Result:', $newJob->job_id), $worker->result_data);
				return \PHPQueue\Base::updateJob($this->queue, $newJob->job_id, $worker->result_data);
			}
			catch (Exception $ex)
			{
				$this->logger->addError($ex->getMessage());
				$this->logger->addInfo(sprintf('Releasing job (%s).', $newJob->job_id));
				$this->queue->releaseJob($newJob->job_id);
				throw $ex;
			}
		}
		$this->logger->addInfo('Sleeping ' . ceil($sleepTime / 1000000) . ' seconds.');
		usleep($sleepTime);
	}
}
?>