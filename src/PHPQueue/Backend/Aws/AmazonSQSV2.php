<?php
namespace PHPQueue\Backend\Aws;

use PHPQueue\Backend\Base;
use PHPQueue\Exception\BackendException;
use PHPQueue\Exception\JobNotFoundException;
use Aws\Sqs\SqsClient;
use Aws\Sqs\Exception\SqsException;

class AmazonSQSV2 extends Base
{
    public $region;
    public $queue_url;
    public $sqs_options = array(
        'key'    => null,
        'secret' => null,
        'region' => null
    );
    public $attribute_options = array(
        'VisibilityTimeout'   => 10
    );
    public $receiving_options = array(
        'WaitTimeSeconds'     => 3,
        'MaxNumberOfMessages' => 1
    );

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['region'])) {
            $this->region = $options['region'];
        }
        if (!empty($options['queue'])) {
            $this->queue_url = $options['queue'];
        }
        if (!empty($options['attribute_options']) && is_array($options['attribute_options'])) {
            $this->attribute_options = array_merge($this->attribute_options, $options['attribute_options']);
        }
        if (!empty($options['receiving_options']) && is_array($options['receiving_options'])) {
            $this->receiving_options = array_merge($this->receiving_options, $options['receiving_options']);
        }
        if (!empty($options['sqs_options']) && is_array($options['sqs_options'])) {
            $this->sqs_options = array_merge($this->sqs_options, $options['sqs_options']);
        }
    }

    /**
     * @param \Aws\Sqs\SqsClient
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function setConnection($connection)
    {
        if (!$connection instanceof SqsClient) {
            throw new \InvalidArgumentException('Connection must be an instance of SqsClient.');
        }

        return parent::setConnection($connection);
    }

    public function connect()
    {
        $this->connection = SqsClient::factory(array(
            'key'    => $this->sqs_options['key'],
            'secret' => $this->sqs_options['secret'],
            'region' => $this->region
        ));

        $this->connection->setQueueAttributes(array(
            'QueueUrl'   => $this->queue_url,
            'Attributes' => $this->attribute_options
        ));
    }

    /**
     * @param  array                                $data
     * @throws \PHPQueue\Exception\BackendException
     * @return boolean                              Status of saving
     */
    public function add($data=array())
    {
        $this->beforeAdd();
        try {
            $this->getConnection()->sendMessage(array(
                'QueueUrl'    => $this->queue_url,
                'MessageBody' => json_encode($data)
            ));
        } catch (SqsException $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
        }

        return true;
    }

    /**
     * @throws \PHPQueue\Exception\JobNotFoundException
     * @return array
     */
    public function get()
    {
        $this->beforeGet();
        try {
            $response = $this->getConnection()->receiveMessage(array_merge(array(
                'QueueUrl' => $this->queue_url
            ), $this->receiving_options));
        } catch (SqsException $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
        }

        $message = $response->getPath('Messages/0');
        if (empty($message)) {
            return null;
        }
        $this->last_job = $response;
        $this->last_job_id = (string) $message['ReceiptHandle'];
        $this->afterGet();

        return json_decode((string) $message['Body'], TRUE);
    }

    /**
     * @param  string                               $jobId
     * @throws \PHPQueue\Exception\BackendException
     * @return boolean
     */
    public function clear($jobId=null)
    {
        $this->beforeClear($jobId);
        $this->isJobOpen($jobId);
        try {
            $response = $this->getConnection()->deleteMessage(array(
                'QueueUrl'      => $this->queue_url,
                'ReceiptHandle' => $jobId
            ));
        } catch (SqsException $exception) {
            throw new BackendException($exception->getMessage(), $exception->getCode());
        }
        $this->last_job_id = $jobId;
        $this->afterClearRelease();

        return true;
    }

    /**
     * @param  string                               $jobId
     * @return boolean
     * @throws \PHPQueue\Exception\BackendException If job wasn't retrieved previously.
     */
    public function release($jobId=null)
    {
        $this->beforeRelease($jobId);
        $this->isJobOpen($jobId);
        $this->last_job_id = $jobId;
        $this->afterClearRelease();

        return true;
    }
}
