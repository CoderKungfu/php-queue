<?php
namespace PHPQueue\Backend\Aws;

use PHPQueue\Backend\FS;
use PHPQueue\Exception\BackendException;

class AmazonS3V1 extends FS
{
    /**
     * @var \AmazonS3
     */
    protected $connection;
    private $region;
    private $region_website;
    public $s3_options = array();
    public $bucket_privacy = \AmazonS3::ACL_PRIVATE;
    private $bucket_websites = array();

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['region'])) {
            $this->region = $options['region'];
        }
        if (!empty($options['region_website'])) {
            $this->region_website = $options['region_website'];
        }
        if (!empty($options['bucket'])) {
            $this->container = $options['bucket'];
        }
        if (!empty($options['s3_options']) && is_array($options['s3_options'])) {
            $this->s3_options = array_merge($this->s3_options, $options['s3_options']);
        }
    }

    /**
     * @return \AmazonS3
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    public function connect()
    {
        $this->connection = new \AmazonS3($this->s3_options);
        $this->connection->set_region($this->region);
    }

    /**
     * @param  string                               $key
     * @return bool
     * @throws \PHPQueue\Exception\BackendException
     */
    public function clear($key = null)
    {
        if (empty($key)) {
            throw new BackendException('Invalid filename: ' . $key);
        }
        if (!$this->getConnection()->if_object_exists($this->container, $key)) {
            throw new BackendException('File not found: ' . $key);
        }
        $response = $this->getConnection()->delete_object($this->container, $key);
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }

        return true;
    }

    /**
     * @param  string                               $container_name
     * @return bool
     * @throws \PHPQueue\Exception\BackendException
     */
    public function createContainer($container_name)
    {
        if (empty($container_name)) {
            throw new BackendException('Invalid Bucket name: ' . $container_name);
        }
        if ($this->getConnection()->if_bucket_exists($container_name)) {
            return true;
        }
        $response = $this->getConnection()->create_bucket($container_name, $this->region, $this->bucket_privacy);
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }

        return true;
    }

    /**
     * @param  string                               $container_name
     * @return bool
     * @throws \PHPQueue\Exception\BackendException
     */
    public function deleteContainer($container_name)
    {
        if (empty($container_name)) {
            throw new BackendException('Invalid Bucket name: ' . $container_name);
        }
        if (!$this->getConnection()->if_bucket_exists($container_name)) {
            return true;
        }
        $response = $this->getConnection()->delete_bucket($container_name);
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }

        return true;
    }

    /**
     * @return array
     * @throws \PHPQueue\Exception\BackendException
     */
    public function listContainers()
    {
        $response = $this->getConnection()->list_buckets();
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }
        $all_containers = array();
        foreach ($response->body->Buckets->Bucket as $container) {
            $container_name = (string) $container->Name;
            $all_containers[] = array(
                  'name'   => $container_name
                , 'url'    => $this->getBucketWebsiteURL($container_name)
                , 'object' => $container
            );
        }

        return $all_containers;
    }

    /**
     * @return array
     * @throws \PHPQueue\Exception\BackendException
     */
    public function listFiles()
    {
        if (empty($this->container)) {
            throw new BackendException('No bucket specified.');
        }
        if (!$this->getConnection()->if_bucket_exists($this->container)) {
            throw new BackendException('Bucket does not exist: ' . $this->container);
        }
        $response = $this->getConnection()->get_object_list($this->container);

        $all_files = array();
        foreach ($response as $file) {
            $url = $this->getBucketWebsiteURL($this->container);
            $file_url = !empty($url) ? $url . '/' . $file : null;
            $all_files[] = array(
                  'name'   => $file
                , 'url'    => $file_url
                , 'object' => $file
            );
        }

        return $all_files;
    }

    /**
     * @param  string                               $src_container
     * @param  string                               $src_file
     * @param  string                               $dest_container
     * @param  string                               $dest_file
     * @return bool
     * @throws \PHPQueue\Exception\BackendException
     */
    public function copy($src_container, $src_file, $dest_container, $dest_file)
    {
        $src_array = array(
              'bucket'   => $src_container
            , 'filename' => $src_file
        );
        $dest_array = array(
              'bucket'   => $dest_container
            , 'filename' => $dest_file
        );
        if (!$this->getConnection()->if_bucket_exists($src_container)) {
            throw new BackendException('Bucket does not exist: ' . $src_container);
        }
        if (!$this->getConnection()->if_bucket_exists($dest_container)) {
            throw new BackendException('Bucket does not exist: ' . $dest_container);
        }
        if (!$this->getConnection()->if_object_exists($src_container, $src_file)) {
            throw new BackendException(sprintf('File does not exist in bucket (%s): %s', $src_container, $src_file));
        }

        $response = $this->getConnection()->copy_object($src_array, $dest_array);
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }

        return true;
    }

    /**
     * @param  string                               $key
     * @param  string                               $file_path
     * @param  array                                $options
     * @return bool
     * @throws \PHPQueue\Exception\BackendException
     */
    public function putFile($key, $file_path = null, $options = array())
    {
        if (empty($key)) {
            throw new BackendException('Invalid filename: ' . $key);
        }
        if (!is_file($file_path)) {
            throw new BackendException('Upload file not found: ' . $file_path);
        }
        if (is_array($options)) {
            $options = array_merge($options, array('fileUpload'=>$file_path));
        } else {
            $options = array('fileUpload'=>$file_path);
        }
        $response = $this->getConnection()->create_object($this->container, $key, $options);
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }

        return true;
    }

    /**
     * @param  string                               $key
     * @param  string                               $destination_path
     * @param  array                                $options
     * @return bool
     * @throws \PHPQueue\Exception\BackendException
     */
    public function fetchFile($key, $destination_path = null, $options = array())
    {
        if (empty($key)) {
            throw new BackendException('Invalid filename: ' . $key);
        }
        if (!is_writable($destination_path)) {
            throw new BackendException('Destination path is not writable: '.$destination_path);
        }
        $destination_file_path = $destination_path . DIRECTORY_SEPARATOR . $key;
        if (is_array($options)) {
            $options = array_merge($options, array('fileDownload'=>$destination_file_path));
        } else {
            $options = array('fileDownload'=>$destination_file_path);
        }
        $response = $this->getConnection()->get_object($this->container, $key, $options);
        if (!$response->isOk()) {
            $error = $response->body->Error;
            throw new BackendException((string) $error->Message, (int) $error->Code);
        }

        return true;
    }

    /**
     * @param $container
     * @return string
     * @throws \PHPQueue\Exception\BackendException
     */
    public function getBucketWebsiteURL($container)
    {
        if (empty($container)) {
            throw new BackendException('No bucket specified.');
        }
        if (empty($this->bucket_websites[$container])) {
            $response = $this->getConnection()->get_website_config($container);
            $website_url = ($response->isOk())
                            ? sprintf('https://%s.%s', $container, $this->region_website)
                            : null;
            $this->bucket_websites[$container] = $website_url;
        }

        return $this->bucket_websites[$container];
    }

    /**
     * @param $region string
     * @param $region_website string
     * @return bool
     */
    public function setRegion($region, $region_website)
    {
        $this->region = $region;
        $this->region_website = $region_website;

        return true;
    }
}
