<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage API
 */

Loader::includeOnce('modules/TimeIt/common.php');

function TimeIt_userapi_loadLang()
{
    pnModLangLoad('TimeIt');
}

/**
 * get meta data for the module
 */
function TimeIt_userapi_getmodulemeta()
{
    return array('viewfunc'    => 'view',
                 'displayfunc' => 'event',
                 'newfunc'     => 'new',
                 'createfunc'  => 'new',
                 'modifyfunc'  => 'modify',
                 'updatefunc'  => 'modify',
                 'deletefunc'  => 'delete',
                 'titlefield'  => 'title',
                 'itemid'      => 'id');
}

/**
 * Return an event by the id.
 * @param id ['id'] of an event
 *           ['translate'] true=translate title and text (default true)
 * @return array
 */
function TimeIt_userapi_get($args)
{
    if(!isset($args['id'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }

    $site_multilingual = pnConfigGetVar('multilingual');
    $translate = isset($args['translate'])? (bool)$args['translate'] : true;
    $object = new $class();

    if(isset($args['dheid'])) {
        $args['dheobj'] = DBUtil::selectObjectByID('TimeIt_date_has_events', (int)$args['dheid']);
    }
    return $object->getEvent((int)$args['id'], $site_multilingual, isset($args['dheobj'])?$args['dheobj']:null);
}

/**
 * Return an event by the iid (= imported id).
 *
 * @param iid iid of an event
 * @return array
 */
function TimeIt_userapi_getByIID($args)
{
    if(!isset($args['iid']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }

    $object = new $class();
    return $object->get((int)$args['iid'], 'iid');
}

/**
 * Alias for TimeIt_userapi_get();
 */
function TimeIt_userapi_getEvent($args)
{
    return TimeIt_userapi_get($args);
}

function TimeIt_userapi_getEventPreformat($args)
{
    if(!isset($args['obj']) || empty($args['obj'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    $obj = &$args['obj'];
    
    //process text format
    if(substr($obj['text'],0,11) == "#plaintext#") {
        $obj['text'] = substr_replace($obj['text'],"",0,11);
        $obj['text'] = nl2br($obj['text']);
    }
    
    // hooks
    $obj['text'] = pnModCallHooks('item', 'transform', '', array($obj['text']));
    $obj['text'] = $obj['text'][0];
    
    // repeats
    $temp = explode(' ', $obj['repeatSpec']);
    $obj['repeat21'] = $temp[0];
    $obj['repeat22'] = $temp[1];
    
    // split duration
    $obj['allDayDur'] = explode(',', $obj['allDayDur']);

    // set username
    $obj['cr_name'] = pnUserGetVar('uname', (int)$obj['cr_uid']);
    $obj['cr_datetime'] = DateUtil::getDatetime(strtotime($obj['cr_date']), _DATETIMEBRIEF);

    // set group name
    if($obj['group'] == 'all') {
        $groupObj = array('name'=>'all'); // group irrelevant
    } else {
        $groupObj = UserUtil::getPNGroup((int)$obj['group']);
    }
    $obj['group_name'] = $groupObj['name'];
    
    return $obj;
}

/**
 * Returns the TimeIt_date_has_events (short: dhe) obj by id or by a event.
 * @param array $args ['obj'] get dhe obj by event
 *                    ['dheid'] get dhe obj by id
 * @return array the dhe obj or false
 */
function Timeit_userapi_getDHE($args)
{
    if((!isset($args['obj']) || empty($args['obj'])) && (!isset($args['dheid']) || empty($args['dheid']))) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if(isset($args['dheid'])) {
        $dheid = $args['dheid'];
    } else {
        $obj = $args['obj'];
    }
    
    if($dheid) {
        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $dheid);
    } else {
        $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$obj['id'], 'date ASC');
        if(count($dheobj)) {
            $dheobj = $dheobj[0];
        } else {
            $dheobj = false;
        }
    }

    return $dheobj;
}

function TimeIt_userapi_getDailySortedEvents($args)
{
    if(!isset($args['start']) || !isset($args['end']) || !isset($args['cid'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    // valid Dates?
    if(!pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$args['start'])) || !pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$args['end']))) {
        return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }

    // load class CategoryUtil
    Loader::loadClass('CategoryUtil');

    $start = $args['start'];
    $end = $args['end'];
    $prozessRepeat = (isset($args['prozessRepeat']))? $args['prozessRepeat']: true;
    
    $pntable = pnDBGetTables();
    $column = $pntable['TimeIt_events_column'];

    $sql = 'tbl.cid = '.DataUtil::formatForStore((int)$args['cid']).' '; // first joion
    $sql .= ' AND ((b.pn_id IS NOT NULL AND b.pn_status = 1) OR (b.pn_id IS NULL AND a.pn_status = 1)) '; // secound join
             //((pn_startDate >= "'.DataUtil::formatForStore($start).'" AND pn_endDate <= "'.DataUtil::formatForStore($end).'"';
    /*if(!$prozessRepeat)
    {
        $sql .= ' AND pn_mmid = 1 OR pn_mmid = 2 ';
    } /*else
    {
        $sql .= ' AND (pn_mmid = 1 OR pn_mmid = 0 ) ';
    }*/
    $sql .= ' AND tbl.the_date >= \''.DataUtil::formatForStore($start).'\' AND tbl.the_date <= \''.DataUtil::formatForStore($end).'\''; // first join
    $User_ID = pnUserGetVar('uid',-1,1);// deafult 1 = Annonymous User 
    $user_lang = pnUserGetLang();

    if(!isset($args['filter_obj']) || !$args['filter_obj']->hasFilterOnField('sharing')) {
        $sql .= ' AND (';
        if(!empty($User_ID)) {
            $sql .= '((b.pn_id IS NOT NULL AND b.pn_cr_uid = '.DataUtil::formatForStore(pnUserGetVar('uid')).' AND (b.'.$column['sharing'].' = 1 OR b.'.$column['sharing'].' = 2)) OR (a.pn_cr_uid = '.DataUtil::formatForStore(pnUserGetVar('uid')).' AND (a.'.$column['sharing'].' = 1 OR a.'.$column['sharing'].' = 2))) OR ';
        }

        $sql .= 'a.pn_sharing = 3 OR a.pn_sharing = 4 OR b.pn_sharing = 3 OR b.pn_sharing = 4)';
    }

    if(isset($args['filter_obj']) && $args['filter_obj'] instanceof TimeIt_Filter) {
        $filter_sql = $args['filter_obj']->toSQL('b');
        
        if(!empty($filter_sql)) {
            $sql .= ' AND ((b.pn_id IS NOT NULL AND '.$filter_sql.') OR';
            $sql .= '( b.pn_id IS NULL AND '.$args['filter_obj']->toSQL('a').'))';
        }
    }

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();
    $class->_objPermissionFilter = array ('realm'            =>  0,
                          'component_left'   =>  'TimeIt',
                          'component_middle' =>  '',
                          'component_right'  =>  'Event',
                          'instance_left'    =>  'id',
                          'instance_middle'  =>  '',
                          'instance_right'   =>  '',
                          'level'            =>  ACCESS_OVERVIEW);
    $array = $class->eventsNew($sql);
    $ret = array();
    //print_r($array);exit();

    // --------------- ContactList integration --------------------
    $buddys = array();
    $ignored = array();
    if (pnModAvailable('ContactList'))  {
        if(pnModGetVar('TimeIt', 'friendCalendar')) {
            $buddys = pnModAPIFunc('ContactList','user','getBuddyList',array('uid'=>$User_ID));
        }

        $ignored = pnModAPIFunc('ContactList','user','getallignorelist',array('uid'=>$User_ID));
    } 
    // --------------- end ContactList integration --------------------

    $site_multilingual = pnConfigGetVar('multilingual');
    foreach($array AS $obj) {
        // Has user got access to one category? 
        if(pnModGetVar('TimeIt', 'filterByPermission', 0) && !CategoryUtil::hasCategoryAccess($obj['__CATEGORIES__'],'TimeIt')) {
            // no access to any category in this object -> ignore event
            continue;
        }
        
        // check permissions
        if(count($obj['__CATEGORIES__']) > 0) {
            $permissionOk = false;
            foreach ($obj['__CATEGORIES__'] AS $cat) {
                $cid = $cat;
                if(is_array($cat)) {
                    $cid = $cat['id'];
                }

                $permissionOk = SecurityUtil::checkPermission('TimeIt:Category:', $cid."::", ACCESS_OVERVIEW);
                if($permissionOk) {
                    // user has got permission -> stop permission checks
                    break;
                }
            }
            // no permission -> irgnore
            if(!$permissionOk) {
                continue;
            }
        }

        // --------------- ContactList integration --------------------
        if((int)$obj['sharing'] == 4 && $obj['cr_uid'] != $User_ID) {
            $buddyFound = false;
            foreach($buddys AS $buddy) {
                if($buddy['uid'] == $obj['cr_uid']) {
                    $buddyFound = true;
                    break;
                }
            }
            if(!$buddyFound) {
                continue; // no buddy connection to cr_uid -> ignore event
            }
        }

        $ignoredFound = false;
        foreach($ignored AS $ignore) {
            if($ignore['iuid'] == $obj['cr_uid']) {
                $ignoredFound = true;
                break;
            }
        }
        if($ignoredFound) {
            continue; // current user is ignoring cr_uid -> ignore event
        }
        // --------------- end ContactList integration --------------------

        if(!TimeIt_groupPermissionCheck($obj, ACCESS_OVERVIEW)) {
            continue;
        }
        
        
        if(isset($args['preformat']) && $args['preformat']) {
            $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj'=>$obj));
        } else if(substr($obj['text'],0,11) == "#plaintext#") { 
            $obj['text'] = substr_replace($obj['text'],"",0,11); 
            $obj['text'] = nl2br($obj['text']); 
        } 

        if($site_multilingual && (!isset($args['translate']) || ( isset($args['translate']) && $args['translate'] == true))) {
            if(isset($obj['title_translate'][$user_lang]) && !empty($obj['title_translate'][$user_lang])) {
                $obj['title'] = $obj['title_translate'][$user_lang];
            }
            
            if(isset($obj['text_translate'][$user_lang]) && !empty($obj['text_translate'][$user_lang])) {
                $obj['text'] = $obj['text_translate'][$user_lang];
            }
        }

        TimeIt_privuserapi_addEventToArray($ret, DateUtil::parseUIDate($obj['dhe_date']), $obj);
    }

    ksort($ret); // sort keys in array
    //print_r($ret);exit();

    foreach($ret as $key => $events) {
        usort($ret[$key], "TimeIt_cat_usort");
    }

    return $ret;
}

/**
 * Returns all events.
 * @param cat calendar id
 * @param filter_obj TimeIt_Filter object
 * @param startnum page number (Default: 0)
 * @param numitems items per page (Default: -1)
 * @param preformat true to preformat all events (Default: true)
 * @param translate true to replace title,text with translations (Default: true)
 * @return array all events
 */
function TimeIt_userapi_getAll($args)
{
    if(!isset($args['cid'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    // Optional arguments.
    if (!isset($args['startnum']) || empty($args['startnum'])) {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems']) || empty($args['numitems'])) {
        $args['numitems'] = -1;
    }
    if(!isset($args['preformat']) || !is_bool($args['preformat'])) {
        $args['preformat'] = true;
    }
    if(!isset($args['translate']) || !is_bool($args['translate'])) {
        $args['translate'] = true;
    }
    if(!isset($args['order']) || !is_bool($args['translate'])) {
        $args['order'] = 'pn_title ASC';
    }

    if (!is_numeric($args['startnum']) || !is_numeric($args['numitems'])) {
        return LogUtil::registerError(_MODARGSERROR);
    }
    
    // laod class
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }

    // get current lang
    $user_lang = pnUserGetLang();
    // load events
    $class = new $class();
    $class->_objPermissionFilter = array ('realm'            =>  0,
                                          'component_left'   =>  'TimeIt',
                                          'component_middle' =>  '',
                                          'component_right'  =>  'Event',
                                          'instance_left'    =>  'id',
                                          'instance_middle'  =>  '',
                                          'instance_right'   =>  '',
                                          'level'            =>  ACCESS_OVERVIEW);
    $class->_objJoin = array( array ('join_table'         =>  'TimeIt_date_has_events',
                                     'join_field'         =>  array('cid'),
                                     'object_field_name'  =>  array('cid'),
                                     'join_method'        =>  'LEFT JOIN',
                                     'compare_field_table'=>  'pn_id',
                                     'compare_field_join' =>  'eid'));

    // build where sql part
    $where = "cid = ".(int)$args['cid']. " AND pn_status = 1";
    if($args['filter_obj']) {
        $sql = $args['filter_obj']->toSQL();
        if($sql) {
            $where .= ' AND '.$sql;
        }
    }
    $where .= " GROUP BY pn_id";
    
    // load event form DB
    $array = $class->get($where, $args['order'], $args['startnum']-1, $args['numitems']);
    $ret = array();
    //print_r($array);exit();

    // --------------- ContactList integration --------------------
    $buddys = array();
    $ignored = array();
    if (pnModAvailable('ContactList')) {
        if(pnModGetVar('TimeIt', 'friendCalendar')) {
            $buddys = pnModAPIFunc('ContactList','user','getBuddyList',array('uid'=>$User_ID));
        }

        $ignored = pnModAPIFunc('ContactList','user','getallignorelist',array('uid'=>$User_ID));
    } 
    // --------------- end ContactList integration --------------------

    // load class CategoryUtil
    Loader::loadClass('CategoryUtil');
    // process all events
    foreach($array AS $obj) {
        // Has user got access to one category? 
        if(pnModGetVar('TimeIt', 'filterByPermission', 0) && !CategoryUtil::hasCategoryAccess($obj['__CATEGORIES__'],'TimeIt')) {
            // no access to any category in this object -> ignore event
            continue;
        }

        // --------------- ContactList integration --------------------
        if((int)$obj['sharing'] == 4 && $obj['cr_uid'] != $User_ID) {
            $buddyFound = false;
            foreach($buddys AS $buddy) {
                if($buddy['uid'] == $obj['cr_uid']) {
                    $buddyFound = true;
                    break;
                }
            }
            if(!$buddyFound) {
                continue; // no buddy connection to cr_uid -> ignore event
            }
        }

        $ignoredFound = false;
        foreach($ignored AS $ignore) {
            if($ignore['iuid'] == $obj['cr_uid']) {
                $ignoredFound = true;
                break;
            }
        }
        if($ignoredFound) {
            continue; // current user is ignoring cr_uid -> ignore event
        }
        // --------------- end ContactList integration --------------------

        if($args['translate']) {
            if(isset($obj['title_translate'][$user_lang]) && !empty($obj['title_translate'][$user_lang])) {
                $obj['title'] = $obj['title_translate'][$user_lang];
            }

            if(isset($obj['text_translate'][$user_lang]) && !empty($obj['text_translate'][$user_lang])) {
                $obj['text'] = $obj['text_translate'][$user_lang];
            }
        }
        
        
        if($args['preformat']) {
            $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj'=>$obj));
        }


        $ret[] = $obj;

    }

    // why sort the array?
    //usort($ret, "TimeIt_cat_usort");
    
    return $ret;
}

function TimeIt_cat_usort($a1 ,$b1)
{
    if(pnModGetVar('TimeIt','sortMode') == 'byname') {
        $a = $a1['info']['name'];
        $b = $b1['info']['name'];
        return strcasecmp($a, $b);
    } else {
        $a = $a1['info']['sort_value'];
        $b = $b1['info']['sort_value'];
        if($a == $b) {
            return 0;
        } else if($a < $b) {
            return -1;
        } else {
            return 1;
        }
    }
}

/**
 *
 * @param array $args ['cid'] calendar id
 * @return int found events
 */
function TimeIt_userapi_countGetAll($args)
{
    if(!isset($args['cid'])){
        return LogUtil::registerError (_MODARGSERROR);
    }
    $t =& pnDBGetTables();
    
    $sql = "SELECT COUNT(DISTINCT pn_id)
            FROM ".$t['TimeIt_events']." tbl
            LEFT JOIN ".$t['TimeIt_date_has_events']." a
                ON a.eid = tbl.pn_id ";

    // build where sql part
    $where = "a.cid = ".(int)$args['cid']." AND tbl.pn_status = 1";
    if($args['filter_obj']) {
        $fsql = $args['filter_obj']->toSQL();
        if($sql) {
            $where .= ' AND '.$fsql;
        }
    }
    // count events
    return DBUtil::selectScalar($sql." WHERE ".$where);
}

function TimeIt_privuserapi_addEventToArray(&$array, $tmestamp, $obj)
{
    $property = pnModGetVar('TimeIt', 'colorCatsProp', 'Main');
    // get category id
    $catID = $obj['__CATEGORIES__'][$property]['id'];
    // There are events out there which aren't in any category
    if(empty($catID)) {
        $catID = 0;
    }
    // isn't the category id set on $array?
    if(!isset($array[$tmestamp][$catID])) {
            $array[$tmestamp][$catID] = array();
            $name = $obj['__CATEGORIES__'][$property]['name'];
            if(isset($obj['__CATEGORIES__'][$property]['display_name'][pnUserGetLang()])) {
                $name = $obj['__CATEGORIES__'][$property]['display_name'][pnUserGetLang()];
            }
            $array[$tmestamp][$catID]['info'] = array('name'=>$name,'color'=>$obj['__CATEGORIES__'][$property]['__ATTRIBUTES__']['color'],'sort_value'=>(int)$obj['__CATEGORIES__'][$property]['sort_value']);
            $array[$tmestamp][$catID]['data'] = array();
            if(empty($array[$tmestamp][$catID]['info']['color']) && $name) {
                $array[$tmestamp][$catID]['info']['color'] = pnModGetVar('TimeIt', 'defalutCatColor');
            }

    }


    // add event to category
    $array[$tmestamp][$catID]['data'][] = $obj;

    if(count($array[$tmestamp][$catID]['data']) > 1) {
        // search best pos in $array
        for ($i=count($array[$tmestamp][$catID]['data'])-1; $i > 0; $i--) {
            $item = $array[$tmestamp][$catID]['data'][$i];
            $itembe = $array[$tmestamp][$catID]['data'][$i-1];
            if($itembe['allDayStart'] > $item['allDayStart']) {
                $objbe = $array[$tmestamp][$catID]['data'][$i-1];
                $array[$tmestamp][$catID]['data'][$i-1] = $array[$tmestamp][$catID]['data'][$i];
                $array[$tmestamp][$catID]['data'][$i] = $objbe;
            }
        }
    }
}

function TimeIt_userapi_yearEvents($args)
{
    if( !isset($args['year'])) {
        return LogUtil::registerError (_MODARGSERROR);
    } else {
        // valid Date?
        if(!pnModAPIFunc('TimeIt','user','checkDate',$args)) {
            return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
        }

        $arrayOfMonths = array();
        for($i=1;$i<=12;$i++) {
            $date = DateUtil::getDatetime(mktime(0,0,0,$i,DateUtil::getDaysInMonth($i, $args['year']),$args['year']), _DATEINPUT);
            $arrayOfMonths[$date] = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthViewV2', array('month' => $i, 'year'=> $args['year'],'firstDayOfWeek'=>$args['firstDayOfWeek']));
        }

        $datesWithEvents = pnModAPIFunc('TimeIt','user','getDatesWithEvents', array('cid'=>$args['cid'],'start'=>$args['year'].'-01-01','end'=>$args['year'].'-12-31'));
        foreach($datesWithEvents AS $date) {
            list($year, $month, $day) = explode('-', $date);
            
            $index = DateUtil::getDatetime(mktime(0,0,0,(int)$month,DateUtil::getDaysInMonth((int)$month, $args['year']),$args['year']), _DATEINPUT);
            foreach($arrayOfMonths[$index] AS $week => $days) {
                    if(array_key_exists($date, $days)) {
                        $arrayOfMonths[$index][$week][$date] = true;
                        break;
                    }
            }
            
        }


        //asort($arrayOfMonths);
        return $arrayOfMonths;
    }
}

/**
 * Returns a list of dates. Each date in the list has min. one event.
 * @param array $args ['start'] start date
 *                    ['end'] end date
 *                    ['cid'] calendar id
 * @return array Dates(Format: yyyy-mm-dd) with events.
 */
function TimeIt_userapi_getDatesWithEvents($args)
{
    if(!isset($args['start']) || !isset($args['end']) || !isset($args['cid'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    // valid Dates?
    if(!pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$args['start'])) || !pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$args['end']))){
        return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }


    return DBUtil::selectFieldArray('TimeIt_date_has_events', 'date', "the_date >= '".DataUtil::formatForStore($args['start'])."' AND the_date <= '".DataUtil::formatForStore($args['end'])."' AND cid = ".(int)$args['cid'], '', true);
}

function TimeIt_userapi_monthEvents($args)
{
    if(!isset($args['month']) || !isset($args['year']) || !isset($args['cid']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
        // valid Date?
        if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
        {
            return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
        }

        $GETMonth = (int)$args['month'];
        $GETYear = (int)$args['year'];

        // get usefull dates
        ///$navdates = pnModAPIFunc('TimeIt', 'user', 'navdates', array('month' => $GETMonth, 'year'=> $GETYear));

        // get array from api function
        $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthViewV2', array('month' => $GETMonth, 'year'=> $GETYear,'firstDayOfWeek'=>$args['firstDayOfWeek']));
        
        // get usefull dates
        //$navdates = pnModAPIFunc('TimeIt', 'user', 'navdates', array('month' => $GETMonth, 'year'=> $GETYear));
        reset($events[0]);
        $start = each($events[0]);
        $start = $start['key'];
        
        
        end($events);
        $end = each($events); // last week
        $key = $end['key']; // key of last week
        end($events[$key]); // last day in last week
        $end = each($events[$key]);
        $end = $end['key'];
        
        
        // get events form db
        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => $start,                   
                  'end' => $end,
                  'cid' => $args['cid'],
                  'prozessRepeat'=> ((isset($args['prozessRepeat']))? $args['prozessRepeat'] : true),
                  'preformat'  => ((isset($args['preformat']))? $args['preformat'] : null),
                  'filter_obj' => isset($args['filter_obj'])? $args['filter_obj'] : null
                  )
        );

        // get array from api function
        //$events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month' => $GETMonth, 'year'=> $GETYear));

        // insert events from data to the events array
        foreach($events AS $weeknr=>$days)
        {
            foreach($days AS $k=>$v)
            {
                $events[$weeknr][$k] = $data[strtotime($k)];
            }
        }

        return $events;
    }
}

function TimeIt_userapi_weekEvents($args)
{
    if(!isset($args['week']) || !isset($args['year']) || !isset($args['cid']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
        // valid Date?
        if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
        {
            return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
        }

        $GETWeek = (int)$args['week'];
        $GETYear = (int)$args['year'];
        
        $startDateArray = getDate(pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', $args));
        $startDate = DateUtil::getDatetime($startDateArray[0], _DATEINPUT);
        $endDate   = DateUtil::getDatetime(mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+6, $startDateArray['year']), _DATEINPUT);
        
        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => $startDate,                   
              'end' => $endDate,
              'cid' => $args['cid'],
              'prozessRepeat'=> ((isset($args['prozessRepeat']))? $args['prozessRepeat']: true),
              'preformat'  => ((isset($args['preformat']))? $args['preformat']: null),
              'filter_obj' => isset($args['filter_obj'])? $args['filter_obj'] : null    )
        ); 
        $week = array();

        for($i=0;$i<7;$i++)
        {
            $temp = mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+$i, $startDateArray['year']);
            $week[DateUtil::getDatetime($temp, _DATEINPUT)] = $data[$temp];
        }
        return $week;
    }
}

function TimeIt_userapi_dayEvents($args)
{
    if(!isset($args['day']) || !isset($args['month']) || !isset($args['year']) || !isset($args['cid']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
        // valid Date?
        if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
        {
            return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
        }

        $GETDay = (int)$args['day'];
        $GETMonth = (int)$args['month'];
        $GETYear = (int)$args['year'];


        $getDate = getDate(mktime(0, 0, 0, $GETMonth, $GETDay, $GETYear));

        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => DateUtil::getDatetime($getDate[0], _DATEINPUT),                   
              'end' => DateUtil::getDatetime($getDate[0], _DATEINPUT),
              'cid' => $args['cid'],
              'prozessRepeat'=> ((isset($args['prozessRepeat']))? $args['prozessRepeat']: true),
              'preformat'  => ((isset($args['preformat']))? $args['preformat']: null),
              'filter_obj' => isset($args['filter_obj'])? $args['filter_obj'] : null  )
        );      

        return $data[$getDate[0]];
    }
}

