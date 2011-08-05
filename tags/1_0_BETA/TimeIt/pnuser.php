<?php

Loader::includeOnce('modules/TimeIt/common.php');


function TimeIt_user_main()
{
    return pnModFunc('TimeIt', 'user', 'view');
}

function TimeIt_user_view()
{
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }
	
	$GETYear = (int)FormUtil::getPassedValue('year', date("Y"), 'GETPOST');
    $GETMonth = (int)FormUtil::getPassedValue('month', date("n"), 'GETPOST');
    $GETWeek = (int)FormUtil::getPassedValue('week', date("W"), 'GETPOST');
    $GETDay = (int)FormUtil::getPassedValue('day', date("j"), 'GETPOST');
    $GETType = FormUtil::getPassedValue('viewType', pnModGetVar('TimeIt', 'defaultView'), 'GETPOST');
    $catFilter = null;
    $cats = array();
    $shareFilter = null;
    // $GETType is a possible security hole. we check the values.
    if($GETType != 'month' && $GETType != 'week' && $GETType != 'day')
    { 
    	$GETType = pnModGetVar('TimeIt', 'defaultView');
    }
    
	// valid Date?
	if(!pnModAPIFunc('TimeIt','user','checkDate',array('day'=>$GETDay,'month'=>$GETMonth,'year'=>$GETYear,'week'=>$GETWeek)))
    {
    	LogUtil::registerError (_TIMEIT_INVALIDDATE);
    	// invalid date, we use today as date
    	$GETYear = (int)date("Y");
    	$GETMonth = (int)date("n");
    	$GETWeek = (int)date("W");
    	$GETDay = (int)date("j");
    }
    
    if($GETType == 'week')
    { // update $GETMonth and $GETDay
    	$timestamp = pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', array('year'=>$GETYear,'weeknr'=>$GETWeek));
    	$getDate = getDate($timestamp);
    	$GETDay = $getDate['mday'];
    	$GETMonth = $getDate['mon'];
    }
    
    // caching
    switch ($GETType) {
    	case 'month':
    		$pnRender->cache_id = 'month'.$GETYear.$GETMonth;
    		break;
    	case 'week':
    		$pnRender->cache_id = 'week'.$GETYear.$GETWeek;
    		break;
    	case 'day':
    		$pnRender->cache_id = 'day'.$GETYear.$GETMonth.$GETDay;
    		break;
    }
	// create pnRender
    $pnRender = pnRender::getInstance('TimeIt');
    // check out if the contents are cached.
    if ($pnRender->is_cached(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm'))) {
      	return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm'));
    }
    
    // categorie/share filter
    if(FormUtil::getPassedValue('submit', null, 'GETPOST') != null && FormUtil::getPassedValue('submit', null, 'GETPOST') == "filter")
    {
    	$cats = FormUtil::getPassedValue('cat', null, 'GETPOST');
        $catFilter = array();
        foreach($cats AS $prop => $val)
        {
        	if($val != 0)
        	{
        		$catFilter[$prop] = $val;
        	}
        }
        if(empty($catFilter))
        {
        	$catFilter = null;
        }
        
        
        
        if(FormUtil::getPassedValue('share', null, 'GETPOST') != null)
        {
        	$shareFilter = FormUtil::getPassedValue('share', null, 'GETPOST');
        	if($shareFilter != 1 && $shareFilter != 2 && $shareFilter != 3)
        	{
        		$shareFilter = null;
        	}
        }
    }
 
    // get events
    $events =  pnModAPIFunc('TimeIt', 'user', $GETType.'Events', 
        array('year' => $GETYear,                   
              'month' => $GETMonth,
              'week' => $GETWeek,
              'day' => $GETDay,
              'catFilter' => $catFilter,
        	  'shareFilter' => $shareFilter)
        );
      
    $pnRender->assign('events', $events);
    $pnRender->assign('viewType', $GETType);
    $pnRender->assign('selectedCats', $cats);
    $pnRender->assign('selectedShare', $shareFilter);
    
    // load the categories system
    if (!Loader::loadClass('CategoryRegistryUtil'))
        pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
    $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
    $pnRender->assign('categories', $categories);
    
    $pnRender->assign('year', $GETYear);
    $pnRender->assign('month', $GETMonth);
    $pnRender->assign('week', $GETWeek);
    $pnRender->assign('dayAsNum', $GETDay);
    
    $pnRender->assign('dayNumNow', DateUtil::getDatetime(null, _DATEINPUT));
    $pnRender->assign('dayAsDate', DateUtil::getDatetime(mktime(0, 0, 0, $GETMonth, $GETDay, $GETYear), _DATEINPUT));
    
    $pnRender->assign('month_startDate', DateUtil::getDatetime(mktime(0, 0, 0, $GETMonth, 1, $GETYear), _DATEINPUT)  );
    $pnRender->assign('month_endDate', DateUtil::getDatetime(mktime(0, 0, 0, $GETMonth, DateUtil::getDaysInMonth($GETMonth, $GETYear), $GETYear), _DATEINPUT) );
    
    $pnRender->assign('monthtoday', pnModGetVar('TimeIt', 'monthtoday'));
    $pnRender->assign('monthoff', pnModGetVar('TimeIt', 'monthoff'));
    $pnRender->assign('monthon', pnModGetVar('TimeIt', 'monthon'));
    // render template and return
    return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm'));
}

