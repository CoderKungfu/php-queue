<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

class Predis extends Base
{
    const TYPE_STRING='string';
    const TYPE_HASH='hash';
    const TYPE_LIST='list';
    const TYPE_SET='set';

    public $servers;
    public $redis_options = array();
    public $queue_name;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['servers']) && is_array($options['servers'])) {
            $this->servers = $options['servers'];
        }
        if (!empty($options['redis_options']) && is_array($options['redis_options'])) {
            $this->redis_options = array_merge($this->redis_options, $options['redis_options']);
        }
        if (!empty($options['queue'])) {
            $this->queue_name = $options['queue'];
        }
    }

    public function connect()
    {
        if (empty($this->servers)) {
            throw new BackendException("No servers specified");
        }
        $this->connection = new \Predis\Client($this->servers, $this->redis_options);
    }

    public function add($data=array())
    {
        $this->beforeAdd();
        if (empty($data)) {
            throw new BackendException("No data.");
        }
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        $encoded_data = json_encode($data);
        $this->getConnection()->rpush($this->queue_name, $encoded_data);

        return true;
    }

    public function get()
    {
        $this->beforeGet();
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        if (!$this->keyExists($this->queue_name) || $this->getConnection()->llen($this->queue_name) == 0) {
            return null;
        }
        $data = $this->getConnection()->lpop($this->queue_name);
        $this->last_job = $data;
        $this->last_job_id = time();
        $this->afterGet();

        return json_decode($data, true);
    }

    public function release($jobId=null)
    {
        $this->beforeRelease($jobId);
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        $job_data = $this->open_items[$jobId];
        $status = $this->getConnection()->rpush($this->queue_name, $job_data);
        if (!$status) {
            throw new BackendException("Unable to save data.");
        }
        $this->last_job_id = $jobId;
        $this->afterClearRelease();
    }

    public function clear($jobId=null)
    {
        $this->beforeClear($jobId);
        $this->afterClearRelease();

        return true;
    }

    /**
     * @param  string              $key
     * @param  mixed               $data
     * @return boolean
     * @throws \PHPQueue\Exception
     */
    public function setKey($key=null, $data=null)
    {
        if (empty($key) && !is_string($key)) {
            throw new BackendException("Key is invalid.");
        }
        if (empty($data)) {
            throw new BackendException("No data.");
        }
        $this->beforeAdd();
        try {
            $status = false;
            if (is_array($data)) {
                $status = $this->getConnection()->hmset($key, $data);
            } elseif (is_string($data) || is_numeric($data)) {
                $status = $this->getConnection()->set($key, $data);
            }
            if (!$status) {
                throw new BackendException("Unable to save data.");
            }
        } catch (Exception $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return $status;
    }

    /**
     * @param  string $key
     * @return mixed
     */
    public function getKey($key=null)
    {
        if (!$this->keyExists($key)) {
            return null;
        }
        $this->beforeGet($key);
        $type = $this->getConnection()->type($key);
        switch ($type) {
            case self::TYPE_STRING:
                $data = $this->getConnection()->get($key);
                break;
            case self::TYPE_HASH:
                if (func_num_args() > 2) {
                    $field = func_get_arg(2);
                    $data = $this->getConnection()->hmget($key, $field);
                } else {
                    $data = $this->getConnection()->hgetall($key);
                }
                break;
            default:
                throw new BackendException(sprintf("Data type (%s) not supported yet.", $type));
                break;
        }

        return $data;
    }

    public function clearKey($key=null)
    {
        $this->beforeClear($key);
        $this->getConnection()->del($key);

        return true;
    }

    public function incrKey($key, $count=1)
    {
        if (!$this->keyExists($key)) {
            return false;
        }
        if ($count > 1) {
            $status = $this->getConnection()->incr($key);
        } else {
            $status = $this->getConnection()->incrby($key, $count);
        }

        return $status;
    }

    public function decrKey($key, $count=1)
    {
        if (!$this->keyExists($key)) {
            return false;
        }
        if ($count > 1) {
            $status = $this->getConnection()->decr($key);
        } else {
            $status = $this->getConnection()->decrby($key, $count);
        }

        return $status;
    }

    public function keyExists($key)
    {
        $this->beforeGet();
        if (!$this->getConnection()->exists($key)) {
            return false;
        }

        return true;
    }

    public function hasQueue()
    {
        return !empty($this->queue_name);
    }
}
