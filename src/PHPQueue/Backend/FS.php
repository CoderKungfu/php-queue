<?php
namespace PHPQueue\Backend;

abstract class FS extends Base
{
    public $container;

    abstract public function createContainer($container_name);
    abstract public function deleteContainer($container_name);
    abstract public function listContainers();
    abstract public function listFiles();
    abstract public function copy($src_container, $src_file, $dest_container, $dest_file);
    public function get(){}
    public function add($data=array()){}
    public function setContainer($container_name)
    {
        if (!empty($container_name))
        {
            $this->container = $container_name;
            return true;
        }
        return false;
    }
}