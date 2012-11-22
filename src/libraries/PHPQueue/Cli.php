<?php
namespace PHPQueue;
class Cli
{
	public $queue_name;

	public function __construct($options=array())
	{
		if ( !empty($options['queue']) )
		{
			$this->queue_name = $options['queue'];
		}
	}

	public function add($payload=array())
	{
		fwrite(STDOUT, "===========================================================\n");
		fwrite(STDOUT, "Adding Job...");
		$status = false;
		try
		{
			$queue = \PHPQueue\Base::getQueue($this->queue_name);
			$status = \PHPQueue\Base::addJob($queue, $payload);
			fwrite(STDOUT, "Done.\n");
		}
		catch (Exception $ex)
		{
			fwrite(STDOUT, sprintf("Error: %s\n", $ex->getMessage()));
			throw $ex;
		}
		return $status;
	}

	public function work()
	{
		$newJob = null;
		$queue = \PHPQueue\Base::getQueue($this->queue_name);
		try
		{
			$newJob = \PHPQueue\Base::getJob($queue);
			fwrite(STDOUT, "===========================================================\n");
			fwrite(STDOUT, "Next Job:\n");
			var_dump($newJob);
		}
		catch (Exception $ex)
		{
			fwrite(STDOUT, "Error: " . $ex->getMessage() . "\n");
		}

		if (empty($newJob))
		{
			fwrite(STDOUT, "Notice: No Job found.\n");
			return;
		}
		try
		{
			fwrite(STDOUT, sprintf("Running worker (%s) now... ", $newJob->worker));
			$newWorker = \PHPQueue\Base::getWorker($newJob->worker);
			\PHPQueue\Base::workJob($newWorker, $newJob);
			fwrite(STDOUT, "Done.\n");
			fwrite(STDOUT, "Updating job... \n");
			return \PHPQueue\Base::updateJob($queue, $newJob->job_id, $newWorker->result_data);
		}
		catch (Exception $ex)
		{
			fwrite(STDOUT, sprintf("\nError occured: %s\n", $ex->getMessage()));
			$queue->releaseJob($newJob->job_id);
			throw $ex;
		}
	}
}
?>