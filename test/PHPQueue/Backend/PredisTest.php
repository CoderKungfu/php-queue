<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\JsonException;

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

    public function tearDown()
    {
        if ($this->object) {
            $this->object->getConnection()->flushall();
        }
        parent::tearDown();
    }

    public function testAddGet()
    {
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
        $this->assertEquals($data1, $result);

        $jobA = $this->object->last_job_id;
        $result = $this->object->release($jobA);
        $this->assertEquals(3, $this->object->getConnection()->llen($this->object->queue_name));

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals($data2, $result);

        $jobB = $this->object->last_job_id;
        $this->object->clear($jobB);
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
    }


    /**
     * Shouldn't be able to save a nested structure
     *
     * @expectedException \PHPQueue\Exception\BackendException
     */
    public function testSetDeep()
    {
        $key = 'A0004';
        $data = array(mt_rand(), 'Willy', 'Wonka', 'boo'=>array(5,6,7));
        $this->object->set($key, $data);
    }

    /**
     * @ depends testSetKey
     */
    public function testGetKey()
    {
        $this->testSetKey();

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
        $this->testSetKey();

        $jobId = 'A0001';
        $result = $this->object->clearKey($jobId, 'Try clearing A0001');
        $this->assertTrue($result);

        $result = $this->object->get($jobId, 'Check A0001');
        $this->assertNull($result);

        $jobId = 'A0003';
        $result = $this->object->clearKey($jobId, 'Try clearing A0003');
        $this->assertTrue($result);

        $result = $this->object->get($jobId, 'Check A0003');
        $this->assertNull($result);
    }

    public function testClearEmpty()
    {
        $jobId = 'xxx';
        $this->assertFalse($this->object->clear($jobId));
    }

    public function testSetGet()
    {
        $key = 'A0001';
        $data = 'Michael-' . mt_rand();
        $this->object->set($key, $data);

        $this->assertEquals($data, $this->object->get($key));
    }

    public function testPushPop()
    {
        $data = 'Weezle-' . mt_rand();
        $this->object->push($data);

        $this->assertEquals($data, $this->object->pop());

        // Check that we did remove the object.
        $this->assertNull($this->object->pop());
    }

    /**
     * @expectedException PHPQueue\Exception\JsonException
     */
    public function testPopBadJson()
    {
        // Bad JSON
        $data = '{"a": bad "Weezle-' . mt_rand() . '"}';
        $this->object->getConnection()->rpush($this->object->queue_name, $data);

        $this->object->pop();

        $this->fail();
    }

    public function testPopEmpty()
    {
        $this->assertNull($this->object->pop());
    }

    public function testPeek()
    {
        $data = 'Weezle-' . mt_rand();
        $this->object->push($data);

        $this->assertEquals($data, $this->object->peek());

        // Check that we didn't remove the object by peeking.
        $this->assertEquals($data, $this->object->pop());
    }
}
