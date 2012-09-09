<?php
namespace PHPQueue;
class Job
{
	public $worker;
	public $jobId;
	public $data;
	public function __construct($data=null, $jobId=null)
	{
		$this->jobId = $jobId;
		if (!empty($data))
		{
			if (is_array($data))
			{
				$this->worker = $data['worker'];
				$this->data = $data['data'];
			}
			else if (is_object($data))
			{
				$this->worker = $data->worker;
				$this->data = $data->data;
			}
			else
			{
				try
				{
					$data = json_decode($data, true);
					$this->worker = $data['worker'];
					$this->data = $data['data'];
				}
				catch (Exception $ex){}
			}
		}
	}
}
?>