function TimeIt_userapi_create($args)
{	
    if(!isset($args['obj'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        if(isset($args['obj']['__WORKFLOW__'])) {

            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
            $object = new $class();
            $object->setData($args['obj']);
            if(isset($args['obj']['__META__']['TimeIt']['preserveValues']) && $args['obj']['__META__']['TimeIt']['preserveValues']) {
                $object->_objInsertPreserve = true;
            }
            $ret = $object->insert();
            $args['obj'] = $object->getData();

            if(!isset($args['obj']['__META__']['TimeIt']['recurrenceOnly']) || !$args['obj']['__META__']['TimeIt']['recurrenceOnly']) {
                if(!isset($args['noRecurrenceCalculation']) || !$args['noRecurrenceCalculation']) {
                    Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
                    Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
                    $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $args['obj']);
                    $prozi->doCalculation();
                }

                // Let any hooks know that we have created a new item
                pnModCallHooks('item', 'create', $args['obj']['id'], array('module' => 'TimeIt'));
            }
        } else {
            $ret = WorkflowUtil::executeAction('standard', $args['obj'], "submit", "TimeIt_events", "TimeIt");
        }

        return $ret;
    }
}

/**
 * Save the changes of an event.
 * @param array $args ['obj'] Timeit event
 * @return boolean
 */
function TimeIt_userapi_update($args)
{
    if(!isset($args['obj'])) {
        return LogUtil::registerError(_MODARGSERROR);
    }

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit(pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }

    if(isset($args['obj']['__META__']['TimeIt']['recurrenceOnly']) && $args['obj']['__META__']['TimeIt']['recurrenceOnly']) {
        $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', '(eid = '.(int)$args['obj']['id'].' OR localeid = '.(int)$args['obj']['id'].') AND the_date = \''.DataUtil::formatForStore($args['obj']['startDate']).'\'');
        $dheobj = $dheobj[0];

        if($dheobj['localeid']) {
            $object = new $class();
            $object->setData($args['obj']);
            $ret = $object->save();
        } else {
            unset($args['obj']['id'],
                  $args['obj']['__WORKFLOW__']);

            pnModAPIFunc('TimeIt','user','create',$args);

            $dheobj['localeid'] = $args['obj']['id'];
            DBUtil::updateObject($dheobj, 'TimeIt_date_has_events');
            $ret = true;
        }
    } else {
        $master = TimeIt_userapi_getMasterEvent(array('obj'=>$args['obj'],'force'=>true));

        if(!isset($args['noRecurrences']) || !$args['noRecurrences']) {
            if( $args['obj']['repeatType']    != $master['repeatType']
                || $args['obj']['repeatSpec'] != $master['repeatSpec']
                || $args['obj']['repeatFrec'] != $master['repeatFrec']
                || $args['obj']['startDate']  != $master['startDate']
                || $args['obj']['endDate']    != $master['endDate']
                || $args['obj']['repeatIrg']  != $master['repeatIrg']
                )
            {
                pnModAPIFunc('TimeIt','user','updateRecurrences', array('obj'=>$args['obj']));
            }
        }

        pnModCallHooks('item', 'update', $args['obj']['id'], array('module' => 'TimeIt'));

        $object = new $class();
        $object->setData($args['obj']);
        $ret = $object->save();
    }

    return $ret;
}

