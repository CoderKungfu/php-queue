<?php
namespace PHPQueue\Backend;
abstract class Base
{
    public $last_job;
    public $last_job_id;
    protected $open_items = array();
    protected $connection;

    public function __construct(){}
    abstract public function connect();

    public function beforeAdd($data=null){}
    public function afterAdd(){}

    public function beforeGet($jobId=null)
    {
        if (!empty($jobId)) {
            $this->last_job_id = $jobId;
        }
    }
    public function afterGet()
    {
        $id = $this->last_job_id;
        $this->open_items[$id] = $this->last_job;
    }

    public function beforeClear($jobId=null)
    {
        if (!empty($jobId)) {
            $this->last_job_id = $jobId;
        }
    }

    public function beforeRelease($jobId=null)
    {
        if (!empty($jobId)) {
            $this->last_job_id = $jobId;
        }
    }
    public function release($jobId=null){}
    public function afterClearRelease()
    {
        $id = $this->last_job_id;
        unset($this->open_items[$id]);
    }

    public function onError($ex){}

    public function isJobOpen($jobId)
    {
        if (empty($this->open_items[$jobId])) {
            throw new \PHPQueue\Exception\JobNotFoundException("Job was not previously retrieved.");
        }
    }

    public function getConnection()
    {
        if (is_null($this->connection)) {
            $this->connect();
        }

        return $this->connection;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;

        return true;
    }
}
