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
                while ($result = $this->object->pop()) {
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
    public function testPushPop()
    {
        $data = array('unique' => $this->unique);
        $this->unclean = true;
        $this->object->push($data);

        $this->assertEquals($data, $this->object->pop());
        $this->unclean = false;
    }

    /**
     * @medium
     */
    public function testSetGet()
    {
        $data = array('unique' => $this->unique);
        $this->unclean = true;
        $result = $this->object->set($this->unique, $data);

        $result = $this->object->get($this->unique);
        $this->assertEquals($data, $result);
        $this->unclean = false;
    }

    /**
     * @medium
     */
    public function testPopEmpty()
    {
        $this->assertNull($this->object->pop());
    }

    /**
     * @medium
     */
    public function testGetNonexistent()
    {
        $this->assertNull($this->object->get(mt_rand()));
    }

    /**
     * @medium
     */
    public function testMergeHeaders()
    {
        $data = array('unique' => $this->unique);
        $this->unclean = true;
        $this->object->push($data, array('fooHeader' => 5));

        $this->object->merge_headers = true;
        $result = $this->object->pop();

        $this->assertTrue(array_key_exists('fooHeader', $result));
        $this->assertEquals($result['fooHeader'], 5);
        $this->assertTrue(array_key_exists('unique', $result));
        $this->assertEquals($result['unique'], $this->unique);
        $this->unclean = false;
    }
}
