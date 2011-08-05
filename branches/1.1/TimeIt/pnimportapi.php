<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function TimeIt_importapi_postcalendar($prefix)
{
	if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
	
	/*if(!pnModAvailable('PostCalendar'))
	{
		
		return false;
	}*/
    
	//$prefix = pnConfigGetVar('prefix');
	
	// tables exists?
	$sql1 = "show table status like '".$prefix."_postcalendar_%'";
	$result1 = DBUtil::executeSQL($sql1);
	if($result1->RecordCount() < 2)
	{
		return LogUtil::registerError(_TIMEIT_ERROR_PCTABLE);
	}
	
	$result = DBUtil::executeSQL('SELECT * FROM '.$prefix.'_postcalendar_events');
	if(!$result)
	{
		return LogUtil::registerError(_TIMEIT_ERROR_PCTABLE);
	}
	
	$repeatSpecNumToStr = array(0=>'day', 1=>'week', 2=>'month', 3=>'year');
	
	// 30 sec are too little
	ini_set('max_execution_time' , 3600);
	
	// convert categroies
	$categorymap = pnModAPIFunc('TimeIt', 'import', 'importcats', $prefix);
	
	// import all records
	while (!$result->EOF) 
	{
		list( $pcobj[eid],
			$pcobj[catid],
			$pcobj[aid],
			$pcobj[title],
			$pcobj[time],
			$pcobj[hometext],
			$pcobj[comments],
			$pcobj[counter],
			$pcobj[topic],
			$pcobj[informant],
			$pcobj[eventDate],
			$pcobj[endDate],
			$pcobj[duration],
			$pcobj[recurrtype],
			$pcobj[recurrspec],
			$pcobj[recurrfreq],
			$pcobj[startTime],
			$pcobj[endTime],
			$pcobj[alldayevent],
			$pcobj[location],
			$pcobj[conttel],
			$pcobj[contname],
			$pcobj[contemail],
			$pcobj[website],
			$pcobj[fee],
			$pcobj[eventstatus],
			$pcobj[sharing],
			$pcobj[language],
			$pcobj[meeting_id]) = $result->fields;
	
		$obj = array();
		$obj['title'] = $pcobj[title];
		$obj['__CATEGORIES__']['pc_imports'] = $categorymap[$pcobj[catid]];
		$obj['text'] = $pcobj[hometext];
		if(substr($obj['text'],0, 6) == ":text:")
		{
			$obj['text'] = substr($obj['text'], 6); // remove :text: and :html:
			$obj['text'] = '#plaintext#'.$obj['text'];
		} else {
			$obj['text'] = substr($obj['text'], 6); // remove :text: and :html:
		}
		$obj['startDate'] = $pcobj[eventDate];
		if($pcobj[endDate] == "0000-00-00")
		{
			if($pcobj[recurrtype] == 0)
			{
				$obj['endDate'] = $obj['startDate'];
			} else
			{
				$obj['endDate'] = '2037-12-31';//FIXME: better way?
			}
		} else
		{
			if($pcobj[recurrtype] == 0)
			{
				$obj['endDate'] = $obj['startDate'];
			} else
			{
				$obj['endDate'] = $pcobj[endDate];
			}
		}
		$obj['allDay'] = $pcobj[alldayevent];
		if((int)$obj['allDay'] == 0)
		{
			$time = explode(':',$pcobj[startTime]);
			$obj['allDayStart'] = $time[0].':'.$time[1];
			$sec = (int)$pcobj[duration];
			$h = $sec / 3600; // hours
			$sec = $sec % 3600; // rest
			$min = $sec / 60; // minutes
			$obj['allDayDur'] =  $h.','.$min;
		}
		$obj['sharing'] = $pcobj[sharing];
		$obj['group'] = 'all';
		if($obj['sharing'] == 0)
		{
			$obj['sharing'] = 1;
		} else
		{
			$obj['sharing'] = 2;
		}
		/*$obj['cr_uid'] = $pcobj[informant];
		$obj['cr_uid'] = pnUserGetVar('uid', pnUserGetIDFromName($obj['cr_uid']), 1);
		$obj['lu_uid'] = $obj['cr_uid'];*/
		
		if((int)$pcobj[recurrtype] == 1)
		{
			$data = unserialize($pcobj[recurrspec]);
			$obj['repeatType'] = 1;
			$obj['repeatSpec'] = $repeatSpecNumToStr[(int)$data['event_repeat_freq_type']];
			$obj['repeatFrec'] = (int)$data['event_repeat_freq'];
		} else if((int)$pcobj[recurrtype] == 2)
		{
			$data = unserialize($pcobj[recurrspec]);
			$obj['repeatType'] = 2;
			$obj['repeatSpec'] = $data['event_repeat_on_num'].' '.$data['event_repeat_on_day'];
			$obj['repeatFrec'] = (int)$data['event_repeat_on_freq'];
		}
		$obj['status'] = $pcobj[eventstatus];
		if($obj['status'] == -1)
		{
			$obj['status'] = 0;
		} else 
		{
			$obj['status'] = 1;
		}
		$location = unserialize($pcobj[location]);
		$obj['data'] = array('phoneNr'=>$pcobj[conttel],
							 'contactPerson'=>$pcobj[contname],
							 'email'=>$pcobj[contemail],
							 'website'=>$pcobj[website],
							 'fee'=>$pcobj[fee],
							 'name'=>$location['event_location'],
							 'city'=>$location['event_city'],
							 'streat'=>$location['event_street1'].' '.$location['event_street2'],
							 'country'=>$location['event_state'],
							 'zip'=>$location['event_postal']);
		
		
		WorkflowUtil::executeAction('standard', $obj, 'submit', 'TimeIt_events');
		$result->MoveNext();
	}
	
	LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
	
	return true;
}

