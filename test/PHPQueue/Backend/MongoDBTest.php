<?php
namespace PHPQueue\Backend;
class MongoDBTest extends \PHPUnit_Framework_TestCase
{
    private $object;
    private $ids = array();

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\Mongo')) {
            $this->markTestSkipped('Mongo extension is not installed');
        } else {
            $options = array(
                'server' => 'mongodb://localhost'
                , 'db'  => 'testdb'
                , 'collection' => 'things'
            );
            $this->object = new MongoDB($options);
        }
    }

    public function tearDown()
    {
        if ($this->object) {
            $this->object->getDB()->drop();
        }
        parent::tearDown();
    }

    public function testAddGet()
    {
        $key = 'A0001';
        $data1 = array('name' => 'Michael');
        $result = $this->object->add($data1, $key);
        $this->assertTrue($result);

        $result = $this->object->get($key);
        $this->assertNotEmpty($result);
        $this->assertEquals($data1, $result);

        $data2 = array('1','Willy','Wonka');
        $result = $this->object->add($data2);
        $this->assertTrue($result);

        $last_id = $this->object->last_job_id;

        $result = $this->object->get($last_id);
        $this->assertNotEmpty($result);
        $this->assertEquals($data2, $result);
    }

    /**
     * @expectedException \PHPQueue\Exception\JobNotFoundException
     */
    public function testClearNonexistent()
    {
        $jobId = 'xxx';
        $result = $this->object->clear($jobId);
    }

    /**
     * @depends testAddGet
     */
    public function testClear()
    {
        $this->testAddGet();

        $jobId = 'A0001';
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertNull($result);
    }

    public function testSet()
    {
        $data = array(mt_rand(), 'Mr.', 'Jones');
        $this->object->set(4, $data);
        $this->assertEquals($data, $this->object->get(4));
    }
}
