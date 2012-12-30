<?php
namespace PHPQueue;
abstract class Worker
{
    public $input_data;
    public $result_data;

    public function __construct(){}

    public function beforeJob($inputData)
    {
        $this->input_data = $inputData;
        $this->result_data = null;
    }
    /**
     * @param \PHPQueue\Job $jobObject
     */
    public function runJob($jobObject){}
    public function afterJob(){}

    public function onSuccess(){}
    public function onError(\Exception $ex){}
}
