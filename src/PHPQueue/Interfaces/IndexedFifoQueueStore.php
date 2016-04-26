<?php
namespace PHPQueue\Interfaces;

/**
 * Implemented by backends that provide queue-like access, where each message
 * also has an ID.
 */
interface IndexedFifoQueueStore extends FifoQueueStore
{
    /**
     * @param mixed $value Serializable value.
     * @return string Message ID.
     * @throws \Exception On failure.
     */
    public function push($value);

    /**
     * @return array The next available data.
     * @throws \PHPQueue\Exception\JobNotFoundException When no data is available.
     * @throws \Exception Other failures.
     *
     * @deprecated This is not a safe operation.  Consider using
     *     AtomicReadBuffer::popAtomic instead.
     */
    public function pop();

    /**
     * @param $key string
     * @throws \Exception
     */
    public function clear($key);
}
