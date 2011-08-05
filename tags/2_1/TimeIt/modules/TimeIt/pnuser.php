<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage UI
 */

Loader::requireOnce('modules/TimeIt/common.php');

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

    $GETCID = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$GETCID);
    if(empty($calendar))
    {
        return LogUtil::registerError (_TIMEIT_INVALIDCALENDAR, 404);
    }
    
    $GETDate = FormUtil::getPassedValue('date', false, 'GETPOST');
    $GETYear = (int)FormUtil::getPassedValue('year', date("Y"), 'GETPOST');
    $GETMonth = (int)FormUtil::getPassedValue('month', date("n"), 'GETPOST');
    $GETWeek = (int)FormUtil::getPassedValue('week', date("W"), 'GETPOST');
    $GETDay = (int)FormUtil::getPassedValue('day', date("j"), 'GETPOST');
    $GETType = FormUtil::getPassedValue('viewType', 
                                        FormUtil::getPassedValue('viewtype', $calendar['defaultView'], 'GETPOST'),
                                        'GETPOST');
    $GETIcal = (int)FormUtil::getPassedValue('ical', false, 'GETPOST');
    $GETTemplate = FormUtil::getPassedValue('template', $calendar['defaultTemplate'], 'GETPOST');
    
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:Calendar:', $GETCID."::", ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }
    
    $catFilter = null;
    $cats = array();
    $shareFilter = null;
    // $GETType is a possible security hole. we check the value.
    if($GETType != 'year' && $GETType != 'month' && $GETType != 'week' && $GETType != 'day')
    { 
        $GETType = $calendar['defaultView'];
    }
    
    if($GETType == 'week')
    { // update $GETMonth and $GETDay
        $GETWeek = date('W', mktime(0, 0, 0, $GETMonth, $GETDay, $GETYear));
    } else if($GETType == 'month')
    { // update $GETWeek
        $GETWeek = date('W', mktime(0, 0, 0, $GETMonth, $GETDay, $GETYear));
    }
    
    // valid Date?
    if(!pnModAPIFunc('TimeIt','user','checkDate',array('day'=>$GETDay,'month'=>$GETMonth,'year'=>$GETYear,'week'=>$GETWeek)))
    {
        return LogUtil::registerError (_TIMEIT_INVALIDDATE);
    }
    
    $filter_obj = TimeIt_Filter::getFilterFormGETPOST();
        
    // caching
    $doCache = false;
    $cache_id=NULL;
    if($catFilter == NULL && $shareFilter == NULL && $user == NULL)
    {
        if(!$calendar['privateCalendar'])
        {
            switch ($GETType) {
                case 'month':
                    $cache_id = $calendar['id'].'|month|'.$GETYear.$GETMonth.'all';
                    $doCache = NULL;
                    break;
                case 'week':
                    $cache_id = $calendar['id'].'|week|'.$GETYear.$GETWeek.'all';
                    $doCache = NULL;
                    break;
                case 'day':
                    $cache_id = $calendar['id'].'|day|'.$GETYear.$GETMonth.$GETDay.'all';
                    $doCache = NULL;
                    break;
            }
        } else
        {
            $doCache = false;
        }
    } else if($calendar['privateCalendar'] && $shareFilter == 3 && $catFilter == NULL && $user == NULL) //3=global
    {
        switch ($GETType) {
            case 'month':
                $cache_id = $calendar['id'].'|month|'.$GETYear.$GETMonth.'global';
                $doCache = NULL;
                break;
            case 'week':
                $cache_id = $calendar['id'].'|week|'.$GETYear.$GETWeek.'global';
                $doCache = NULL;
                break;
            case 'day':
                $cache_id = $calendar['id'].'|day|'.$GETYear.$GETMonth.$GETDay.'global';
                $doCache = NULL;
                break;
        }
    } else 
    {
        $doCache = false;
    }
    
    // create pnRender
    $pnRender = pnRender::getInstance('TimeIt', $doCache);
    // check out if the contents are cached.
    if ($pnRender->is_cached(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm', $GETTemplate). $cache_id)) {
          return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm', $GETTemplate), $cache_id);
    }
 
        
    if($GETIcal)
    {
         $events =  pnModAPIFunc('TimeIt', 'user', $GETType.'Events',
            array('year' => $GETYear,
                  'month' => $GETMonth,
                  'week' => $GETWeek,
                  'day' => $GETDay,
                  'prozessRepeat' => false,
                  'cid' => $GETCID,
                  'filter_obj'=>$filter_obj
                 )
            );

        TimeIt_createIcal($events,$GETType=='day'?true:false);
    } else 
    {    
            // get events
        $events =  pnModAPIFunc('TimeIt', 'user', $GETType.'Events',
            array('year' => $GETYear,
                  'month' => $GETMonth,
                  'week' => $GETWeek,
                  'day' => $GETDay,
                  'firstDayOfWeek'=> FormUtil::getPassedValue('firstDayOfWeek', -1, 'GETPOST'),
                  'cid' => $GETCID,
                  'filter_obj'=>$filter_obj)
            );
            
        $pnRender->assign('events', $events);
        $pnRender->assign('viewType', $GETType);
        $pnRender->assign('selectedCats', $cats);
        $pnRender->assign('selectedShare', $shareFilter);
        $pnRender->assign('selectedUser', $selectedUser);
        $pnRender->assign('tiConfig', pnModGetVar('TimeIt'));
        $pnRender->assign('calendar', $calendar);
        $pnRender->assign('firstDayOfWeek',FormUtil::getPassedValue('firstDayOfWeek', -1, 'GETPOST'));
        $pnRender->assign('filter_obj', $filter_obj);
        $pnRender->assign('filter_obj_url', $filter_obj->toURL());

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

        // add meta tags for feeds
        PageUtil::addVar('rawtext', '<link rel="alternate" href="'.pnModURL('TimeIt', 'user', 'feed', array('mode'=>'last','theme'=>'Atom'),null,null,true).'" title="'._TIMEIT_FEED_LAST.'" type="application/atom+xml" />');
        PageUtil::addVar('rawtext', '<link rel="alternate" href="'.pnModURL('TimeIt', 'user', 'feed', array('mode'=>'today','theme'=>'Atom'),null,null,true).'" title="'._TIMEIT_FEED_TODAY.'" type="application/atom+xml" />');
        PageUtil::addVar('rawtext', '<link rel="alternate" href="'.pnModURL('TimeIt', 'user', 'feed', array('mode'=>'week','theme'=>'Atom'),null,null,true).'" title="'._TIMEIT_FEED_WEEK.'" type="application/atom+xml" />');
        PageUtil::addVar('rawtext', '<link rel="alternate" href="'.pnModURL('TimeIt', 'user', 'feed', array('mode'=>'month','theme'=>'Atom'),null,null,true).'" title="'._TIMEIT_FEED_MONTH.'" type="application/atom+xml" />');

        // render template and return
        return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_'.DataUtil::formatForOS($GETType).'.htm', $GETTemplate), $cache_id);
    }
}