function TimeIt_user_event()
{
	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    
    $id = (int)FormUtil::getPassedValue('id', null, 'GET');
    
    // create pnRender
    $pnRender = pnRender::getInstance('TimeIt');
    // set cache id
    $pnRender->cache_id = $id;
	// check out if the contents are cached.
    if ($pnRender->is_cached(TimeIt_templateWithTheme($pnRender, 'TimeIt_user_event.htm'))) {
      	return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_event.htm'));
    }
    // get event
    $object = new $class();
    $obj = $object->getEvent($id);
    if(empty($obj) || (isset($obj) && $obj['status'] == 0))
    {
        return LogUtil::registerError(pnML('_TIMEIT_IDNOTEXIST',array('s'=>$id)), 404);
    }
    
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::Event', $id."::", ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }
    
    // set the page title
    PageUtil::setVar('title', $obj['title']);
    
    $obj['cr_name'] = pnUserGetVar('uname', (int)$obj['cr_uid']);
    $obj['cr_datetime'] = DateUtil::getDatetime(strtotime($obj['cr_date']), _DATETIMEBRIEF);
    $obj['text'] = pnModCallHooks('item', 'transform', '', array($obj['text']));
    $obj['text'] = $obj['text'][0];
    $temp = explode(' ', $obj['repeatSpec']);
    $obj['repeat21'] = $temp[0];
    $obj['repeat22'] = $temp[1];
    if($obj['group'] == 'all')
    {
     	$groupObj = array('name'=>'all'); // group irrelevant
    } else {
    	$groupObj = UserUtil::getPNGroup((int)$obj['group']);
    }
    $obj['group_name'] = $groupObj['name'];
    
    
    $getDate = getDate(strtotime($obj['startDate']));
    
    // load the categories system
    if (!($class = Loader::loadClass('CategoryRegistryUtil')))
    {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'CategoryRegistryUtil')));
    }
    $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
    $pnRender->assign('categories', $categories);
    // data vor naviation
    $pnRender->assign('year', $getDate['year']);
    $pnRender->assign('month', $getDate['mon']);
    $pnRender->assign('week', date('W', $getDate[0]));
    $pnRender->assign('dayAsNum', $getDate['mday']);
    
    // data for event
    $pnRender->assign('event', $obj);
    $pnRender->assign('dayNames', explode(" ", _DAY_OF_WEEK_SHORT));
    $pnRender->assign('dayFrec', array('day' => _DAYS,
                                       'week' => _WEEKS,
                                       'month' => _MONTHS,
                                       'year' => _YEARS));
    $pnRender->assign('frec', array(1  => 'First',
                                    2  => 'Second',
                                    3  => 'Third',
                                    4 => 'Fourth',
                                    5 => 'Last'));
    $pnRender->assign('dataIdToML', array('contactPerson' => _TIMEIT_CONTACTPERSON,
    									  'email' => _EMAIL,
    									  'phoneNr' => _TIMEIT_PHONE,
    									  'website' => _TIMEIT_WEBSITE,
    									  'city' => _TIMEIT_CITY,
    									  'streat' => _TIMEIT_STREAT,
    									  'houseNumber' => _TIMEIT_HOUSENUMBER,
    									  'country' => _TIMEIT_COUNTRY,
    									  'zip' => _TIMEIT_ZIP,
    									  'fee' => _TIMEIT_FEE));
    return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_event.htm'));
}

function TimeIt_user_rss()
{
	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
    	pn_exit ("Unable to load class [Event] ...");
    }
    
    $pntable = pnDBGetTables();
    $cols = $pntable['TimeIt_events_column'];
    
    $class = new $class();
    $array = $class->get('', $cols['lu_date'].' DESC', -1, (int)pnModGetVar('TimeIt', 'rssatomitems'));
	
	$pnRender = pnRender::getInstance('TimeIt');
	$pnRender->assign('events', $array);
	return $pnRender->fetch('TimeIt_user_rss.htm');
}

function TimeIt_user_atom()
{
	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
    	pn_exit ("Unable to load class [Event] ...");
    }
    
    $pntable = pnDBGetTables();
    $cols = $pntable['TimeIt_events_column'];
    
    $class = new $class();
    $array = $class->get('', $cols['lu_date'].' DESC', -1, (int)pnModGetVar('TimeIt', 'rssatomitems'));
	
	$pnRender = pnRender::getInstance('TimeIt');
	$pnRender->assign('events', $array);
	return $pnRender->fetch('TimeIt_user_atom.htm');
}
