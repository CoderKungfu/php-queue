<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

class IronMQ extends Base
{
    public $token = null;
    public $project_id = null;
    public $queue_name;
    public $default_send_options = array(
                                      "timeout" => 60
                                    , "delay"   => 0
                                    , "expires_in" => 172800
                                );

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['token'])) {
            $this->token = $options['token'];
        }
        if (!empty($options['project_id'])) {
            $this->project_id = $options['project_id'];
        }
        if (!empty($options['queue'])) {
            $this->queue_name = $options['queue'];
        }
        if (!empty($options['msg_options']) && is_array($options['msg_options'])) {
            $this->default_send_options = array_merge($this->default_send_options, $options['msg_options']);
        }
    }

    public function connect()
    {
        if (!empty($this->token) && !empty($this->project_id)) {
            $options = array(
                            'token' => $this->token,
                            'project_id' => $this->project_id
                        );
            $this->connection = new \IronMQ($options);
        } else {
            $this->connection = new \IronMQ();
        }
    }

    /**
     * @param  array               $data
     * @return boolean             Status of saving
     * @throws \PHPQueue\Exception
     */
    public function add($data=array())
    {
        $this->beforeAdd();
        $body = array('body'=>json_encode($data));
        $payload = array_merge($this->default_send_options, $body);
        try {
            $this->getConnection()->postMessage($this->queue_name, $payload);
        } catch (BackendException $ex) {
            throw $ex;
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
        $response = $this->getConnection()->getMessage($this->queue_name);
        $this->last_job = $response;
        $this->last_job_id = (string) $response->id;
        $this->afterGet();

        return json_decode($response->body);
    }

    /**
     * @param  string              $jobId
     * @return boolean
     * @throws \PHPQueue\Exception
     */
    public function clear($jobId=null)
    {
        $this->beforeClear($jobId);
        $this->isJobOpen($jobId);
        $response = $this->getConnection()->deleteMessage($this->queue_name, $jobId);
        $msg = json_decode($response, true);
        if ($msg['msg'] == 'Deleted') {
            $this->last_job_id = $jobId;
            $this->afterClearRelease();

            return true;
        }

        return false;
    }

}
