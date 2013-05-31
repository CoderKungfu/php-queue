<?php
namespace PHPQueue\Backend;

abstract class Proxy
{
    protected $backend;

    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    public function getBackend()
    {
        return $this->backend;
    }

    public function __get($property)
    {
        return $this->getBackend()->{$property};
    }

    public function __set($property, $value)
    {
        $this->getBackend()->{$property} = $value;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->getBackend(), $method), $arguments);
    }
}
