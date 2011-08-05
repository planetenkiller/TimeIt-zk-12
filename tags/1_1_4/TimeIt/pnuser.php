<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

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
    $GETType = FormUtil::getPassedValue('viewType', 
                                        FormUtil::getPassedValue('viewtype', pnModGetVar('TimeIt', 'defaultView'), 'GETPOST'), 
                                        'GETPOST');
    $GETTemplate = FormUtil::getPassedValue('template', pnModGetVar('TimeIt', 'defaultTemplate'), 'GETPOST');
    $catFilter = null;
    $cats = array();
    $shareFilter = null;
    // $GETType is a possible security hole. we check the values.
    if($GETType != 'year' && $GETType != 'month' && $GETType != 'week' && $GETType != 'day')
    { 
    	$GETType = pnModGetVar('TimeIt', 'defaultView');
    }
    
    if($GETType == 'week')
    { // update $GETMonth and $GETDay
    	$timestamp = pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', array('year'=>$GETYear,'weeknr'=>$GETWeek));
    	$getDate = getDate($timestamp);
    	$GETDay = $getDate['mday'];
    	$GETMonth = $getDate['mon'];
    } else if($GETType == 'month')
    { // update $GETWeek
    	$GETWeek = date('W', mktime(0, 0, 0, $GETMonth, $GETDay, $GETYear));
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
    
    // filters
    /*if(FormUtil::getPassedValue('submit', null, 'GETPOST') != null && FormUtil::getPassedValue('submit', null, 'GETPOST') == "filter")
    {*/
    	// categorie Filter
    	$cats = FormUtil::getPassedValue('cat', array(), 'GETPOST');
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
        
        
        // share Filter
        if(FormUtil::getPassedValue('share', null, 'GETPOST') != null)
        {
        	$shareFilter = FormUtil::getPassedValue('share', null, 'GETPOST');
        	if($shareFilter != 1 && $shareFilter != 2 && $shareFilter != 3 && $shareFilter != 4)
        	{
        		$shareFilter = null;
        	}
        }
        
        $selectedUser = NULL;
        // user Filter
        if(FormUtil::getPassedValue('user', null, 'GETPOST') != null)
        {
        	// get username
        	$user = FormUtil::getPassedValue('user', null, 'GETPOST');
        	// 'User Name' is the default value -> reset filter
        	if($user == 'User Name')
        	{
        		$user = NULL;
        	}
        	// username empty?
        	if(empty($user))
        	{
        		$user = null;
        	} else {
        		$uid = pnUserGetIDFromName($user); // get user id form user name
	        	// no user found?
        		if(empty($uid))
	        	{
	        		// show error
	        		LogUtil::registerError(pnML('_TIMEIT_ERROR_USERNOFOUND',array('s'=>$user)));
	        		// reset filter
	        		$user = null;
	        	} else {
	        		$selectedUser = $user;
	        		$user = $uid;
	        		// reset share filter to all
	        		$shareFilter = null;
	        	}
        	}
        }
    /*}*/
        
        
    // create pnRender
    $pnRender = pnRender::getInstance('TimeIt', $doCache);
    
    // caching
   	$doCache = false;
    if($catFilter == NULL && $shareFilter == NULL && $user == NULL)
    {
    	if(!pnModGetVar('TimeIt', 'privateCalendar'))
    	{
		    switch ($GETType) {
		    	case 'month':
		    		$pnRender->cache_id = 'month|'.$GETYear.$GETMonth.'all';
		    		$doCache = NULL;
		    		break;
		    	case 'week':
		    		$pnRender->cache_id = 'week|'.$GETYear.$GETWeek.'all';
		    		$doCache = NULL;
		    		break;
		    	case 'day':
		    		$pnRender->cache_id = 'day|'.$GETYear.$GETMonth.$GETDay.'all';
		    		$doCache = NULL;
		    		break;
	    	}
    	} else 
    	{
    		$doCache = false;
    	}
    } else if(pnModGetVar('TimeIt', 'privateCalendar') && $shareFilter == 3 && $catFilter == NULL && $user == NULL) //3=global
    {
	    switch ($GETType) {
	    	case 'month':
	    		$pnRender->cache_id = 'month|'.$GETYear.$GETMonth.'global';
	    		$doCache = NULL;
	    		break;
	    	case 'week':
	    		$pnRender->cache_id = 'week|'.$GETYear.$GETWeek.'global';
	    		$doCache = NULL;
	    		break;
	    	case 'day':
	    		$pnRender->cache_id = 'day|'.$GETYear.$GETMonth.$GETDay.'global';
	    		$doCache = NULL;
	    		break;
	    }
    } else 
    {
    	$doCache = false;
    }
    
    // check out if the contents are cached.
    if ($pnRender->is_cached(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm', $GETTemplate))) {
      	return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm', $GETTemplate));
    }
 
    // get events
    $events =  pnModAPIFunc('TimeIt', 'user', $GETType.'Events', 
        array('year' => $GETYear,                   
              'month' => $GETMonth,
              'week' => $GETWeek,
              'day' => $GETDay,
              'catFilter' => $catFilter,
        	  'shareFilter' => $shareFilter,
        	  'userFilter'  => $user)
        );
      
    $pnRender->assign('events', $events);
    $pnRender->assign('viewType', $GETType);
    $pnRender->assign('selectedCats', $cats);
    $pnRender->assign('selectedShare', $shareFilter);
    $pnRender->assign('selectedUser', $selectedUser);
    $pnRender->assign('tiConfig', pnModGetVar('TimeIt'));
    
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
    $pnRender->assign('TiTheme', $GETTemplate);
    // render template and return
    return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm', $GETTemplate));
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
    if (!SecurityUtil::checkPermission( 'TimeIt::Event', $id."::", ACCESS_READ) || !TimeIt_groupPermissionCheck($obj, ACCESS_READ)) 
    {
        return LogUtil::registerPermissionError();
    }
    
	// check permissions
    if(count($obj['__CATEGORIES__']) > 0)
    {
   		$permissionOk = false;
	    foreach ($obj['__CATEGORIES__'] AS $cat)
	    {
	    	$cid = $cat;
	        if(is_array($cat))
	        {
	          	$cid = $cat['id'];
	        }
	            	
		    $permissionOk = SecurityUtil::checkPermission('TimeIt:Category:', $cid."::", ACCESS_READ);
		    if($permissionOk)
		    {   // user has got permission -> stop permission checks
		      	break;
		    }
	    }
	    // no permission -> irgnore
	    if(!$permissionOk)
	    {
	     	return LogUtil::registerPermissionError();
	    }
	}
    
    
    // --------------- ContactList integration --------------------
	if (pnModAvailable('ContactList') && pnModGetVar('TimeIt', 'friendCalendar')) 
    {
        $buddys = pnModAPIFunc('ContactList','user','getBuddyList',array('uid'=>pnUserGetVar('uid')));
    } else 
    {
        $buddys = array();
    }
    if((int)$obj['sharing'] == 4 && $obj['cr_uid'] != pnUserGetVar('uid'))
    {
    	$buddyFound = false;
        foreach($buddys AS $buddy)
        {
        	if($buddy['uid'] == $obj['cr_uid'])
            {
            	$buddyFound = true;
            	break;
            }
        }
        if(!$buddyFound)
        {
            return LogUtil::registerPermissionError(); // no buddy connection to cr_uid -> permission error
        }
    }
    // --------------- ContactList integration --------------------
    
    // set the page title
    PageUtil::setVar('title', $obj['title']);   
    
    $obj['cr_name'] = pnUserGetVar('uname', (int)$obj['cr_uid']);
    $obj['cr_datetime'] = DateUtil::getDatetime(strtotime($obj['cr_date']), _DATETIMEBRIEF);
    if(substr($obj['text'],0,11) == "#plaintext#")
    {
    	$obj['text'] = substr_replace($obj['text'],"",0,11);
    	$obj['text'] = nl2br($obj['text']);
    }
    $obj['text'] = pnModCallHooks('item', 'transform', '', array($obj['text']));
    $obj['text'] = $obj['text'][0];
    $temp = explode(' ', $obj['repeatSpec']);
    $obj['repeat21'] = $temp[0];
    $obj['allDayDur'] = explode(',', $obj['allDayDur']);
    $obj['repeat22'] = $temp[1];
    if($obj['group'] == 'all')
    {
     	$groupObj = array('name'=>'all'); // group irrelevant
    } else {
    	$groupObj = UserUtil::getPNGroup((int)$obj['group']);
    }
    $obj['group_name'] = $groupObj['name'];
    
    if(pnModAvailable('locations') && isset($obj['data']['locations']) && !empty($obj['data']['locations']))
    {
    	$pnRender->assign('locations', pnModAPIFunc('locations','user','getLocationByID',array('locationid'=>(int)$obj['data']['locations'])));
    }
    
    if($obj['allDay'] == 0)
    {
    	$obj['allDayStart'] = DateUtil::getDatetime(strtotime($obj['allDayStart']), _TIMEBRIEF);
    }
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
    $pnRender->assign('tiConfig', pnModGetVar('TimeIt'));
    
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
    									  'fee' => _TIMEIT_FEE,
    									  'name'=> 'Name',
    									  'state'=>'State',
    									  'fax'=> 'Fax'));
    $pnRender->assign('currentUserId', pnUserGetVar('uid',-1,1)); // deafult 1 = Annonymous User
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