function TimeIt_importapi_importcats($prefix)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // load the admin language file
    // pull all data from the old tables
    //$prefix = pnConfigGetVar('prefix');
    $sql = "SELECT pc_catid, pc_catname, pc_catcolor, pc_catdesc FROM {$prefix}_postcalendar_categories";
    $result = DBUtil::executeSQL($sql);
    $categories = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $categories[] = $result->fields;
    }

    //$result->Close();
    //print_r($categories);
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = pnUserGetLang();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules');

    // create placeholder for all our migrated categories
    $cat = new PNCategory ();
    $cat->setDataField('parent_id', $rootcat['id']);
    $cat->setDataField('name', 'TimeIt');
    $cat->setDataField('value', '-1');
    $cat->insert();
    $cat->update();
    $rootid = $cat->getDataField('id');
    
    // add registry entry
    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname', 'TimeIt');
    $registry->setDataField('table', 'TimeIt_events');
    $registry->setDataField('property', 'pc_imports');
    $registry->setDataField('category_id', $rootid);
    $registry->insert();
   	

    // migrate our root categories
    $categorymap = array();
    foreach ($categories as $category) {
        $cat = new PNCategory();
        $cat->setDataField('parent_id', $rootid);
        $cat->setDataField('name', $category[1]);
        $cat->setDataField('display_name', array($lang => $category[1]));
        $cat->setDataField('display_desc', array($lang => $category[3]));
        $cat->insert();
        $cat->update();
        $newcatid = $cat->getDataField('id');
        $categorymap[$category[0]] = $newcatid;
        
		$data = array('attribute_name' => 'color',
					  'object_id'      => $newcatid,
					  'object_type'    => 'categories_category',
					  'value'          => $category[2]);
		DBUtil::insertObject($data, 'objectdata_attributes');
    }

    return $categorymap;
}

function TimeIt_importapi_fromICal($path)
{
	if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
	
	if(!file_exists($path))
	{
		return LogUtil::registerError(_TIMEIT_ERROR_UPLOADINVALID);
	}
	
	Loader::requireOnce('modules/TimeIt/classes/iCalcreator.class.php');
	
	$v = new vcalendar();
	//$v->setConfig('unique_id', pnModUrl('TimeIt'));
	//$v->setProperty( 'method', 'PUBLISH' );
	
	$pathinfo = pathinfo($path);
	$v->setConfig( 'directory', $pathinfo['dirname'] ); // identify directory
	$v->setConfig( 'filename', $pathinfo['basename'] ); // identify file name
	$v->parse();
	$v->sort();
	
	while($event = $v->getComponent('vevent'))
	{
		$obj = array();
		$obj['title'] = $event->getProperty("SUMMARY");
		$property = $event->getProperty("DESCRIPTION");
		if(!empty($property))
		{
			$obj['text'] = $property;
		}
		
		$property = $event->getProperty("DTSTART");
		$startDate = mktime(0,0,0,$property['month'], $property['day'], $property['year']);
		$property = $event->getProperty("DTEND");
		$endDate = mktime(0,0,0,$property['month'], $property['day'], $property['year']);
		$obj['startDate'] = DateUtil::getDatetime($startDate, _DATEINPUT);
		$obj['endDate'] = DateUtil::getDatetime(strtotime("-1 day", $endDate), _DATEINPUT);
		//$obj['cr_date'] = DateUtil::getDatetime($event['DTSTAMP'], _DATEINPUT); How? DBUtil overwrite this!
		$property = $event->getProperty("CLASS");
		if(!empty($property))
		{
			if($property == 'PRIVATE')
			{
				$obj['sharing'] = 1;
			} else 
			{
				$obj['sharing'] = 3;
			}
		} else 
		{
			$obj['sharing'] = 3;
		}
		$property = $event->getProperty("LOCATION");
		if(!empty($property))
		{
			$obj['data'] = array('city'=>$property);
		}
		
		$obj['group'] = 'all';
		
		WorkflowUtil::executeAction('standard', $obj, 'submit', 'TimeIt_events');
	}
	
	LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
	return true;
}

