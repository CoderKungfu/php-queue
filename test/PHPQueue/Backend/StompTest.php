<?php
namespace PHPQueue\Backend;

use PHPUnit_Framework_TestCase;
use PHPQueue\Exception\JobNotFoundException;

class StompTest extends PHPUnit_Framework_TestCase
{
    protected $object;
    protected $unique;
    protected $unclean;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\FuseSource\Stomp\Stomp')) {
            $this->markTestSkipped('STOMP library not installed');
        } else {
            $options = array(
                'uri' => 'tcp://127.0.0.1:61613',
                'queue' => 'test_queue',
                'read_timeout' => 1,
            );
            $this->object = new Stomp($options);
        }

        $this->unique = mt_rand();
    }

    public function tearDown()
    {
        if ($this->unclean) {
            // Gross.  Clear the queue.
            try {
                while ($result = $this->object->get()) {
                    // pass
                }
            } catch (JobNotFoundException $ex) {
                // pass
            }
        }

        parent::tearDown();
    }

    /**
     * @medium
     */
    public function testAdd()
    {
        $data = array('unique' => $this->unique);
        $this->unclean = true;
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $result = $this->object->get();
        $this->unclean = false;
    }

    /**
     * @depends testAdd
     * @medium
     */
    public function testGet()
    {
        $data = array('unique' => $this->unique);
        $this->unclean = true;
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $result = $this->object->get();
        $this->assertEquals($data, $result);
        $this->unclean = false;
    }

    /**
     * @depends testAdd
     * @medium
     */
    public function testMergeHeaders()
    {
        $data = array('unique' => $this->unique);
        $this->unclean = true;
        $result = $this->object->add($data, array('fooHeader' => 5));
        $this->assertTrue($result);

        $this->object->merge_headers = true;
        $result = $this->object->get();

        $this->assertTrue(array_key_exists('fooHeader', $result));
        $this->assertEquals($result['fooHeader'], 5);
        $this->assertTrue(array_key_exists('unique', $result));
        $this->assertEquals($result['unique'], $this->unique);
        $this->unclean = false;
    }
}
