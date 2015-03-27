<?php
/**
 * PHPQueue\REST server
 *
 * Example:
 *   curl -XPOST http://<server>/<queueName> -d "var1=foo&var2=bar"
 *   curl -XPOST http://<server>/<queueName> -H "Content-Type: application/json" -d '{"var1":"foo","var2":"bar"}'
 *   curl -XPUT http://<server>/<queueName>
 */
namespace PHPQueue;

use PHPQueue\Exception\Exception;

class REST
{
    public static $json_payload_key = null;
    public static $rest_server;
    public static $response_content = array(
                            'application/json' => 'json_encode'
                        );
    public $auth_class = null;

    public function __construct($options=array())
    {
        if ( !empty($options['auth']) ) {
            $auth_class = $options['auth'];
            if (is_string($auth_class)) {
                if (!(strpos($auth_class, "\\") === 0)) {
                    $auth_class = '\\' . $auth_class;
                }
                $auth_class = new $auth_class($options);
            }
            $this->auth_class = $auth_class;
        }
    }

    /**
     * Starts a Respect/REST server with default routes:
     */
    public static function defaultRoutes($options=array())
    {
        $router = !empty($options['router']) ? $options['router'] : '\PHPQueue\REST';
        if (is_string($router)) {
            $router = new $router($options);
        }
        $response_format = !empty($options['format'])
                            ? array_merge(self::$response_content, $options['format'])
                            : self::$response_content;

        self::startServer()
                ->always('Accept', $response_format)
                ->any('/*/**', function($queue=null, $actions=array()) use ($router, $options) {
                    return $router->route($queue, $actions, $options);
                });
    }

    /**
     * Starts the Respect/REST server
     * @return \Respect\Rest\Router
     */
    public static function startServer()
    {
        if (empty(self::$rest_server)) {
            self::$rest_server = new \Respect\Rest\Router;
        }

        return self::$rest_server;
    }

    /**
     * Specify how a routed URL should be handled.
     * @param  string   $queue
     * @param  array    $actions
     * @param  array    $options array('auth'=>Object)
     * @return stdClass
     */
    public function route($queue=null, $actions=array(), $options=array())
    {
        try {
            $this->isAuth();
        } catch (\Exception $ex) {
            return $this->failed(401, $ex->getMessage());
        }

        $method = !empty($_GET['REQUEST_METHOD']) ? $_GET['REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD'];
        switch ($method) {
            case 'POST':
                return $this->post($queue);
                break;
            case 'PUT':
                return $this->work($queue);
                break;
            default:
                return $this->failed(404, "Method not supported.");
                break;
        }
    }

    protected function isAuth()
    {
        if ( !is_null($this->auth_class) ) {
            if ( !is_a($this->auth_class, '\PHPQueue\Interfaces\Auth') ) {
                throw new Exception("Invalid Auth Object.");
            }
            if (!$this->auth_class->isAuth()) {
                throw new Exception("Not Authorized");
            }
        }

        return true;
    }

    /**
     * Handles a POST method
     * @param  string   $queueName
     * @return stdClass
     */
    protected function post($queueName=null)
    {
        $payload = $this->getPayload();
        try {
            $queue = Base::getQueue($queueName);
            Base::addJob($queue, $payload);

            return $this->successful();
        } catch (\Exception $ex) {
            return $this->failed($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * @return array
     */
    protected function getPayload()
    {
        $payload = array();
        switch ($_SERVER['CONTENT_TYPE']) {
            case 'application/json':
                $content = file_get_contents('php://input');
                $payload = json_decode($content, true);
                if ( !empty(self::$json_payload_key) && isset($payload['data']) ) {
                    $payload = $payload['data'];
                }
                break;
            default:
                if (!empty($_POST)) {
                    $payload = $_POST;
                }
                break;
        }

        return $payload;
    }

    /**
     * Trigger a worker for a queue. Next item in the queue will be retrieved and worked with the appropriate worker.
     * @param  string   $queueName
     * @return stdClass
     */
    protected function work($queueName=null)
    {
        $queue = Base::getQueue($queueName);
        try {
            $newJob = Base::getJob($queue);
        } catch (\Exception $ex) {
            return $this->failed(405, $ex->getMessage());
        }

        if (empty($newJob)) {
            return $this->failed(404, "No Job in queue.");
        }
        try {
            if (empty($newJob->worker)) {
                throw new Exception("No worker declared.");
            }
            if (is_string($newJob->worker)) {
                $result_data = $this->processWorker($newJob->worker, $newJob);
            } elseif (is_array($newJob->worker)) {
                foreach ($newJob->worker as $worker_name) {
                    $result_data = $this->processWorker($worker_name, $newJob);
                    $newJob->data = $result_data;
                }
            }
            Base::updateJob($queue, $newJob->job_id, $result_data);

            return $this->successful();
        } catch (\Exception $ex) {
            $queue->releaseJob($newJob->job_id);

            return $this->failed($ex->getCode(), $ex->getMessage());
        }
    }

    protected function processWorker($worker_name, $new_job)
    {
        $newWorker = Base::getWorker($worker_name);
        Base::workJob($newWorker, $new_job);

        return $newWorker->result_data;
    }

    /**
     * Convenience method for Successful call.
     * @return stdClass
     */
    protected function successful()
    {
        return self::respond(null, 200, "OK");
    }

    /**
     * Convenience method for Failed call.
     * @return stdClass
     */
    protected function failed($code=501, $reason="")
    {
        return self::respond(null, $code, $reason);
    }

    /**
     * Convenience method for a Data call.
     * @return stdClass
     */
    protected function showData($data=null)
    {
        return self::respond($data, 200, "OK");
    }

    /**
     * Main method for generating response stdClass.
     * @return stdClass
     */
    protected function respond($data=null, $code=200, $message="")
    {
        return Helpers::output($data, $code, $message);
    }
}
