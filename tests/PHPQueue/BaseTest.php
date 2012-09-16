<?php
class BaseTest extends PHPUnit_Framework_TestCase
{
	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		if (file_exists('/tmp/sample_data.ser'))
		{
			@unlink('/tmp/sample_data.ser');
		}
	}

    public function testGetQueue()
    {
		try
		{
			PHPQueue\Base::getQueue(null);
			$this->fail("Should not be able to get the Queue");
		}
		catch (Exception $ex)
		{
			$this->assertEquals("Queue name is empty", $ex->getMessage());
		}
		try
		{
			PHPQueue\Base::getQueue('NonExistent');
			$this->fail("Should not be able to get the Queue");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Queue file does not exist:", $ex->getMessage());
		}
		try
		{
			PHPQueue\Base::getQueue('Sample', array('className'=>'NotSample'));
			$this->fail("Should not be able to get the Queue");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Queue class does not exist:", $ex->getMessage());
		}
		$result = PHPQueue\Base::getQueue('Sample');
		$this->assertInstanceOf('\\PHPQueue\\JobQueue', $result);
    }

	public function testAddJob()
	{
		try
		{
			PHPQueue\Base::addJob(null);
			$this->fail("Should not be able to add to Queue");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Invalid queue object.", $ex->getMessage());
		}

		$queue = PHPQueue\Base::getQueue('Sample');
		try
		{
			PHPQueue\Base::addJob($queue, null);
			$this->fail("Should not be able to add to Queue");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Invalid job data.", $ex->getMessage());
		}
		$result = PHPQueue\Base::addJob($queue, array('var1'=>"Hello, world!"));
		$this->assertTrue($result);
		$this->assertEquals(1, $queue->getQueueSize());
	}

	public function testGetJob()
	{
		try
		{
			PHPQueue\Base::getJob(null);
			$this->fail("Should not be able to get job from Queue");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Invalid queue object.", $ex->getMessage());
		}

		$queue = PHPQueue\Base::getQueue('Sample');
		$result = PHPQueue\Base::getJob($queue);
		$this->assertInstanceOf('\\PHPQueue\\Job', $result);
		$this->assertEquals(0, $queue->getQueueSize());

		try
		{
			$result = PHPQueue\Base::getJob($queue);
			$this->fail("Should not be able to get job from Queue");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("No more jobs.", $ex->getMessage());
		}
	}

	public function testGetWorker()
	{
		try
		{
			PHPQueue\Base::getWorker(null);
			$this->fail("Should not be able to get the Worker");
		}
		catch (Exception $ex)
		{
			$this->assertEquals("Worker name is empty", $ex->getMessage());
		}
		try
		{
			PHPQueue\Base::getWorker('NonExistent');
			$this->fail("Should not be able to get the Worker");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Worker file does not exist:", $ex->getMessage());
		}
		try
		{
			PHPQueue\Base::getWorker('Sample', array('className'=>'NotSample'));
			$this->fail("Should not be able to get the Worker");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Worker class does not exist:", $ex->getMessage());
		}
		$result = PHPQueue\Base::getWorker('Sample');
		$this->assertInstanceOf('\\PHPQueue\\Worker', $result);
	}

	public function testWorkJob()
	{
		try
		{
			PHPQueue\Base::workJob(null, null);
			$this->fail("Should not be able to work the Job");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Invalid worker object", $ex->getMessage());
		}

		$worker = PHPQueue\Base::getWorker('Sample');
		try
		{
			PHPQueue\Base::workJob($worker, null);
			$this->fail("Should not be able to work the Job");
		}
		catch (Exception $ex)
		{
			$this->assertStringStartsWith("Invalid job object", $ex->getMessage());
		}

		$job = new PHPQueue\Job();
		$job->worker = 'Sample';
		$job->data = array('var1'=>'Hello, world!');
		$result = PHPQueue\Base::workJob($worker, $job);
		$this->assertEquals(array('var1'=>'Hello, world!', 'var2'=>"Welcome back!"), $result->resultData);
		$this->assertEquals(\PHPQueue\Job::OK, $job->status);
		$this->assertTrue($job->isSuccessful());
	}
}
?>