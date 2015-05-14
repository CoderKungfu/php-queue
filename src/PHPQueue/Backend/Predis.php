<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;
use PHPQueue\Interfaces\KeyValueStore;
use PHPQueue\Interfaces\FifoQueueStore;

/**
 * NOTE: The FIFO index is not usable as a key-value selector in this backend.
 */
class Predis
    extends Base
    implements FifoQueueStore, KeyValueStore
{
    const TYPE_STRING='string';
    const TYPE_HASH='hash';
    const TYPE_LIST='list';
    const TYPE_SET='set';
    const TYPE_NONE='none';

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

    /** @deprecated */
    public function add($data=array())
    {
        if (empty($data)) {
            throw new BackendException("No data.");
        }
        $this->push($data);
        return true;
    }

    public function push($data)
    {
        $this->beforeAdd();
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        $encoded_data = json_encode($data);
        // Note that we're ignoring the "new length" return value, cos I don't
        // see how to make it useful.
        $this->getConnection()->rpush($this->queue_name, $encoded_data);
    }

    /**
     * @return array|null
     */
    public function pop()
    {
        $this->beforeGet();
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        $data = $this->getConnection()->lpop($this->queue_name);
        if (!$data) {
            return null;
        }
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

    /** @deprecated */
    public function setKey($key=null, $data=null)
    {
        $this->set($key, $data);
        return true;
    }

    /**
     * @param  string              $key
     * @param  array|string        $data
     * @return boolean
     * @throws \PHPQueue\Exception
     */
    public function set($key, $data)
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
        } catch (\Exception $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    /** @deprecated */
    public function getKey($key=null)
    {
        return $this->get($key);
    }

    /**
     * @param  string $key
     * @return mixed
     * @throws \Exception
     */
    public function get($key=null)
    {
        if (!$key) {
            // Deprecated usage.
            return $this->pop();
        }
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
            case self::TYPE_NONE:
                return null;
            default:
                throw new BackendException(sprintf("Data type (%s) not supported yet.", $type));
                break;
        }

        return $data;
    }

    /**
     * @deprecated
     */
    public function clearKey($key=null)
    {
        return $this->clear($key);
    }

    public function clear($key)
    {
        $this->beforeClear($key);
        $num_removed = $this->getConnection()->del($key);

        $this->afterClearRelease();

        return $num_removed > 0;
    }

    public function incrKey($key, $count=1)
    {
        if (!$this->keyExists($key)) {
            return false;
        }
        if ($count === 1) {
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
        if ($count === 1) {
            $status = $this->getConnection()->decr($key);
        } else {
            $status = $this->getConnection()->decrby($key, $count);
        }

        return $status;
    }

    public function keyExists($key)
    {
        $this->beforeGet();
        return $this->getConnection()->exists($key);
    }

    public function hasQueue()
    {
        return !empty($this->queue_name);
    }
}
