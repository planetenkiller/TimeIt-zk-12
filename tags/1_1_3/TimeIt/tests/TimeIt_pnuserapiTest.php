<?php
include 'TimeIt_TestBase.php';

class TimeIt_pnuserapiTest extends TimeIt_TestBase
{
	public function testupdate()
	{
		$dataForD = array();
        $dataForD['title'] = 'Test';
        $dataForD['text'] = 'test text';
        $dataForD['endDate'] = '2008-03-01';
        $dataForD['sharing'] = 2;
        $dataForD['startDate'] = '2008-03-01';
        $dataForD['allDay'] = 1;
        $dataForD['repeatType'] = 0;
        $dataForD['__CATEGORIES__']['Main'] = 37;
        

        $ret = WorkflowUtil::executeAction('standard', $dataForD, 'submit', 'TimeIt_events');
        $this->assertTrue($ret != false);
        
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        	$this->fail('Unable to load class [Event] ...');
        }
        $object = new $class();
        $obj = $object->getEvent($dataForD['id']);
        
        $obj['title'] = 'test 2';
        
        $params = array();
        $params['obj'] = &$dataForD;
        $ret = pnModAPIFunc('TimeIt', 'user', 'update', $params);
        $this->assertTrue($ret != false);
        
        
        $object = new $class();
        $obj = $object->getEvent($dataForD['id']);
        