function TimeIt_user_viewall()
{
    $filter_obj = TimeIt_Filter::getFilterFormGETPOST();
    $GETCID = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$GETCID);
    // check arguments
    if(empty($calendar)) {
        return LogUtil::registerError (_TIMEIT_INVALIDCALENDAR, 404);
    }
    
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:Calendar:', $GETCID."::", ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }
    
    // get page
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    // get evnets
    $events = pnModAPIFunc('TimeIt','user','getAll',array('cid'=>$calendar['id'],'filter_obj'=>$filter_obj,'startnum'=>$startnum,'numitems'=>$itemsperpage));

    $pnRender = pnRender::getInstance('TimeIt', false);
    // data for pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countGetAll',array('cid'=>$calendar['id'],'filter_obj'=>$filter_obj)),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign_by_ref('events', $events);  
    return $pnRender->fetch('TimeIt_user_viewByCat.htm');
}

function TimeIt_user_event()
{
    $id = (int)FormUtil::getPassedValue('id', null, 'GET');
    $dheid = (int)FormUtil::getPassedValue('dheid', null, 'GET');

    if($dheid) {
        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $dheid);
        //$obj['cid'] = $dheobj['cid'];
    } else {
        $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$id, 'date ASC');
        if(count($dheobj)) {
            $dheobj = $dheobj[0];
            //$obj['cid'] = $dheobj['cid'];
        } else {
            return LogUtil::registerError(pnML('_TIMEIT_IDNOTEXIST',array('s'=>$id)), 404);
        }
    }

    // get event
    $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$id,'dheobj'=>$dheobj));
    
    if(empty($obj) || (isset($obj) && $obj['status'] == 0))
    {
        return LogUtil::registerError(pnML('_TIMEIT_IDNOTEXIST',array('s'=>$id)), 404);
    }

    
    $calendar = pnModAPIFunc('TimeIt','calendar','get', $obj['cid']);

    // create pnRender
    $pnRender = pnRender::getInstance('TimeIt');
    // set cache id
    $pnRender->cache_id = $id;
    // check out if the contents are cached.
    if ($pnRender->is_cached(TimeIt_templateWithTheme($pnRender, 'TimeIt_user_event.htm', $calendar['defaultTemplate']))) {
          return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_event.htm', $calendar['defaultTemplate']));
    }

    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::Event', $id."::", ACCESS_READ) 
        && !TimeIt_groupPermissionCheck($obj, ACCESS_READ)
        && !SecurityUtil::checkPermission( 'TimeIt:Calendar:', $obj['cid']."::", ACCESS_READ)) 
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

            $permissionOk = SecurityUtil::checkPermission('TimeIt:Category:', $cid."::", ACCESS_OVERVIEW);
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
    $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj'=>$obj));
    
    // assign calendar
    $pnRender->assign('calendar', $calendar);
    // contact event plugin
    $pnRender->assign('eventplugin_contact', $obj['plugins']['contact']);
    // location event plugin
    $pnRender->assign('eventplugin_locations', $obj['plugins']['location']);
    
    if($obj['allDay'] == 0){
        $obj['allDayStart'] = DateUtil::getDatetime(strtotime($obj['allDayStart']), _TIMEBRIEF);
    }
    
    // return in iCalendar format
    if((int)FormUtil::getPassedValue('ical', false, 'GETPOST')) {
        // with getMasterEvent the .ics file contains the event with all occurrences
        TimeIt_createIcal(array(array(array('data'=>array(pnModAPIFunc('TimeIt','user','getMasterEvent',array('obj'=>$obj)))))), true);
        return;
    }
    
    // load the categories system
    if (!($class = Loader::loadClass('CategoryRegistryUtil')))
    {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'CategoryRegistryUtil')));
    }
    $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
    $pnRender->assign('categories', $categories);
    
    // data for naviation
    $getDate = getDate(strtotime($dheobj['date']));
    $pnRender->assign('year', $getDate['year']);
    $pnRender->assign('month', $getDate['mon']);
    $pnRender->assign('week', date('W', $getDate[0]));
    $pnRender->assign('dayAsNum', $getDate['mday']);
    $pnRender->assign('tiConfig', pnModGetVar('TimeIt'));
    $pnRender->assign('viewDate', $dheobj['date']);
    
    // data for event
    $pnRender->assign('event', $obj);
    $pnRender->assign('dheobj', $dheobj);
    $pnRender->assign('masterEvent', pnModAPIFunc('TimeIt','user','getMasterEvent',array('obj'=>$obj)));
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
    $pnRender->assign_by_ref('smarty_all_vars', $pnRender->get_template_vars());
    return $pnRender->fetch(TimeIt_templateWithTheme($pnRender,'TimeIt_user_event.htm', $calendar['defaultTemplate']));
}

