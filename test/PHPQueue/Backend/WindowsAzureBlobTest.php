<?php
namespace PHPQueue\Backend;
class WindowsAzureBlobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPQueue\Backend\WindowsAzureBlob
     */
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\WindowsAzure\Common\ServicesBuilder')) {
            $this->markTestSkipped('Windows Azure not installed');
        } else {
            $options = array(
                  'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=noobsgblob;AccountKey=WHkYwMCHYFMB1EHu061XlD11XS7v0gzKWcYKh4s5YTTioWpyYIVOki2KYki42gekpaVLmKN9WaYc3elyvh/qpQ=='
            );
            $this->object = new WindowsAzureBlob($options);
        }
    }

    public function testManageContainers()
    {
        $container_name = 'test'.time();

        $result = $this->object->listContainers();
        $num = count($result);

        $result = $this->object->createContainer($container_name);
        $this->assertTrue($result);

        $result = $this->object->listContainers();
        $this->assertEquals($num + 1, count($result));

        $result = $this->object->deleteContainer($container_name);
        $this->assertTrue($result);

        $result = $this->object->listContainers();
        $this->assertEquals($num, count($result));
    }

    public function testAdd()
    {
        sleep(1);
        $container_name = 'testimg';
        $result = $this->object->createContainer($container_name);
        $this->assertTrue($result);

        $this->object->setContainer($container_name);
        $file = __DIR__ . '/cc_logo.jpg';
        $result = $this->object->putFile('image.jpg', $file);
        $this->assertTrue($result);

        $result = $this->object->listFiles();
        $this->assertNotEmpty($result);
    }

    /**
     * @depends testAdd
     */
    public function testGet()
    {
        $container_name = 'testimg';
        $this->object->setContainer($container_name);
        $result = $this->object->fetchFile('image.jpg', __DIR__ . '/downloads/image.jpg');
        $this->assertNotEmpty($result);
    }

    /**
     * @expectedException \PHPQueue\Exception\BackendException
     */
    public function testClearInvalidName()
    {
        $container_name = 'testimg';
        $this->object->setContainer($container_name);

        $fake_filename = 'xxx';
        $this->object->clear($fake_filename);
        $this->fail("Should not be able to delete.");
    }

    /**
     * @depends testAdd
     */
    public function testClear()
    {
        $container_name = 'testimg';
        $this->object->setContainer($container_name);
        $result = $this->object->clear('image.jpg');
        $this->assertTrue($result);

        $result = $this->object->deleteContainer($container_name);
        $this->assertTrue($result);
    }
}