function TimeIt_userapi_updateRecurrences($args)
{
    if(!isset($args['obj'])) {
        return LogUtil::registerError(_MODARGSERROR);
    }
    $obj = $args['obj'];

    $master = TimeIt_userapi_getMasterEvent(array('obj'=>$obj,'force'=>true));
    
    if($obj['repeatType'] == $master['repeatType'] && $obj['repeatSpec'] == $master['repeatSpec'] && $obj['repeatFrec'] == $master['repeatFrec'] && $obj['startDate'] == $master['startDate'] && ($obj['repeatIrg'] == $master['repeatIrg'] || empty($master['repeatIrg']) )) {
        if($obj['endDate'] < $master['endDate']) {
            $temp = $obj;
            $temp['endDate'] = $master['endDate'];
            $temp['startDate'] = DateUtil::getDatetime(strtotime('+1 day', strtotime($obj['endDate'])), _DATEINPUT);
    
            Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
            Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
            $out = new TimeIt_Recurrence_Output_Array();
            $prozi = new TimeIt_Recurrence_Processor($out, $temp);
            $prozi->doCalculation();
            $array =& $out->getData();
            
            // delete the old occurrences
            foreach($array AS $toDelDate) {
                $toDelObj = DBUtil::selectObject('TimeIt_date_has_events', "eid = '".(int)$obj['id']."' AND the_date = '".DataUtil::formatForStore($toDelDate)."'");

                if(!empty($toDelObj)) {
                    // delete all subcribtions
                    DBUtil::deleteWhere('TimeIt_regs', 'pn_eid = '.(int)$toDelObj['id']);

                    // delete recurrence
                    DBUtil::deleteWhere('TimeIt_date_has_events', 'id = '.(int)$toDelObj['id']);

                    // delete modified occurrence if there is one
                    if($toDelObj['localeid']) {
                        pnModAPIFunc('TimeIt','user','delete', array('id'=>(int)$toDelObj['localeid'],'eventOnly'=>true));
                    }
                }
            }
        } else if($obj['endDate'] > $master['endDate']) {
            // calculate the new occurrences
            $temp = $obj;
            $temp['startDate'] = DateUtil::getDatetime(strtotime('+1 day', strtotime($master['endDate'])), _DATEINPUT);
            Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
            Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
            $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $temp);
            $prozi->doCalculation();
        }

        if(!empty($obj['repeatIrg']) && empty($master['repeatIrg'])) {
            $dates = explode(',', $obj['repeatIrg']);
            pnModAPIFunc('TimeIt','user','deleteAllRecurrences', array('obj'=>$obj,'dates'=>$dates));
        }
    } else {
        pnModAPIFunc('TimeIt','user','deleteAllRecurrences', array('obj'=>$obj));
        $temp = $obj;
        Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
        Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
        $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $temp);
        $prozi->doCalculation();
    }
}

