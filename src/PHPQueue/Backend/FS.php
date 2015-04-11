<?php
namespace PHPQueue\Backend;

// TODO: Not sure how this fits in with existing interfaces.
abstract class FS extends Base
{
    public $container;

    abstract public function createContainer($container_name);
    abstract public function deleteContainer($container_name);
    abstract public function listContainers();
    abstract public function listFiles();
    abstract public function copy($src_container, $src_file, $dest_container, $dest_file);
    abstract public function putFile($key, $file_path=null, $options=null);
    abstract public function fetchFile($key, $destination_path=null, $options=null);
    public function get(){}
    public function add($data=array()){}
    public function setContainer($container_name)
    {
        if (!empty($container_name)) {
            $this->container = $container_name;

            return true;
        }

        return false;
    }
}
