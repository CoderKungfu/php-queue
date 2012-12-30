<?php
namespace PHPQueue\Backend;
class PredisTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\Predis\Client')) {
            $this->markTestSkipped('Predis not installed');
        } else {
            $options = array(
                'servers' => array('host' => '127.0.0.1', 'port' => 6379)
                , 'queue' => 'testqueue'
            );
            $this->object = new Predis($options);
        }
    }

    public function testQueue()
    {
        $this->object->getConnection()->flushall();

        $data1 = array('full_name'=>'Michael Cheng');
        $result = $this->object->add($data1);
        $this->assertTrue($result);

        $data2 = array('full_name'=>'Andrew Chew');
        $result = $this->object->add($data2);
        $this->assertTrue($result);

        $data3 = array('full_name'=>'Peter Tiel');
        $result = $this->object->add($data3);
        $this->assertTrue($result);

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals($data1, $result);

        $jobA = $this->object->last_job_id;
        $result = $this->object->release($jobA);
        $this->assertEquals(3, $this->object->getConnection()->llen($this->object->queue_name));

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals($data2, $result);

        $jobB = $this->object->last_job_id;
        $result = $this->object->clear($jobB);
        $this->assertTrue($result);
        try {
            $result = $this->object->isJobOpen($jobB);
            $this->fail("Job should not still be open.");
        } catch (\Exception $ex) {
            $this->assertTrue(true);
        }
        $this->assertEquals(2, $this->object->getConnection()->llen($this->object->queue_name));
    }

    public function testSetKey()
    {
        $key = 'A0001';
        $data = 'Michael';
        $result = $this->object->setKey($key, $data);
        $this->assertTrue($result);

        $key = 'A0001';
        $data = 'Michael Cheng';
        $result = $this->object->setKey($key, $data);
        $this->assertTrue($result);

        $key = 'A0002';
        $data = 20333;
        $result = $this->object->setKey($key, $data);
        $this->assertTrue($result);

        $key = 'A0003';
        $data = array(1, 'Willy', 'Wonka');
        $result = $this->object->setKey($key, $data);
        $this->assertTrue($result);

        try {
            $key = 'A0004';
            $data = array(1, 'Willy', 'Wonka', 'boo'=>array(5,6,7));
            $result = $this->object->setKey($key, $data);
            $this->fail("Shouldn't be able to save");
        } catch (\Exception $ex) {
            $this->assertTrue(true);
        }
    }

    public function testGetKey()
    {
        $result = $this->object->getKey('A0001');
        $this->assertNotEmpty($result);
        $this->assertEquals('Michael Cheng', $result);

        $result = $this->object->getKey('A0002');
        $this->assertNotEmpty($result);
        $this->assertEquals(20333, $result);

        $result = $this->object->getKey('A0003');
        $this->assertNotEmpty($result);
        $this->assertEquals(array(1, 'Willy', 'Wonka'), $result);
    }

    public function testIncrDecr()
    {
        $key = 'A0002';
        $data = 20333;
        $result = $this->object->setKey($key, $data);
        $this->assertTrue($result);

        $result = $this->object->incrKey('A0002');
        $this->assertEquals(20334, $result);

        $result = $this->object->getKey('A0002');
        $this->assertNotEmpty($result);
        $this->assertEquals(20334, $result);

        $result = $this->object->decrKey('A0002');
        $this->assertEquals(20333, $result);

        $result = $this->object->getKey('A0002');
        $this->assertNotEmpty($result);
        $this->assertEquals(20333, $result);
    }

    /**
     * @depends testSetKey
     */
    public function testClearKey()
    {
        try {
            $jobId = 'xxx';
            $this->object->clearKey($jobId);
            $this->fail("Should not be able to delete.");
        } catch (\Exception $ex) {
            $this->assertTrue(true);
        }

        $jobId = 'A0001';
        $result = $this->object->clearKey($jobId, 'Try clearing A0001');
        $this->assertTrue($result);

        $result = $this->object->getKey($jobId, 'Check A0001');
        $this->assertEmpty($result);

        $jobId = 'A0003';
        $result = $this->object->clearKey($jobId, 'Try clearing A0003');
        $this->assertTrue($result);

        $result = $this->object->getKey($jobId, 'Check A0003');
        $this->assertEmpty($result);

        $this->object->getConnection()->flushall();
    }
}
