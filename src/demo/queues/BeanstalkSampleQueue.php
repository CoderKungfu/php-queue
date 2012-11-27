<?php
class BeanstalkSampleQueue extends PHPQueue\JobQueue
{
    private $dataSource;
    private $sourceConfig = array(
          'server' => '127.0.0.1'
        , 'tube'   => 'queue1'
    );
    private $queueWorker = 'Sample';
    private $resultLog;

    public function __construct()
    {
        $this->dataSource = \PHPQueue\Base::backendFactory('Beanstalkd', $this->sourceConfig);
        $this->resultLog = \PHPQueue\Logger::createLogger(
                              'BeanstalkSampleLogger'
                            , PHPQueue\Logger::INFO
                            , __DIR__ . '/logs/results.log'
                        );
    }

    public function addJob(array $newJob)
    {
        $formatted_data = array('worker'=>$this->queueWorker, 'data'=>$newJob);
        $this->dataSource->add($formatted_data);
        return true;
    }

    public function getJob()
    {
        $data = $this->dataSource->get();
        $nextJob = new \PHPQueue\Job($data, $this->dataSource->last_job_id);
        $this->last_job_id = $this->dataSource->last_job_id;
        return $nextJob;
    }

    public function updateJob($jobId = null, $resultData = null)
    {
        $this->resultLog->addInfo('Result: ID='.$jobId, $resultData);
    }

    public function clearJob($jobId = null)
    {
        $this->dataSource->clear($jobId);
    }

    public function releaseJob($jobId = null)
    {
        $this->dataSource->release($jobId);
    }
}
?>