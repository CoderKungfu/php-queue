<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;
use PHPQueue\Exception\JobNotFoundException;

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\ServiceBus\Models\QueueInfo;
use WindowsAzure\ServiceBus\Models\BrokeredMessage;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;

class WindowsAzureServiceBus extends Base
{
    public $connection_string = '';
    public $queue_name = '';

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['queue'])) {
            $this->queue_name = $options['queue'];
        }
        if (!empty($options['connection_string'])) {
            $this->connection_string = $options['connection_string'];
        }
    }

    public function connect()
    {
        if (empty($this->connection_string)) {
            throw new BackendException("Connection string not specified.");
        }
        $this->connection = ServicesBuilder::getInstance()->createServiceBusService($this->connection_string);
    }

    /**
     * @return \WindowsAzure\ServiceBus\ServiceBusRestProxy
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    /**
     * @param  array                                $data
     * @throws \PHPQueue\Exception\BackendException
     * @return boolean                              Status of saving
     */
    public function add($data=array())
    {
        $this->beforeAdd();
        $this->checkQueue();
        try {
            $message = new BrokeredMessage();
            $message->setBody(json_encode($data));
            $this->getConnection()->sendQueueMessage($this->queue_name, $message);
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
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
        $this->checkQueue();
        try {
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();
            $response = $this->getConnection()->receiveQueueMessage($this->queue_name, $options);
            if (empty($response)) {
                throw new JobNotFoundException('No message found.', 404);
            }

            $this->last_job = $response;
            $this->last_job_id = $response->getMessageId();
            $this->afterGet();

            return json_decode($response->getBody(), TRUE);
        } catch (ServiceException $ex) {
            throw new JobNotFoundException($ex->getMessage(), $ex->getCode());
        }
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
            $this->getConnection()->deleteMessage($this->open_items[$jobId]);
            $this->last_job_id = $jobId;
            $this->afterClearRelease();

            return true;
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * @param  string                               $jobId
     * @throws \PHPQueue\Exception\BackendException
     * @return boolean
     */
    public function release($jobId=null)
    {
        $this->beforeRelease($jobId);
        $this->isJobOpen($jobId);
        try {
            $this->getConnection()->unlockMessage($this->open_items[$jobId]);
            $this->last_job_id = $jobId;
            $this->afterClearRelease();

            return true;
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    public function createQueue($queue_name)
    {
        try {
            $queueInfo = new QueueInfo($queue_name);
            $this->getConnection()->createQueue($queueInfo);

            return true;
        } catch (ServiceException $ex) {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    private function checkQueue()
    {
        if (empty($this->queue_name)) {
            throw new BackendException("Queue name not specified.");
        }
        $queue_info = $this->getConnection()->getQueue($this->queue_name);
        if ($this->queue_name != $queue_info->getTitle()) {
            return $this->createQueue($this->queue_name);
        }

        return true;
    }
}
