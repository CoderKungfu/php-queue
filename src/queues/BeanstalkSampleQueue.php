<?php
require_once dirname(__DIR__) . '/config.php';

class BeanstalkSampleQueue extends PHPQueue\JobQueue
{
	public $dataSource;
	public $sourceConfig = array(
		  'server' => '127.0.0.1'
		, 'tube'   => 'queue1'
	);
	private $resultLog;

	public function __construct()
	{
		parent::__construct();
		$this->dataSource = new PHPQueue\Backend\Beanstalkd($this->sourceConfig);
		$this->resultLog = \PHPQueue\Logger::startLogger(
							  'BeanstalkSampleLogger'
							, PHPQueue\Logger::INFO
							, __DIR__ . '/results.log'
						);
	}

	public function addJob(array $newJob)
	{
		parent::addJob($newJob);
		$this->dataSource->add($newJob);
		return true;
	}

	public function getJob()
	{
		parent::getJob();
		$data = $this->dataSource->get();
		$nextJob = new \PHPQueue\Job();
		$nextJob->jobId = $this->dataSource->last_job_id;
		$this->lastJobId = $this->dataSource->last_job_id;
		$nextJob->data = $data;
		$nextJob->worker = 'Sample';
		return $nextJob;
	}

	public function updateJob($jobId = null, $resultData = null)
	{
		parent::updateJob($jobId, $resultData);
		$this->resultLog->addInfo('Result: ', $resultData);
	}

	public function clearJob($jobId = null)
	{
		parent::clearJob($jobId);
		$this->dataSource->clear($jobId);
	}
}
?>