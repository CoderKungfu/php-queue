<?php
namespace PHPQueue\Backend;

class PDOTestSqlite extends PDOBaseTest {

	public function setUp()
	{
		$options = array(
			  'connection_string' => 'sqlite::memory:'
		    , 'db_table' => 'pdotest'
		);
		$this->object = new PDO($options);
		// Create table
		$this->assertTrue($this->object->createTable('pdotest'));
		$this->object->clearAll();
	}
}

