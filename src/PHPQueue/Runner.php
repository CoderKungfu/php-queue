<?php
namespace PHPQueue;

use PHPQueue\Exception\Exception;

abstract class Runner
{
    const RUN_USLEEP = 1000000;
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
        if (
               !empty($options['logPath'])
            && is_writable($options['logPath'])
        )
        {
            $this->log_path = $options['logPath'];
        }
        if ( !empty($options['logLevel']) )
        {
            $this->log_level = $options['logLevel'];
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
        $this->loop();
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
        $this->logger = Logger::createLogger(
                              $this->queue_name
                            , $this->log_level
                            , $this->log_path . $logFileName
                        );
    }

    protected function beforeLoop()
    {
        if (empty($this->queue_name))
        {
            throw new Exception('Queue name is invalid');
        }
        $this->queue = Base::getQueue($this->queue_name);
    }

    protected function loop()
    {
        while (true)
        {
            $this->workJob();
        }
    }

    public function workJob()
    {
        $sleepTime = self::RUN_USLEEP;
        $newJob = null;
        try
        {
            $newJob = Base::getJob($this->queue);
        }
        catch (\Exception $ex)
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
                if (empty($newJob->worker))
                {
                    throw new Exception("No worker declared.");
                }
                if (is_string($newJob->worker))
                {
                    $result_data = $this->processWorker($newJob->worker, $newJob);
                }
                else if (is_array($newJob->worker))
                {
                    $this->logger->addInfo(sprintf("Running chained new job (%s) with workers", $new_job->job_id), $newJob->worker);
                    foreach($newJob->worker as $worker_name)
                    {
                        $result_data = $this->processWorker($worker_name, $newJob);
                        $newJob->data = $result_data;
                    }
                }
                return Base::updateJob($this->queue, $newJob->job_id, $result_data);
            }
            catch (\Exception $ex)
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

    protected function processWorker($worker_name, $new_job)
    {
        $this->logger->addInfo(sprintf("Running new job (%s) with worker: %s", $new_job->job_id, $worker_name));
        $worker = Base::getWorker($worker_name);
        Base::workJob($worker, $new_job);
        $this->logger->addInfo(sprintf('Worker is done. Updating job (%s). Result:', $new_job->job_id), $worker->result_data);
        return $worker->result_data;
    }
}