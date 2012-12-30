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
        } else {
            $options = array(
                'queue' => 'test_queue',
                'msg_options' => array('timeout'=>1)
            );
            $this->object = new IronMQ($options);
        }
    }

    public function testAdd()
    {
        $this->object->getConnection()->clearQueue($this->object->queue_name);

        $data = array('1','Willy','Wonka');
        $result = $this->object->add($data);
        $this->assertTrue($result);
    }

    /**
     * @depends testAdd
     */
    public function testGet()
    {
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

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $jobId = $this->object->last_job_id;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);
    }
}
