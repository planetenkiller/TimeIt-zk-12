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

/**
 * get meta data for the module
 */
function TimeIt_userapi_getmodulemeta()
{
    return array('viewfunc'    => 'view',
                 'displayfunc' => 'display',
                 'newfunc'     => 'edit',
                 'createfunc'  => 'edit',
                 'modifyfunc'  => 'edit',
                 'updatefunc'  => 'edit',
                 'deletefunc'  => 'delete',
                 'titlefield'  => 'title',
                 'itemid'      => 'id');
}

/**
 * Helper API function to get the TimeIt gettext domain (Used in formicula templates).
 */
function TimeIt_userapi_getGTDomain() {
    return ZLanguage::getModuleDomain('TimeIt');
}

/**
 * Return an event by the id.
 * @param id ['id'] of an event
 *           ['translate'] true=translate title and text (default: true)
 * @return array
 */
function TimeIt_userapi_get($args)
{
    if(!isset($args['id'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit (__f('Unable to load array class of the object type %s.', 'Event'));
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
 * Used by pnimportapi.
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
 * Returns the TimeIt_date_has_events (short: dhe) obj by id or by a event.
 * @param array $args ['obj']['id'] get dhe obj by event
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
            $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$args['obj']['id'], 'date ASC');
            if(count($dheobj)) {
                $dheobj = $dheobj[0];
            } else {
                $dheobj = false;
            }
        }

    return $dheobj;
}

function Timeit_userapi_getDHEByDate($args)
{
    if((!isset($args['eid']) || empty($args['eid']))
       && (!isset($args['cid']) || empty($args['cid']))
        && (!isset($args['date']) || empty($args['date']))) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.((int)$args['eid']).' AND cid = '.((int)$args['cid']).' AND the_date = \''.$args['date'].'\'');
    if(count($dheobj)) {
        $dheobj = $dheobj[0];
    } else {
        $dheobj = false;
    }

    return $dheobj;
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
    if(!isset($args['noHooks']) || $args['noHooks'] == false) {
        $obj['text'] = pnModCallHooks('item', 'transform', '', array($obj['text']));
        $obj['text'] = $obj['text'][0];
    }
    
    // repeats
    if($obj['repeatType'] == 2) {
        $temp = explode(' ', $obj['repeatSpec']);
        $obj['repeat21'] = $temp[0];
        $obj['repeat22'] = $temp[1];
    }
    
    // split duration
    $obj['allDayDur'] = explode(',', $obj['allDayDur']);

    TimeItUtil::convertAlldayStartToLocalTime($obj);

    // set username
    $obj['cr_name'] = pnUserGetVar('uname', (int)$obj['cr_uid']);
    $obj['cr_datetime'] = DateUtil::getDatetime(strtotime($obj['cr_date']), "datetimebrief");

    // set group name
    if($obj['group'] == 'all') {
        $obj['group_name'] = 'all';
    } else {
        Loader::loadClass('UserUtil');
        $groupNames = array();
        foreach(explode(',', $obj['group']) AS $grpId) {
            $groupObj = UserUtil::getPNGroup((int)$grpId);
            $groupNames[] = $groupObj['name'];
        }

        $obj['group_name'] = $groupNames;
    }
    
    
    return $obj;
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

/**
 * @return array 2 dimensional array.
 * e.g.: array[0][YYYY-MM-DD] = NULL;
 *
 */
function TimeIt_userapi_arrayForMonthView($args)
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
            //$array[$week][DateUtil::getDatetime($timestamp, DATEONLYFORMAT_FIXED)] = NULL;
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
    if(empty($args)) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if(isset($args['date'])) {
        $a = explode('-', $args['date']);
        $args['day'] = (int)$a[2];
        $args['month'] = (int)$a[1];
        $args['year'] = (int)$a[0];
    }

    if(isset($args['week'])) {
        $args['weeknr'] = $args['week'];
    }

    if(isset($args['day'])) {
        $i = (int)$args['day']; // cast to int
        // invalid day=
        if($i < 1 || $i > 31) {
            return false;
        }
    }

    if(isset($args['weeknr'])) {
        $i = (int)$args['weeknr']; // cast to int
        // invalid day=
        if($i < 1 || $i > 53) {
            return false;
        }
    }

    if(isset($args['month'])) {
        $i = (int)$args['month']; // cast to int
        // invalid day=
        if($i < 1 || $i > 12) {
            return false;
        }
    }

    if(isset($args['year'])) {
        $i = (int)$args['year']; // cast to int
        // invalid day=
        if($i < 1970 || $i > 2037) {
            return false;
        }
    }

    if(isset($args['day']) && isset($args['month']) && isset($args['year'])) {
        $i = (int)DateUtil::getDaysInMonth($args['month'], $args['year']);
        if((int)$args['day'] > $i) {
            return false;
        }
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

    if($result) {
        return $result;
    } else {
        return DateUtil::getDatetime(time(), DATEONLYFORMAT_FIXED);
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

    if($result) {
        return $result;
    } else {
        return '2037-12-31'; // return hightest possible date
    }
}

/**
 * Returns the first day of the week as unix timestamp.
 * @param array $args eg. array('year'=>2009,'week'=>1) or array('year'=>2009,'month'=>1,'day'=>1)
 * @return int unix timestamp
 */
function TimeIt_userapi_getFirstDayOfWeek($args)
{
    if(empty($args['year'])) {
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
    if(!pnModAPIFunc('TimeIt','user','checkDate',$args)) {
        return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }

    $date = $args['year'].'-'.(((int)$args['month'] < 10)?'0'.$args['month']:$args['month']).'-'.(((int)$args['day'] < 10)?'0'.$args['day']:$args['day']);
    $array = pnModAPIFunc('TimeIt','user','arrayForMonthView',$args);
    $found = false;
    $first = false;

    foreach($array AS $week => $days) {
        foreach($days AS $day => $value) {
            if($day == $date) {
                $found = $day;
                break;
            }

            // save first day
            if($first === false) {
                $first = $day;
            }
        }

        if($found !== false) {
            // go to first day in week
            foreach($days AS $day => $value) {
                return strtotime($day);
            }
        }
    }

    return strtotime($first);
}