function TimeIt_user_new()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    $cid = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$cid);

    // get google api key
    $apiKey = pnModGetVar('TimeIt', 'googleMapsApiKey');
    $apiKeyLocations = pnModGetVar('locations', 'GoogleMapsAPIKey');
    if(!$apiKey && $apiKeyLocations) {
        $apiKey = $apiKeyLocations;
    }

    if($calendar['enableMapView'])
    {
        PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.$apiKey);
    }
    
    /*PageUtil::addVar("rawtext", "<style type=\"text/css\"> form#pnFormForm span { margin-left:1em; } input.error, textarea.error  { border-color:red; } 
    select#repeat21, select#repeat22, select#repeatFrec1, input#repeatFrec2, select#allDayStart_m{ margin-left:0em; }
    </style>");*/
    
    $render = FormUtil::newpnForm('TimeIt');

    Loader::requireOnce('modules/TimeIt/classes/FormHandler/Event.php');
    return $render->pnFormExecute('TimeIt_user_new.htm', new Timeit_FormHandler_event('user'));
}

function TimeIt_user_modify($args=array())
{
    $cid = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$cid);

    // get google api key
    $apiKey = pnModGetVar('TimeIt', 'googleMapsApiKey');
    $apiKeyLocations = pnModGetVar('locations', 'GoogleMapsAPIKey');
    if(!$apiKey && $apiKeyLocations) {
        $apiKey = $apiKeyLocations;
    }

    if($calendar['enableMapView'])
    {
        PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.$apiKey);
    }

    /*PageUtil::addVar("rawtext", "<style type=\"text/css\"> form#pnFormForm span { margin-left:1em; } input.error, textarea.error  { border-color:red; } 
    select#repeat21, select#repeat22, select#repeatFrec1, input#repeatFrec2, select#allDayStart_m{ margin-left:0em; }
    </style>");*/
    $render = FormUtil::newpnForm('TimeIt');
    
    Loader::requireOnce('modules/TimeIt/classes/FormHandler/Event.php');
    return $render->pnFormExecute('TimeIt_user_new.htm', new Timeit_FormHandler_event('user'));
}

