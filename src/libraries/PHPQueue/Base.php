<?php
namespace PHPQueue;
class Exception extends \Exception{}
class Base
{
	static public $queuePath = '';
	static public $workerPath = '';
	static private $allQueues = array();
	static private $allWorkers = array();

	/**
	 * @param string $queue
	 * @param array $options
	 * @return \PHPQueue\JobQueue
	 */
	static public function getQueue($queue=null, $options=array())
	{
		if (empty($queue))
		{
			throw new \PHPQueue\Exception("Queue name is empty");
		}
		if ( empty(self::$allQueues[$queue]) )
		{
			$classFile = self::$queuePath . '/' . $queue . 'Queue.php';
			if ( !empty($options['classFile']) )
			{
				$classFile = $options['classFile'];
			}
			if (file_exists($classFile))
			{
				require_once $classFile;
			}
			else
			{
				throw new \PHPQueue\Exception("Worker file does not exist: $classFile");
			}

			$className =  "\\" . $queue . 'Queue';
			if ( !empty($options['className']) )
			{
				$className = $options['className'];
			}
			if (class_exists($className))
			{
				self::$allQueues[$queue] = new $className();
			}
			else
			{
				throw new \PHPQueue\Exception("Worker class does not exist: $className");
			}
		}
		return self::$allQueues[$queue];
	}

	/**
	 * @param \PHPQueue\JobQueue $queue
	 * @param array $newJob
	 * @return boolean
	 */
	static public function addJob(\PHPQueue\JobQueue $queue, $newJob=array())
	{
		$status = false;
		try
		{
			$queue->beforeAdd();
			$status = $queue->addJob($newJob);
			$queue->afterAdd();
		}
		catch (Exception $ex)
		{
			$queue->onError($ex);
			throw $ex;
		}
		return $status;
	}

	/**
	 * @param JobQueue $queue
	 * @param string $jobId
	 * @return \PHPQueue\Job
	 */
	static public function getJob(\PHPQueue\JobQueue $queue, $jobId=null)
	{
		$job = null;
		try
		{
			$queue->beforeGet();
			$job = $queue->getJob($jobId);
			$queue->afterGet();
		}
		catch (Exception $ex)
		{
			$queue->onError($ex);
			throw $ex;
		}
		return $job;
	}

	/**
	 * @param \PHPQueue\JobQueue $queue
	 * @param string $jobId
	 * @param mixed $resultData
	 * @return boolean
	 */
	static public function updateJob(\PHPQueue\JobQueue $queue, $jobId=null, $resultData=null)
	{
		$status = false;
		try
		{
			$queue->beforeUpdate();
			$queue->updateJob($jobId, $resultData);
			$status = $queue->clearJob($jobId);
			$queue->afterUpdate();
		}
		catch (Exception $ex)
		{
			$queue->onError($ex);
			throw $ex;
		}
		return $status;
	}

	/**
	 * @param string $workerName
	 * @param array $options
	 * @return \PHPQueue\Worker
	 * @throws \PHPQueue\Exception
	 */
	static public function getWorker($workerName=null, $options=array())
	{
		if (empty($workerName))
		{
			throw new \PHPQueue\Exception("Worker name is empty");
		}
		if ( empty(self::$allWorkers[$workerName]) )
		{
			$classFile = self::$queuePath . '/' . $workerName . 'Worker.php';
			if ( !empty($options['classFile']) )
			{
				$classFile = $options['classFile'];
			}
			if ( file_exists($classFile) )
			{
				require_once $classFile;
			}
			else
			{
				throw new \PHPQueue\Exception("Worker file does not exist: $classFile");
			}

			$className = "\\" . $queue . 'Worker';
			if ( !empty($options['className']) )
			{
				$className = $options['className'];
			}
			if ( class_exists($className) )
			{
				self::$allWorkers[$workerName] = new $className();
			}
			else
			{
				throw new \PHPQueue\Exception("Worker class does not exist: $className");
			}
		}
		return self::$allWorkers[$workerName];
	}

	/**
	 * @param \PHPQueue\Worker $worker
	 * @param \PHPQueue\Job $job
	 * @return \PHPQueue\Worker
	 * @throws \PHPQueue\Exception
	 */
	static public function workJob(\PHPQueue\Worker $worker, \PHPQueue\Job $job)
	{
		try
		{
			$worker->beforeJob();
			$worker->runJob($job);
			$worker->afterJob();
			$worker->onSuccess();
		}
		catch (Exception $ex)
		{
			$worker->onError($ex);
			throw $ex;
		}
		return $worker;
	}
}
Base::$queuePath = dirname(dirname(__DIR__)) . '/queues/';
Base::$workerPath = dirname(dirname(__DIR__)) . '/workers/';
?>