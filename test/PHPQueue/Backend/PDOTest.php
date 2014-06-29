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
        } else {
            $options = array(
                  'connection_string' => 'mysql:host=localhost;dbname=phpqueuetest'
                , 'db_user'           => 'root'
                , 'db_password'       => ''
                , 'db_table'          => 'pdotest'
                , 'pdo_options'       => array(
                    \PDO::ATTR_PERSISTENT => true
                )
            );
            $this->object = new PDO($options);
        }
    }

    public function testAddGet()
    {
        // Create table
        $this->assertTrue($this->object->createTable('pdotest'));
        $this->object->clearAll();

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
        $jobId = 1;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertNull($result);

        $result = $this->object->deleteTable('pdotest');
        $this->assertTrue($result);
    }
}
