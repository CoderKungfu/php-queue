<?php
namespace PHPQueue\Backend;

abstract class PDOBaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PDO
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();

        if ( !class_exists( '\PDO' ) ) {
            $this->markTestSkipped( 'PDO extension is not installed' );
        }
    }

    public function tearDown()
    {
        if ($this->object) {
            $result = $this->object->deleteTable('pdotest');
            $this->assertTrue($result);
        }

        parent::tearDown();
    }

    public function testAddGet()
    {

        $data1 = array('2', 'Boo', 'Moeow');
        $data2 = array('1','Willy','Wonka');

        // Queue first message
        $this->assertTrue($this->object->add($data1));
        $this->assertEquals(1, $this->object->last_job_id);

        // Queue second message
        $this->assertTrue($this->object->add($data2));

        // Check get method
        $this->assertEquals($data2, $this->object->get($this->object->last_job_id));

        // Check get method with no message ID.
        $this->assertEquals($data1, $this->object->get());
    }

    /**
     * @depends testAddGet
     */
    public function testClear()
    {
        // TODO: Include test fixtures instead of relying on side effect.
        $this->testAddGet();

        $jobId = 1;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertNull($result);
    }

    public function testSet()
    {
        $data = array(mt_rand(), 'Gas', 'Prom');

        // Set message.
        $this->object->set(3, $data);

        $this->assertEquals($data, $this->object->get(3));
    }

    public function testPush()
    {
        $data = array(mt_rand(), 'Snow', 'Den');

        // Set message.
        $id = $this->object->push($data);
        $this->assertTrue($id > 0);
        $this->assertEquals($data, $this->object->get($id));
    }

    public function testPop()
    {
        $data = array(mt_rand(), 'Snow', 'Den');

        // Set message.
        $id = $this->object->push($data);
        $this->assertTrue($id > 0);
        $this->assertEquals($data, $this->object->pop());
    }

    public function testPopEmpty()
    {
        $this->assertNull( $this->object->pop() );
    }

    /**
     * popAtomic should pop if the processor callback is successful.
     */
    public function testPopAtomicCommit()
    {
        $data = array(mt_rand(), 'Abbie', 'Hoffman');

        $this->object->push($data);
        $self = $this;
        $did_run = false;
        $callback = function ($message) use ($self, &$did_run, $data) {
            $self->assertEquals($data, $message);
            $did_run = true;
        };
        $this->assertEquals($data, $this->object->popAtomic($callback));
        $this->assertEquals(true, $did_run);
        // Record has really gone away.
        $this->assertEquals(null, $this->object->pop());
    }

    /**
     * popAtomic should not pop if the processor throws an error.
     */
    public function testPopAtomicRollback()
    {
        $data = array(mt_rand(), 'Abbie', 'Hoffman');

        $this->object->push($data);
        $self = $this;
        $callback = function ($message) use ($self, $data) {
            $self->assertEquals($data, $message);
            throw new \Exception("Foiled!");
        };
        try {
            $this->assertEquals($data, $this->object->popAtomic($callback));
            $this->fail("Should have failed by this point");
        } catch (\Exception $ex) {
            $this->assertEquals("Foiled!", $ex->getMessage());
        }

        // Punchline: data should still be available for the retry pop.
        $this->assertEquals($data, $this->object->pop());
    }

    /**
     * popAtomic should not call the callback if there are no messages
     */
    public function testPopAtomicEmpty()
    {
        $did_run = false;
        $callback = function ($unused) use (&$did_run) {
            $did_run = true;
        };
        $data = $this->object->popAtomic($callback);
        $this->assertNull($data, 'Should return null on an empty queue');
        $this->assertFalse($did_run, 'Should not call callback without a message');
    }

    /**
     * Should be able to push without creating the table first
     */
    public function testImplicitCreateTable()
    {
        $this->object->deleteTable('pdotest'); // created in setUp
        $data = array(mt_rand(), 'Daniel', 'Berrigan');
        try {
            $id = $this->object->push($data);
            $this->assertTrue($id > 0);
            $this->assertEquals($data, $this->object->get($id));
        } catch (\Exception $ex) {
            $this->fail('Should not throw exception when no table');
        }
    }
}
