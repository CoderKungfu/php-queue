<?php
namespace PHPQueue\Backend;
class AmazonSQS extends Base
{
    public $region;
    public $queue_url;
    public $sqs_options = array();
    public $receiving_options = array(
                                      'VisibilityTimeout'   => 10
                                    , 'WaitTimeSeconds'     => 3
                                    , 'MaxNumberOfMessages' => 1
                                );

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['region']))
        {
            $this->region = $options['region'];
        }
        if (!empty($options['queue']))
        {
            $this->queue_url = $options['queue'];
        }
        if (!empty($options['receiving_options']) && is_array($options['receiving_options']))
        {
            $this->receiving_options = array_merge($this->receiving_options, $options['receiving_options']);
        }
        if (!empty($options['sqs_options']) && is_array($options['sqs_options']))
        {
            $this->sqs_options = array_merge($this->sqs_options, $options['sqs_options']);
        }
    }

    public function connect()
    {
        $this->connection = new \AmazonSQS($this->sqs_options);
        $this->connection->set_region($this->region);
    }

    /**
     * @param array $data
     * @return boolean Status of saving
     * @throws \PHPQueue\Exception
     */
    public function add($data=array())
    {
        $this->beforeAdd();
        $response = $this->getConnection()->send_message($this->queue_url, json_encode($data));
        if (!$response->isOK())
        {
            $error = $response->body->Error;
            throw new \PHPQueue\Exception((string)$error->Message, (int)$error->Code);
        }
        return true;
    }

    /**
     * @return array
     * @throws \PHPQueue\Exception
     */
    public function get()
    {
        $this->beforeGet();
        $response = $this->getConnection()->receive_message($this->queue_url, $this->receiving_options);
        if (!$response->isOk())
        {
            $error = $response->body->Error;
            throw new \PHPQueue\Exception((string)$error->Message, (int)$error->Code);
        }
        else
        {
            if (empty($response->body->ReceiveMessageResult->Message))
            {
                return null;
            }
            $message = $response->body->ReceiveMessageResult->Message;
            $this->last_job = $response;
            $this->last_job_id = (string)$message->ReceiptHandle;
            $this->afterGet();
            return json_decode((string)$message->Body, TRUE);
        }
    }

    /**
     * @param string $jobId
     * @return boolean
     * @throws \PHPQueue\Exception
     */
    public function clear($jobId=null)
    {
        $this->beforeClear($jobId);
        $this->isJobOpen($jobId);
        $response = $this->getConnection()->delete_message($this->queue_url, $jobId);
        if (!$response->isOk())
        {
            $error = $response->body->Error;
            throw new \PHPQueue\Exception((string)$error->Message, (int)$error->Code);
        }
        $this->last_job_id = $jobId;
        $this->afterClearRelease();
        return true;
    }

    /**
     * @param string $jobId
     * @return boolean
     * @throws \PHPQueue\Exception If job wasn't retrieved previously.
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