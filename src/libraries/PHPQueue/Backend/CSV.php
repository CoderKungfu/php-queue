<?php
namespace PHPQueue\Backend;
class CSV extends Base
{
	public $filePath;
	public $putHandle;
	public $getHandle;

	public function __construct($options=array())
	{
		parent::__construct();
		$this->preserveGetLine = true;
		if ( !empty($options['preserveGetLine']) )
		{
			$this->preserveGetLine = (bool)$options['preserveGetLine'];
		}

		if ( !empty($options['filePath']) )
		{
			$this->filePath = $options['filePath'];
		}
		if ( !file_exists($this->filePath) )
		{
			file_put_contents($this->filePath, '');
		}
		if (is_writable($this->filePath))
		{
			$this->putHandle = fopen($this->filePath, 'a');
			$this->getHandle = fopen($this->filePath, 'r+');
		}
		else
		{
			throw new \PHPQueue\Exception(sprintf("File is not writable: %s", $this->filePath));
		}
	}

	public function get($jobId=null)
	{
		if (!is_null($jobId))
		{
			$curPos = ftell($this->getHandle);
			rewind($this->getHandle);
			while ($lineJob = fgetcsv($this->getHandle))
			{
				if ($lineJob[0] == $jobId)
				{
					$lineData = $lineJob;
					break;
				}
			}
			fseek($this->getHandle, $curPos);
		}
		else
		{
			$lineData = fgetcsv($this->getHandle);
		}
		return $lineData;
	}

	public function add($data=array())
	{
		if (!is_array($data))
		{
			throw new \PHPQueue\Exception("Data is not an array.");
		}
		$written_bytes = fputcsv($this->putHandle, $data);
		return ($written_bytes > 0);
	}
}
?>