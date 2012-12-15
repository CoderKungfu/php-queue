<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;
use PHPQueue\Exception\JobNotFoundException;

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\ServiceBus\Models\QueueInfo;
use WindowsAzure\ServiceBus\models\BrokeredMessage;
use WindowsAzure\ServiceBus\models\ReceiveMessageOptions;

class WindowsAzureServiceBus extends Base
{
    public $connection_string = '';
    public $queue_name = '';

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['queue']))
        {
            $this->queue_name = $options['queue'];
        }
        if (!empty($options['connection_string']))
        {
            $this->connection_string = $options['connection_string'];
        }
    }

    public function connect()
    {
        if (empty($this->connection_string))
        {
            throw new BackendException("Connection string not specified.");
        }
        $this->connection = ServicesBuilder::getInstance()->createServiceBusService($this->connection_string);
    }

    

    /**
     * @param array $data
     * @return boolean Status of saving
     * @throws \PHPQueue\Exception
     */
    public function add($data=array())
    {
        $this->beforeAdd();
        $this->checkQueue();
        try {
            $message = new BrokeredMessage();
            $message->setBody(json_encode($data));
            $this->getConnection()->sendQueueMessage($this->queue_name, $message);
        }
        catch(ServiceException $ex)
        {
            throw new BackendException($ex->getMessage(), $ex->getCode());
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
        $this->checkQueue();
        try
        {
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();
            $response = $this->getConnection()->receiveQueueMessage($this->queue_name, $options);

            $this->last_job = $response;
            $this->last_job_id = $response->getMessageId();
            $this->afterGet();
            return json_decode($response->getBody(), TRUE);
        }
        catch(ServiceException $ex)
        {
            throw new JobNotFoundException($ex->getMessage(), $ex->getCode());
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
        try
        {
            $this->getConnection()->deleteMessage($this->open_items[$jobId]);
            $this->last_job_id = $jobId;
            $this->afterClearRelease();
            return true;
        }
        catch (ServiceException $ex)
        {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
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
        try
        {
            $this->getConnection()->unlockMessage($this->open_items[$jobId]);
            $this->last_job_id = $jobId;
            $this->afterClearRelease();
            return true;
        }
        catch (ServiceException $ex)
        {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
    }

    public function createQueue($queue_name)
    {
        try
        {
            $queueInfo = new QueueInfo($queue_name);
            $this->getConnection()->createQueue($queueInfo);
            return true;
        }
        catch(ServiceException $ex)
        {
            throw new BackendException($ex->getMessage(), $ex->getCode());
        }
        return false;
    }

    private function checkQueue()
    {
        if (empty($this->queue_name))
        {
            throw new BackendException("Queue name not specified.");
        }
        $queue_info = $this->getConnection()->getQueue($this->queue_name);
        if ($this->queue_name != $queue_info->getTitle())
        {
            return $this->createQueue($this->queue_name);
        }
        return true;
    }
}