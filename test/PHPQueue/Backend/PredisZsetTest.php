<?php
namespace PHPQueue\Backend;
class PredisZsetTest extends \PHPUnit_Framework_TestCase
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
                , 'queue' => 'testqueue-' . mt_rand()
                , 'order_key' => 'timestamp'
                , 'correlation_key' => 'txn_id'
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

    public function testSet()
    {
        $key = 'A0001';
        $data = array('name' => 'Michael', 'timestamp' => 1);
        $this->object->set($key, $data);

        $key = 'A0001';
        $data = array('name' => 'Michael Cheng', 'timestamp' => 2);
        $this->object->set($key, $data);

        $key = 'A0002';
        $data = array('name' => 'Michael Cheng', 'timestamp' => 3);
        $this->object->set($key, $data);
    }

    public function testGet()
    {
        $key = 'A0001';
        $data1 = array('name' => 'Michael', 'timestamp' => 1);
        $this->object->set($key, $data1);

        $key = 'A0001';
        $data2 = array('name' => 'Michael Cheng', 'timestamp' => 2);
        $this->object->set($key, $data2);

        $key = 'A0002';
        $data3 = array('name' => 'Michael Cheng', 'timestamp' => 3);
        $this->object->set($key, $data3);

        $result = $this->object->get('A0001');
        $this->assertEquals($data2, $result);

        $result = $this->object->getKey('A0002');
        $this->assertEquals($data3, $result);
    }

    public function testClear()
    {
        $key = 'A0002';
        $data = array('name' => 'Adam Wight', 'timestamp' => 2718);
        $result = $this->object->set($key, $data);

        $result = $this->object->clear($key);
        $this->assertTrue($result);

        $result = $this->object->get($key);
        $this->assertNull($result);
    }

    public function testClearEmpty()
    {
        $jobId = 'xxx';
        $this->assertFalse($this->object->clear($jobId));
    }

    public function testPushPop()
    {
        $data = array(
            'name' => 'Weezle-' . mt_rand(),
            'timestamp' => mt_rand(),
            'txn_id' => mt_rand(),
        );
        $this->object->push($data);

        $this->assertEquals($data, $this->object->get($data['txn_id']));

        $this->assertEquals($data, $this->object->pop());

        $this->assertNull($this->object->get($data['txn_id']));
    }

    public function testPopEmpty()
    {
        $this->assertNull($this->object->pop());
    }
}
