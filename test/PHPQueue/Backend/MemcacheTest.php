<?php
namespace PHPQueue\Backend;
class MemcacheTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\Memcache')) {
            $this->markTestSkipped('Memcache not installed');
        }

        $options = array(
            'servers' => array(
                array('localhost', 11211)
            ),
            'expiry' => 600,
        );

        // Try to connect to Memcache, skip test politely if unavailable.
        try {
            $connection = new \Memcache();
            $connection->addserver($options['servers'][0][0], $options['servers'][0][1]);
            $success = $connection->set('test' . mt_rand(), 'foo', 1);
            if ( !$success ) {
                throw new \Exception("Couldn't store to Memcache");
            }
        } catch (\Exception $ex) {
            $this->markTestSkipped($ex->getMessage());
        }

        $this->object = new Memcache($options);
    }

    public function testAdd()
    {
        $key = 'A0001';
        $data = 'Michael';
        $result = $this->object->add($key, $data);
        $this->assertTrue($result);

        $key = 'A0001';
        $data = 'Michael Cheng';
        $result = $this->object->add($key, $data);
        $this->assertTrue($result);

        $key = 'A0002';
        $data = array('1','Willy','Wonka');
        $result = $this->object->add($key, $data);
        $this->assertTrue($result);
    }

    /**
     * @depends testAdd
     */
    public function testGet()
    {
        // TODO: fixtures.
        $this->testAdd();

        $result = $this->object->get('A0001');
        $this->assertNotEmpty($result);
        $this->assertEquals('Michael Cheng', $result);

        $result = $this->object->get('A0002');
        $this->assertNotEmpty($result);
        $this->assertEquals(array('1','Willy','Wonka'), $result);
    }

    public function testSet()
    {
        $data = array('4', 'Crepuscular');
        $this->object->set(4, $data);
        $this->assertEquals($data, $this->object->get(4));
    }

    /**
     * @depends testAdd
     */
    public function testClear()
    {
        $this->testAdd();

        try {
            $jobId = 'xxx';
            $this->object->clear($jobId);
            $this->fail("Should not be able to delete.");
        } catch (\Exception $ex) {
            $this->assertTrue(true);
        }

        $jobId = 'A0001';
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertEmpty($result);
    }
}
