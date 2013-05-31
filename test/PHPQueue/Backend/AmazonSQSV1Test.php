<?php
namespace PHPQueue\Backend;

class AmazonSQSV1Test extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        parent::setUp();
        if (!class_exists('\AmazonSQS')) {
            $this->markTestSkipped('Amazon PHP SDK not installed');
        } else {
            $options = array(
                'region' => \AmazonSQS::REGION_APAC_SE1,
                'queue' => 'https://sqs.ap-southeast-1.amazonaws.com/524787626913/testqueue',
                'sqs_options' => array(
                    'key' => 'your_sqs_key',
                    'secret' => 'your_sqs_secret'
                ),
                'receiving_options' => array('VisibilityTimeout' => 0)
            );
            $this->object = new AmazonSQS();
            $this->object->setBackend(new Aws\AmazonSQSV1($options));
        }
    }

    public function testAdd()
    {
        $data = array('1','Willy','Wonka');
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
        $this->object->release($this->object->last_job_id);
    }

    /**
     * @depends testAdd
     */
    public function testClear()
    {
        try {
            $jobId = 'xxx';
            $this->object->clear($jobId);
            $this->fail("Should not be able to delete.");
        } catch (\Exception $ex) {
            $this->assertTrue(true);
        }

        $result = $this->object->get();
        $this->assertNotEmpty($result);
        $jobId = $this->object->last_job_id;
        $result = $this->object->clear($jobId);
        $this->assertTrue($result);
    }
}