function TimeIt_user_subscribe($args=array())
{
	$id = (empty($args['eid']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): $args['eid'];
	
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:subscribe:', "::", ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }
	
	if($id !== false && pnModGetVar('TimeIt', 'allowSubscribe'))
	{
		$result = pnModAPIFunc('TimeIt','subscribe','subscribe', array('eid'=>(int)$id));
	} else 
	{
		return LogUtil::registerError(_MODARGSERROR);
	}
	
	// no pnRediect()?
	if(isset($args['noRedirect']) && $args['noRedirect'])
	{
		return $result;
	} else
	{
		return pnRedirect(pnModURL('TimeIt','user','event',array('id'=>$id)));
	}
}

function TimeIt_user_unsubscribe($args=array())
{
	$id = (empty($args['eid']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): $args['eid'];
	
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:subscribe:', "::", ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }
	
	if($id !== false && pnModGetVar('TimeIt', 'allowSubscribe'))
	{
		$result = pnModAPIFunc('TimeIt','subscribe','unsubscribe', array('eid'=>$id));
	} else 
	{
		return LogUtil::registerError(_MODARGSERROR);
	}
	
	// no pnRediect()?
	if(isset($args['noRedirect']) && $args['noRedirect'])
	{
		return $result;
	} else
	{
		return pnRedirect(pnModURL('TimeIt','user','event',array('id'=>$id)));
	}
}

function TimeIt_user_deleteSubscribUser($args=array())
{
	$id = (empty($args['id']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): $args['id'];
	
	if($id)
	{
		$result = pnModAPIFunc('TimeIt','subscribe','delete', (int)$id);
	} else 
	{
		$result = false;
	}
	
	// no pnRediect()?
	if(isset($args['noRedirect']) && $args['noRedirect'])
	{
		return $result;
	} else
	{
		return pnRedirect(pnModURL('TimeIt','user','main'));
	}
}

function TimeIt_user_viewUserOfSubscribedEvent($eid=false)
{
	$id = (empty($eid))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): $eid;

	if($id !== false && pnModGetVar('TimeIt', 'allowSubscribe'))
	{
		if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
	        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
	    }
	    $object = new $class();
    	$obj = $object->getEvent($id);
		if(empty($obj) || (isset($obj) && $obj['status'] == 0))
	    {
	        return LogUtil::registerError(pnML('_TIMEIT_IDNOTEXIST',array('s'=>$id)), 404);
	    }
		
		$args = array('eid'=>$id);
		if(SecurityUtil::checkPermission( 'TimeIt:subscribe:', "::", ACCESS_DELETE) 
		   || SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN) 
		   || $obj['cr_uid'] == pnUserGetVar('uid'))
		{
		   	$args['withPending'] = true;
		}
		$pnRender = pnRender::getInstance('TimeIt');
		$pnRender->assign('eid', $id);
		$pnRender->assign('event', $obj);
		$pnRender->assign('uid', pnUserGetVar('uid'));
		$pnRender->assign('users', pnModAPIFunc('TimeIt','subscribe','userArrayForEvent', $args));
		return $pnRender->fetch('TimeIt_user_viewUserOfSubscribedEvent.htm');
	} else 
	{
		LogUtil::registerError(_MODARGSERROR, 404);
	}
}

function TimeIt_user_viewSubscribedEventsOfUser()
{
	$pnRender = pnRender::getInstance('TimeIt');
	$pnRender->assign('events', pnModAPIFunc('TimeIt','subscribe','arrayOfEventsForUser'));
	return $pnRender->fetch('TimeIt_user_viewSubscribedEventsOfUser.htm');
}

function TimeIt_user_deletePendingStateOfSubscribedUser($args=array())
{
	$id = (empty($args['id']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): $args['id'];
	
	if($id)
	{
		$result = pnModAPIFunc('TimeIt','subscribe','deletePendingState', (int)$id);
	} else 
	{
		$result = false;
	}
	
	// no pnRediect()?
	if(isset($args['noRedirect']) && $args['noRedirect'])
	{
		return $result;
	} else
	{
		return pnRedirect(pnModURL('TimeIt','user','main'));
	}
}
