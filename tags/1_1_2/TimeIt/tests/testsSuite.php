<?php

require_once 'PHPUnit/Framework/TestSuite.php';

require_once 'tests/TimeIt_pnuserapiTest.php';

/**
 * Static test suite.
 */

class testsSuite extends PHPUnit_Framework_TestSuite {
	
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$_GET['name'] = 'TimeIt';
    	chdir('/home/roland/Desktop/lampp_ordner/htdocs/pn8/');
    	// include base api
		include 'includes/pnAPI.php';

		// start PN
		pnInit(PN_CORE_ALL & ~PN_CORE_AJAX);
		
		$this->setName ( 'testsSuite' );
		
		$this->addTestSuite ( 'TimeIt_pnuserapiTest' );
	
	}
	
	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self ( );
	}
}

