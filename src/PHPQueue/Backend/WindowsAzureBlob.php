<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\Common\ServiceException;

class WindowsAzureBlob extends FS
{
    public $connection_string = '';

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['container'])) {
            $this->container = $options['container'];
        }
        if (!empty($options['connection_string'])) {
            $this->connection_string = $options['connection_string'];
        }
    }

    /**
     * @return \WindowsAzure\Blob\BlobRestProxy
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    public function connect()
    {
        if (empty($this->connection_string)) {
            throw new BackendException("Connection string not specified.");
        }
        $this->connection = ServicesBuilder::getInstance()->createBlobService($this->connection_string);
    }

    public function createContainer($container_name, $container_options=null)
    {
        if (empty($container_options)) {
            $container_options = new CreateContainerOptions();
            $container_options->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);
        }
        try {
            $this->getConnection()->createContainer($container_name, $container_options);
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return true;
    }

    public function deleteContainer($container_name)
    {
        try {
            $this->getConnection()->deleteContainer($container_name);
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return true;
    }

    public function listContainers()
    {
        $all_containers = array();
        try {
            $containers = $this->getConnection()->listContainers();
            $list_containers = $containers->getContainers();
            foreach ($list_containers as $container) {
                $all_containers[] = array(
                      'name'   => $container->getName()
                    , 'url'    => $container->getUrl()
                    , 'object' => $container
                );
            }
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return $all_containers;
    }

    public function listFiles()
    {
        $all_files = array();
        try {
            $blob_list = $this->getConnection()->listBlobs($this->container);
            $blobs = $blob_list->getBlobs();
            foreach ($blobs as $blob) {
                $all_files[] = array(
                      'name'   => $blob->getName()
                    , 'url'    => $blob->getUrl()
                    , 'object' => $blob
                );
            }
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return $all_files;
    }

    public function put($key, $data=null, $options=null)
    {
        try {
            $this->getConnection()->createBlockBlob($this->container, $key, $data, $options);
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return true;
    }

    public function putFile($key, $data=null, $options=null)
    {
        if (is_string($data) && is_file($data)) {
            $data = fopen($data, 'r');
        }

        return $this->put($key, $data, $options);
    }

    public function clear($key = null)
    {
        try {
            $this->getConnection()->deleteBlob($this->container, $key);
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }

        return true;
    }

    public function fetch($key)
    {
        try {
            $blob = $this->getConnection()->getBlob($this->container, $key);

            return array(
                        'name'   => $key
                      , 'meta'   => $blob->getMetadata()
                      , 'object' => $blob
                  );
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    public function fetchFile($key, $destination=null, $options=null)
    {
        $response = $this->fetch($key);
        $handle = $response['object']->getContentStream();
        $contents = '';
        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        fclose($handle);

        return file_put_contents($destination, $contents);
    }

    public function copy($src_container, $src_file, $dest_container, $dest_file, $options=null)
    {
        try {
            return $this->getConnection()->copyBlob($src_container, $src_file, $dest_container, $dest_file, $options);
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }
}