/**
 * Deletes an event.
 * @param array $args ['id'] TimeIt event id
 *                    ['eventOnly'] if true this function deletes only this event
 * @return boolean
 */
function TimeIt_userapi_delete($args)
{

    if(!isset($args['id']) || empty($args['id'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        if(isset($args['eventOnly']) && $args['eventOnly']) {
            $obj = pnModAPIFunc('TimeIt','user','get', array('id'=>$args['id']));

            if(!empty($obj)) {
                WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events', 'id', 'TimeIt');
                WorkflowUtil::deleteWorkflow($obj);
                $delobj = array('localeid'=>null);
                DBUtil::updateObject($delobj, 'TimeIt_date_has_events', 'localeid = '.(int)$args['id']);
                return true;
            } else {
                return false;
            }
        } else {
            $dhearray = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$args['id']);

            $dheids = array();
            $eids = array($args['id']);

            foreach($dhearray AS $dheobj) {
                $dheids[] = $dheobj['id'];
                if($dheobj['localeid']) {
                    $eids[] = $dheobj['localeid'];
                }
            }

            // delete all subcribtions
            DBUtil::deleteWhere('TimeIt_regs', 'pn_eid IN('.implode(',', $dheids).')');

            // delete recurrences
            DBUtil::deleteWhere('TimeIt_date_has_events', 'id IN('.implode(',', $dheids).')');

            // get all events to delete
            $events = DBUtil::selectObjectArray('TimeIt_events','pn_id IN('.implode(',', $eids).')');
            // delete all events
            foreach($events AS $obj) {
                WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events', 'id', 'TimeIt');
                WorkflowUtil::deleteWorkflow($obj);
            }

            // Let any hooks know that we have deleted an item
            pnModCallHooks('item', 'delete', $args['obj']['id'], array('module' => 'TimeIt'));

            return true;
        }
    }
}

/**
 * Delets all recurrences in the DB. This function deletes all modified occurrences(=separate events) too.
 * @param array obj TimeIt event
 * @param array dates Deletes only these recurences
 * @return boolean
 */
function TimeIt_userapi_deleteAllRecurrences($args)
{
    if(!isset($args['obj']) || empty($args['obj'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        if(isset($args['dates']) && is_array($args['dates']) && !empty($args['dates'])) {
            $datessql = ' AND the_date IN(';
            foreach($args['dates'] AS $date) {
                $datessql .= "'".DataUtil::formatForStore($date)."',";
            }
            $datessql = substr($datessql, 0, strlen($datessql)-1); // remove last ,
            $datessql .= ')';
        } else {
            $datessql = '';
        }
        $dhearray = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$args['obj']['id'].$datessql);

        $dheids = array();
        $localeids = array();

        foreach($dhearray AS $dheobj) {
            $dheids[] = $dheobj['id'];
            if($dheobj['localeid']) {
                $localeids[] = $dheobj['localeid'];
            }
        }

        if(!empty($dheids)) {
            // delete all subcribtions
            DBUtil::deleteWhere('TimeIt_regs', 'pn_eid IN('.implode(',', $dheids).')');

            // delete recurrences
            DBUtil::deleteWhere('TimeIt_date_has_events', 'id IN('.implode(',', $dheids).')');
        }

        if(!empty($localeids)) {
            // get all events to delete
            $events = DBUtil::selectObjectArray('TimeIt_events','pn_id IN('.implode(',', $localeids).')');
        
            // delete all events
            foreach($events AS $obj) {
                WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events', 'id', 'TimeIt');
                WorkflowUtil::deleteWorkflow($obj);
            }
        }

        // clear the cache because otherwise non exist rows are in the cache
        DBUtil::objectCache(true, 'TimeIt_date_has_events');
        
        return true;
    }
}

/**
 * Returns the original event.
 * @param array $args ['obj'] an event
 *                    ['dheid'] an TimeIt_date_has_events id
 *                    ['force'] if true it won't use caching
 * @return array
 */
function TimeIt_userapi_getMasterEvent($args)
{    
    if (!isset($args['obj']) && !isset($args['dheid']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else if((isset($args['obj']) && empty($args['obj'])) || (isset($args['dheid']) && empty($args['dheid']))) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if($args['dheid']) {
        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $args['dheid']);
        $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$dheobj['eid']));
    } else {
        $dheobj = DBUtil::selectObject('TimeIt_date_has_events', 'eid = '.(int)$args['obj']['id'].' OR localeid = '.(int)$args['obj']['id']);
        if($dheobj['eid'] != $args['obj']['id'] || $args['force']) {
            $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$dheobj['eid']));
        } else {
            $obj = $args['obj'];
        }
    }

    return $obj;
}

