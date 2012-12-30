<?php
namespace PHPQueue;
class Job
{
    const OK = 'success';
    const NOT_OK = 'failed';

    public $worker;
    public $job_id;
    public $data;
    public $status;

    /**
     * @param array $data
     * @param mixed $jobId
     */
    public function __construct($data=null, $jobId=null)
    {
        $this->job_id = $jobId;
        if (!empty($data)) {
            if (is_array($data)) {
                $this->worker = $data['worker'];
                $this->data = $data['data'];
            } elseif (is_object($data)) {
                $this->worker = $data->worker;
                $this->data = $data->data;
            } else {
                try {
                    $data = json_decode($data, true);
                    $this->worker = $data['worker'];
                    $this->data = $data['data'];
                } catch (\Exception $ex) {}
            }
        }
    }

    public function isSuccessful()
    {
        return ($this->status == self::OK);
    }

    public function onSuccessful()
    {
        $this->status = self::OK;
    }

    public function onError()
    {
        $this->status = self::NOT_OK;
    }
}
