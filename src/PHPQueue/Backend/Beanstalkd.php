<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;
use PHPQueue\Exception\JobNotFoundException;
use PHPQueue\Interfaces\FifoQueueStore;

class Beanstalkd
    extends Base
    implements FifoQueueStore
{
    public $server_uri;
    public $tube;
    public static $reserve_timeout = 1;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['server'])) {
            $this->server_uri = $options['server'];
        }
        if (!empty($options['tube'])) {
            $this->tube = $options['tube'];
        }
    }

    public function connect()
    {
        $this->connection = new \Pheanstalk\Pheanstalk($this->server_uri);
    }

    /**
     * @deprecated
     * @param  array   $data
     * @return boolean Status of saving
     */
    public function add($data=array(), $DEFAULT_PRIORITY=1024, $DEFAULT_DELAY=0, $DEFAULT_TTR=60)
    {
        $this->push($data, $DEFAULT_PRIORITY, $DEFAULT_DELAY, $DEFAULT_TTR);
        return true;
    }

    /**
     * @param array $data
     * @return integer Primary ID of the new record.
     */
    public function push($data, $DEFAULT_PRIORITY=1024, $DEFAULT_DELAY=0, $DEFAULT_TTR=60)
    {
        $this->beforeAdd();
        $response = $this->getConnection()->useTube($this->tube)->put(json_encode($data), $DEFAULT_PRIORITY, $DEFAULT_DELAY, $DEFAULT_TTR);
        if (!$response) {
            throw new BackendException("Unable to save job.");
        }
        return $response;
    }

    /**
     * @deprecated
     * @return array|null
     */
    public function get()
    {
        return $this->pop();
    }

    /**
     * @return array|null
     */
    public function pop()
    {
        $this->beforeGet();
        $newJob = $this->getConnection()->watch($this->tube)->reserve(self::$reserve_timeout);
        if (!$newJob) {
            return null;
        }
        $this->last_job = $newJob;
        $this->last_job_id = $newJob->getId();
        $this->afterGet();

        return json_decode($newJob->getData(), true);
    }

    public function clear($jobId=null)
    {
        $this->beforeClear($jobId);
        $this->isJobOpen($jobId);
        $theJob = $this->open_items[$jobId];
        $this->getConnection()->delete($theJob);
        $this->last_job_id = $jobId;
        $this->afterClearRelease();

        return true;
    }

    public function release($jobId=null)
    {
        $this->beforeRelease($jobId);
        $this->isJobOpen($jobId);
        $theJob = $this->open_items[$jobId];
        $this->getConnection()->release($theJob);
        $this->last_job_id = $jobId;
        $this->afterClearRelease();

        return true;
    }
}
