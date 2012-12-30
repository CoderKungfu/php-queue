<?php
namespace PHPQueue;
abstract class JobQueue
{
    public $last_job_id;
    public $last_job;

    public function __construct(){}
    public function getQueueSize(){}

    /**
     * @param  type                $newJob
     * @throws \PHPQueue\Exception
     */
    public function beforeAdd($newJob=null){}
    /**
     * @param  \PHPQueue\Job       $newJob
     * @throws \PHPQueue\Exception
     */
    public function addJob($newJob=null){}
    public function afterAdd(){}

    public function beforeGet(){}
    /**
     * @param  string        $jobId
     * @return \PHPQueue\Job
     */
    public function getJob($jobId=null)
    {
        return null;
    }
    public function afterGet(){}

    public function beforeUpdate(){}
    public function updateJob($jobId=null, $resultData=null){}
    public function releaseJob($jobId=null){}
    public function clearJob($jobId=null){}
    public function afterUpdate(){}

    public function onError(\Exception $ex){}
}
