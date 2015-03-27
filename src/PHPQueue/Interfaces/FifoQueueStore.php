<?php
namespace PHPQueue\Interfaces;

/**
 * Implemented by backends that provide queue-like access.
 */
interface FifoQueueStore
{
    /**
     * @param $value mixed Serializable value
     * @throws \Exception On failure.
     */
    public function push($value);

    /**
     * @return array The next available data.
     * @throws \PHPQueue\Exception\JobNotFoundException When no data is available.
     * @throws \Exception Other failures.
     *
     * TODO: We should provide transactionality to make this operation safer.
     */
    public function pop();
}
