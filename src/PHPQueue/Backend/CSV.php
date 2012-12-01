<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

class CSV extends Base
{
    public $file_path;
    public $put_handle;
    public $get_handle;

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
            $this->file_path = $options['filePath'];
        }
        if ( !file_exists($this->file_path) )
        {
            file_put_contents($this->file_path, '');
        }
        if (is_writable($this->file_path))
        {
            $this->put_handle = fopen($this->file_path, 'a');
            $this->get_handle = fopen($this->file_path, 'r+');
        }
        else
        {
            throw new BackendException(sprintf("File is not writable: %s", $this->file_path));
        }
    }

    public function connect()
    {
    }

    public function clear($jobId=null)
    {
    }

    public function get($jobId=null)
    {
        if (!is_null($jobId))
        {
            $curPos = ftell($this->get_handle);
            rewind($this->get_handle);
            while ($lineJob = fgetcsv($this->get_handle))
            {
                if ($lineJob[0] == $jobId)
                {
                    $lineData = $lineJob;
                    break;
                }
            }
            fseek($this->get_handle, $curPos);
        }
        else
        {
            $lineData = fgetcsv($this->get_handle);
        }
        return $lineData;
    }

    public function add($data=array())
    {
        if (!is_array($data))
        {
            throw new BackendException("Data is not an array.");
        }
        $written_bytes = fputcsv($this->put_handle, $data);
        return ($written_bytes > 0);
    }
}