<?php
namespace PHPQueue;
class BaseTest extends \PHPUnit_Framework_TestCase
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
            Base::getQueue(null);
            $this->fail("Should not be able to get the Queue");
        }
        catch (\Exception $ex)
        {
            $this->assertEquals("Queue name is empty", $ex->getMessage());
        }
        try
        {
            Base::getQueue('NonExistent');
            $this->fail("Should not be able to get the Queue");
        }
        catch (\Exception $ex)
        {
            $this->assertStringStartsWith("Queue file does not exist:", $ex->getMessage());
        }
        try
        {
            Base::getQueue('SampleNotThere');
            $this->fail("Should not be able to get the Queue");
        }
        catch (\Exception $ex)
        {
            $this->assertStringStartsWith("Queue file does not exist:", $ex->getMessage());
        }
        $result = Base::getQueue('Sample');
        $this->assertInstanceOf('\\PHPQueue\\JobQueue', $result);
    }

    public function testAddJob()
    {
        $queue = Base::getQueue('Sample');
        try
        {
            Base::addJob($queue, null);
            $this->fail("Should not be able to add to Queue");
        }
        catch (\Exception $ex)
        {
            $this->assertStringStartsWith("Invalid job data.", $ex->getMessage());
        }
        $result = Base::addJob($queue, array('var1'=>"Hello, world!"));
        $this->assertTrue($result);
        $this->assertEquals(1, $queue->getQueueSize());
    }

    public function testGetJob()
    {
        $queue = Base::getQueue('Sample');
        $result = Base::getJob($queue);
        $this->assertInstanceOf('\\PHPQueue\\Job', $result);
        $this->assertEquals(0, $queue->getQueueSize());

        try
        {
            $result = Base::getJob($queue);
            $this->fail("Should not be able to get job from Queue");
        }
        catch (\Exception $ex)
        {
            $this->assertStringStartsWith("No more jobs.", $ex->getMessage());
        }
    }

    public function testGetWorker()
    {
        try
        {
            Base::getWorker(null);
            $this->fail("Should not be able to get the Worker");
        }
        catch (\Exception $ex)
        {
            $this->assertEquals("Worker name is empty", $ex->getMessage());
        }
        try
        {
            Base::getWorker('NonExistent');
            $this->fail("Should not be able to get the Worker");
        }
        catch (\Exception $ex)
        {
            $this->assertStringStartsWith("Worker file does not exist:", $ex->getMessage());
        }
        try
        {
            Base::getWorker('SampleNotThere');
            $this->fail("Should not be able to get the Worker");
        }
        catch (\Exception $ex)
        {
            $this->assertStringStartsWith("Worker file does not exist:", $ex->getMessage());
        }
        $result = Base::getWorker('Sample');
        $this->assertInstanceOf('\\PHPQueue\\Worker', $result);
    }

    public function testWorkJob()
    {
        $worker = Base::getWorker('Sample');
        $job = new Job();
        $job->worker = 'Sample';
        $job->data = array('var1'=>'Hello, world!');
        $result = Base::workJob($worker, $job);
        $this->assertEquals(array('var1'=>'Hello, world!', 'var2'=>"Welcome back!"), $result->result_data);
        $this->assertEquals(Job::OK, $job->status);
        $this->assertTrue($job->isSuccessful());
    }
}
?>