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
                            , 'tube'   => 'testqueue'
                        );
            $this->object = new Beanstalkd($options);
        }
    }

    public function testAdd()
    {
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
        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $jobId = $this->object->last_job_id;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);
    }
}
