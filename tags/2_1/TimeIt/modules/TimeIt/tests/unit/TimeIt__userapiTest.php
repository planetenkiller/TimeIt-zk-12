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
 * Tests for the pnuserapi.php file.
 *
 * @author planetenkiller
 */
class TimeIt__userapiTest extends ZkUnitTestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testgetEvent()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getEvent', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getEvent', array('id' => 100000)));

        $obj = pnModAPIFunc('TimeIt','user','getEvent', array('id' => $this->sharedFixture->TimeIt_events['one']['id']));
        $this->assertEquals($this->sharedFixture->TimeIt_events['one']['id'], $obj['id']);
        $this->assertEquals('allday',$obj['title']);
    }

    public function testsharing_private()
    {
        $this->switchToAnonymous();
        $obj = pnModAPIFunc('TimeIt','user','getEvent', array('id' => $this->sharedFixture->TimeIt_events['evt_1']['id']));
        $this->assertTrue(!empty($obj)); //TODO: getEvent does no permission check
    }

    public function testgetEventPreformat()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getEventPreformat', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getEventPreformat', array('obj' => null)));

        $obj = pnModAPIFunc('TimeIt','user','getEvent', array('id' => $this->sharedFixture->TimeIt_events['one']['id']));
        $objF = pnModAPIFunc('TimeIt','user','getEventPreformat', array('obj' => $obj));
        
        $this->assertEquals($this->sharedFixture->TimeIt_events['one']['id'], $objF['id']);
        $this->assertEquals('allday event',$objF['text']);
        $this->assertTrue(is_array($objF['allDayDur']));
    }

    public function testgetDailySortedEvents()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getDailySortedEvents', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getDailySortedEvents', array('start'=>'2009-01-01')));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getDailySortedEvents', array('start'=>'2009-01-01',
                                                                               'end'  =>'2009-01-29')));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getDailySortedEvents', array('start'=>'2009-01-01',
                                                                               'end'  =>'9999-01-29',
                                                                               'cid' => $this->sharedFixture->TimeIt_calendars['one']['id'])));

        $array = pnModAPIFunc('TimeIt','user','getDailySortedEvents', array('start'=>'2009-01-01',
                                                                            'end'  =>'2009-01-02',
                                                                            'cid' => $this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(2, count($array)); //two days
        $this->assertEquals($this->sharedFixture->TimeIt_events['one']['id'], $array[strtotime('2009-01-01 00:00:00')][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['two']['id'], $array[strtotime('2009-01-01 00:00:00')][0]['data'][1]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['three']['id'], $array[strtotime('2009-01-02 00:00:00')][0]['data'][0]['id']);
    }

    public function testyearEvents()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','yearEvents', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','yearEvents', array('year'=>9999)));

        $array = pnModAPIFunc('TimeIt','user','yearEvents', array('year'=>2009));

        $this->assertEquals(12, count($array));
    }

    public function testmonthEvents()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','monthEvents', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>9999)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>12)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>20,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id'])));

        $array = pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>1,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(5, count($array)); // 5 weeks
        
        $this->assertEquals($this->sharedFixture->TimeIt_events['one']['id'], $array[0]['2009-01-01'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['two']['id'], $array[0]['2009-01-01'][0]['data'][1]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['three']['id'], $array[0]['2009-01-02'][0]['data'][0]['id']);
    
        $this->assertEquals($this->sharedFixture->TimeIt_events['four']['id'], $array[0]['2009-01-03'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['four']['id'], $array[0]['2009-01-04'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['four']['id'], $array[1]['2009-01-05'][0]['data'][0]['id']);
    }

    public function testmonthEvents_reptyp_1()
    {
        $array = pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>2,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(5, count($array)); // 5 weeks

        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_1']['id'], $array[1]['2009-02-02'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_1']['id'], $array[1]['2009-02-04'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_1']['id'], $array[1]['2009-02-06'][0]['data'][0]['id']);

        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_2']['id'], $array[1]['2009-02-02'][0]['data'][1]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_2']['id'], $array[2]['2009-02-09'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_2']['id'], $array[3]['2009-02-16'][0]['data'][0]['id']);

        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_3']['id'], $array[2]['2009-02-10'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_4']['id'], $array[1]['2009-02-02'][0]['data'][2]['id']);
    }

    public function testmonthEvents_reptyp_2()
    {
        $array = pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>3,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(6, count($array)); // 6 weeks

        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_5']['id'], $array[1]['2009-03-02'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_6']['id'], $array[2]['2009-03-09'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_7']['id'], $array[3]['2009-03-16'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_8']['id'], $array[4]['2009-03-23'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_9']['id'], $array[5]['2009-03-30'][0]['data'][0]['id']);

        $array = pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>4,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        // next month
        $this->assertEquals(5, count($array)); // 5 weeks

        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_5']['id'], $array[1]['2009-04-06'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_6']['id'], $array[2]['2009-04-13'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_7']['id'], $array[3]['2009-04-20'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_8']['id'], $array[4]['2009-04-27'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_9']['id'], $array[4]['2009-04-27'][0]['data'][1]['id']);
    }

    public function testmonthEvents_reptyp_3()
    {
        $array = pnModAPIFunc('TimeIt','user','monthEvents', array('year'=>2009,'month'=>5,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(5, count($array)); // 5 weeks

        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_10']['id'], $array[0]['2009-05-02'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_10']['id'], $array[0]['2009-05-03'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_10']['id'], $array[1]['2009-05-09'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_10']['id'], $array[1]['2009-05-10'][0]['data'][0]['id']);
    }

    public function testweekEvents()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','weekEvents', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','weekEvents', array('year'=>9999)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','weekEvents', array('year'=>2009,'week'=>1)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','weekEvents', array('year'=>2009,'week'=>2000,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id'])));

        $array = pnModAPIFunc('TimeIt','user','weekEvents', array('year'=>2009,'week'=>1,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(7, count($array)); // 7 days
        
        $this->assertEquals($this->sharedFixture->TimeIt_events['one']['id'], $array['2009-01-01'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['two']['id'], $array['2009-01-01'][0]['data'][1]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['three']['id'], $array['2009-01-02'][0]['data'][0]['id']);

        $this->assertEquals($this->sharedFixture->TimeIt_events['four']['id'], $array['2009-01-03'][0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['four']['id'], $array['2009-01-04'][0]['data'][0]['id']);// +1 because timeit stores recurences sparatly
    }

    public function testdayEvents()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','dayEvents', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','dayEvents', array('year'=>9999)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','dayEvents', array('year'=>2009,'month'=>1)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','dayEvents', array('year'=>2009,'month'=>2000,'day'=>1,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id'])));

        $array = pnModAPIFunc('TimeIt','user','dayEvents', array('year'=>2009,'month'=>1,'day'=>1,'cid'=>$this->sharedFixture->TimeIt_calendars['one']['id']));

        $this->assertEquals(1, count($array)); // 1 categories
        $this->assertEquals(2, count($array[0]['data'])); // 2 events

        $this->assertEquals($this->sharedFixture->TimeIt_events['one']['id'], $array[0]['data'][0]['id']);
        $this->assertEquals($this->sharedFixture->TimeIt_events['two']['id'], $array[0]['data'][1]['id']);
    }

    public function testdelete()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','delete', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','delete', array('obj'=>null)));

        //$obj = pnModAPIFunc('TimeIt','user','getEvent', array('id' => $this->sharedFixture->TimeIt_events['one']['id']));

        $this->assertTrue(pnModAPIFunc('TimeIt','user','delete', array('id'=>$this->sharedFixture->TimeIt_events['one']['id'])));

        // use DBUtil function with parameter cache=FALSE because the TimeIt function uses cache=TRUE(default).
        // DBUtil cached the obeject a few lines before.
        $this->assertNull(DBUtil::selectObjectByID('TimeIt_events', $this->sharedFixture->TimeIt_events['one']['id'], 'id', null, null, null, false));
    }

    public function testgetFirstDayOfWeek()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getFirstDayOfWeek', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getFirstDayOfWeek', array('year'=>2009,'month'=>1)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getFirstDayOfWeek', array('year'=>2009,'month'=>11111,'day'=>4)));

        $timestamp = pnModAPIFunc('TimeIt','user','getFirstDayOfWeek', array('year'=>2009,'month'=>1,'day'=>4));
        $this->assertEquals(strtotime('2008-12-29 00:00:00'), $timestamp);
    }

    public function testarrayForMonthViewV2()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','arrayForMonthViewV2', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','arrayForMonthViewV2', array('year'=>2009)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','arrayForMonthViewV2', array('year'=>2009,'month'=>1111)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','arrayForMonthViewV2', array('year'=>11119,'month'=>1)));

        $month = pnModAPIFunc('TimeIt','user','arrayForMonthViewV2', array('year'=>2009,'month'=>2));

        $this->assertEquals(5, count($month)); // weeks
        $this->assertEquals(7, count(array_keys($month[0]))); // days week 5
        $this->assertEquals(7, count(array_keys($month[1]))); // days week 6
        $this->assertEquals(7, count(array_keys($month[2]))); // days week 7
        $this->assertEquals(7, count(array_keys($month[3]))); // days week 8
        $this->assertEquals(7, count(array_keys($month[4]))); // days week 9
    }

    public function testcheckDate()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array()));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array('date'=>'9999-01-15')));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array('date'=>'2009-99-15')));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array('date'=>'2009-01-99')));
        $this->assertTrue(pnModAPIFunc('TimeIt','user','checkDate', array('date'=>'2009-01-15')));

        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array('year'=>9999,'month'=>1,'day'=>15)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array('year'=>2009,'month'=>99,'day'=>15)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','checkDate', array('year'=>2009,'month'=>1,'day'=>99)));
        $this->assertTrue(pnModAPIFunc('TimeIt','user','checkDate', array('year'=>2009,'month'=>1,'day'=>15)));
    }

    /* TODO: create new version which uses a TimeIt_date_has_events id
    public function testgetMasterEvent()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getMasterEvent'));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getMasterEvent',array('obj'=>null)));
        $this->assertFalse(pnModAPIFunc('TimeIt','user','getMasterEvent',array('mid'=>null)));

        $obj = pnModAPIFunc('TimeIt','user','getEvent', array('id' => $this->sharedFixture->TimeIt_events['rec_1']['id']+2));
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_1']['id'], $obj['id']);

        $obj_master = pnModAPIFunc('TimeIt','user','getMasterEvent', array('obj'=>$obj));
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_1']['id'], $obj_master['id']);

        $obj_master = pnModAPIFunc('TimeIt','user','getMasterEvent', array('mid'=>$obj['mid']));
        $this->assertEquals($this->sharedFixture->TimeIt_events['rec_1']['id'], $obj_master['id']);
    }*/

    public function testcountPendingEvents()
    {
        $this->assertEquals(1, pnModAPIFunc('TimeIt','user','countPendingEvents'));

        $this->switchToAnonymous();
        $this->assertEquals(1, pnModAPIFunc('TimeIt','user','countPendingEvents'));
    }

    public function testpendingEvents()
    {
        $array = pnModAPIFunc('TimeIt','user','pendingEvents');

        $this->assertEquals(1, count($array));
        $this->assertEquals($this->sharedFixture->TimeIt_events['evt_2']['id'], $array[0]['id']);
    }

    public function testcounthiddenEvents()
    {
        $this->assertEquals(1, pnModAPIFunc('TimeIt','user','counthiddenEvents'));

        $this->switchToAnonymous();
        $this->assertEquals(1, pnModAPIFunc('TimeIt','user','counthiddenEvents'));
    }

    public function testhiddenEvents()
    {
        $array = pnModAPIFunc('TimeIt','user','hiddenEvents');

        $this->assertEquals(1, count($array));
        $this->assertEquals($this->sharedFixture->TimeIt_events['evt_3']['id'], $array[0]['id']);
    }

    public function testdeleteEventsOfUser()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','deleteEventsOfUser'));

        $this->assertTrue(pnModAPIFunc('TimeIt','user','deleteEventsOfUser', array('uid'=>2)));
        $this->assertEquals(0, DBUtil::selectObjectCount('TimeIt_events'));
    }

    public function testanonymizeEventsOfUser()
    {
        $this->assertFalse(pnModAPIFunc('TimeIt','user','anonymizeEventsOfUser'));

        $this->assertTrue(pnModAPIFunc('TimeIt','user','anonymizeEventsOfUser', array('uid'=>2)));
        $this->assertEquals(0, DBUtil::selectObjectCount('TimeIt_events', 'pn_cr_uid = 2'));
    }

    public function testgetOldestDate()
    {
        $this->assertEquals('2009-01-01', pnModAPIFunc('TimeIt','user','getOldestDate'));
    }

    public function testgetLastDate()
    {
        $this->assertEquals('2010-02-05', pnModAPIFunc('TimeIt','user','getLastDate'));
    }
}

