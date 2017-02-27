<?php
namespace PHPQueue\Backend;

use MongoClient;

use PHPQueue\Exception\BackendException;
use PHPQueue\Exception\JobNotFoundException;

class MongoDB
    extends Base
{
    public $server_uri;
    public $db_name;
    public $collection_name;
    public $mongo_options = array("connect"=>true);

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['server'])) {
            $this->server_uri = $options['server'];
        }
        if (!empty($options['db']) && is_string($options['db'])) {
            $this->db_name = $options['db'];
        }
        if (!empty($options['collection']) && is_string($options['collection'])) {
            $this->collection_name = $options['collection'];
        }
        if (!empty($options['mongo_options']) && is_array($options['mongo_options'])) {
            $this->mongo_options = array_merge($this->mongo_options, $options['mongo_options']);
        }
    }

    public function connect()
    {
        if (empty($this->server_uri)) {
            throw new BackendException("No server specified");
        }
        $this->connection = new MongoClient($this->server_uri, $this->mongo_options);
    }

    public function getDB()
    {
        if (empty($this->db_name) || !is_string($this->db_name)) {
            throw new BackendException("DB is invalid.");
        }
        $db = $this->db_name;

        return $this->getConnection()->$db;
    }

    public function getCollection()
    {
        if (empty($this->collection_name) || !is_string($this->collection_name)) {
            throw new BackendException("Collection is invalid.");
        }
        $db = $this->getDB();
        $collection = $this->collection_name;

        return $db->$collection;
    }

    /**
     * @deprecated
     */
    public function add($data=null, $key=null)
    {
        $this->set($key, $data);
        return true;
    }

    /**
     * @throws \PHPQueue\Exception\BackendException
     * @return boolean Deprecated (always true)
     */
    public function set($key, $data, $properties=array())
    {
        if (empty($data) || !is_array($data)) {
            throw new BackendException("No data.");
        }
        if (!isset($data['_id'])) {
            $data['_id'] = !empty($key) ? $key : uniqid();
        }
        $this->beforeAdd();
        $the_collection = $this->getCollection();
        $status = $the_collection->insert($data);
        if (!$status) {
            throw new BackendException("Unable to save data.");
        }
        $this->last_job_id = $data['_id'];

        // FIXME: always true.
        return $status;
    }

    /**
     * @param  string $key
     * @return mixed
     */
    public function get($key=null)
    {
        $this->beforeGet($key);
        $cursor = $this->getCollection()->find(array('_id' => $key));
        if ($cursor->count() < 1) {
            return null;
        }
        $cursor->next();
        $data = $cursor->current();
        unset($data['_id']);

        return $data;
    }

    public function clear($key=null)
    {
        $this->beforeClear($key);
        $data = $this->get($key);
        if (is_null($data)) {
            throw new JobNotFoundException("Record not found.");
        }
        $this->getCollection()->remove(array('_id' => $key));
        $this->last_job_id = $key;

        return true;
    }
}
