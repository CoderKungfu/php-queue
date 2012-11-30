<?php
namespace PHPQueue\Backend;
class CSVTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function __construct()
    {
        parent::__construct();
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.csv';
        file_put_contents($filename, '');
        $opt = array('filePath'=>$filename);
        $this->object = new CSV($opt);
    }

    public function testAdd()
    {
        $data = array('1','Willy','Wonka');
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $data = array('2','Charlie','Chaplin');
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $data = array('3','Apple','Wong');
        $result = $this->object->add($data);
        $this->assertTrue($result);
    }

    /**
     * @depends testAdd
     */
    public function testGet()
    {
        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals(array('1','Willy','Wonka'), $result);

        $result = $this->object->get(3);
        $this->assertNotEmpty($result);
        $this->assertEquals(array('3','Apple','Wong'), $result);

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals(array('2','Charlie','Chaplin'), $result);

        $data = array('4','Cherian','George');
        $result = $this->object->add($data);
        $this->assertTrue($result);

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals(array('3','Apple','Wong'), $result);

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $this->assertEquals(array('4','Cherian','George'), $result);
    }
}
