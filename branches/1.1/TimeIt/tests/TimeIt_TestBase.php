<?php
require_once 'PHPUnit/Framework.php';

class TimeIt_TestBase extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$dbconn = DBConnectionStack::getConnection();
		$dbconn->BeginTrans();
	}
	
	public function tearDown()
	{
		$dbconn = DBConnectionStack::getConnection();
		$dbconn->RollbackTrans();
	}
}