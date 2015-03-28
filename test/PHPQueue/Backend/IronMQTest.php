<?php
namespace PHPQueue\Backend;
/**
 * @testdox To enable test: Save iron.json to ~/.iron.json in your home folder.
 */
class IronMQTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\IronMQ')) {
            $this->markTestSkipped('Iron MQ library not installed');
        }
        $options = array(
            'queue' => 'test_queue',
            'msg_options' => array('timeout'=>1)
        );
        $this->object = new IronMQ($options);

        $this->object->getConnection()->clearQueue($this->object->queue_name);
    }

    public function testAdd()
    {
        $data = array(mt_rand(),'Willy','Wonka');
        $result = $this->object->add($data);
        $this->assertTrue($result);
    }

    /**
     * @depends testAdd
     */
    public function testGet()
    {
        $data = array(mt_rand(),'Willy','Wonka');
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals(array('1','Willy','Wonka'), $result);
        $this->object->release($this->object->last_job_id);
        sleep(1);
    }

    /**
     * @depends testAdd
     */
    public function testClear()
    {
        try {
            $jobId = 'xxx';
            $this->object->clear($jobId);
            $this->fail("Should not be able to delete.");
        } catch (\Exception $ex) {
            $this->assertNotEquals("Should not be able to delete.", $ex->getMessage());
        }

        $data = array(mt_rand(),'Willy','Wonka');
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $jobId = $this->object->last_job_id;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);
    }

    public function testPush()
    {
        $data = array(mt_rand(), 'Snow', 'Den');

        // Set message.
        $id = $this->object->push($data);
        $this->assertTrue($id > 0);
        $this->assertEquals($data, $this->object->get($id));
    }

    public function testPop()
    {
        $data = array(mt_rand(), 'Snow', 'Den');

        // Set message.
        $id = $this->object->push($data);
        $this->assertTrue($id > 0);
        $this->assertEquals($data, $this->object->pop());
    }
}
