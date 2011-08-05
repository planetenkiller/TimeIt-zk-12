<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Tests
 */

/**
 * Tests for the pncalendarapi.php file.
 *
 * @author roland
 */
class TimeIt__subscribeapiTest extends ZkUnitTestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testisSubscribed()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','isSubscribed'));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','isSubscribed', array('id'=>null)));

        $this->assertTrue(pnModAPIFunc('TimeIt','subscribe','isSubscribed', array('id'=>3)));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','isSubscribed', array('id'=>10000)));
    }

    public function testdelete()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','delete'));

        $r = pnModAPIFunc('TimeIt','subscribe','delete', $this->sharedFixture->TimeIt_regs['one']['id']);
        $this->assertTrue(!empty($r));
    }

    public function testdeletePendingState()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','deletePendingState'));

        $this->assertTrue(pnModAPIFunc('TimeIt','subscribe','deletePendingState', $this->sharedFixture->TimeIt_regs['two']['id']));
    }

    public function testsubscribe()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','subscribe'));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','subscribe', array('id'=>null)));

        $this->assertTrue(pnModAPIFunc('TimeIt','subscribe','subscribe', array('id'=>2)));
        $this->assertTrue(pnModAPIFunc('TimeIt','subscribe','isSubscribed', array('id'=>2)));
    }

    public function testcountUserForEvent()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','countUserForEvent'));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','countUserForEvent', array('id'=>null)));

        $this->assertEquals(2, pnModAPIFunc('TimeIt','subscribe','countUserForEvent', array('id'=>3)));
    }

    public function testuserArrayForEvent()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','userArrayForEvent'));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','userArrayForEvent', array('id'=>null)));

        // without pending
        $array = pnModAPIFunc('TimeIt','subscribe','userArrayForEvent', array('id'=>3));
        $this->assertEquals(1, count($array));

        $this->assertEquals(1, $array[0]['uid']);

        // with pending
        $array = pnModAPIFunc('TimeIt','subscribe','userArrayForEvent', array('id'=>3,'withPending'=>true));
        $this->assertEquals(2, count($array));

        $this->assertEquals(1, $array[0]['uid']);
        $this->assertEquals(2, $array[1]['uid']);
    }

    public function testcountEventsForUser()
    {
        $this->assertEquals(1, pnModAPIFunc('TimeIt','subscribe','countEventsForUser'));
        $this->assertEquals(0, pnModAPIFunc('TimeIt','subscribe','countEventsForUser', array('uid'=>-1000)));

        $this->assertEquals(1, pnModAPIFunc('TimeIt','subscribe','countEventsForUser', array('uid'=>2)));
    }

    public function testarrayOfEventsForUser()
    {
        $array = pnModAPIFunc('TimeIt','subscribe','arrayOfEventsForUser', array('uid'=>1));
        $this->assertEquals(1, count($array));
        $this->assertEquals(3, $array[0]['id']);
    }

    public function testunsubscribe()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','unsubscribe'));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','unsubscribe', array('id'=>null)));

        $this->assertTrue(pnModAPIFunc('TimeIt','subscribe','unsubscribe', array('id'=>3)));
        $this->assertFalse(pnModAPIFunc('TimeIt','subscribe','isSubscribed', array('id'=>3)));
    }
}