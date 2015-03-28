<?php
namespace PHPQueue\Backend;
class BeanstalkdTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\Pheanstalk\Pheanstalk')) {
            $this->markTestSkipped('\Pheanstalk\Pheanstalk not installed');
        } else {
            $options = array(
                              'server' => '127.0.0.1'
                            , 'tube'   => 'testqueue-' . mt_rand()
                        );
            $this->object = new Beanstalkd($options);
        }
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
        $id = $this->object->push($data);
        $this->assertTrue($id > 0);

        $this->assertEquals($data, $this->object->get());

        $this->object->release($this->object->last_job_id);
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
            $this->assertTrue(true);
        }

        $data = array(mt_rand(),'Willy','Wonka');
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $this->assertEquals($data, $this->object->get());
        $jobId = $this->object->last_job_id;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $this->assertNull($this->object->pop());
    }

    public function testPush()
    {
        $data = array(mt_rand(),'Willy','Wonka');
        $id = $this->object->push($data);
        $this->assertTrue($id > 0);

        $this->assertEquals($data, $this->object->get($id));
    }

    public function testPop()
    {
        $data = array(mt_rand(),'Willy','Wonka');
        $this->object->push($data);

        $this->assertEquals($data, $this->object->pop());
    }

    public function testPopEmpty()
    {
        $this->assertNull($this->object->pop());
    }
}
