<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

class Memcache
    extends Base
{
    public $servers;
    public $is_persistent = false;
    public $use_compression = false;
    public $expiry = 0;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['servers']) && is_array($options['servers'])) {
            $this->servers = $options['servers'];
        }
        if (!empty($options['persistent'])) {
            $this->is_persistent = $options['persistent'];
        }
        if (!empty($options['compress']) && is_bool($options['compress'])) {
            $this->use_compression = $options['compress'];
        }
        if (!empty($options['expiry']) && is_numeric($options['expiry'])) {
            $this->expiry = $options['expiry'];
        }
    }

    public function connect()
    {
        if (empty($this->servers)) {
            throw new BackendException("No servers specified");
        }
        $this->connection = new \Memcache;
        foreach ($this->servers as $server) {
            if (is_string($server)) {
                // TODO: configure port
                $this->connection->addserver($server, 11211, $this->is_persistent);
            } elseif (is_array($server)) {
                call_user_func_array(array($this->connection, 'addserver'), $server);
            } else {
                throw new BackendException("Unknown Memcache server arguments.");
            }
        }
    }

    /**
     * @deprecated Use set($k, $v) and $this->expiry instead.
     * @param  string              $key
     * @param  mixed               $data
     * @param  int                 $expiry
     * @return boolean
     * @throws \PHPQueue\Exception
     */
    public function add($key, $data, $expiry=null)
    {
        $this->set($key, $data, $expiry);
        return true;
    }

    /**
     * @param  string              $key
     * @param  mixed               $data
     * @param  array|int           $properties array is preferred, "expiry" is the only key used here.  Deprecated int argument will also be used as expiry.
     * @throws \PHPQueue\Exception
     */
    public function set($key, $data, $properties=array())
    {
        if (is_array($properties) && isset($properties["expiry"])) {
            $expiry = $properties["expiry"];
        } else if (is_numeric($properties)) {
            $expiry = $properties;
        } else {
            $expiry = $this->expiry;
        }
        if (empty($key) && !is_string($key)) {
            throw new BackendException("Key is invalid.");
        }
        if (empty($data)) {
            throw new BackendException("No data.");
        }
        $this->beforeAdd();
        $status = $this->getConnection()->replace($key, json_encode($data), $this->use_compression, $expiry);
        if ($status == false) {
            $status = $this->getConnection()->set($key, json_encode($data), $this->use_compression, $expiry);
        }
        if (!$status) {
            throw new BackendException("Unable to save data.");
        }
    }

    /**
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->beforeGet($key);

        return json_decode($this->getConnection()->get($key), true);
    }

    public function clear($key)
    {
        $this->beforeClear($key);
        $this->getConnection()->delete($key);
        $this->last_job_id = $key;

        return true;
    }
}
