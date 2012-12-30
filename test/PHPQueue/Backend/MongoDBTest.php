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

    public function testAddGet()
    {
        $this->object->getDB()->drop();

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
     * @depends testAddGet
     */
    public function testClear()
    {
        try {
            $jobId = 'xxx';
            $result = $this->object->clear($jobId);
        } catch (\PHPQueue\Exception\JobNotFoundException $ex) {
            $this->assertTrue(true);
        } catch (\Exception $ex) {
            $this->fail('Should not be able to delete.');
        }

        $jobId = 'A0001';
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertNull($result);
    }
}
