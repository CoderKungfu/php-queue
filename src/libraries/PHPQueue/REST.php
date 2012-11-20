<?php
namespace PHPQueue;
class REST
{
	static public $json_payload_key = null;
	static public $rest_server;

	/**
	 * Starts a Respect/REST server with default routes:
	 * curl -XPOST http://<server>/<queueName> -d "var1=foo&var2=bar"
	 * curl -XPOST http://<server>/<queueName> -H "Content-Type: application/json" -d '{"var1":"foo","var2":"bar"}'
	 */
	static public function defaultRoutes()
	{
		self::startServer()
				->always('Accept', array(
							'application/json' => 'json_encode'
						)
				)
				->any('/*/**', function($queue=null, $jobId=null)
				{
					return \PHPQueue\REST::route($queue, $jobId);
				});
	}

	/**
	 * Starts the Respect/REST server
	 * @return \Respect\Rest\Router
	 */
	static public function startServer()
	{
		if (empty(self::$rest_server))
		{
			self::$rest_server = new \Respect\Rest\Router;
		}
		return self::$rest_server;
	}

	/**
	 * Specify how a routed URL should be handled.
	 * @param string $queue
	 * @param array $actions
	 * @return stdClass
	 */
	static public function route($queue=null, $actions=array())
	{
		$method = $_SERVER['REQUEST_METHOD'];
		switch($method)
		{
			case 'POST':
				return self::post($queue);
				break;
			case 'GET':
				return self::get($queue, $actions);
				break;
			default:
				return self::failed(404, "Method not supported.");
				break;
		}
	}

	/**
	 * Handles a GET method
	 * @param string $queueName
	 * @param array $actions
	 * @return stdClass
	 */
	protected static function get($queueName=null, $actions=array())
	{
		$action = $actions[0];
		switch($action)
		{
			case 'work':
				return self::work($queueName);
				break;
			default:
				return self::failed(404, "Action not supported.");
				break;
		}
	}

	/**
	 * Handles a POST method
	 * @param string $queueName
	 * @return stdClass
	 */
	protected static function post($queueName=null)
	{
		$payload = self::getPayload();
		try
		{
			$queue = \PHPQueue\Base::getQueue($queueName);
			\PHPQueue\Base::addJob($queue, $payload);
			return self::successful();
		}
		catch (Exception $ex)
		{
			return self::failed($ex->getCode(), $ex->getMessage());
		}
	}

	/**
	 * @return array
	 */
	protected static function getPayload()
	{
		$payload = array();
		switch($_SERVER['CONTENT_TYPE'])
		{
			case 'application/json':
				$content = file_get_contents('php://input');
				$payload = json_decode($content, true);
				if( !empty(self::$json_payload_key) && isset($payload['data']) )
				{
					$payload = $payload['data'];
				}
				break;
			default:
				if (!empty($_POST))
				{
					$payload = $_POST;
				}
				break;
		}
		return $payload;
	}

	/**
	 * Trigger a worker for a queue. Next item in the queue will be retrieved and worked with the appropriate worker.
	 * @param string $queueName
	 * @return stdClass
	 */
	protected static function work($queueName=null)
	{
		$queue = \PHPQueue\Base::getQueue($queueName);
		try
		{
			$newJob = \PHPQueue\Base::getJob($queue);
		}
		catch (Exception $ex)
		{
			return self::failed(405, $ex->getMessage());
		}

		if (empty($newJob))
		{
			return self::failed(404, "No Job in queue.");
		}
		try
		{
			$newWorker = \PHPQueue\Base::getWorker($newJob->worker);
			\PHPQueue\Base::workJob($newWorker, $newJob);
			\PHPQueue\Base::updateJob($queue, $newJob->jobId, $newWorker->resultData);
			return self::successful();
		}
		catch (Exception $ex)
		{
			$queue->releaseJob($newJob->jobId);
			return self::failed($ex->getCode(), $ex->getMessage());
		}
	}

	/**
	 * Convenience method for Successful call.
	 * @return stdClass
	 */
	protected static function successful()
	{
		return self::respond(null, 200, "OK");
	}

	/**
	 * Convenience method for Failed call.
	 * @return stdClass
	 */
	protected static function failed($code=501, $reason="")
	{
		return self::respond(null, $code, $reason);
	}

	/**
	 * Convenience method for a Data call.
	 * @return stdClass
	 */
	protected static function showData($data=null)
	{
		return self::respond($data, 200, "OK");
	}

	/**
	 * Main method for generating response stdClass.
	 * @return stdClass
	 */
	protected static function respond($data=null, $code=200, $message="")
	{
		return Helpers::output($data, $code, $message);
	}
}
?>