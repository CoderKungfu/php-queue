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
        if ( !empty($options['preserveGetLine']) ) {
            $this->preserveGetLine = (bool) $options['preserveGetLine'];
        }

        if ( !empty($options['filePath']) ) {
            $this->file_path = $options['filePath'];
        }
    }

    public function connect()
    {
        if ( !is_file($this->file_path) ) {
            file_put_contents($this->file_path, '');
        }
        if (is_writable($this->file_path)) {
            $this->put_handle = fopen($this->file_path, 'a');
            $this->get_handle = fopen($this->file_path, 'r+');
            $this->connection = true;
        } else {
            throw new BackendException(sprintf("File is not writable: %s", $this->file_path));
        }
    }

    public function get($jobId=null)
    {
        $this->beforeGet();
        $this->getConnection();
        if (!is_null($jobId)) {
            $curPos = ftell($this->get_handle);
            rewind($this->get_handle);
            while ($lineJob = fgetcsv($this->get_handle)) {
                if ($lineJob[0] == $jobId) {
                    $data = $lineJob;
                    break;
                }
            }
            fseek($this->get_handle, $curPos);
        } else {
            $data = fgetcsv($this->get_handle);
        }
        $this->last_job = $data;
        $this->last_job_id = time();
        $this->afterGet();

        return $data;
    }

    public function add($data=array())
    {
        $this->beforeAdd($data);
        $this->getConnection();
        if (!is_array($data)) {
            throw new BackendException("Data is not an array.");
        }
        $written_bytes = fputcsv($this->put_handle, $data);

        return ($written_bytes > 0);
    }

    public function clear($jobId=null)
    {
        $this->beforeClear($jobId);
        $this->afterClearRelease();

        return true;
    }

    public function release($jobId=null)
    {
        $this->beforeRelease($jobId);
        $data = $this->open_items[$jobId];
        $this->getConnection();
        $written_bytes = fputcsv($this->put_handle, $data);
        if ($written_bytes < 0) {
            throw new BackendException("Unable to release data.");
        }
        $this->last_job_id = $jobId;
        $this->afterClearRelease();
    }
}
