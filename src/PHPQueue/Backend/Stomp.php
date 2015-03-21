<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\JobNotFoundException;
use FuseSource\Stomp\Stomp as FuseStomp;

/**
 * Wrap a STOMP queue
 *
 * This has been tested against the ActiveMQ and ApolloMQ implementations.
 *
 * @author awight@wikimedia.org
 */
class Stomp extends Base
{
    public $queue_name;
    public $uri;
    public $connection;
    public $merge_headers;
    public $read_timeout;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['queue'])) {
            $this->queue_name = $options['queue'];
        }
        if (!empty($options['uri'])) {
            $this->uri = $options['uri'];
        }
        if (!empty($options['merge_headers'])) {
            $truish = array('true', '1', true, 1);
            $this->merge_headers = in_array($options['merge_headers'], $truish, true);
        }
        if (!empty($options['read_timeout'])) {
            $this->read_timeout = (int)$options['read_timeout'];
        }
    }

    public function __destruct()
    {
        if ( $this->connection ) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    public function connect()
    {
        $this->connection = new FuseStomp($this->uri);
        $this->connection->connect();
    }

    /**
     * @param  array               $data
     * @return boolean             Status of saving
     * @throws \PHPQueue\Exception
     */
    public function add($data=array(), $properties=array())
    {
        $this->beforeAdd();

        $body = json_encode($data);
        $this->getConnection()->send($this->queue_name, $body, $properties);

        return true;
    }

    /**
     * @return array
     * @throws \PHPQueue\Exception
     */
    public function get()
    {
        $this->beforeGet();
        $this->getConnection()->subscribe($this->queue_name);
        if ($this->read_timeout) {
            $this->getConnection()->setReadTimeout($this->read_timeout);
        }
        $response = $this->getConnection()->readFrame();
        $this->afterGet();

        $this->getConnection()->unsubscribe($this->queue_name);

        // TODO: We should provide finer-grained control of ack().

        if (!$response) {
            throw new JobNotFoundException('No message found');
        }

        $this->getConnection()->ack($response);

        $message = json_decode($response->body, true);
        if ($this->merge_headers) {
            $message = array_merge($response->headers, $message);
        }
        return $message;
    }

    public function clear($key=null)
    {
        throw new Exception('Not implemented.');
    }
}
