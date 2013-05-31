<?php
namespace PHPQueue\Backend\Aws;

use PHPQueue\Backend\FS;
use PHPQueue\Exception\BackendException;
use Aws\S3\S3Client;
use Aws\S3\Enum\CannedAcl;
use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\BucketAlreadyOwnedByYouException;
use Aws\S3\Exception\NoSuchBucketException;
use Aws\S3\Exception\NoSuchKeyException;
use Aws\S3\Model\ClearBucket;

class AmazonS3V2 extends FS
{
    /**
     * @var \Aws\S3\S3Client
     */
    protected $connection;
    private $region = null;
    private $region_website = null;
    public $s3_options = array();
    public $bucket_privacy = CannedAcl::PRIVATE_ACCESS;
    private $bucket_websites = array();

    public function __construct($options=array())
    {
        parent::__construct();

        if ($options instanceof S3Client) {
            $this->connection = $options;
        } elseif (is_array($options)) {
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
    }

    /**
     * @return \Aws\S3\S3Client
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    /**
     * @param \Aws\S3\S3Client
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function setConnection($connection)
    {
        if (!$connection instanceof S3Client) {
            throw new \InvalidArgumentException('Connection must be an instance of S3Client.');
        }

        return parent::setConnection($connection);
    }

    public function connect()
    {
        $this->connection = S3Client::factory(array(
            'key'    => $this->s3_options['key'],
            'secret' => $this->s3_options['secret'],
            'region' => $this->region
        ));
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

        try {
            $response = $this->getConnection()->deleteObject(array(
                'Bucket' => $this->container,
                'Key' => $key
            ));
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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

        try {
            $this->getConnection()->createBucket(array(
                'Bucket' => $container_name,
                'LocationConstraint' => $this->region,
                'ACL' => $this->bucket_privacy
            ));
            $this->getConnection()->waitUntilBucketExists(array(
                'Bucket' => $container_name
            ));
        } catch (BucketAlreadyOwnedByYouException $exception) {
            return true;
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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

        try {
            $clear = new ClearBucket($this->getConnection(), $container_name);
            $clear->clear();

            $this->getConnection()->deleteBucket(array(
                'Bucket' => $container_name
            ));
            $this->getConnection()->waitUntilBucketNotExists(array(
                'Bucket' => $container_name
            ));
        } catch (NoSuchBucketException $exception) {
            return true;
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
        }

        return true;
    }

    /**
     * @return array
     * @throws \PHPQueue\Exception\BackendException
     */
    public function listContainers()
    {
        $all_containers = array();

        try {
            $response = $this->getConnection()->listBuckets();
            foreach ($response['Buckets'] as $container) {
                $container_name = (string) $container['Name'];
                $all_containers[] = array(
                    'name'   => $container_name,
                    'url'    => $this->getBucketWebsiteURL($container_name),
                    'object' => $container
                );
            }
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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

        $all_files = array();

        try {
            $response = $this->getConnection()->getIterator('ListObjects', array(
                'Bucket' => $this->container
            ));
            foreach ($response as $file) {
                $url = $this->getBucketWebsiteURL($this->container);
                $file_url = !empty($url) ? $url . '/' . $file['Key'] : null;
                $all_files[] = array(
                    'name'   => $file['Key'],
                    'url'    => $file_url,
                    'object' => $file
                );
            }
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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
        try {
            $this->getConnection()->copyObject(array(
                'Bucket' => $dest_container,
                'Key' => $dest_file,
                'CopySource' => urlencode($src_container . '/' . $src_file)
            ));
            $this->getConnection()->waitUntilObjectExists(array(
                'Bucket' => $dest_container,
                'Key'    => $dest_file
            ));
        } catch (NoSuchBucketException $exception) {
            throw new BackendException('Bucket does not exist.');
        } catch (NoSuchKeyException $exception) {
            throw new BackendException(sprintf('File does not exist in bucket (%s): %s', $src_container, $src_file));
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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
            $options = array_merge($options, array('SourceFile' => $file_path));
        } else {
            $options = array('SourceFile' => $file_path);
        }
        try {
            $this->getConnection()->putObject(array_merge(array(
                'Bucket' => $this->container,
                'Key'    => $key
            ), $options));
            $this->getConnection()->waitUntilObjectExists(array(
                'Bucket' => $this->container,
                'Key'    => $key
            ));
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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
            $options = array_merge($options, array('SaveAs' => $destination_file_path));
        } else {
            $options = array('SaveAs' => $destination_file_path);
        }
        try {
            $response = $this->getConnection()->getObject(array_merge(array(
                'Bucket' => $this->container,
                'Key' => $key
            ), $options));
        } catch (S3Exception $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
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
            try {
                $response = $this->getConnection()->getBucketWebsite(array(
                   'Bucket' => $container
                ));
                $website_url = sprintf('https://%s.%s', $container, $this->region_website);
            } catch (S3Exception $exception) {
                $website_url = null;
            }

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
