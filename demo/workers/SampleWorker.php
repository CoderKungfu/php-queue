<?php
class SampleWorker extends PHPQueue\Worker
{
    /**
     * @param \PHPQueue\Job $jobObject
     */
    public function runJob($jobObject)
    {
        parent::runJob($jobObject);
        $jobData = $jobObject->data;
        $jobData['var2'] = "Welcome back!";
        $this->result_data = $jobData;
    }
}
