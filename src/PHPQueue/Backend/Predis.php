<?php
namespace PHPQueue\Backend;

use Predis\Response\ResponseInterface;
use Predis\Transaction\MultiExec;

use PHPQueue\Exception\BackendException;
use PHPQueue\Interfaces\AtomicReadBuffer;
use PHPQueue\Interfaces\KeyValueStore;
use PHPQueue\Interfaces\FifoQueueStore;
use PHPQueue\Json;

/**
 * Wraps several styles of redis use:
 *     - If constructed with a "order_key" option, the data will be accessible
 *       as a key-value store, and will also provide pop and push using
 *       $data[$order_key] as the FIFO ordering.  If the ordering value is a
 *       timestamp, for example, then the queue will have real-world FIFO
 *       behavior over time, and even if the data comes in out of order, we will
 *       always pop the true oldest record.
 *       If you wish to push to this type of store, you'll also need to provide
 *       the "correlation_key" option so the random-access key can be
 *       extracted from data.
 *     - Pushing scalar data will store it as a queue under queue_name.
 *     - Setting scalar data will store it under the key.
 *     - If data is an array, setting will store it as a hash, under the key.
 *
 * TODO: The different behaviors should be modeled as several backends which
 * perhaps inherit from an AbstractPredis.
 */
class Predis
    extends Base
    implements
        AtomicReadBuffer,
        FifoQueueStore,
        KeyValueStore
{
    const TYPE_STRING='string';
    const TYPE_HASH='hash';
    const TYPE_LIST='list';
    const TYPE_SET='set';
    const TYPE_NONE='none';

    // Internal sub-key to hold the ordering.
    const FIFO_INDEX = 'fifo';

    public $servers;
    public $redis_options = array();
    public $queue_name;
    public $expiry;
    public $order_key;
    public $correlation_key;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['servers'])) {
            $this->servers = $options['servers'];
        }
        if (!empty($options['redis_options']) && is_array($options['redis_options'])) {
            $this->redis_options = array_merge($this->redis_options, $options['redis_options']);
        }
        if (!empty($options['queue'])) {
            $this->queue_name = $options['queue'];
        }
        if (!empty($options['expiry'])) {
            $this->expiry = $options['expiry'];
        }
        if (!empty($options['order_key'])) {
            $this->order_key = $options['order_key'];
            $this->redis_options['prefix'] = $this->queue_name . ':';
        }
        if (!empty($options['correlation_key'])) {
            $this->correlation_key = $options['correlation_key'];
        }
    }

    public function connect()
    {
        if (!$this->servers) {
            throw new BackendException("No servers specified");
        }
        $this->connection = new \Predis\Client($this->servers, $this->redis_options);
    }

    /** @deprecated */
    public function add($data=array())
    {
        if (!$data) {
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
        if ($this->order_key) {
            if (!$this->correlation_key) {
                throw new BackendException("Cannot push to indexed fifo queue without a correlation key.");
            }
            $key = $data[$this->correlation_key];
            if (!$key) {
                throw new BackendException("Cannot push to indexed fifo queue without correlation data.");
            }
            $status = $this->addToIndexedFifoQueue($key, $data);
            if (!self::boolStatus($status)) {
                throw new BackendException('Couldn\'t push to indexed fifo queue: ' . $status->getMessage());
            }
        } else {
            // Note that we're ignoring the "new length" return value, cos I don't
            // see how to make it useful.
            $this->getConnection()->rpush($this->queue_name, $encoded_data);
        }
    }

    /**
     * Remove stale elements at the top of the queue and return the first real entry
     *
     * When data expires, it still leaves a queue entry linking to its
     * correlation ID.  Clear any of these stale entries at the head of
     * the queue.
     *
     * Note that we run this from inside a transaction, to make it less
     * likely that we'll hit a race condition.
     *
     * @param MultiExec $tx transaction we're working within.
     *
     * @return string|null Top element's key, or null if the queue is empty.
     */
    public function peekWithCleanup(MultiExec $tx)
    {
        for (;;) {
            // Look up the first element in the FIFO ordering.
            $values = $tx->zrange(Predis::FIFO_INDEX, 0, 0);
            if ($values) {
                // Use that value as a key into the key-value block.
                $key = $values[0];
                $exists = $tx->exists($key);

                if (!$exists) {
                    // If the data is missing, then remove from the FIFO index.
                    $tx->zrem(Predis::FIFO_INDEX, $key);
                } else {
                    return $key;
                }
            } else {
                break;
            }
        }
        return null;
    }

    /**
     * @return array|null
     */
    public function pop()
    {
        $data = null;
        $this->beforeGet();
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        if ($this->order_key) {
            // Pop the first element.
            // Adapted from https://github.com/nrk/predis/blob/v1.0/examples/transaction_using_cas.php
            $options = array(
                'cas' => true,
                'watch' => self::FIFO_INDEX,
                'retry' => 3,
            );
            $self = $this;
            $this->getConnection()->transaction($options, function ($tx) use (&$data, &$self) {
                // Begin transaction.
                $tx->multi();

                $key = $self->peekWithCleanup($tx);

                if ($key) {
                    // Use that value as a key into the key-value block.
                    $data = $tx->get($key);

                    // Remove from both indexes.
                    $tx->zrem(Predis::FIFO_INDEX, $key);
                    $tx->del($key);
                }
            });
        } else {
            $data = $this->getConnection()->lpop($this->queue_name);
        }
        if (!$data) {
            return null;
        }
        $this->last_job = $data;
        $this->last_job_id = time();
        $this->afterGet();

        return Json::safe_decode($data);
    }

    public function popAtomic($callback) {
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        if ($this->order_key) {
            throw new BackendException("atomicPop not yet supported for zsets");
        }

        // Pop and process the first element, erring on the side of
        // at-least-once processing where the callback might get the same
        // element before it's popped in the case of a race.
        $options = array(
            'cas' => true,
            'watch' => $this->queue_name,
            'retry' => 3,
        );
        $data = null;
        $self = $this;
        $this->getConnection()->transaction($options, function ($tx) use (&$data, $callback, $self) {
            // Begin transaction.
            $tx->multi();

            $data = $tx->lpop($self->queue_name);
            $data = Json::safe_decode($data);
            if ($data !== null) {
                call_user_func($callback, $data);
            }
        });
        return $data;
    }

    /**
     * Return the top element in the queue.
     *
     * @return array|null
     */
    public function peek()
    {
        $data = null;
        $this->beforeGet();
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        if ($this->order_key) {
            // Adapted from https://github.com/nrk/predis/blob/v1.0/examples/transaction_using_cas.php
            $options = array(
                'cas' => true,
                'watch' => self::FIFO_INDEX,
                'retry' => 3,
            );
            $self = $this;
            $this->getConnection()->transaction($options, function ($tx) use (&$data, &$self) {
                // Begin transaction.
                $tx->multi();

                $key = $self->peekWithCleanup($tx);

                if ($key) {
                    // Use that value as a key into the key-value block.
                    $data = $tx->get($key);
                }
            });
        } else {
            $data_range = $this->getConnection()->lrange($this->queue_name, 0, 0);
            if (!$data_range) {
                return null;
            } else {
                // Unpack list.
                $data = $data_range[0];
            }
        }
        if (!$data) {
            return null;
        }
        $this->last_job = $data;
        $this->last_job_id = time();
        $this->afterGet();

        return Json::safe_decode($data);
    }

    public function release($jobId=null)
    {
        $this->beforeRelease($jobId);
        if (!$this->hasQueue()) {
            throw new BackendException("No queue specified.");
        }
        $job_data = $this->open_items[$jobId];
        $status = $this->getConnection()->rpush($this->queue_name, $job_data);
        if (!is_int($status)) {
            throw new BackendException('Unable to save data: ' . $status->getMessage());
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
     * @param  array               $properties
     * @throws \PHPQueue\Exception
     */
    public function set($key, $data, $properties=array())
    {
        if (!$key || !is_string($key)) {
            throw new BackendException("Key is invalid.");
        }
        if (!$data) {
            throw new BackendException("No data.");
        }
        $this->beforeAdd();
        try {
            $status = false;
            if ($this->order_key) {
                $status = $this->addToIndexedFifoQueue($key, $data);
            } elseif (is_array($data)) {
                // FIXME: Assert
                $status = $this->getConnection()->hmset($key, $data);
            } elseif (is_string($data) || is_numeric($data)) {
                if ($this->expiry) {
                    $status = $this->getConnection()->setex($key, $this->expiry, $data);
                } else {
                    $status = $this->getConnection()->set($key, $data);
                }
            }
            if (!self::boolStatus($status)) {
                throw new BackendException('Unable to save data.: ' . $status->getMessage());
            }
        } catch (\Exception $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Store the data under its order and correlation keys
     *
     * @param string $key
     * @param array $data
     * @return Predis\Response\ResponseInterface
     */
    protected function addToIndexedFifoQueue($key, $data)
    {
        $options = array(
            'cas' => true,
            'watch' => self::FIFO_INDEX,
            'retry' => 3,
        );
        $score = $data[$this->order_key];
        $encoded_data = json_encode($data);
        $status = false;
        $expiry = $this->expiry;
        $this->getConnection()->transaction($options, function ($tx) use ($key, $score, $encoded_data, $expiry, &$status) {
            $tx->multi();
            $tx->zadd(Predis::FIFO_INDEX, $score, $key);
            if ($expiry) {
                $status = $tx->setex($key, $expiry, $encoded_data);
            } else {
                $status = $tx->set($key, $encoded_data);
            }
        });
        return $status;
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
        if ($this->order_key) {
            $data = $this->getConnection()->get($key);
            return Json::safe_decode($data);
        }
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

        if ($this->order_key) {
            $result = $this->getConnection()->pipeline()
                ->zrem(self::FIFO_INDEX, $key)
                ->del($key)
                ->execute();

            $num_removed = $result[1];
        } else {
            $num_removed = $this->getConnection()->del($key);
        }

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

        return is_int($status);
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

        return is_int($status);
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

    protected static function boolStatus(ResponseInterface $status) {
        return ($status == 'OK' || $status == 'QUEUED');
    }
}
