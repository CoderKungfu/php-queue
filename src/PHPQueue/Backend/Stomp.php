<?php
namespace PHPQueue\Backend;

use FuseSource\Stomp\Stomp as FuseStomp;

use BadMethodCallException;
use PHPQueue\Exception\BackendException;
use PHPQueue\Exception\JobNotFoundException;
use PHPQueue\Interfaces\FifoQueueStore;

/**
 * Wrap a STOMP queue
 *
 * This has been tested against the ActiveMQ and ApolloMQ implementations.
 *
 * @author awight@wikimedia.org
 */
class Stomp
    extends Base
    implements FifoQueueStore
{
    public $queue_name;
    public $uri;
    public $connection;
    public $merge_headers;
    public $read_timeout;
    public $ack = true;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['queue'])) {
            $this->queue_name = $options['queue'];
        }
        if (!empty($options['uri'])) {
            $this->uri = $options['uri'];
        }
        if (isset($options['merge_headers'])) {
            $this->merge_headers = (bool)$options['merge_headers'];
        }
        if (isset($options['read_timeout'])) {
            $this->read_timeout = (int)$options['read_timeout'];
        }
        if (isset($options['ack'])) {
            $this->ack = (bool)$options['ack'];
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
     * @param array $data
     * @param array $properties Optional headers.
     *
     * @throws \PHPQueue\Exception\BackendException
     */
    public function push($data=array(), $properties=array())
    {
        $this->beforeAdd();

        $body = json_encode($data);
        $result = $this->getConnection()->send($this->queue_name, $body, $properties);
        if (!$result) {
            throw new BackendException("Couldn't send a message!");
        }
    }

    /**
     * @return array|null
     * @throws \PHPQueue\Exception\BackendException
     */
    public function pop()
    {
        return $this->readFrame();
    }

    public function set($key, $data=array(), $properties=array())
    {
        $properties['correlation-id'] = $key;
        $this->push($data, $properties);
    }

    /**
     * @return array|null
     */
    public function get($key)
    {
        $properties = array(
            'ack' => 'client',
            'selector' => "JMSCorrelationID='{$key}' OR JMSMessageID='{$key}'",
        );
        return $this->readFrame($properties);
    }

    /**
     * @param array|null $properties Optional selectors.
     */
    protected function readFrame($properties = null)
    {
        $this->beforeGet();
        if ($properties === null) {
            $properties = array('ack' => 'client');
        }
        $result = $this->getConnection()->subscribe($this->queue_name, $properties);
        if (!$result) {
            throw new BackendException("No response when subscribing to queue {$this->queue_name}");
        }
        if ($this->read_timeout) {
            $this->getConnection()->setReadTimeout($this->read_timeout);
        }
        $response = $this->getConnection()->readFrame();
        $this->afterGet();

        if ($response && $this->ack) {
            $this->getConnection()->ack($response);
        }

        $this->getConnection()->unsubscribe($this->queue_name);

        if (!$response) {
            return null;
        }

        $message = json_decode($response->body, true);
        if ($this->merge_headers) {
            $message = array_merge($response->headers, $message);
        }
        return $message;
    }

    public function clear($key=null)
    {
        throw new BadMethodCallException('Not implemented.');
    }
}