function TimeIt_userapi_pendingEvents($args)
{
    // Optional arguments.
    if (!isset($args['startnum']) || empty($args['startnum'])) 
    {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems']) || empty($args['numitems'])) 
    {
        $args['numitems'] = -1;
    }

    if (!is_numeric($args['startnum']) || !is_numeric($args['numitems']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }

    $groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }
    pnModDBInfoLoad('Workflow');
    $pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];

    $where = "WHERE $workflows_column[module]='TimeIt'
                AND $workflows_column[obj_table]='TimeIt_events'
                AND $workflows_column[obj_idcolumn]='id'
                AND $workflows_column[state]='waiting'";
    if($groups !== true && count($groups) > 0)
    {
        $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
    }

    $join = array(array ('join_table'   =>  'workflows',
                       'join_field'         =>  array('obj_id'),
                       'object_field_name'  =>  array('obj_id'),
                       'compare_field_table'=>  'id',
                       'compare_field_join' =>  'obj_id'));

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();

    $class->_objJoin = $join;
    return $class->get($where, '', $args['startnum']-1, $args['numitems']);
}

function TimeIt_userapi_countPendingEvents($args)
{
    $groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return 0;
    }

    pnModDBInfoLoad('Workflow');
    $pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];

    $where = "WHERE $workflows_column[module]='TimeIt'
                AND $workflows_column[obj_table]='TimeIt_events'
                AND $workflows_column[obj_idcolumn]='id'
                AND $workflows_column[state]='waiting'";
    if($groups !== true && count($groups) > 0)
    {
        $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
    }

    $join = array(array ('join_table'   =>  'workflows',
                       'join_field'         =>  array('obj_id'),
                       'object_field_name'  =>  array('obj_id'),
                       'compare_field_table'=>  'id',
                       'compare_field_join' =>  'obj_id'));


    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class(); // make object
    $class->_objJoin = $join; // set join array
    return $class->getCount($where, true);// count items
}

function TimeIt_userapi_hiddenEvents($args)
{
    // Optional arguments.
    if (!isset($args['startnum']) || empty($args['startnum'])) 
    {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems']) || empty($args['numitems'])) 
    {
        $args['numitems'] = -1;
    }

    if (!is_numeric($args['startnum']) || !is_numeric($args['numitems']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }

    $groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }

    pnModDBInfoLoad('Workflow');
    $pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];

    $where = "WHERE $workflows_column[module]='TimeIt'
                AND $workflows_column[obj_table]='TimeIt_events'
                AND $workflows_column[obj_idcolumn]='id'
                AND $workflows_column[state]='approved'
                AND $timeit_events_column[status]=0";
    if($groups !== true && count($groups) > 0)
    {
        $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
    }

    $join = array(array ('join_table'   =>  'workflows',
                                    'join_field'         =>  array('obj_id'),
                                    'object_field_name'  =>  array('obj_id'),
                                    'compare_field_table'=>  'id',
                                    'compare_field_join' =>  'obj_id'));

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();

    $class->_objJoin = $join;
    return $class->get($where, '', $args['startnum']-1, $args['numitems']);
}

function TimeIt_userapi_countHiddenEvents($args)
{
    $groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }

    pnModDBInfoLoad('Workflow');
    $pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];

    $where = "WHERE $workflows_column[module]='TimeIt'
                AND $workflows_column[obj_table]='TimeIt_events'
                AND $workflows_column[obj_idcolumn]='id'
                AND $workflows_column[state]='approved'
                AND $timeit_events_column[status]=0";
    if($groups !== true && count($groups) > 0)
    {
        $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
    }

    $join = array(array ('join_table'   =>  'workflows',
                                    'join_field'         =>  array('obj_id'),
                                    'object_field_name'  =>  array('obj_id'),
                                    'compare_field_table'=>  'id',
                                    'compare_field_join' =>  'obj_id'));

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();
    $class->_objJoin = $join;
    return $class->getCount($where, true);
}

/**
 * Returns the first day of the week as unix timestamp.
 * @param array $args eg. array('year'=>2009,'week'=>1) or array('year'=>2009,'month'=>1,'day'=>1)
 * @return int unix timestamp
 */
