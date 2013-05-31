<?php
namespace PHPQueue\Backend;

class AmazonS3V1Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPQueue\Backend\AmazonS3
     */
    private $object;
    private $test_upload_bucket = 'phpqueuetestbucket';

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\AmazonS3')) {
            $this->markTestSkipped('Amazon PHP SDK not installed');
        } else {
            $options = array(
                'region'         => \AmazonS3::REGION_APAC_SE1,
                'region_website' => \AmazonS3::REGION_APAC_SE1_WEBSITE,
                'bucket'         => $this->test_upload_bucket,
                's3_options' => array(
                    'key'    => 'your_s3_key',
                    'secret' => 'your_s3_secret'
                )
            );
            $this->object = new AmazonS3();
            $this->object->setBackend(new Aws\AmazonS3V1($options));
        }
    }

    public function testManageContainers()
    {
        $container_name = 'test'.time();

        $result = $this->object->listContainers();
        $count = count($result);

        $result = $this->object->createContainer($container_name);
        $this->assertTrue($result);

        $result = $this->object->listContainers();
        $this->assertEquals($count + 1, count($result));

        $result = $this->object->deleteContainer($container_name);
        $this->assertTrue($result);

        $result = $this->object->listContainers();
        $this->assertEquals($count, count($result));
    }

    public function testAdd()
    {
        sleep(1);
        $result = $this->object->createContainer($this->test_upload_bucket);
        $this->assertTrue($result);

        $this->object->setContainer($this->test_upload_bucket);
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
        $result = $this->object->fetchFile('image.jpg', __DIR__ . '/downloads');
        $this->assertNotEmpty($result);
    }

    /**
     * @depends testAdd
     * @expectedException \PHPQueue\Exception\BackendException
     */
    public function testClearInvalidName()
    {
        $fake_filename = 'xxx';
        $this->object->clear($fake_filename);
        $this->fail("Should not be able to delete.");
    }

    /**
     * @depends testAdd
     */
    public function testClear()
    {
        $result = $this->object->clear('image.jpg');
        $this->assertTrue($result);

        $result = $this->object->deleteContainer($this->test_upload_bucket);
        $this->assertTrue($result);
    }
}
