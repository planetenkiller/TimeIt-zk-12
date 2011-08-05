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
class TimeIt__calendarapiTest extends ZkUnitTestCase
{
    public function __construct() {
        parent::__construct('TimeIt');
    }

    public function testget()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','calendar','get'));

        $obj = pnModAPIFunc('TimeIt','calendar','get', $this->sharedFixture->TimeIt_calendars['one']['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['one']['id'], $obj['id']);
    }

    public function testgetAll()
    {
        $array = pnModAPIFunc('TimeIt','calendar','getAll');

        $this->assertEquals(2, count($array));
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['one']['id'], $array[0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['two']['id'], $array[1]['id']);
    }

    public function testgetAllForDropdown()
    {
        $array = pnModAPIFunc('TimeIt','calendar','getAllForDropdown');

        $this->assertEquals(2, count($array));
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['one']['id'], $array[0]['value']);
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['one']['name'], $array[0]['text']);
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['two']['id'], $array[1]['value']);
        $this->assertEquals($this->sharedFixture->TimeIt_calendars['two']['name'], $array[1]['text']);
    }

    public function testcreate()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','calendar','create'));

        $obj = pnModAPIFunc('TimeIt','calendar','create', array('name'=>'create test','desc'=>'desc','privateCalendar'=>1,'globalCalendar'=>1));
        $this->assertNotNull($obj['id']);
        $obj_get = pnModAPIFunc('TimeIt','calendar','get', $obj['id']);
        $this->assertEquals('create test', $obj_get['name']);
    }

    public function testupdate()
    {
        $obj = pnModAPIFunc('TimeIt','calendar','get', $this->sharedFixture->TimeIt_calendars['one']['id']);
        $obj['name'] = 'name updated';
        $this->assertTrue(false !== pnModAPIFunc('TimeIt','calendar','update',$obj));

        // clear cache
        pnModAPIFunc('TimeIt','calendar','get', -1);
        DBUtil::objectCache(true);

        $obj_get = pnModAPIFunc('TimeIt','calendar','get', $this->sharedFixture->TimeIt_calendars['one']['id']);
        $this->assertEquals('name updated', $obj_get['name']);
    }
}