        $this->assertEquals('Test', $obj['title']);
	}
	
	public function testcreate()
	{
		$dataForD = array();
        $dataForD['title'] = 'Test';
        $dataForD['text'] = 'test text';
        $dataForD['endDate'] = '2008-03-01';
        $dataForD['sharing'] = 2;
        $dataForD['startDate'] = '2008-03-01';
        $dataForD['allDay'] = 1;
        $dataForD['repeatType'] = 0;
        $dataForD['__CATEGORIES__']['Main'] = 37;
        

        $ret = WorkflowUtil::executeAction('standard', $dataForD, 'submit', 'TimeIt_events');
        $this->assertTrue($ret != false);
        
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        	$this->fail('Unable to load class [Event] ...');
        }
        $object = new $class();
        $obj = $object->getEvent($dataForD['id']);
        $this->assertEquals('Test', $obj['title']);
        $this->assertEquals('test text', $obj['text']);
        $this->assertEquals('2008-03-01', $obj['endDate']);
        $this->assertEquals('2008-03-01', $obj['startDate']);
        $this->assertEquals(2, $obj['sharing']);
        $this->assertEquals(1, $obj['allDay']);
        $this->assertEquals(0, $obj['repeatType']);
        
        
        $params = array();
        $params['obj'] = &$dataForD;
       	$this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
	}
	
	public function testdelete()
	{
		$dataForDB = array();
        $dataForDB['title'] = 'Test';
        $dataForDB['text'] = 'test text';
        $dataForDB['endDate'] = '2008-03-01';
        $dataForDB['sharing'] = 2;
        $dataForDB['startDate'] = '2008-03-01';
        $dataForDB['allDay'] = 1;
        $dataForDB['repeatType'] = 0;
        $dataForDB['__CATEGORIES__']['Main'] = 37;
        
        $ret = WorkflowUtil::executeAction('standard', $dataForDB, 'submit', 'TimeIt_events');
        $this->assertTrue($ret != false);
        
        $params = array();
        $params['obj'] = &$dataForDB;
        //print_r($dataForDB);
        //$params['obj'] = &$ret;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
	}

	public function testmonthEvents()
	{
		// standard data
		$dStd = array();
        $dStd['title'] = 'Test';
        $dStd['text'] = 'test text';
        $dStd['sharing'] = 2;
        $dStd['allDay'] = 1;
        $dStd['repeatType'] = 0;
        $dStd['__CATEGORIES__']['Main'] = 37;
        
        // test event: one Day
        $dDay = array();
        $dDay = $dStd;
        $dDay['startDate'] = '2008-03-01';
        $dDay['endDate'] = '2008-03-01';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dDay, 'submit', 'TimeIt_events') != false);
        
        // test event: Day, repeat every 7 days
        $dDayR7 = array();
        $dDayR7 = $dStd;
        $dDayR7['startDate'] = '2008-03-03';
        $dDayR7['endDate'] = '2008-03-17';
        $dDayR7['repeatType'] = 1;
        $dDayR7['repeatFrec'] = 7;
        $dDayR7['repeatSpec'] = 'day';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dDayR7, 'submit', 'TimeIt_events') != false);
        
        // test event: Day, repeat every 1 week
        $dWeekR1 = array();
        $dWeekR1 = $dStd;
        $dWeekR1['startDate'] = '2008-03-04';
        $dWeekR1['endDate'] = '2008-03-18';
        $dWeekR1['repeatType'] = 1;
        $dWeekR1['repeatFrec'] = 1;
        $dWeekR1['repeatSpec'] = 'week';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dWeekR1, 'submit', 'TimeIt_events') != false);
        
        // test event: Day, repeat on first mon
        $dOnMonFirst = array();
        $dOnMonFirst = $dStd;
        $dOnMonFirst['startDate'] = '2008-03-01';
        $dOnMonFirst['endDate'] = '2008-03-31';
        $dOnMonFirst['repeatType'] = 2;
        $dOnMonFirst['repeatFrec'] = 1;
        $dOnMonFirst['repeatSpec'] = '1 1';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dOnMonFirst, 'submit', 'TimeIt_events') != false);
        
        // test event: Day, repeat on last mon
        $dOnMonLast = array();
        $dOnMonLast = $dStd;
        $dOnMonLast['startDate'] = '2008-03-01';
        $dOnMonLast['endDate'] = '2008-03-31';
        $dOnMonLast['repeatType'] = 2;
        $dOnMonLast['repeatFrec'] = 1;
        $dOnMonLast['repeatSpec'] = '5 1';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dOnMonLast, 'submit', 'TimeIt_events') != false);
        
        // get events for month
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'monthEvents', array('year'=>2008)));
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'monthEvents', array('month'=>3)));
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'monthEvents', array()));
        $array = pnModAPIFunc('TimeIt', 'user', 'monthEvents', array('month'=>3,'year'=>2008));
        $this->assertTrue($array != false);
        
        //print($array['09']['2008-03-01'][0]['id']);
        //print($dDay['id']);
        // check all events
        // one Day:
        $this->assertEquals($array['09']['2008-03-01'][0]['id'], $dDay['id']);
        // day, repeat every 7 days
        $this->assertEquals($array['10']['2008-03-03'][0]['id'], $dDayR7['id']);
        $this->assertEquals($array['11']['2008-03-10'][0]['id'], $dDayR7['id']);
        $this->assertEquals($array['12']['2008-03-17'][0]['id'], $dDayR7['id']);
        // day, repeat every 1 week
        $this->assertEquals($array['10']['2008-03-04'][0]['id'], $dWeekR1['id']);
        $this->assertEquals($array['11']['2008-03-11'][0]['id'], $dWeekR1['id']);
        $this->assertEquals($array['12']['2008-03-18'][0]['id'], $dWeekR1['id']);
        // Day, repeat on first mon
        $this->assertEquals($array['10']['2008-03-03'][1]['id'], $dOnMonFirst['id']);
        $this->assertEquals(count($array['09']['2008-02-25']), 0);
        // Day, repeat on last mon
        $this->assertEquals($array['14']['2008-03-31'][0]['id'], $dOnMonLast['id']);
        
        
        // delete events from db
        $params = array();
        $params['obj'] = &$dDay;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dDayR7;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dWeekR1;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dOnMonFirst;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dOnMonLast;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
	}
	
	public function testweekEvents()
	{
		// standard data
		$dStd = array();
        $dStd['title'] = 'Test';
        $dStd['text'] = 'test text';
        $dStd['sharing'] = 2;
        $dStd['allDay'] = 1;
        $dStd['repeatType'] = 0;
        $dStd['__CATEGORIES__']['Main'] = 37;
        
        // test event: one Day
        $dDay = array();
        $dDay = $dStd;
        $dDay['startDate'] = '2008-03-08';
        $dDay['endDate'] = '2008-03-08';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dDay, 'submit', 'TimeIt_events') != false);
        
        // test event: Day, repeat every 7 days
        $dDayR7 = array();
        $dDayR7 = $dStd;
        $dDayR7['startDate'] = '2008-03-03';
        $dDayR7['endDate'] = '2008-03-17';
        $dDayR7['repeatType'] = 1;
        $dDayR7['repeatFrec'] = 7;
        $dDayR7['repeatSpec'] = 'day';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dDayR7, 'submit', 'TimeIt_events') != false);
        
        // get events for month
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'weekEvents', array()));
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'weekEvents', array('week'=>10)));
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'weekEvents', array('year'=>2008)));
        $array = pnModAPIFunc('TimeIt', 'user', 'weekEvents', array('week'=>10,'year'=>2008));
        $this->assertTrue($array != false);
        
        //print_r($array);
        //print_r($dDayR7);
        // check all events
        // one Day:
        $this->assertEquals($array['2008-03-08'][0]['id'], $dDay['id']);
        // day, repeat every 7 days
        $this->assertEquals($array['2008-03-03'][0]['id'], $dDayR7['id']);
        
        
        // delete events from db
        $params = array();
        $params['obj'] = &$dDay;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dDayR7;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
	}
	
	public function testdayEvents()
	{
		// standard data
		$dStd = array();
        $dStd['title'] = 'Test';
        $dStd['text'] = 'test text';
        $dStd['sharing'] = 2;
        $dStd['allDay'] = 1;
        $dStd['repeatType'] = 0;
        $dStd['__CATEGORIES__']['Main'] = 37;
        
        // test event: one Day
        $dDay = array();
        $dDay = $dStd;
        $dDay['startDate'] = '2008-03-08';
        $dDay['endDate'] = '2008-03-08';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dDay, 'submit', 'TimeIt_events') != false);
        
        // test event: repeat every 7 days
        $dDayR7 = array();
        $dDayR7 = $dStd;
        $dDayR7['startDate'] = '2008-03-03';
        $dDayR7['endDate'] = '2008-03-17';
        $dDayR7['repeatType'] = 1;
        $dDayR7['repeatFrec'] = 7;
        $dDayR7['repeatSpec'] = 'day';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dDayR7, 'submit', 'TimeIt_events') != false);
        
        // test event: repeat every 1 week
        $dWeekR1 = array();
        $dWeekR1 = $dStd;
        $dWeekR1['startDate'] = '2008-03-03';
        $dWeekR1['endDate'] = '2008-03-17';
        $dWeekR1['repeatType'] = 1;
        $dWeekR1['repeatFrec'] = 1;
        $dWeekR1['repeatSpec'] = 'week';
        $this->assertTrue(WorkflowUtil::executeAction('standard', $dWeekR1, 'submit', 'TimeIt_events') != false);
        
        // get events for month
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'dayEvents', array()));
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'dayEvents', array('year'=>2008)));
        $this->assertTrue(!pnModAPIFunc('TimeIt', 'user', 'dayEvents', array('month'=>3,'year'=>2008)));
        $array = pnModAPIFunc('TimeIt', 'user', 'dayEvents', array('day'=>10,'month'=>3,'year'=>2008));
        $this->assertTrue($array != false);
        
        //print($array['09']['2008-03-01'][0]['id']);
        //print($dDay['id']);
        // check all events
        // repeat every 7 days
        $this->assertEquals($array[0]['id'], $dDayR7['id']);
        // repeat every 1 week
        $this->assertEquals($array[1]['id'], $dWeekR1['id']);
        
        
        // delete events from db
        $params = array();
        $params['obj'] = &$dDay;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dDayR7;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
        $params['obj'] = &$dWeekR1;
        $this->assertTrue(pnModAPIFunc('TimeIt', 'user', 'delete', $params));
	}
}