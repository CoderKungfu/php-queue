<?php
namespace PHPQueue;

use PHPQueue\Exception\QueueNotFoundException;
use PHPQueue\Exception\WorkerNotFoundException;

class Base
{
    static public $queue_path = null;
    static public $worker_path = null;
    static public $queue_namespace = null;
    static public $worker_namespace = null;
    static private $all_queues = array();
    static private $all_workers = array();

    /**
     * @param string $queue
     * @param array $options
     * @return \PHPQueue\JobQueue
     */
    static public function getQueue($queue)
    {
        if (empty($queue))
        {
            throw new \InvalidArgumentException("Queue name is empty");
        }
        if ( empty(self::$all_queues[$queue]) )
        {
            $className = self::loadAndGetQueueClassName($queue);
            try
            {
                self::$all_queues[$queue] = new $className();
            }
            catch (\Exception $ex)
            {
                throw new QueueNotFoundException($ex->getMessage(), $ex->getCode());
            }
        }
        return self::$all_queues[$queue];
    }

    static protected function loadAndGetQueueClassName($queue_name)
    {
        $class_name = '';
        if (!is_null(self::$queue_path))
        {
            $classFile = self::$queue_path . '/' . $queue_name . 'Queue.php';
            if (file_exists($classFile))
            {
                require_once $classFile;
            }
            else
            {
                throw new QueueNotFoundException("Queue file does not exist: $classFile");
            }
            $class_name =  "\\" . $queue_name . 'Queue';
        }
        if (!is_null(self::$queue_namespace))
        {
            if (!(strpos(self::$queue_namespace, "\\") === 0))
            {
                $class_name = "\\";
            }
            $class_name .= self::$queue_namespace . "\\" . $queue_name;
        }
        return $class_name;
    }

    /**
     * @param \PHPQueue\JobQueue $queue
     * @param array $newJob
     * @return boolean
     */
    static public function addJob(JobQueue $queue, $newJob)
    {
        if (empty($newJob))
        {
            throw new \InvalidArgumentException("Invalid job data.");
        }
        $status = false;
        try
        {
            $queue->beforeAdd($newJob);
            $status = $queue->addJob($newJob);
            $queue->afterAdd();
        }
        catch (\Exception $ex)
        {
            $queue->onError($ex);
            throw $ex;
        }
        return $status;
    }

    /**
     * @param \PHPQueue\JobQueue $queue
     * @param string $jobId
     * @return \PHPQueue\Job
     */
    static public function getJob(JobQueue $queue, $jobId=null)
    {
        $job = null;
        try
        {
            $queue->beforeGet();
            $job = $queue->getJob($jobId);
            $queue->afterGet();
        }
        catch (\Exception $ex)
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
    static public function updateJob(JobQueue $queue, $jobId=null, $resultData=null)
    {
        $status = false;
        try
        {
            $queue->beforeUpdate();
            $queue->updateJob($jobId, $resultData);
            $status = $queue->clearJob($jobId);
            $queue->afterUpdate();
        }
        catch (\Exception $ex)
        {
            $queue->onError($ex);
            $queue->releaseJob($jobId);
            throw $ex;
        }
        return $status;
    }

    /**
     * @param string $worker_name
     * @param array $options
     * @return \PHPQueue\Worker
     * @throws \PHPQueue\Exception
     */
    static public function getWorker($worker_name)
    {
        if (empty($worker_name))
        {
            throw new \InvalidArgumentException("Worker name is empty");
        }
        if ( empty(self::$all_workers[$worker_name]) )
        {
            $className = self::loadAndGetWorkerClassName($worker_name);
            try
            {
                self::$all_workers[$worker_name] = new $className();
            }
            catch (\Exception $ex)
            {
                throw new WorkerNotFoundException($ex->getMessage(), $ex->getCode());
            }
        }
        return self::$all_workers[$worker_name];
    }

    static protected function loadAndGetWorkerClassName($worker_name)
    {
        $class_name = '';
        if (!is_null(self::$worker_path))
        {
            $classFile = self::$worker_path . '/' . $worker_name . 'Worker.php';
            if (file_exists($classFile))
            {
                require_once $classFile;
            }
            else
            {
                throw new WorkerNotFoundException("Worker file does not exist: $classFile");
            }
            $class_name =  "\\" . $worker_name . 'Worker';
        }
        if (!is_null(self::$worker_namespace))
        {
            if (!(strpos(self::$worker_namespace, "\\") === 0))
            {
                $class_name = "\\";
            }
            $class_name .= self::$worker_namespace . "\\" . $worker_name;
        }
        return $class_name;
    }

    /**
     * @param \PHPQueue\Worker $worker
     * @param \PHPQueue\Job $job
     * @return \PHPQueue\Worker
     * @throws \PHPQueue\Exception
     */
    static public function workJob(Worker $worker, Job $job)
    { 
        try
        {
            $worker->beforeJob($job->data);
            $worker->runJob($job);
            $job->onSuccessful();
            $worker->afterJob();
            $worker->onSuccess();
        }
        catch (\Exception $ex)
        {
            $worker->onError($ex);
            $job->onError();
            throw $ex;
        }
        return $worker;
    }

    /**
     * Factory method to instantiate a copy of a backend class.
     * @param string $type Case-sensitive class name.
     * @param array $options Constuctor options.
     * @return \PHPQueue\backend_classname
     * @throws \PHPQueue\Exception
     */
    static public function backendFactory($type, $options=array())
    {
        $backend_classname = '\\PHPQueue\\Backend\\' . $type;
        $obj = new $backend_classname($options);
        if ($obj instanceof $backend_classname)
        {
            return $obj;
        }
        else
        {
            throw new \InvalidArgumentException("Invalid Backend object.");
        }
    }
}