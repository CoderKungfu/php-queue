<?php
namespace PHPQueue\Backend;
class PDOTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PDO
     */
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\PDO')) {
            $this->markTestSkipped('PDO extension is not installed');
        }
        $options = array(
              'connection_string' => 'mysql:host=localhost;dbname=phpqueuetest'
            , 'db_table'          => 'pdotest'
            , 'pdo_options'       => array(
                \PDO::ATTR_PERSISTENT => true
            )
        );

        // Check that the database exists, and politely skip if not.
        try {
            $this->object = new \PDO($options['connection_string']);
        } catch ( \PDOException $ex ) {
            $this->markTestSkipped('Database access failed: ' . $ex->getMessage());
        }

        // Create table
        $this->assertTrue($this->object->createTable('pdotest'));
        $this->object->clearAll();
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
}
