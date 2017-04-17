<?php
namespace PHPQueue\Backend;

class PDOMysqlTest extends PDOBaseTest {

	public function setUp()
	{
		$options = array(
			  'connection_string' => 'mysql:host=localhost;dbname=phpqueuetest'
		    , 'db_table'          => 'pdotest'
		    , 'pdo_options'       => array(
				  \PDO::ATTR_PERSISTENT => true
			)
		);

		// Check that the database exists, and politely skip if not.
		try {
			new \PDO($options['connection_string']);
		} catch ( \PDOException $ex ) {
			$this->markTestSkipped('Database access failed: ' . $ex->getMessage());
		}

		$this->object = new PDO($options);
		// Create table
		$this->assertTrue($this->object->createTable('pdotest'));
		$this->object->clearAll();
	}
}