function TimeIt_importapi_postschedule($prefix)
{
	if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
	
	/*if(!pnModAvailable('PostCalendar'))
	{
		
		return false;
	}*/
    
	//$prefix = pnConfigGetVar('prefix');
	
	// tables exists?
	$sql1 = "show table status like '".$prefix."_PostSchedule%'";
	$result1 = DBUtil::executeSQL($sql1);
	if($result1->RecordCount() < 2)
	{
		return LogUtil::registerError(_TIMEIT_ERROR_PSTABLE);
	}
	
	$result = DBUtil::executeSQL('SELECT * FROM '.$prefix.'_PostSchedule');
	if(!$result)
	{
		return LogUtil::registerError(_TIMEIT_ERROR_PSTABLE);
	}
	
	$repeatSpecNumToStr = array('d'=>'day', 'w'=>'week', 'm'=>'month', 'y'=>'year');
	
	// 30 sec are too little
	ini_set('max_execution_time' , 3600);
	
	// convert categroies
	$categorymap = pnModAPIFunc('TimeIt', 'import', 'psimportcats', $prefix);
	
	// import all records
	while (!$result->EOF) 
	{
		list( $pcobj[eid],
			$pcobj[uid],
			$pcobj[aid],
			$pcobj[submitdate],
			$pcobj[approveddate],
			$pcobj[startdate],
			$pcobj[enddate],
			$pcobj[starttime],
			$pcobj[endtime],
			$pcobj[title],
			$pcobj[alldayevent],
			$pcobj[repeattype],
			$pcobj[topic],
			$pcobj[body]) = $result->fields;

		$obj = array();
		$obj['title'] = $pcobj[title];
		$obj['__CATEGORIES__']['ps_imports'] = $categorymap[$pcobj[topic]];
		$obj['text'] = $pcobj[body];
		$obj['startDate'] = $pcobj[startdate];
		$obj['endDate'] = $pcobj[enddate];
			
		$obj['allDay'] = $pcobj[alldayevent];
		if((int)$obj['allDay'] == 0)
		{
			$time = explode(':',$pcobj[startTime]);
			$obj['allDayStart'] = $time[0].':'.$time[1];
			
			$time_start = strtotime($pcobj[starttime]);
			$time_end = strtotime($pcobj[endtime]);
			
			$sec = $time_end - $time_start;
			$h = $sec / 3600; // hours
			$sec = $sec % 3600; // rest
			$min = $sec / 60; // minutes
			$obj['allDayDur'] =  $h.','.$min;
		}
		$obj['sharing'] = 3;
		$obj['group'] = 'all';
		
		/*$obj['cr_uid'] = $pcobj[informant];
		$obj['cr_uid'] = pnUserGetVar('uid', pnUserGetIDFromName($obj['cr_uid']), 1);
		$obj['lu_uid'] = $obj['cr_uid'];*/
		
		if($pcobj[repeattype] != 'n')
		{
			$obj['repeatType'] = 1;
			$obj['repeatSpec'] = $repeatSpecNumToStr[$pcobj[repeattype]];
			$obj['repeatFrec'] = 1;
		} else 
		{
			$obj['endDate'] = $obj['startDate'];
		}
		
		$obj['status'] = 1;
		
		$user_count = DBUtil::selectObjectCount('users','pn_uid = '.(int)$pcobj[uid]);
		if($user_count == 1)
		{
			$obj['cr_uid'] = (int)$pcobj[uid];
			$obj['cr_date'] = $pcobj[submitdate];
		} 
		
		if((int)$pcobj[aid] == 0)
		{
			$schema = 'moderate';
		} else 
		{
			$schema = 'standard';
		}
		
		$obj['__META__']['TimeIt']['preserve'] = true;
	
		WorkflowUtil::executeAction($schema, $obj, 'submit', 'TimeIt_events');
		$result->MoveNext();
	}
	
	LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
	
	return true;
}

function TimeIt_importapi_psimportcats($prefix)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // load the admin language file
    // pull all data from the old tables
    //$prefix = pnConfigGetVar('prefix');
    $sql = "SELECT pn_topicid, pn_topicname, pn_topictext FROM {$prefix}_PostSchedule_topics";
    $result = DBUtil::executeSQL($sql);
    $categories = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $categories[] = $result->fields;
    }

    //$result->Close();
    //print_r($categories);
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = pnUserGetLang();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules');

    // create placeholder for all our migrated categories
    $cat = new PNCategory ();
    $cat->setDataField('parent_id', $rootcat['id']);
    $cat->setDataField('name', 'TimeIt_PostSchedule');
    $cat->setDataField('value', '-1');
    $cat->insert();
    $cat->update();
    $rootid = $cat->getDataField('id');
    
    // add registry entry
    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname', 'TimeIt');
    $registry->setDataField('table', 'TimeIt_events');
    $registry->setDataField('property', 'ps_imports');
    $registry->setDataField('category_id', $rootid);
    $registry->insert();
   	

    // migrate our root categories
    $categorymap = array();
    foreach ($categories as $category) {
        $cat = new PNCategory();
        $cat->setDataField('parent_id', $rootid);
        $cat->setDataField('name', $category[1]);
        $cat->setDataField('display_name', array($lang => $category[1]));
        $cat->setDataField('display_desc', array($lang => $category[2]));
        $cat->insert();
        $cat->update();
        $newcatid = $cat->getDataField('id');
        $categorymap[$category[0]] = $newcatid;
    }

    return $categorymap;
}
