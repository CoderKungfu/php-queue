<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

class LocalFS extends FS
{
    public $doc_root;

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['doc_root'])) {
            $this->doc_root = $options['doc_root'];
        }
        if (!empty($options['container'])) {
            $this->container = $options['container'];
        }
    }

    public function connect()
    {
        $this->connection = true;
    }

    public function clear($key = null)
    {
        $file_path = $this->getFullPath($key);
        if (!is_file($file_path)) {
            throw new BackendException('File does not exist: '.$file_path);
        }
        if (!is_writable($file_path)) {
            throw new BackendException('File is not deletable: '.$file_path);
        }
        $status = unlink($file_path);
        if (!$status) {
            throw new BackendException('Unable to delete file: '.$file_path);
        }
        clearstatcache();

        return $status;
    }

    public function createContainer($container_name)
    {
        $dir_path = $this->getContainerPath($container_name);
        if (is_dir($dir_path)) {
            return true;
        }
        $status = mkdir($dir_path, 0777, true);
        if (!$status) {
            throw new BackendException('Unable to create directory: '.$dir_path);
        }
        clearstatcache();

        return $status;
    }

    public function deleteContainer($container_name)
    {
        $dir_path = $this->getContainerPath($container_name);
        $status = rmdir($dir_path);
        if (!$status) {
            throw new BackendException('Unable to delete directory: '.$dir_path);
        }
        clearstatcache();

        return $status;
    }

    public function listContainers()
    {
        if (empty($this->doc_root)) {
            throw new BackendException('Document root is not set.');
        }
        $all_containers = array();
        $dir = new \DirectoryIterator($this->doc_root);
        foreach ($dir as $file_info) {
            if (!$file_info->isDot() && $file_info->isDir()) {
                $all_containers[] = array(
                      'name'   => $file_info->getFilename()
                    , 'url'    => $file_info->getPathname()
                    , 'object' => $file_info
                );
            }
        }

        return $all_containers;
    }

    public function listFiles()
    {
        if (empty($this->doc_root) || empty($this->container)) {
            throw new BackendException('Document root or Container not set.');
        }
        $all_files = array();
        $dir = new \DirectoryIterator($this->getCurrentContainerPath());
        foreach ($dir as $file_info) {
            if (!$file_info->isDot() && $file_info->isFile()) {
                $all_files[] = array(
                      'name'   => $file_info->getFilename()
                    , 'url'    => $file_info->getPathname()
                    , 'object' => $file_info
                );
            }
        }

        return $all_files;
    }

    public function copy($src_container, $src_file, $dest_container, $dest_file)
    {
        $src_path = $this->getContainerPath($src_container) . DIRECTORY_SEPARATOR . $src_file;
        $dest_path = $this->getContainerPath($dest_container) . DIRECTORY_SEPARATOR . $dest_file;
        $status = copy($src_path, $dest_path);
        if (!$status) {
            $msg = sprintf('Unable to copy file: %s to file: %s', $src_path, $dest_path);
            throw new BackendException($msg);
        }
        clearstatcache();

        return $status;
    }

    public function putFile($key, $file_path = null, $options = null)
    {
        $dest_path = $this->getFullPath($key);
        if (!is_file($file_path)) {
            throw new BackendException('Upload file does not exist: '.$file_path);
        }
        if (!is_writable($this->getCurrentContainerPath())) {
            throw new BackendException('Destination Container is not writable: '.$this->container);
        }
        if (is_file($dest_path) && !is_writable($dest_path)) {
            throw new BackendException('Destination file is not writable: '.$dest_path);
        }
        if (is_uploaded_file($file_path)) {
            $status = move_uploaded_file($file_path, $dest_path);
        } else {
            $status = copy($file_path, $dest_path);
        }
        if (!$status) {
            $msg = sprintf('Unable to put file: %s to file: %s', $file_path, $dest_path);
            throw new BackendException($msg);
        }
        clearstatcache();

        return $status;
    }

    public function fetchFile($key, $destination_path = null, $options = null)
    {
        $src_path = $this->getFullPath($key);
        if (!is_file($src_path)) {
            throw new BackendException('File does not exist: '.$src_path);
        }
        if (!is_writable($destination_path)) {
            throw new BackendException('Destination path is not writable: '.$destination_path);
        }
        $destination_file_path = $destination_path . DIRECTORY_SEPARATOR . $key;
        $status = copy($src_path, $destination_file_path);
        if (!$status) {
            $msg = sprintf('Unable to fetch file: %s to file: %s', $src_path, $destination_path);
            throw new BackendException($msg);
        }
        clearstatcache();

        return $status;
    }

    public function hasContainer($container)
    {
        $dir_path = $this->getContainerPath($container);

        return is_dir($dir_path);
    }

    protected function getContainerPath($directory_name)
    {
        if (empty($this->doc_root)) {
            throw new BackendException('Document root is not set.');
        }
        if (empty($directory_name)) {
            throw new BackendException('Invalid directory name.');
        }

        return $this->doc_root . DIRECTORY_SEPARATOR . $directory_name;
    }

    protected function getFullPath($key)
    {
        if (empty($this->doc_root) || empty($this->container)) {
            throw new BackendException('Document root or Container not set.');
        }
        if (empty($key)) {
            throw new BackendException('Invalid file name.');
        }

        return $this->getCurrentContainerPath() . DIRECTORY_SEPARATOR . $key;
    }

    protected function getCurrentContainerPath()
    {
        if (empty($this->container)) {
            throw new BackendException('Container not set.');
        }

        return $this->getContainerPath($this->container);
    }
}
