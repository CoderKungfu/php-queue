<?php
namespace PHPQueue\Backend;

use PHPQueue\Backend\Aws\AmazonSQSV1;
use PHPQueue\Backend\Aws\AmazonSQSV2;
use PHPQueue\Exception\BackendException;

class AmazonSQS extends Proxy
{
    protected $options;

    public function __construct($options=array())
    {
        $this->options = $options;
    }

    /**
     * @throws \PHPQueue\Exception\BackendException
     * @return \PHPQueue\Backend\Aws\AmazonSQSV1|\PHPQueue\Backend\Aws\AmazonSQSV2
     */
    public function getBackend()
    {
        if (null === $this->backend) {
            if (class_exists('\Aws\Sqs\SqsClient')) { // SDK v2
                $this->backend = new AmazonSQSV2($this->options);
            } elseif (class_exists('\AmazonSQS')) { // SDK v1
                $this->backend = new AmazonSQSV1($this->options);
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
        if (!$backend instanceof AmazonSQSV1 && !$backend instanceof AmazonSQSV2) {
            throw new BackendException('Backend must be instance of AmazonSQSV1 or AmazonSQSV2.');
        }

        $this->backend = $backend;
    }
}
