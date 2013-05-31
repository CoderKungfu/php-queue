<?php
namespace PHPQueue\Backend;

use PHPQueue\Backend\Aws\AmazonS3V1;
use PHPQueue\Backend\Aws\AmazonS3V2;
use PHPQueue\Exception\BackendException;

class AmazonS3 extends Proxy
{
    protected $options;

    public function __construct($options=array())
    {
        $this->options = $options;
    }

    /**
     * @throws \PHPQueue\Exception\BackendException
     * @return \PHPQueue\Backend\Aws\AmazonS3V1|\PHPQueue\Backend\Aws\AmazonS3V2
     */
    public function getBackend()
    {
        if (null === $this->backend) {
            if (class_exists('\Aws\S3\S3Client')) { // SDK v2
                $this->backend = new AmazonS3V2($this->options);
            } elseif (class_exists('\AmazonS3')) { // SDK v1
                $this->backend = new AmazonS3V1($this->options);
            } else {
                throw new BackendException('AWS PHP SDK not found.');
            }
        }

        return $this->backend;
    }

    /**
     * @throws \PHPQueue\Exception\BackendException
     */
    public function setBackend($backend)
    {
        if (!$backend instanceof AmazonS3V1 && !$backend instanceof AmazonS3V2) {
            throw new BackendException('Backend must be instance of AmazonS3V1 or AmazonS3V2.');
        }

        $this->backend = $backend;
    }
}
