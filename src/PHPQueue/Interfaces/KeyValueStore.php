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
     * @throws \Exception
     */
    public function set($key, $value, $properties=array());

    /**
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
