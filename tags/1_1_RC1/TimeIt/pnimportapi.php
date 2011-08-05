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
	$sql1 = "show table status like '".$prefix."_postcalendar_events'";
	$sql2 = "show table status like '".$prefix."_postcalendar_categroies'";
	$result1 = DBUtil::executeSQL($sql1);
	$result2 = DBUtil::executeSQL($sql2);
	if($result1->RecordCount() != 1 || $result2->RecordCount() != 1)
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
	$categorymap = pnModAPIFunc('TimeIt', 'import', 'importcats');
	
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
		$obj['text'] = substr($obj['text'], 6); // remove :text: and :html:
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
		$obj['sharing'] = $pcobj[sharing];
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
							 'city'=>$location['event_city'],
							 'streat'=>$location['event_street1'].' '.$location['event_street1'],
							 'country'=>$location['event_state'],
							 'zip'=>$location['event_postal']);
		
		
		
		WorkflowUtil::executeAction('standard', $obj, 'submit', 'TimeIt_events');
		$result->MoveNext();
	}
	
	LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
	
	return true;
}

function TimeIt_importapi_importcats()
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // load the admin language file
    // pull all data from the old tables
    $prefix = pnConfigGetVar('prefix');
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
			}
		}
		$property = $event->getProperty("LOCATION");
		if(!empty($property))
		{
			$obj['data'] = array('city'=>$property);
		}
		
		WorkflowUtil::executeAction('standard', $obj, 'submit', 'TimeIt_events');
	}
	
	LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
	return true;
}