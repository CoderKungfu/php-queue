<?php
namespace PHPQueue;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        if (file_exists('/tmp/sample_data.ser')) {
            @unlink('/tmp/sample_data.ser');
        }
    }

    protected function getSampleQueue()
    {
        return Base::getQueue('Sample');
    }

    public function testCanGetQueue()
    {
        $result = $this->getSampleQueue();
        $this->assertInstanceOf('\\PHPQueue\\JobQueue', $result);
    }

    /**
     * @expectedException \PHPQueue\Exception\QueueNotFoundException
     */
    public function testCanFailWhenInvalidQueueNameAreGiven()
    {
        Base::getQueue('NonExistent');
    }

    public function testAddJob()
    {
        $queue = $this->getSampleQueue();
        $result = Base::addJob($queue, array('var1'=>"Hello, world!"));
        $this->assertTrue($result);
        $this->assertEquals(1, $queue->getQueueSize());
        $result = Base::getJob($queue); //clear
    }

    public function testNoNullJob()
    {
        $queue = $this->getSampleQueue();
        try {
            Base::addJob($queue, null);
            $this->fail("Should not be able to add to Queue");
        } catch (\Exception $ex) {
            $this->assertStringStartsWith("Invalid job data.", $ex->getMessage());
        }
    }

    public function testGetJob()
    {
        $queue = $this->getSampleQueue();
        $result = Base::addJob($queue, array('var1'=>"Hello, world!"));
        $queue = $this->getSampleQueue();
        $result = Base::getJob($queue);
        $this->assertInstanceOf('\\PHPQueue\\Job', $result);
        $this->assertEquals(0, $queue->getQueueSize());
    }

    public function testNoMoreJob()
    {
        $queue = $this->getSampleQueue();
        $result = Base::addJob($queue, array('var1'=>"Hello, world!"));
        $result = Base::getJob($queue); //clear
        try {
            $result = Base::getJob($queue);
            $this->fail("Should not be able to get job from Queue");
        } catch (\Exception $ex) {
            $this->assertStringStartsWith("No more jobs.", $ex->getMessage());
        }
    }

    /**
     * @expectedException \PHPQueue\Exception\WorkerNotFoundException
     */
    public function testCanFailWhenInvalidWorkerNameAreGiven()
    {
        Base::getWorker('NonExistent');
    }

    public function testCanGetWorker()
    {
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
