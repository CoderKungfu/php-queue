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
        } else {
            $options = array(
                'servers' => array(
                                    array('localhost', 11211)
                            )
                , 'expiry'  => 600
            );
            $this->object = new Memcache($options);
        }
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

    public function testGet()
    {
        $result = $this->object->get('A0001');
        $this->assertNotEmpty($result);
        $this->assertEquals('Michael Cheng', $result);

        $result = $this->object->get('A0002');
        $this->assertNotEmpty($result);
        $this->assertEquals(array('1','Willy','Wonka'), $result);
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

        $jobId = 'A0001';
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertEmpty($result);
    }
}