function TimeIt_user_delete()
{
    $eid = (int)FormUtil::getPassedValue('eid', null, 'POST');
    if(!$eid) {
         return LogUtil::registerError (_MODARGSERROR, 404);
    }
    $obj = pnModAPiFunc('TimeIt','user','get', array('id'=>$eid));
    if(!$obj) {
         return LogUtil::registerError (_MODARGSERROR, 404);
    }
    $dheobj = pnModAPIFunc('TimeIt','user','getDHE',array('obj'=>$obj));

    if($obj['group'] == 'all' || empty($obj['group'])){
        $groupObj = array('name'=>'all'); // group irrelevant
    } else {
        $groupObj = UserUtil::getPNGroup((int)$obj['group']);
    }

    $auth_self_edit = false;
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$dheobj['cid']);
    if($calendar['userCanEditHisEvents'] && $obj['cr_uid'] == pnUserGetVar('uid')) {
        $auth_self_edit = true;
    }

    $perm = SecurityUtil::checkPermission('TimeIt::', '::', ACCESS_DELETE)
           || SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", ACCESS_DELETE)
           || $auth_self_edit;

    if(!$perm) {
        return LogUtil::registerPermissionError();
    }

    WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events');
    WorkflowUtil::deleteWorkflow($obj);

    return pnRedirect(pnModURL('TimeIt', 'user'));
}

function TimeIt_user_rss()
{
    return TimeIt_user_feed();
}

/**
 * This function is not usable because it needs to much memory(5000 Evemts over 100MB), cpu and time.
 *
function TimeIt_user_ical()
{
    $GETCID = (int)FormUtil::getPassedValue('cid', false, 'GETPOST');
    
    if($GETCID === false)
    {
        return LogUtil::registerError (_TIMEIT_INVALIDCALENDAR, 404);
    }
    
    // include class
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit ("Unable to load class [Event] ...");
    }
    
    // get tables array
    $pntable = pnDBGetTables();
    $cols = $pntable['TimeIt_events_column'];
    
    // build sql where part
    $where = '';
    if($GETCID !== false && $GETCID > 0)
    {
        $where .= $cols['cid'].' = '.$GETCID;
    }
    
    
    ini_set('max_execution_time' , 120);
    
    // create class and load events
    $class = new $class();
    $array = $class->get($where);
    
    
   
    TimeIt_createIcal($array);
}*/

function TimeIt_user_atom()
{
    return TimeIt_user_feed();
}

