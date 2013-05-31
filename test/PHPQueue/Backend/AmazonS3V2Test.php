<?php
namespace PHPQueue\Backend;

class AmazonS3V2Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Aws\S3\S3Client
     */
    private static $client;
    /**
     * @var \PHPQueue\Backend\AmazonS3V2
     */
    private $object;
    private static $test_region = 'ap-southeast-1';
    private static $test_upload_bucket = 'phpqueuetestbucket';
    private static $s3_key = 'your_s3_key';
    private static $s3_secret = 'your_s3_secret';

    public static function setUpBeforeClass()
    {
        if (!class_exists('\Aws\S3\S3Client')) {
            self::markTestSkipped('Amazon PHP SDK 2 not installed');
        }

        parent::setUpBeforeClass();

        self::$client = \Aws\S3\S3Client::factory(array(
            'key'    => self::$s3_key,
            'secret' => self::$s3_secret,
            'region' => self::$test_region
        ));
        try {
            self::$client->createBucket(array(
                'Bucket' => self::$test_upload_bucket,
                'LocationConstraint' => self::$test_region
            ));
            self::$client->waitUntilBucketExists(array(
                'Bucket' => self::$test_upload_bucket
            ));
        } catch (\Aws\S3\Exception\BucketAlreadyOwnedByYouException $exception) {
        }
    }

    public function setUp()
    {
        parent::setUp();

        $options = array(
            'region'         => self::$test_region,
            'region_website' => null,
            'bucket'         => self::$test_upload_bucket,
            's3_options' => array(
                'key'    => self::$s3_key,
                'secret' => self::$s3_secret
            )
        );
        $this->object = new AmazonS3();
        $this->object->setBackend(new Aws\AmazonS3V2($options));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidConnection()
    {
        $this->object->setConnection(new \StdClass());
        $this->fail('Should not be able to set invalid connection.');
    }

    public function testSetConnection()
    {
        $this->object->setConnection(self::$client);
        $result = $this->object->listContainers();
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testManageContainers()
    {
        $container_name = 'test'.time();

        $result = $this->object->listContainers();
        $this->assertGreaterThanOrEqual(1, count($result));
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
        $this->object->setContainer(self::$test_upload_bucket);
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
     */
    public function testClearInvalidName()
    {
        $fake_filename = 'xxx';
        $result = $this->object->clear($fake_filename);
        $this->assertTrue($result); // SDK v2 will return success even if the file does not exist
    }

    /**
     * @depends testAdd
     */
    public function testClear()
    {
        $result = $this->object->clear('image.jpg');
        $this->assertTrue($result);
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        try {
            $clear = new \Aws\S3\Model\ClearBucket(self::$client, self::$test_upload_bucket);
            $clear->clear();

            self::$client->deleteBucket(array(
                'Bucket' => self::$test_upload_bucket
            ));
            self::$client->waitUntilBucketNotExists(array(
                'Bucket' => self::$test_upload_bucket
            ));
        } catch (\Exception $exception) {
        }
    }
}
