<?php
header('Content-type: text/plain');


if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}
 
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

// Test classes
require_once 'TimeIt_TestBase.php';
require_once 'TimeIt_pnuserapiTest.php';

class runTimeItTests
{
    public static function main()
    {
        
    	
    	PHPUnit_TextUI_TestRunner::run(self::suite());
    }
 
    public static function suite()
    {
    	$_GET['name'] = 'TimeIt';
    	chdir('../../../');
    	// include base api
		include 'includes/pnAPI.php';

		// start PN
		pnInit(PN_CORE_ALL & ~PN_CORE_AJAX);
    	
    	$suite = new PHPUnit_Framework_TestSuite('PHPUnit');
 		
        // add tests
        $suite->addTestSuite('TimeIt_pnuserapiTest');
 
        return $suite;
    }
}
 
if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    runTimeItTests::main();
}