function TimeIt_user_feed()
{
    $GETCID = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GET');
    $GETMODE = FormUtil::getPassedValue('mode', null, 'GET');
    $GETFEEDTYPE = FormUtil::getPassedValue('theme', 'RSS', 'GET');
    if($GETFEEDTYPE != 'RSS' && $GETFEEDTYPE != 'Atom') {
        $GETFEEDTYPE = 'RSS';
    }
    
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:Calendar:', $GETCID."::", ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }
    
    // include class
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit ("Unable to load class [Event] ...");
    }
    
    // get tables array
    $pntable = pnDBGetTables();
    $cols = $pntable['TimeIt_events_column'];

    $filter_obj = TimeIt_Filter::getFilterFormGETPOST();

    // build sql where part
    if($GETMODE == 'last') {
        // create class and load events
        $array = pnModAPIFunc('TimeIt','user','getAll',array('cid'=>$GETCID,'filter_obj'=>$filter_obj,'order'=>$cols['lu_date'].' DESC','numitems'=>(int)pnModGetVar('TimeIt', 'rssatomitems')));
    } else if($GETMODE == 'today') {
        $date = DateUtil::getDatetime('', _DATEINPUT);
        $events = pnModAPIFunc('TimeIt','user','getDailySortedEvents',array('cid'       =>$GETCID,
                                                                            'start'     =>$date,
                                                                            'end'       =>$date,
                                                                            'filter_obj'=>$filter_obj,
                                                                            'preformat' =>true));
        // convert multi-dimensional array to 1-dimensional array 
        
        $array = array();
        foreach($events AS $cats) {
            foreach($cats AS $cat) {
                foreach($cat['data'] AS $obj) {
                    $array[] = $obj;
                }
            }
        }
    } else if($GETMODE == 'week') {
        $date = getdate(time());
        $startDateArray = getDate(pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', array('day'=>$date['mday'],'month'=>$date['mon'],'year'=>$date['year'])));
        $startDate = DateUtil::getDatetime($startDateArray[0], _DATEINPUT);
        $endDate   = DateUtil::getDatetime(mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+6, $startDateArray['year']), _DATEINPUT);
        $events = pnModAPIFunc('TimeIt','user','getDailySortedEvents',array('cid'       =>$GETCID,
                                                                            'start'     =>$startDate,
                                                                            'end'       =>$endDate,
                                                                            'filter_obj'=>$filter_obj,
                                                                            'preformat' =>true));
        // convert multi-dimensional array to 1-dimensional array
        $array = array();
        foreach($events AS $cats) {
            foreach($cats AS $cat) {
                foreach($cat['data'] AS $obj) {
                    $array[] = $obj;
                }
            }
        }
    } else if($GETMODE == 'month') {
        $date = getdate(time());

        // get array from api function
        $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthViewV2', array('month'=>$date['mon'],'year'=>$date['year']));
        // get start date
        reset($events[0]);
        $start = each($events[0]);
        $start = $start['key'];

        // get end date
        end($events);
        $end = each($events); // last week
        $key = $end['key']; // key of last week
        end($events[$key]); // last day in last week
        $end = each($events[$key]);
        $end = $end['key'];

        $events = pnModAPIFunc('TimeIt','user','getDailySortedEvents',array('cid'       =>$GETCID,
                                                                            'start'     =>$start,
                                                                            'end'       =>$end,
                                                                            'filter_obj'=>$filter_obj,
                                                                            'preformat' =>true));
        // convert multi-dimensional array to 1-dimensional array
        $array = array();
        foreach($events AS $cats) {
            foreach($cats AS $cat) {
                foreach($cat['data'] AS $obj) {
                    $array[] = $obj;
                }
            }
        }
    } else {
        $GETFEEDTYPE = 'feed';
    }
    
    // render xml
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->assign('events', $array);
    $pnRender->assign('cid', (int)$GETCID);
    return $pnRender->fetch('TimeIt_user_'.DataUtil::formatForOS(strtolower($GETFEEDTYPE)).'.htm');
}

