<?php
namespace PHPQueue;
class Base
{
	static public $queuePath;

	/**
	 * @param string $queue
	 * @param array $options
	 * @return \PHPQueue\JobQueue
	 */
	static public function getQueue($queue=null, $options=array())
	{
		if ( empty($options['classFile']) )
		{
			require_once self::$queuePath . '/' . $queue . 'Queue.php';
		}
		else
		{
			require_once $options['classFile'];
		}
		if ( empty($options['className']) )
		{
			$className = $queue . 'Queue';
		}
		else
		{
			$className = $options['className'];
		}
		return new $className();
	}

	static public function addJob(\PHPQueue\JobQueue $queue, $newJob=array())
	{
		$queue->beforeAdd();
		$queue->addJob($newJob);
		$queue->afterAdd();
		return true;
	}

	/**
	 * @param JobQueue $queue
	 * @param string $jobId
	 * @return \PHPQueue\Job
	 */
	static public function getJob(\PHPQueue\JobQueue $queue, $jobId=null)
	{
		$queue->beforeGet();
		$job = $queue->getJob($jobId);
		$queue->afterGet();
		return $job;
	}

	static public function updateJob(\PHPQueue\JobQueue $queue, $jobId=null, $updateData=null)
	{
		$queue->beforeUpdate();
		$queue->updateJob($jobId, $updateData);
		$queue->afterUpdate();
		return true;
	}
}
Base::$queuePath = dirname(dirname(__DIR__)) . '/queues/';
?>
