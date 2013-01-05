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
                , 'db_password'       => 'media1'
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
        $result = $this->object->createTable('pdotest');
        $this->assertTrue($result);

        $this->object->clearAll();

        $data1 = array('2','Boo','Moeow');
        $result = $this->object->add($data1);
        $this->assertTrue($result);

        $this->assertEquals(1, $this->object->last_job_id);

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
        $jobId = 1;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);

        $result = $this->object->get($jobId);
        $this->assertNull($result);

        $result = $this->object->deleteTable('pdotest');
        $this->assertTrue($result);
    }
}