function TimeIt_userapi_getFirstDayOfWeek($args)
{
    if(empty($args['year']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        if(!empty($args['week']) && !isset($args['month']) && !isset($args['day'])) {
            // convert week&year to year&month/day
            $date = getdate(strtotime($args['year'].'-01-01 + '.(((int)$args['week'])-1).' weeks'));
            $args['month'] = $date['mon'];
            $args['day'] = $date['mday'];
        } else if(empty($args['month']) || empty($args['day'])) {
            return LogUtil::registerError(_MODARGSERROR);
        }
    }
    
    // valid Date?
    if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    {
        return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }

    $date = $args['year'].'-'.(((int)$args['month'] < 10)?'0'.$args['month']:$args['month']).'-'.(((int)$args['day'] < 10)?'0'.$args['day']:$args['day']);
    $array = pnModAPIFunc('TimeIt','user','arrayForMonthViewV2',$args);
    $found = false;
    $first = false;
    
    foreach($array AS $week => $days)
    {
        foreach($days AS $day => $value)
        {
            if($day == $date)
            {
                $found = $day;
                break;
            }
            
            // save first day
            if($first === false)
            {
                $first = $day;
            }
        }
        
        if($found !== false)
        {
            // go to first day in week
            foreach($days AS $day => $value)
            {
                return strtotime($day);
            }
        }
    }

    return strtotime($first);
}

/**
 * Version 2
 *@return array 2 dimensional array. 
 * e.g.: array[0][YYYY-MM-DD] = NULL;
 *
 */
function TimeIt_userapi_arrayForMonthViewV2($args)
{
    if(!isset($args['month']) || !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    {
        return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }

    $array = array();
    $year = $args['year'];
    $month = $args['month'];
    $firstDayOfWeek = (isset($args['firstDayOfWeek'])
                           && (int)$args['firstDayOfWeek'] >= 0
                           &&(int)$args['firstDayOfWeek'] <= 6)?(int)$args['firstDayOfWeek'] : (int)pnModGetVar('TimeIt', 'firstWeekDay'); // 0 = Sun 1 = Mo ...

    // calc first day of week in the first week of the month
    $timestampFirstDayOfMonth = gmmktime(0,0,0,(int)$month,1,(int)$year);
    $day1 = (gmdate('w', $timestampFirstDayOfMonth) - $firstDayOfWeek) % 7;
    if($day1 < 0)
        $day1 += 7;

    $timestamp = strtotime('-'.$day1.' days',$timestampFirstDayOfMonth);
    $daysInMonth = DateUtil::getDaysInMonth((int)$month, (int)$year);

    // create array
    $lastDayInMonthFound = false;
    for($week=0; $week < 6; $week++)
    {
        for($day=1; $day <= 7; $day++)
        {
            $dayNum = date('j',$timestamp);
            //$array[$week][DateUtil::getDatetime($timestamp, _DATEINPUT)] = NULL;
            $array[$week][gmdate('Y-m-d', $timestamp)] = NULL;
            if($dayNum == $daysInMonth && $week > 0)
            {
                $lastDayInMonthFound = true;
            }
            $timestamp += 86400;
        }
        if($lastDayInMonthFound)
        {
            break;
        }
    }

    return $array;
}


function TimeIt_userapi_checkDate($args)
{
    if(empty($args))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if(isset($args['date']))
    {
        $a = explode('-', $args['date']);
        $args['day'] = (int)$a[2];
        $args['month'] = (int)$a[1];
        $args['year'] = (int)$a[0];
    }

    if(isset($args['week']))
    {
        $args['weeknr'] = $args['week'];
    }

    if(isset($args['day']))
    {
        $i = (int)$args['day']; // cast to int
        // invalid day=
        if($i < 1 || $i > 31)
        {
            return false;
        }
    }

    if(isset($args['weeknr']))
    {
        $i = (int)$args['weeknr']; // cast to int
        // invalid day=
        if($i < 1 || $i > 53)
        {
            return false;
        }
    }

    if(isset($args['month']))
    {
        $i = (int)$args['month']; // cast to int
        // invalid day=
        if($i < 1 || $i > 12)
        {
            return false;
        }
    }

    if(isset($args['year']))
    {
        $i = (int)$args['year']; // cast to int
        // invalid day=
        if($i < 1970 || $i > 2037)
        {
            return false;
        }
    }

    if(isset($args['day']) && isset($args['month']) && isset($args['year']))
    {
        $i = (int)DateUtil::getDaysInMonth($args['month'], $args['year']);
        if((int)$args['day'] > $i)
        {
            return false;
        }
    }

    return true;
}

function TimeIt_userapi_icalRruleProzess(&$retArray, &$obj, $start, $end)
{
    $class = new IcalRrulePorcessor($retArray, $obj, $start, $end);
    $class->process();
}

/*TODO: add new parameters (eg. filter) and functions. do detailed tests
function TimeIt_userapi_decodeUrl($args)
{
    // check we actually have some vars to work with...
    if (!isset($args['vars'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    // define the available user functions
    $funcs = array('event','viewUserOfSubscribedEvent','deleteSubscribUser','viewSubscribedEventsOfUser',
                   'deletePendingStateOfSubscribedUser','unsubscribe','subscribe','atom','rss','viewByCat',
                   'new','modify');
    
    // set the correct function name based on our input
    if (empty($args['vars'][2])) {
        pnQueryStringSetVar('func', 'main');
    } elseif (!in_array($args['vars'][2], $funcs)) {
        pnQueryStringSetVar('func', 'view');
    } else {
        pnQueryStringSetVar('func', $args['vars'][2]);
    }
    
    $func = FormUtil::getPassedValue('func', 'main', 'GET');
    
    if ($func == 'view' && isset($args['vars'][3]) && isset($args['vars'][4])
        && isset($args['vars'][5]) && isset($args['vars'][6])) 
    {
        // calendar
        $calendar = $args['vars'][2];
        $calendar = explode('-', $calendar);
        $calendar = (int)$calendar[0];
        pnQueryStringSetVar('cid', $calendar);
        
        // viewType
        pnQueryStringSetVar('viewType',  $args['vars'][3]);
        
        // day
        pnQueryStringSetVar('day',  $args['vars'][4]);
        
        // month
        pnQueryStringSetVar('month',  $args['vars'][5]);
        
        // year
        pnQueryStringSetVar('year',  $args['vars'][6]);
        
        // template and fistDayOfWeek
        if(isset($args['vars'][7]))
        {
            $exp = explode('-', $args['vars'][7]);
            
            if($exp[0] == 'tp')
            {
                pnQueryStringSetVar('template',  $exp[1]);
            } else if($exp[0] == 'fdw')
            {
                pnQueryStringSetVar('firstDayOfWeek',  $exp[1]);
            } else if($exp[0] == 'format' && $exp[1] == 'ical')
            {
                pnQueryStringSetVar('ical',  1);
            }
        }
        if(isset($args['vars'][8]))
        {
            $exp = explode('-', $args['vars'][7]);
            
            if($exp[0] == 'tp')
            {
                pnQueryStringSetVar('template',  $exp[1]);
            } else if($exp[0] == 'fdw')
            {
                pnQueryStringSetVar('firstDayOfWeek',  $exp[1]);
            } else if($exp[0] == 'format' && $exp[1] == 'ical')
            {
                pnQueryStringSetVar('ical',  1);
            }
        }
        
        return true;
    }
    
    if ($func == 'event' && isset($args['vars'][3]) && isset($args['vars'][4])) 
    {
        pnQueryStringSetVar('id', (int)$args['vars'][3]);
        pnQueryStringSetVar('date', $args['vars'][4]);
        
        // ical
        if(isset($args['vars'][5]) && $args['vars'][5] == 'ical')
        {
             pnQueryStringSetVar('ical',  1);
        }
        
        return true;
    }
    
    if($func == 'new' && isset($args['vars'][4]))
    {
        pnQueryStringSetVar('cid',  $args['vars'][4]);
        return true;
    }
    
    return false;
}


function TimeIt_userapi_encodeUrl($args)
{
    // check we have the required input
    if (!isset($args['modname']) || !isset($args['func']) || !isset($args['args'])) {
        
        return LogUtil::registerError (_MODARGSERROR);
    }

    if (!isset($args['type'])) {
        $args['type'] = 'user';
    }
    
    // create an empty string ready for population
    $vars = '';
    
    // main calls view so can change main to view
    if($args['func'] == 'main')
    {
        $args['func'] = 'view';
    }
    
    
    if($args['func'] == 'view' &&  isset($args['args']['cid']) && isset($args['args']['year']) 
        && isset($args['args']['month']) && isset($args['args']['day']) && isset($args['args']['viewType']))
    {
        // calendar
        $calendar = pnModAPIFunc('TimeIt','calendar','get',$args['args']['cid']);
        $vars .= $args['args']['cid'].'-'.$calendar['name'];
        
        // viewType
        $vars .= '/'.$args['args']['viewType'];
        
        // day
        $vars .= '/'.(int)$args['args']['day'];
        
        // month
        $vars .= '/'.(int)$args['args']['month'];
        
        // year
        $vars .= '/'.(int)$args['args']['year'];
        
        // template
        if(isset($args['args']['template']) && $args['args']['template'] != $calendar['defaultTemplate'])
        {
            $vars .= '/tp-'.$args['args']['template'];
        }
        
        // firstDayOfWeek
        if(isset($args['args']['firstDayOfWeek']))
        {
            $vars .= '/fdw-'.$args['args']['firstDayOfWeek'];
        }
        
        // format ical
        if(isset($args['args']['firstDayOfWeek']))
        {
            $vars .= '/format-ical';
        }
    }
    
    if($args['func'] == 'event' && isset($args['args']['id']) && isset($args['args']['date']))
    {
        $vars .= $args['func'].'/'.$args['args']['id'].'/'.$args['args']['date'];
        
        if(isset($args['args']['ical']) && $args['args']['ical'])
        {
            $vars .= '/ical';
        }
    }
    
    if(!empty($vars))
    {
        return $args['modname'] . '/' . $vars . '/';
    } else
    {
        return false;
    }
}*/

/**
 * This function deletes all events of an user.
 * @param array $args[uid] user id
 */
function TimeIt_userapi_deleteEventsOfUser($args)
{
    if (!isset($args['uid'])) 
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    // search events of user
    $events = DBUtil::selectFieldArray('TimeIt_events','id', 'pn_cr_uid = '.DataUtil::formatForStore($args['uid']));

    // only continue work when $events contains at least one id
    if(!empty($events)) {
        // delete all registred users of these events
        DBUtil::deleteWhere('TimeIt_regs','pn_eid IN('.implode(',', $events).')');

        foreach($events AS $id)
        {
            $obj = array('id'=>$id);
            WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events');
            WorkflowUtil::deleteWorkflow($obj);
            pnModCallHooks('item', 'delete', $obj['id'], array('module' => 'TimeIt'));
        }
    }
    
    return true;
}

/**
 * This function anonymizes all events of an user.
 * @param array $args[uid] user id
 */
function TimeIt_userapi_anonymizeEventsOfUser($args)
{
    if (!isset($args['uid'])) 
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    // search events of user
    $events = DBUtil::selectFieldArray('TimeIt_events','id', 'pn_cr_uid = '.DataUtil::formatForStore($args['uid']));

    // only continue work when $events contains at least one id
    if(!empty($events)) {
        // anonymize the cr_uid of all those events
        $obj = array('cr_uid'=>1);
        DBUtil::updateObject($obj, 'TimeIt_events','pn_id IN('.implode(',', $events).')');
    }
    return true;
}

/**
 * This function returns the oldest start date over all events.
 * @return string 'yyyy-mm-dd'
 */
function TimeIt_userapi_getOldestDate()
{
    $pntables = pnDBGetTables();
    $table    = $pntables['TimeIt_events'];
    $result = DBUtil::selectScalar('SELECT MIN(pn_startDate) FROM '.$table, false);

    if($result)
    {
        return $result;
    } else
    {
        return DateUtil::getDatetime(time(), _DATEINPUT);
    }
}

/**
 * This function returns the last end date of all events.
 * @return string 'yyyy-mm-dd'
 */
function TimeIt_userapi_getLastDate()
{
    $pntables = pnDBGetTables();
    $table    = $pntables['TimeIt_events'];
    $result = DBUtil::selectScalar('SELECT MAX(pn_endDate) FROM '.$table, false);

    if($result)
    {
        return $result;
    } else
    {
        return '2037-12-31'; // return hightest possible date
    }
}

class IcalRrulePorcessor
{
    var $obj;
    var $rrule;
    var $start;
    var $end;
    var $retArray;
    var $numToWeekDay;
    var $count;

    function IcalRrulePorcessor(&$ret, $obj, $start, $end)
    {
        $this->obj = $obj;
        $this->start = $start;
        $this->end = $end;
        $this->retArray =& $ret;

        $this->numToWeekDay = array(0 => 'SU',
            1 => 'MO',
            2 => 'TU',
            3 => 'WE',
            4 => 'TH',
            5 => 'FR',
            6 => 'SA');
        $this->count = 0;
        
        $this->rrule = unserialize($this->obj['repeatSpec']);

        if(isset($this->rrule['BYMONTH']) && !is_array($this->rrule['BYMONTH']))
        {
            $this->rrule['BYMONTH'] = array($this->rrule['BYMONTH']);
        }
    }

    function process()
    {
        $rrule = $this->rrule;
        switch ($this->rrule['FREQ']) {
            case 'YEARLY':
            $freq = 'year';
            break;
            case 'MONTHLY':
            $freq = 'month';
            break;

            case 'WEEKLY':
            $freq = 'week';
            break;

            case 'DAILY':
            $freq = 'day';
            break;

            default:
            // error stop
            return LogUtil::registerError(_MODARGSERROR);
        }
        $date = $this->obj['startDate'];
        $dateNew = $date;
        $firstLoop = true;
        if(isset($this->rrule['COUNT']) && !empty($this->rrule['COUNT']))
        {
            $this->count = (int)$this->rrule['COUNT'];
        } else 
        {   // no counter
            $this->count = pow(2, 25); // i use a very big number for the counter
        }

        if(isset($this->rrule['INTERVAL']) && !empty($this->rrule['INTERVAL']))
        {
            $interval = (int)$this->rrule['INTERVAL'];
        } else 
        {  
            $interval = 1; 
        }

        if(isset($this->rrule['UNTIL']) && !empty($this->rrule['UNTIL']))
        {
            $util = $this->rrule['UNTIL'];
        } else 
        {  
            $util = $this->end;
        }
        
        if(isset($this->rrule['RDATE']) && !empty($this->rrule['RDATE']))
        {
            $this->process_rdate($this->rrule['RDATE']);
        }
        
        while($dateNew <= $this->end && $dateNew <= $this->obj['endDate'] && $this->count > 0 && $dateNew <= $util)
        {
            if(!$firstLoop)
            {
                // calculate next date
                $stamp = strtotime('+'.$interval.' '.$freq, strtotime($dateNew));
                $date = $dateNew;
                $dateNew = DateUtil::getDatetime($stamp, _DATEINPUT);
                if($dateNew > $this->end || $dateNew > $this->obj['endDate'] || $dateNew > $util)
                {
                    break;
                }
            } else
            {
                $dateNew = $date;
            }

            if(isset($rrule['BYDAY']) && !empty($rrule['BYDAY']))
            {
                if($this->rrule['FREQ'] == 'YEARLY')
                {
                    $dates = $this->calc_Year($dateNew);
                    $startDate = $dates['start'];
                    $endDate = $dates['end'];
                } else if($this->rrule['FREQ'] == 'WEEKLY')
                {
                    $dates = $this->calc_Week($dateNew);
                    $startDate = $dates['start'];
                    $endDate = $dates['end'];
                } else if($this->rrule['FREQ'] == 'MONTHLY')
                {
                    $dates = $this->calc_Month($dateNew);
                    $startDate = $dates['start'];
                    $endDate = $dates['end'];
                } else
                {
                    $startDate = $date;
                    $endDate = $dateNew;
                }
                $this->process_byDay($endDate, $startDate);        	
            } else if(isset($this->rrule['BYMONTH']) && !empty($this->rrule['BYMONTH']))
            {
                if($this->check_byMonth($dateNew))
                {
                    if(isset($this->rrule['BYMONTHDAY']) && !empty($this->rrule['BYMONTHDAY']))
                    {
                        if($this->rrule['FREQ'] == 'YEARLY')
                        {
                            $dates = $this->calc_Year($dateNew);
                            $startDate = $dates['start'];
                            $endDate = $dates['end'];
                        } else if($this->rrule['FREQ'] == 'WEEKLY')
                        {
                            $dates = $this->calc_Week($dateNew);
                            $startDate = $dates['start'];
                            $endDate = $dates['end'];
                        } else if($this->rrule['FREQ'] == 'MONTHLY')
                        {
                            $dates = $this->calc_Month($dateNew);
                            $startDate = $dates['start'];
                            $endDate = $dates['end'];
                        } else
                        {
                            $startDate = $date;
                            $endDate = $dateNew;
                        }
                        $this->process_byMonthDay($dateNew, $startDate, $endDate);
                    } else 
                    {
                        $this->insert($dateNew);
                    }
                }
            } else if(isset($this->rrule['BYMONTHDAY']) && !empty($this->rrule['BYMONTHDAY']))
            {
                if($this->rrule['FREQ'] == 'YEARLY')
                {
                    $dates = $this->calc_Year($dateNew);
                    $startDate = $dates['start'];
                    $endDate = $dates['end'];
                } else if($this->rrule['FREQ'] == 'WEEKLY')
                {
                    $dates = $this->calc_Week($dateNew);
                    $startDate = $dates['start'];
                    $endDate = $dates['end'];
                } else if($this->rrule['FREQ'] == 'MONTHLY')
                {
                    $dates = $this->calc_Month($dateNew);
                    $startDate = $dates['start'];
                    $endDate = $dates['end'];
                } else
                {
                    $startDate = $date;
                    $endDate = $dateNew;
                }
                $this->process_byMonthDay($dateNew, $startDate, $endDate);
            } else if(isset($this->rrule['BYYEARDAY']) && !empty($this->rrule['BYYEARDAY']) && $this->rrule['FREQ'] == 'YEARLY')
            {
                $dates = $this->calc_Year($dateNew);
                $startDate = $dates['start'];
                $endDate = $dates['end'];
                $this->process_byYearDay($dateNew, $startDate, $endDate);
            } else 
            {
                $this->insert($dateNew);
            }


            $firstLoop = false;
        }

    }
    
    function process_rdate($dates)
    {
        foreach($dates AS $date)
        {
            $dateNew = $date['year']."-".$date['month']."-".$date['day'];
            $this->insert($dateNew);
        }
    }

    function calc_Year($date)
    {
        $getdate = getdate(strtotime($date));

        return array('start' => $getdate['year'].'-01-01', 'end'=> $getdate['year'].'-12-31');
    }

    function calc_Week($date)
    {
        $getdate = getdate(strtotime($date));

        if($getdate['wday'] == 0)
        {
            $sun = $date;
            $mon = DateUtil::getDatetime(strtotime('last mon', $getdate[0]), _DATEINPUT);
        } else if($getdate['wday'] == 1)
        {
            $mon = $date;
            $sun = DateUtil::getDatetime(strtotime('next sun', $getdate[0]), _DATEINPUT);
        } else 
        {
            $mon = DateUtil::getDatetime(strtotime('last mon', $getdate[0]), _DATEINPUT);
            $sun = DateUtil::getDatetime(strtotime('next sun', $getdate[0]), _DATEINPUT);
        }

        return array('start' => $mon, 'end'=> $sun);
    }

    function calc_Month($date)
    {
        list($year, $month, $day) = explode('-', $date);
        $daysInMonth = DateUtil::getDaysInMonth($month, $year);
        $daysLeft = (int)$daysInMonth - (int)$day;

        $end = DateUtil::getDatetime(strtotime('+'.$daysLeft.' days', strtotime($date)), _DATEINPUT);
        $start = DateUtil::getDatetime(strtotime('-'.((int)$day-1).' days', strtotime($date)), _DATEINPUT);
        return array('start'=>$start,'end'=>$end);
    }

    function calc_endOfYear($date)
    {
        list($year, $month, $day) = explode('-', $date);

        return DateUtil::getDatetime(strtotime($year.'-12-31'), _DATEINPUT);
    }

    function check_byDay_contains($weekday, $ret=false)
    {
        $array = array();

        foreach ($this->rrule['BYDAY'] as $arr) 
        {
            if($arr['DAY'] == $weekday)
            {

                $array[] = $arr;
            }
        }

        if(!empty($array))
        {
            if($ret)
            {
                return $array;
            } else 
            {
                return true;
            }
        } else
        {
            return false;
        }
    }

    function check_byMonth($date)
    {
        list($year, $month, $day) = explode('-',$date);
        if(in_array((int)$month, $this->rrule['BYMONTH']))
        {
            return true;
        } 

        return false;
    }

    /**
     * Process BYDAY rule
     *
     * @param string $dateNew end date
     * @param string $date start date
     */
    function process_byDay($dateNew, $date)
    {	
        //echo $date;exit();
        $weekdayMap = array('SU' => 'sun',
                                            'MO' => 'mon',
                                    'Tu' => 'tue',
                                    'WE' => 'wed',
                                    'TH' => 'thu',
                                    'FR' => 'fri',
                                    'SA' => 'sat');

        $date2 = $date;
        //$dateNew2 = $date2;
        // iterate over all days between $date and $dateNew
        while($date2 <= $dateNew)
        {
            $getDate = getdate(strtotime($date2));
            // valid week day?

            if($this->check_byDay_contains($this->numToWeekDay[$getDate['wday']]))
            {
                $byDayValA = $this->check_byDay_contains($this->numToWeekDay[$getDate['wday']], true);
                $byDayOK = true;

                if(!empty($byDayValA))
                {
                    foreach($byDayValA AS $byDayVal)
                    {
                        // negaive value?
                        if(isset($byDayVal[0]) && (int)$byDayVal[0] < 0)
                        {
                            $strtotimeSTR = ' -'.(abs(((int)$byDayVal[0]+1)*7)).' day';
                            if(((int)$byDayVal[0]+1) == 0)
                            {
                                $strtotimeSTR = '';
                            }
                            $dateCheck = strtotime('last '.$weekdayMap[$byDayVal['DAY']].$strtotimeSTR, strtotime('+1 day',mktime(0,0,0,$getDate['mon'],DateUtil::getDaysInMonth($getDate['mon'], $getDate['year']),$getDate['year'],0)));

                        } else if(isset($byDayVal[0]) && (int)$byDayVal[0] > 0)
                        {
                            $dateCheck = strtotime('first '.$weekdayMap[$byDayVal['DAY']].' +'.(((int)$byDayVal[0]-1)*7).' day', mktime(0,0,0,$getDate['mon'],1,$getDate['year'],0));
                        } else 
                        {
                            $dateCheck = strtotime($date2);
                        }

                        if($date2 != DateUtil::getDatetime($dateCheck, _DATEINPUT))
                        {
                            $byDayOK = false;
                        } else 
                        {
                            $byDayOK = true;
                            break;
                        }
                    }
                }

                if($byDayOK)
                {
                    // BYMONTH rule given?
                    if(isset($this->rrule['BYMONTH']) && !empty($this->rrule['BYMONTH']))
                    {
                        $resultByMonth = $this->check_byMonth($date2);
                        if($resultByMonth)
                        {
                            if($this->check_byMonthDay($date2))
                            {
                                $this->insert($date2);
                            }
                        }
                    } else 
                    {
                        if($this->check_byMonthDay($date2))
                        {
                            $this->insert($date2);
                        }
                    }
                }

            }
            $date2 = DateUtil::getDatetime(strtotime('+1 day', strtotime($date2)), _DATEINPUT);
        }
    }

    function check_byMonthDay($date)
    {
        if(isset($this->rrule['BYMONTHDAY']) && !empty($this->rrule['BYMONTHDAY']))
        {

            list($year, $month, $day) = explode('-',$date);
            $dates = $this->calc_Month($date);

            foreach ($this->rrule['BYMONTHDAY'] as $date2) 
            {
                $value = (int)$date2;
                if($value < -31 || $value > 31 || $value == 0)
                {
                    continue; // invalied date -> ignore
                }

                if((int)$day == $value)
                {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    function process_byMonthDay($date, $start, $end)
    {
        list($year, $month, $day) = explode('-',$date);
        $dates = $this->calc_Month($date);

        foreach ($this->rrule['BYMONTHDAY'] as $date2) 
        {
            $value = (int)$date2;
            if($value < -31 || $value > 31 || $value == 0)
            {
                continue; // invalied date -> ignore
            }

            if($value > 0)
            {
                $dateNew = $year.'-'.$month.'-'.(($value < 10)? '0'.$value : $value);
            } else if($value < 0)
            {
                $stamp = strtotime('-'.($value+1).' day', strtotime($dates['end']));
                $dateNew = DateUtil::getDatetime($stamp, _DATEINPUT);
            }

            if($dateNew >= $start && $dateNew <= $end)
            {
                $this->insert($dateNew);
            }
        }

    }

    function process_byYearDay($date, $start, $end)
    {
        list($year, $month, $day) = explode('-',$date);
        $dates = $this->calc_Year($date);

        foreach ($this->rrule['BYYEARDAY'] as $date2) 
        {
            $value = (int)$date2;
            if($value < -366 || $value > 366 || $value == 0)
            {
                continue; // invalied date -> ignore
            }

            if($value > 0)
            {
                $dateNew =  DateUtil::getDatetime(strtotime('+'.($value-1).' day', strtotime($dates['start'])), _DATEINPUT);
            } else if($value < 0)
            {
                $stamp = strtotime('-'.($value+1).' day', strtotime($dates['end']));
                $dateNew = DateUtil::getDatetime($stamp, _DATEINPUT);
            }

            if($dateNew >= $start && $dateNew <= $end)
            {
                $this->insert($dateNew);
            }
        }

    }

    function insert($date)
    {
        $timestamp = DateUtil::parseUIDate($date);
        if($date >= $this->start && $date >= $this->obj['startDate'] && $this->count > 0 && !isset($this->retArray[$timestamp]))
        {

            $this->retArray[$timestamp] = &$this->obj;
            $this->count--;
        }
    }
}
