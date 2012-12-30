<?php
namespace PHPQueue;

use PHPQueue\Exception\Exception;

class Cli
{
    public $queue_name;

    public function __construct($options=array())
    {
        if ( !empty($options['queue']) ) {
            $this->queue_name = $options['queue'];
        }
    }

    public function add($payload=array())
    {
        fwrite(STDOUT, "===========================================================\n");
        fwrite(STDOUT, "Adding Job...");
        $status = false;
        try {
            $queue = Base::getQueue($this->queue_name);
            $status = Base::addJob($queue, $payload);
            fwrite(STDOUT, "Done.\n");
        } catch (\Exception $ex) {
            fwrite(STDOUT, sprintf("Error: %s\n", $ex->getMessage()));
            throw $ex;
        }

        return $status;
    }

    public function peek()
    {
        $newJob = null;
        $queue = Base::getQueue($this->queue_name);
        try {
            $newJob = Base::getJob($queue);
            fwrite(STDOUT, "===========================================================\n");
            fwrite(STDOUT, "Next Job:\n");
            var_dump($newJob);
            fwrite(STDOUT, "\nReleasing Job...\n");
            $queue->releaseJob($newJob->job_id);
        } catch (\Exception $ex) {
            fwrite(STDOUT, "Error: " . $ex->getMessage() . "\n");
        }
    }

    public function work()
    {
        $newJob = null;
        $queue = Base::getQueue($this->queue_name);
        try {
            $newJob = Base::getJob($queue);
            fwrite(STDOUT, "===========================================================\n");
            fwrite(STDOUT, "Next Job:\n");
            var_dump($newJob);
        } catch (\Exception $ex) {
            fwrite(STDOUT, "Error: " . $ex->getMessage() . "\n");
        }

        if (empty($newJob)) {
            fwrite(STDOUT, "Notice: No Job found.\n");

            return;
        }
        try {
            if (empty($newJob->worker)) {
                throw new Exception("No worker declared.");
            }
            if (is_string($newJob->worker)) {
                $result_data = $this->processWorker($newJob->worker, $newJob);
            } elseif (is_array($newJob->worker)) {
                foreach ($newJob->worker as $worker_name) {
                    $result_data = $this->processWorker($worker_name, $newJob);
                    $newJob->data = $result_data;
                }
            }
            fwrite(STDOUT, "Updating job... \n");

            return Base::updateJob($queue, $newJob->job_id, $result_data);
        } catch (\Exception $ex) {
            fwrite(STDOUT, sprintf("\nError occured: %s\n", $ex->getMessage()));
            $queue->releaseJob($newJob->job_id);
            throw $ex;
        }
    }

    protected function processWorker($worker_name, $new_job)
    {
        fwrite(STDOUT, sprintf("Running worker (%s) now... ", $worker_name));
        $newWorker = Base::getWorker($worker_name);
        Base::workJob($newWorker, $new_job);
        fwrite(STDOUT, "Done.\n");

        return $newWorker->result_data;
    }
}