function TimeIt_user_subscribe($args=array())
{
    $id = (empty($args['id']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): (int)$args['id'];
    
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:subscribe:', "::", ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $id);
    $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$dheobj['eid']));
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$dheobj['cid']);

    if($id !== false && $calendar['allowSubscribe'])
    {
        $result = pnModAPIFunc('TimeIt','subscribe','subscribe', array('id'=>(int)$dheobj['id']));

        if($result)
        {
            if($obj['subscribeWPend'])
            {
                LogUtil::registerStatus (_TIMEIT_SUBSCRIBEPENDING_CHECK);
            } else
            {
                LogUtil::registerStatus (_TIMEIT_SUBSCRIBE_CHECK);
            }
        }
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
        return pnRedirect(pnModURL('TimeIt','user','event',array('id'=>($dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid']), 'dheid'=>$dheobj['id'])));
    }
}

function TimeIt_user_unsubscribe($args=array())
{
    $id = (empty($args['id']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): (int)$args['id'];

    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:subscribe:', "::", ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }
    
    $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $id);
    $calendar = pnModAPIFunc('TimeIt','calendar','get',$dheobj['cid']);

    if($id !== false && $calendar['allowSubscribe'])
    {
        $result = pnModAPIFunc('TimeIt','subscribe','unsubscribe', array('id'=>$id));
    } else 
    {
        return LogUtil::registerError(_MODARGSERROR);
    }

    // no pnRediect?
    if(isset($args['noRedirect']) && $args['noRedirect'])
    {
        return $result;
    } else
    {
        return pnRedirect(pnModURL('TimeIt','user','event',array('id'=>($dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid']), 'dheid'=>$dheobj['id'])));
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

    // no pnRediect?
    if(isset($args['noRedirect']) && $args['noRedirect'])
    {
        return $result;
    } else
    {
        return pnRedirect(pnModURL('TimeIt','user','main'));
    }
}

function TimeIt_user_viewUserOfSubscribedEvent($args=array())
{
    $id = (empty($args['id']))? (int)FormUtil::getPassedValue('id', false, 'GETPOST'): (int)$args['id'];

    $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $id);
    $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>($dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid']),'dheobj'=>$dheobj));
    //$obj = pnModAPIFunc('TimeIt','user','getEvent',array('id'=>$id));

    if(empty($obj) || (isset($obj) && $obj['status'] == 0)) {
        return LogUtil::registerError(pnML('_TIMEIT_IDNOTEXIST',array('s'=>$id)), 404);
    }

    $calendar = pnModAPIFunc('TimeIt','calendar','get',$dheobj['cid']);

    if($id !== false && $calendar['allowSubscribe'])
    {
        /*if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
        }
        
        $object = new $class();
        $obj = $object->getEvent($id);*/

        $args = array('id'=>$id);
        $showAddress = false;
        if(SecurityUtil::checkPermission( 'TimeIt:subscribe:', "::", ACCESS_DELETE) 
           || SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN) 
           || $obj['cr_uid'] == pnUserGetVar('uid'))
        {
            $args['withPending'] = true;
            $showAddress = true;
        }
        $pnRender = pnRender::getInstance('TimeIt');
        $pnRender->assign('eid', $id);
        $pnRender->assign('date', $date);
        $pnRender->assign('event', $obj);
        $pnRender->assign('showAddress', $showAddress);
        $pnRender->assign('uid', pnUserGetVar('uid'));
        $pnRender->assign('calendar', $calendar);
        $pnRender->assign('users', pnModAPIFunc('TimeIt','subscribe','userArrayForEvent', $args));
        
        if(FormUtil::getPassedValue('format', false, 'GETPOST') == 'csv')
        {
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="users.csv"' );
            echo $pnRender->fetch('TimeIt_user_viewUserOfSubscribedEvent_CSV.htm');
            return true;
        } else 
        {
            return $pnRender->fetch('TimeIt_user_viewUserOfSubscribedEvent.htm');
        }
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
        if (!($class = Loader::loadClassFromModule('TimeIt', 'Reg'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        $class = new $class();
        $obj = $class->get((int)$id);
        
        // record found?
        if($obj)
        {
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
        
            // load event
            $object = new $class();
            $eobj = $object->getEvent($obj['eid']);
            // get group name
            if($eobj['group'] == 'all')
            {
                $groupObj = array('name'=>'all'); // gorup irrelevant
            } else {
                $groupObj = UserUtil::getPNGroup((int)$eobj['group']);
            }
            
            // Security check
            if($eobj['subscribeWPend'] && ($eobj['cr_uid'] = pnUserGetVar('uid') || (SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_MODERATE)  
                                                                                     || SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", ACCESS_MODERATE) )))
            {
                $result = pnModAPIFunc('TimeIt','subscribe','deletePendingState', (int)$id);
                if($result && pnUserGetVar('uid') > 1)
                {
                    $message = pnML('_TIMEIT_SUBSCRIBEPENDING_MAIL_MSG', array('title'=>$obj['title']));
                    pnMail(pnUserGetVar('email'), _TIMEIT_SUBSCRIBEPENDING_MAIL, $message);
                }
            } else 
            {
                return LogUtil::registerPermissionError();
            }
        } else 
        {
            $result = false;
        }
    } else 
    {
        $result = false;
    }

    // no pnRediect?
    if(isset($args['noRedirect']) && $args['noRedirect'])
    {
        return $result;
    } else
    {
        return pnRedirect(pnModURL('TimeIt','user','main'));
    }
}

function TimeIt_user_formicula_send()
{
    $ret = pnModFunc('formicula','user','send');

    // test against true because formicula uses pnRedirect() which returns true
    if($ret === true) {
        
        return pnRedirect(pnModURL('TimeIt','user','event', array('id'=>FormUtil::getPassedValue('timeit_eid', null, 'GETPOST'),
                                                                  'dheid'=>FormUtil::getPassedValue('timeit_dheid', null, 'GETPOST'))));
    } else {
        return $ret;
    }
}

function TimeIt_user_rruleTest2()
{
    require 'modules/TimeIt/classes/Recurrence.php';
     $count = 0;
    for($i = 0; $i < 20; $i++)
    {
        $obj = new Horde_Date_Recurrence(array('year'=>1997,'month'=>1,'day'=>1));
        $obj->setRecurType(HORDE_DATE_RECUR_WEEKLY);
        $obj->setRecurInterval(1);
                        // Recur on the day of the week of the original
                        // recurrence.
                        $maskdays = array(
                            HORDE_DATE_SUNDAY => HORDE_DATE_MASK_SUNDAY,
                            HORDE_DATE_MONDAY => HORDE_DATE_MASK_MONDAY,
                            HORDE_DATE_TUESDAY => HORDE_DATE_MASK_TUESDAY,
                            HORDE_DATE_WEDNESDAY => HORDE_DATE_MASK_WEDNESDAY,
                            HORDE_DATE_THURSDAY => HORDE_DATE_MASK_THURSDAY,
                            HORDE_DATE_FRIDAY => HORDE_DATE_MASK_FRIDAY,
                            HORDE_DATE_SATURDAY => HORDE_DATE_MASK_SATURDAY);
                        $obj->setRecurOnDay($maskdays[$obj->getRecurStart()->dayOfWeek()]);

        $count = 0;
        $out = "Dates: <br />";
        $date = array('year'=>1997,'month'=>3,'day'=>1);
        while(( $date=$obj->nextRecurrence($date) ) != false && $count <= 8)
        {
            $out .= $date->cTime()."<br />";
            $date->mday++;
            $count++;
        }
         $count = $i;
    }
    $out .= '$count='.$count;
    return $out;
}

function TimeIt_user_rruleTest()
{
    $obj = array();
    $obj['startDate'] = '1997-01-01';
    $obj['endDate'] = '2020-12-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'WEEKLY','INTERVAL'=>1));

    $ret = array();

pnModAPIFunc('TimeIt','user','navdates',array('month'=>1,'year'=>2008));

    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-03-01', '1997-04-31');

    $out = "ret:<br />";
    foreach ($ret as $stamp => $row)
    {
        $out .= DateUtil::getDatetime($stamp, _DATEINPUT).'<br />';
    }
    return $out;
}

function TimeIt_user_rruleTests()
{
    pnModAPILoad('TimeIt','user'); // load api

    $output = 'RRULE Tests:<br />';


    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-07';
    $obj['endDate'] = '1998-09-22';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'DAILY','COUNT'=>5));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-12-31');
    $output .= 'Daily for 5 occurrences:';
    if(isset($ret[strtotime('1997-09-07')])
    && isset($ret[strtotime('1997-09-08')])
    && isset($ret[strtotime('1997-09-09')])
    && isset($ret[strtotime('1997-09-10')])
    && isset($ret[strtotime('1997-09-11')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------
        
        //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-01-01';
    $obj['endDate'] = '1997-01-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'WEEKLY','INTERVAL'=>1,'COUNT'=>3));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-01-01', '1997-01-31');
    $output .= 'Every Week for 3 occurrences:';
    if(isset($ret[strtotime('1997-01-01')])
    && isset($ret[strtotime('1997-01-08')])
    && isset($ret[strtotime('1997-01-15')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------
        
        //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-01-01';
    $obj['endDate'] = '1997-02-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'WEEKLY','INTERVAL'=>3,'COUNT'=>3));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-01-01', '1997-02-31');
    $output .= 'Every third Week for 3 occurrences:';
    if(isset($ret[strtotime('1997-01-01')])
    && isset($ret[strtotime('1997-01-22')])
    && isset($ret[strtotime('1997-02-12')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------
        
        //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-01-01';
    $obj['endDate'] = '1999-02-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'YEARLY','INTERVAL'=>1,'COUNT'=>3));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-01-01', '1999-02-31');
    $output .= 'Every Year for 3 occurrences:';
    if(isset($ret[strtotime('1997-01-01')])
    && isset($ret[strtotime('1998-01-01')])
    && isset($ret[strtotime('1999-01-01')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-07';
    $obj['endDate'] = '1998-09-22';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'DAILY','COUNT'=>5,'INTERVAL'=>3));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-12-31');
    $output .= 'Every 3 days, 5 occurrences:';
    if(isset($ret[strtotime('1997-09-07')])
    && isset($ret[strtotime('1997-09-10')])
    && isset($ret[strtotime('1997-09-13')])
    && isset($ret[strtotime('1997-09-16')])
    && isset($ret[strtotime('1997-09-19')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-07';
    $obj['endDate'] = '1997-10-22';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'DAILY','BYMONTH'=>array(9)));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-10-20');
    $output .= 'Everyday in September:';
    if(isset($ret[strtotime('1997-09-07')])
    && isset($ret[strtotime('1997-09-08')])
    && isset($ret[strtotime('1997-09-17')])
    && isset($ret[strtotime('1997-09-29')])
    && isset($ret[strtotime('1997-09-30')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-07';
    $obj['endDate'] = '1998-10-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'WEEKLY','COUNT'=>5));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-10-31');
    $output .= 'Weekly for 5 occurrences:';
    if(isset($ret[strtotime('1997-09-07')])
    && isset($ret[strtotime('1997-09-14')])
    && isset($ret[strtotime('1997-09-21')])
    && isset($ret[strtotime('1997-09-28')])
    && isset($ret[strtotime('1997-10-05')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-05';
    $obj['endDate'] = '1998-12-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'WEEKLY','COUNT'=>5,'INTERVAL'=>2));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-12-31');
    $output .= 'Every other week for 5 occurrences:';
    if(isset($ret[strtotime('1997-09-05')])
    && isset($ret[strtotime('1997-09-19')])
    && isset($ret[strtotime('1997-10-03')])
    && isset($ret[strtotime('1997-10-17')])
    && isset($ret[strtotime('1997-10-31')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-05';
    $obj['endDate'] = '1998-12-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'WEEKLY','COUNT'=>7,'INTERVAL'=>2,'BYDAY'=>array(array('DAY'=>'MO'),array('DAY'=>'WE'),array('DAY'=>'FR'))));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-12-31');
    $output .= 'Every other week on Monday, Wednesday and Friday:';
    if(isset($ret[strtotime('1997-09-05')])
    && isset($ret[strtotime('1997-09-15')])
    && isset($ret[strtotime('1997-09-17')])
    && isset($ret[strtotime('1997-09-19')])
    && isset($ret[strtotime('1997-09-29')])
    && isset($ret[strtotime('1997-10-01')]))
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-07';
    $obj['endDate'] = '1998-09-22';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'MONTHLY','COUNT'=>10,'BYDAY'=>array(array(0=>1,'DAY'=>'SU'),array(0=>-1,'DAY'=>'SU'))));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-01', '1997-12-31');
    $output .= 'Every month on the 1st and last Sunday of the month:';
    if(isset($ret[strtotime('1997-09-07')])
    && isset($ret[strtotime('1997-09-28')])
    && isset($ret[strtotime('1997-10-05')])
    && isset($ret[strtotime('1997-10-26')])
    && isset($ret[strtotime('1997-11-02')])
    && isset($ret[strtotime('1997-11-30')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-09-30';
    $obj['endDate'] = '1998-12-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'MONTHLY','BYMONTHDAY'=>array(1,-1)));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-09-30', '1997-12-31');
    $output .= 'Monthly on the first and last day of the month:';
    if(isset($ret[strtotime('1997-09-30')])
    && isset($ret[strtotime('1997-10-01')])
    && isset($ret[strtotime('1997-10-31')])
    && isset($ret[strtotime('1997-11-01')])
    && isset($ret[strtotime('1997-11-30')])
    && isset($ret[strtotime('1997-12-01')])
    && isset($ret[strtotime('1997-12-31')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    //------ TEST--------
    $obj = array();
    $obj['startDate'] = '1997-01-01';
    $obj['endDate'] = '2006-12-31';
    $obj['repeatSpec'] = serialize(array('FREQ'=>'YEARLY','INTERVAL'=>3,'COUNT'=>10,'BYYEARDAY'=>array(1,100,200)));
    $ret = array();
    TimeIt_userapi_icalRruleProzess($ret, $obj, '1997-01-01', '2006-12-31');
    $output .= 'Yearly on the 1st, 100th and 200th day:';
    if(isset($ret[strtotime('1997-01-01')])
    && isset($ret[strtotime('1997-04-10')])
    && isset($ret[strtotime('1997-07-19')])
    && isset($ret[strtotime('2000-01-01')])
    && isset($ret[strtotime('2000-04-09')])
    && isset($ret[strtotime('2000-07-18')])
    && isset($ret[strtotime('2003-01-01')])
    && isset($ret[strtotime('2003-04-10')])
    && isset($ret[strtotime('2003-07-19')])
    && isset($ret[strtotime('2006-01-01')]) )
    {
        $output .= ' OK<br />';
    } else
    {
        $output .= ' Failed<br />';
    }
    //------- END TEST -------

    return $output;
}
