<?php
class BaseTest extends PHPUnit_Framework_TestCase
{
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
}
?>