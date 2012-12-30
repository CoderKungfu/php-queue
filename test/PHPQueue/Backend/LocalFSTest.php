<?php
namespace PHPQueue\Backend;

class LocalFSMock extends LocalFS
{
    public function getContainerPath($directory_name)
    {
        return parent::getContainerPath($directory_name);
    }

    public function getFullPath($key)
    {
        return parent::getFullPath($key);
    }

    public function getCurrentContainerPath()
    {
        return parent::getCurrentContainerPath();
    }
}

class LocalFSTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPQueue\Backend\LocalFSMock
     */
    private $object;

    public function setUp()
    {
        parent::setUp();
        $options = array(
            'doc_root' => __DIR__ . '/localfs_docroot'
        );
        $this->object = new LocalFSMock($options);
    }

    public function testContainerNames()
    {
        $result = $this->object->getContainerPath('boo');
        $this->assertEquals(__DIR__ . '/localfs_docroot/boo', $result);

        $this->object->setContainer('mah');
        $result = $this->object->getFullPath('bah.jpg');
        $this->assertEquals(__DIR__ . '/localfs_docroot/mah/bah.jpg', $result);

        $result = $this->object->getCurrentContainerPath();
        $this->assertEquals(__DIR__ . '/localfs_docroot/mah', $result);
    }

    public function testManageContainers()
    {
        $container_name = 'test'.time();

        $result = $this->object->listContainers();
        $this->assertEmpty($result);

        $result = $this->object->createContainer($container_name);
        $this->assertTrue($result);

        $result = $this->object->listContainers();
        $this->assertEquals(1, count($result));

        $result = $this->object->deleteContainer($container_name);
        $this->assertTrue($result);

        $result = $this->object->listContainers();
        $this->assertEmpty($result);
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
        $result = $this->object->fetchFile('image.jpg', __DIR__ . '/downloads');
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
