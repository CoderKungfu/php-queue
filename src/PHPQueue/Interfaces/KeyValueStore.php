<?php
namespace PHPQueue\Interfaces;

/**
 * Implemented by backends that support key-value retrieval.
 */
interface KeyValueStore
{
    /**
     * @param $key string
     * @param $value mixed Serializable value
     * @param $properties array optional additional message properties
     *     FIXME: Define better.  Are these columns the indexes?  Why separate
     *     from the message?
     * @throws \Exception
     */
    public function set($key, $value, $properties=array());

    /**
     * Look up and return a value by its index value.
     *
     * @param $key string
     * @return array The data.
     * @throws \Exception
     */
    public function get($key);

    /**
     * @param $key string
     * @throws \Exception
     */
    public function clear($key);
}
