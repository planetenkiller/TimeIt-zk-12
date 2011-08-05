<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Core
 */

Loader::loadClass('TimeItDomainFactory', 'modules/TimeIt/classes/domain');
Loader::loadClass('TimeItFilter', 'modules/TimeIt/classes/filter');

// Klasse aus pnForm.php vorher laden
if(file_exists('includes/pnForm.php')) { // if: Zikula 1.3 compatibilty
    Loader::requireOnce('includes/pnForm.php');
}
// Zikula 1.3 fix: include custom form plugins
Loader::loadFile('function.tiformdateinput.php', 'modules/TimeIt/pntemplates/plugins');
Loader::loadFile('function.tiformcategoryselector.php', 'modules/TimeIt/pntemplates/plugins');

/**
 * Util class with some usefull functions.
 */
abstract class TimeItUtil
{
    /**
     * Returns all available object types for a functions.
     * @param string $function view, display,edit,delete
     * @return array available object types
     */
    public static function getObjectTypes($function)
    {
        // available types
        $types = array('view'    => array('calendar','event','reg'),
                       'display' => array('event'),
                       'edit'    => array('calendar','event'),
                       'delete'  => array('calendar','event','reg'));

        if(isset($types[$function])) {
            return $types[$function];
        } else {
            return array();
        }
    }

    public static function getTemplate($render, $objectType, $type, $func, $theme=null, $tpl=null, $defaultTheme=null)
    {
        $template = $type . '_' . $func . '_' . $objectType;
        if($tpl != null) {
            $template .= '_' . $tpl;
        }
        $template .= '.htm';

        if(!empty($theme) && $render->template_exists(DataUtil::formatForOS($theme).'/'.$template)) {
            return DataUtil::formatForOS($theme).'/'.$template;
        } else if(!empty($defaultTheme) && $render->template_exists(DataUtil::formatForOS($defaultTheme).'/'.$template)) {
            return DataUtil::formatForOS($defaultTheme).'/'.$template;
        } else {
            return $template;
        }
    }

    /**
     * Extendes DateUtil::getDatetime() with translations of the month and weekday names.
     */
    public static function getDatetime($time='', $format=DATEFORMAT_FIXED)
    {
        return DateUtil::getDatetime($time, $format);
    }

    /**
     * converts the $obj[allDayStart] to the current timezone
     * @param array $obj Event
     */
    public static function convertAlldayStartToLocalTime(&$obj) {
        if($obj['allDay'] == 0) {
            if(strpos($obj['allDayStart'], ' ') !== false) {
                // calc local start time
                $time = substr($obj['allDayStart'], 0, strpos($obj['allDayStart'], ' '));
                $timezone = (int)substr($obj['allDayStart'], strpos($obj['allDayStart'], ' ')+1);
                $timezoneCurr = (int)(pnUserGetVar('tzoffset')!==false ? pnUserGetVar('tzoffset') : pnConfigGetVar('timezone_offset'));
                $zoneOffset = ($timezone * -1) + $timezoneCurr;
                list($hour, $min) = explode(':', $time);
                list($zone_hour, $zone_minDez) = explode('.', $zoneOffset);
                $hour += $zone_hour;
                $min += $zone_minDez * 60; // convert e.g. 0.75 to 45
                // more than 60 minutes than add an hour and reduce the minutes
                if($min >= 60) {
                    $hour++;
                    $min = $min - 60;
                }

                if($hour < 0) {
                    $obj['allDayStartLocalDateCorrection'] = -1;
                    $hour = 24 + $hour; // fix minus value
                } else if($hour > 24) {
                    $obj['allDayStartLocalDateCorrection'] = +1;
                    $hour = $hour - 24; // fix to big value
                }

                $obj['allDayStartLocal'] = ($hour < 10?'0':'').$hour.':'.($min<10?'0':'').$min;
            } else {
                $obj['allDayStartLocal'] = $obj['allDayStart'];
            }

            // format it
            $obj['allDayStartLocalFormated'] = DateUtil::getDatetime(strtotime($obj['startDate'].' '.$obj['allDayStartLocal'].':00'), 'timebrief');
            // Add timezone to the time
            //$obj['allDayStartLocalFormated'] = $obj['allDayStartLocalFormated'].' '.DateUtil::strftime('%Z');

        }
    }
}

/**
 * Util class for the event plugin system.
 */
abstract class TimeItEventPluginsUtil
{
    /**
     * Returns all available event plugins.
     *
     * @return array 'contact'  => contact eventplugins (strings)
     *               'location' => location eventsplugins (strings)
     */
    public static function getEventPlugins($grouped=true)
    {
        static $cache_eventplugins;

        if(!$cache_eventplugins) {
            $cache_eventplugins = array('contact'  => array('ContactTimeIt',
                                                            'ContactFormicula',
                                                            'ContactAddressbook',
                                                            'ContactTiFormiCombi'),
                                        'location' => array('LocationTimeIt',
                                                            'LocationLocations',
                                                            'LocationAddressbook'));

            if(file_exists('modules/TimeIt/config/config.php')) {
                include 'modules/TimeIt/config/config.php';
                if(isset($eventplugins)) {
                    foreach($eventplugins AS $type => $plugins) {
                        foreach($plugins AS $plugin) {
                            if(file_exists(pnModGetBaseDir().'EventPlugins/EventPlugins'.$plugin)) {
                                $cache_eventplugins[$type][] = $plugin;
                            }
                        }
                    }
                }
            }
        }

        if($grouped) {
            return $cache_eventplugins;
        } else {
            return array_merge($cache_eventplugins['contact'], $cache_eventplugins['location']);
        }
    }

    /**
     * Returns an new object of an event plugin.
     * @param string $name name of the event plugin
     * @return object new instance or null
     */
    public static function getEventPluginInstance($name)
    {
        if(!in_array($name, self::getEventPlugins(false))) {
            return null; // event plugin is not avaiable so skip loading
        }

        $filename = 'EventPlugins'.$name;
        $classname = 'TimeIt'.$filename;

        // load class if it isn't available
        if(!class_exists($classname)) {
            Loader::loadFile($filename.'.php','modules/TimeIt/classes/EventPlugins');
        }

        $instance = null;


        if(class_exists($classname)) {
            $instance = new $classname();
        }

        return $instance;
    }

    /**
     * Returns the classname for a event plugin name.
     * @param string $pluginname
     * @return string
     */
    public static function getEventPluginClassname($pluginname)
    {
        return 'TimeItEventPlugins'.$pluginname;
    }

    public static function getEventPluginNameWithoutType($pluginname)
    {
        if(strpos($pluginname, 'Location') !== false) {
            return substr($pluginname, strlen('Location'));
        } else if(strpos($pluginname, 'Contact') !== false) {
            return substr($pluginname, strlen('Contact'));
        } else {
            return $pluginname;
        }
    }

    /**
     * Returns instances of all eventsplugins.
     * @param string $name name of the event plugin
     * @return array same as getEventPlugins() but with objects insted of strings
     */
    public static function getEventPluginInstances()
    {
        $plugins = self::getEventPlugins(true);

        foreach($plugins AS &$array) {
            foreach($array AS $key => $plugin) {
                $array[$key] = self::getEventPluginInstance($plugin);
            }
        }

        return $plugins;
    }
}

/**
 * Central point with all permission check functions
 */
abstract class TimeItPermissionUtil
{
    public static function adminAccessCheck($return=false)
    {
        if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_MODERATE)) {
            Loader::loadClass('UserUtil');
            $groups = UserUtil::getGroupsForUser(pnUserGetVar('uid'));
            $groups[] = array('name' => 'all', 'gid'=>'all');

            // check each group for permission
            $ret = array();
            foreach ($groups as $group) {
                if(isset($group['gid']) && $group['gid'] == 'all') {
                    $name = 'all';
                } else {
                    $group = UserUtil::getPNGroup((int)$group);
                    $name = $group['name'];
                }
                if(SecurityUtil::checkPermission( 'TimeIt:Group:', $name."::", ACCESS_MODERATE)) {
                    if(!$return) {
                        return true;
                    } else {
                        $ret[] = $group['gid'];
                    }
                }
            }
            if(!$return) {
                return false;
            } else {
                return $ret;
            }
        } else  {
            return true;
        }
    }

    /**
     * Returns true if the current user can create an event.
     * @return bool
     */
    public static function canCreateEvent($calendarId=null, $modeModerate=false)
    {
        $permLevel = $modeModerate? ACCESS_MODERATE : ACCESS_COMMENT;

        return (SecurityUtil::checkPermission('TimeIt::', '::', $permLevel)
                || ($calendarId != null && SecurityUtil::checkPermission('TimeIt:Calendar:', $calendarId.'::', $permLevel)));
    }

    /**
     * Returns true if the current user can create a calendar.
     * @return bool
     */
    public static function canCreateCalendar() {
        return SecurityUtil::checkPermission('TimeIt::', "::", ACCESS_ADMIN);
    }

    public static function isAdmin() {
        return SecurityUtil::checkPermission('TimeIt::', "::", ACCESS_ADMIN);
    }

    /**
     * Returns true if the current user can edit a calendar.
     * @return bool
     */
    public static function canEditCalendar() {
        return SecurityUtil::checkPermission('TimeIt::', "::", ACCESS_ADMIN);
    }

    /**
     * Returns true if the current user can edit the event $event.
     * @param array $event
     * @return bool
     */
    public static function canEditEvent($event, $modeModerator=false)
    {
        if(empty($event)) {
           return LogUtil::registerError("canEditEvent called with an empty array!");
        }
        
        Loader::loadClass('UserUtil');
        $groupName = $event['group_name'];

        // Do we need to get the group names ourself?
        if(empty($groupName)) {
            if($event['group'] == 'all') {
                $groupName = array('all'); // group irrelevant
            } else {
                $groupNames = array();
                foreach(explode(',', $event['group']) AS $grpId) {
                    $groupObj = UserUtil::getPNGroup((int)$grpId);
                    $groupNames[] = $groupObj['name'];
                }

                $groupName = $groupNames;
            }
        }

        // get calendar
        $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$event['id'], 'date ASC');
        if(count($dheobj)) {
            $dheobj = $dheobj[0];
        }
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

        // check permissions
        $permLevel = !$modeModerator? ACCESS_EDIT : ACCESS_MODERATE;
        if(!SecurityUtil::checkPermission('TimeIt::', '::', $permLevel)) {
            if(!SecurityUtil::checkPermission('TimeIt:Calendar:', $calendar['id'].'::', $permLevel)) {
                $access = false;
                foreach($groupName AS $name) {
                    if(SecurityUtil::checkPermission('TimeIt:Group:', $name.'::', $permLevel)) {
                        $access = true;
                    }
                }

                // continue permission checks when $access is false (== no permissions)
                if(!$access) {
                    if($calendar != null && $calendar['userCanEditHisEvents'] && $event['cr_uid'] == pnUserGetVar('uid')) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } 

        return true;
    }

    /**
     * Returns true if the current user can translate the event $event.
     * @param array $event
     * @return bool
     */
    public static function canTranslateEvent($event)
    {
        return (pnConfigGetVar('multilingual') && (self::canEditEvent($event) || SecurityUtil::checkPermission('TimeIt:Translate:', '::', ACCESS_EDIT)));
    }

    /**
     * Returns true if the current user can delete the calender with the id $calendarId.
     * @param int $calendarId
     * @return bool
     */
    public static function canDeleteCalendar($calendarId) {
        return SecurityUtil::checkPermission('TimeIt::', '::', ACCESS_ADMIN);
    }

    /**
     * Returns true if the current user can delete the registration with the date_has_events id $dheid.
     * @param int $calendarId
     * @return bool
     */
    public static function canDeleteReg($dheId) {
        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $dheId);
        $event = TimeItDomainFactory::getInstance('event')->getObject($dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid']);

        return self::canEditEvent($event);
    }

    /**
     * Returns true if the current user can delete the event $event.
     * @param array $event
     * @return bool
     */
    public static function canDeleteEvent($event)
    {
        Loader::loadClass('UserUtil');
        $groupName = $event['group_name'];

        // Do we need to get the group names ourself?
        if(empty($groupName)) {
            if($event['group'] == 'all') {
                $groupName = array('all'); // group irrelevant
            } else {
                $groupNames = array();
                foreach(explode(',', $event['group']) AS $grpId) {
                    $groupObj = UserUtil::getPNGroup((int)$grpId);
                    $groupNames[] = $groupObj['name'];
                }

                $groupName = $groupNames;
            }
        }

        // get calendar
        $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$event['id'], 'date ASC');
        if(count($dheobj)) {
            $dheobj = $dheobj[0];
        }
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

        // check permissions
        if(!SecurityUtil::checkPermission('TimeIt::', '::', ACCESS_DELETE)) {
            if(!SecurityUtil::checkPermission('TimeIt:Calendar:', $calendar['id'].'::', ACCESS_DELETE)) {
                $access = false;
                foreach($groupName AS $name) {
                    if(SecurityUtil::checkPermission('TimeIt:Group:', $name.'::', ACCESS_DELETE)) {
                        $access = true;
                    }
                }


                if(!$access) {
                    if($calendar != null && $calendar['userCanEditHisEvents'] && $event['cr_uid'] == pnUserGetVar('uid')) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } 

        return true;
    }

    /**
     * Returns true if the current user can view the event $event.
     * @param array $event
     * @return bool
     */
    public static function canViewEvent($event, $level=ACCESS_READ)
    {
        Loader::loadClass('UserUtil');
        $groups = UserUtil::getGroupsForUser(pnUserGetVar('uid'));
        // hack: Admins (group id 2 are in group 1(users) to)
        if(in_array(2, $groups)) {
            $groups[] = 1;
        }

        if($event['group'] == 'all') {
            $groupId = null; // group irrelevant
        } else {
            $groupId = explode(',', $event['group']);
        }

        static $calendarCache = array();
        if(!isset($calendarCache[(int)$event['id']])) {
            // get calendar
            $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$event['id'], 'date ASC');
            if(count($dheobj)) {
                $dheobj = $dheobj[0];
            }
            $calendarCache[(int)$event['id']] = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);
        }

        $calendar = $calendarCache[(int)$event['id']];

        // check permissions

        // hierarchy level 1: module itself
        if(!SecurityUtil::checkPermission('TimeIt::', '::', $level))
            return false;

        // hierarchy level 2: calendar
        if(!SecurityUtil::checkPermission('TimeIt:Calendar:', $calendar['id'].'::', $level))
            return false;

        // hierarchy level 3: group
        if(!empty($groupId)) {
            $access = false;
            foreach($groupId AS $grpId) {
                if(in_array($grpId, $groups)) {
                    $access = true;
                }
            }

            if(!$access) {
                return false;
            }
        }

        // hierarchy level 5: timeit category permission
        if(count($event['__CATEGORIES__']) > 0) {
            $permissionOk = false;
            foreach ($event['__CATEGORIES__'] AS $cat) {
                $cid = $cat;
                if(is_array($cat)) {
                    $cid = $cat['id'];
                }

                $permissionOk = SecurityUtil::checkPermission('TimeIt:Category:', $cid."::", $level);
                if($permissionOk) {
                    // user has got permission -> stop permission checks
                    $hasPermission = true;
                    break;
                }
            }

            if(!$hasPermission)
                return false;
        }

        // hierarchy level 6: zikula category permission
        Loader::loadClass('CategoryUtil');
        if(pnModGetVar('TimeIt', 'filterByPermission', 0) && !CategoryUtil::hasCategoryAccess($event['__CATEGORIES__'], 'TimeIt', $level)) {
            return false;
        }

        // hierarchy level 7: event
        if(!SecurityUtil::checkPermission('TimeIt::Event', $event['id'].'::', $level))
            return false;


        // hierarchy level 8: contact list
        if(pnModAvailable('ContactList')) {
            // cache
            static $ignored = null;

            if($ignored == null) {
                $ignored = pnModAPIFunc('ContactList','user','getallignorelist',array('uid' => pnUserGetVar('uid')));
            }

            if($calendar['friendCalendar']) {
                $buddys = pnModAPIFunc('ContactList','user','getBuddyList',array('uid' => $event['cr_uid']));
            }

            if((int)$event['sharing'] == 4 && $event['cr_uid'] != pnUserGetVar('uid')) {
                $buddyFound = false;
                foreach($buddys AS $buddy) {
                    if($buddy['uid'] == pnUserGetVar('uid')) {
                        $buddyFound = true;
                        break;
                    }
                }

                if(!$buddyFound)
                    return false;
            }

            $ignoredFound = false;
            foreach($ignored AS $ignore) {
                if($ignore['iuid'] == $obj['cr_uid']) {
                    $ignoredFound = true;
                    break;
                }
            }
            if($ignoredFound) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if the current user can register itself to the event $event.
     * @return bool
     */
    public static function canCreateReg($event)
    {
        // get calendar
        $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$event['id'], 'date ASC');
        if(count($dheobj)) {
            $dheobj = $dheobj[0];
        }
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

        // check permissions
        if(SecurityUtil::checkPermission('TimeIt:subscribe:', '::', ACCESS_COMMENT)) {
            if($calendar != null && $calendar['allowSubscribe'] && $event['subscribeLimit'] > 0) {
                return true;
            }
        }

        return false;
    }

    public static function canViewRegDetails($event) {
        if(/*self::canCreateReg($event) security problem: all users with register permissions can see the address!
           || */SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)
           || $event['cr_uid'] === pnUserGetVar('uid'))
        {
            return true;
        }

        return false;
    }
}

function TimeIt_getGroupsForSelect()
{
    $array = array();

    Loader::loadClass('UserUtil');
    $groups = UserUtil::getGroupsForUser(pnUserGetVar('uid'));
    foreach ($groups as $group)
    {
        $groupDB = UserUtil::getPNGroup((int)$group);
        $array[$group] = $groupDB['name'];
    }

    return $array;
}

function TimeIt_decorateWitEventPlugins(&$obj)
{
    $return = false;

    // contact event plugin
    if(!$obj['data']['eventplugin_contact']) {
        $obj['data']['plugindata']['ContactTimeIt'] = array();
        $obj['data']['plugindata']['ContactTimeIt']['contactPerson'] = $obj['data']['contactPerson'];
        $obj['data']['plugindata']['ContactTimeIt']['email']         = $obj['data']['email'];
        $obj['data']['plugindata']['ContactTimeIt']['phoneNr']       = $obj['data']['phoneNr'];
        $obj['data']['plugindata']['ContactTimeIt']['website']       = $obj['data']['website'];
        $obj['data']['eventplugin_contact'] = 'ContactTimeIt';
        unset($obj['data']['contactPerson'],
              $obj['data']['email'],
              $obj['data']['phoneNr'],
              $obj['data']['website']);
        $return = true;
    }
    $eventplugin_c = TimeItEventPluginsUtil::getEventPluginInstance($obj['data']['eventplugin_contact']);
    $eventplugin_c->loadData($obj);
    $obj['plugins']['contact'] =& $eventplugin_c;

    // location event plugin
    // old locations data format? -> convert to new format
    if(isset($obj['data']['locations']) && (int)$obj['data']['locations'] > 0) {
        $obj['data']['plugindata']['LocationLocations'] = array();
        $obj['data']['plugindata']['LocationLocations']['id'] = (int)$obj['data']['locations'];
        $obj['data']['plugindata']['LocationLocations']['displayMap']= $obj['data']['displayMap'];
        unset($obj['data']['locations'],
              $obj['data']['displayMap']);
        $obj['data']['eventplugin_location'] = 'LocationLocations';
        $return = true;
    } else if(!$obj['data']['eventplugin_location']) {
        $obj['data']['plugindata']['LocationTimeIt'] = array();
        $obj['data']['plugindata']['LocationTimeIt']['name'] = $obj['data']['name'];
        $obj['data']['plugindata']['LocationTimeIt']['street'] = $obj['data']['streat'];
        $obj['data']['plugindata']['LocationTimeIt']['houseNumber'] = $obj['data']['houseNumber'];
        $obj['data']['plugindata']['LocationTimeIt']['zip'] = $obj['data']['zip'];
        $obj['data']['plugindata']['LocationTimeIt']['city'] = $obj['data']['city'];
        $obj['data']['plugindata']['LocationTimeIt']['country']= $obj['data']['country'];
        $obj['data']['plugindata']['LocationTimeIt']['lat']= $obj['data']['lat'];
        $obj['data']['plugindata']['LocationTimeIt']['lng']= $obj['data']['lng'];
        $obj['data']['plugindata']['LocationTimeIt']['displayMap']= $obj['data']['displayMap'];

        unset(  $obj['data']['name'],
                $obj['data']['streat'],
                $obj['data']['houseNumber'],
                $obj['data']['zip'],
                $obj['data']['city'],
                $obj['data']['country'],
                $obj['data']['lat'],
                $obj['data']['lng'],
                $obj['data']['displayMap']);
        $obj['data']['eventplugin_location'] = 'LocationTimeIt';
        $return = true;
    }

    $eventplugin_loc = TimeItEventPluginsUtil::getEventPluginInstance($obj['data']['eventplugin_location']);
    $eventplugin_loc->loadData($obj);
    $obj['plugins']['location'] =& $eventplugin_loc;

    return $return;
}


/**
 * Creates and displays the events in the iCalendar format.
 * @param array $events
 * @param array $single
 */
function TimeIt_createIcal($events, $single=false)
{
    Loader::requireOnce('modules/TimeIt/pnincludes/iCalcreator.class.php');
    //print_r($events); exit();
    if($single) {
        $events = array($events);
    }

    $v = new vcalendar();
    $v->setConfig( 'unique_id', 'TimeIt 3.0 Calendar' );
    $v->setProperty( 'method', 'PUBLISH' );

    $ids_already_done = array();
    foreach($events AS $_week) {
        foreach($_week AS $_day) {
            if(empty($_day)) continue;
            foreach($_day AS $cat) {
                foreach ($cat['data'] AS $obj) {
                    // ignore recurrences of an event
                    if(in_array($obj['id'], $ids_already_done)) {
                        continue;
                    }
                    $ids_already_done[] = $obj['id'];

                    $vevent = new vevent();

                    $h = 0;
                    $m = 0;
                    $h2 = 0;
                    $m2 = 0;

                    if(!$obj['allDay']) {
                        $temp = explode(':', $obj['allDayStartLocal']);
                        $h = (int)$temp[0];
                        $m = (int)$temp[1];

                        $temp = !is_array($obj['allDayDur'])? explode(',', $obj['allDayDur']) : $obj['allDayDur'];
                        $t_h = (int)$temp[0];
                        $t_m = (int)$temp[1];

                        $h2 = $h + $t_h;
                        $m2 = $m + $t_m;

                        if($m2 >= 60) {
                            $h2++;
                            $m2 = $m2 - 60;
                        }
                    }

                    $startDate1 = explode('-', $obj['startDate']);
                    if(!$obj['allDay']) {
                        $startDate = array( "year"  => (int)$startDate1[0] ,
                                            "month" => (int)$startDate1[1]  ,
                                            "day"   => (int)$startDate1[2],
                                            'hour'  => $h,
                                            'min'   => $m,
                                            'sec'   => 0);
                        $vevent->setProperty( "dtstart", $startDate);
                    } else {
                        $startDate = array( "year"  => (int)$startDate1[0] ,
                                            "month" => (int)$startDate1[1]  ,
                                            "day"   => (int)$startDate1[2]);
                        $vevent->setProperty( "dtstart", $startDate, array('VALUE' => 'DATE'));
                    }
                                            //print_r($startDate);exit();

                    if($obj['allDay']) {
                        // if the event is a all day event the end date is the next day.
                        $obj['endDate'] = DateUtil::getDatetime(strtotime('+1 day', strtotime($obj['endDate'])), DATEONLYFORMAT_FIXED);
                    }

                    $endDate = explode('-', $obj['endDate']);

                    if(!$obj['allDay']) {
                        $endDate = array( "year"  => (int)$endDate[0],
                                          "month" => (int)$endDate[1],
                                          "day"   => (int)$endDate[2],
                                          'hour'  => $h2,
                                          'min'   => $m2,
                                          'sec'   => 0);
                        $vevent->setProperty( "dtend", $endDate);
                    } else {
                        $endDate = array( "year"  => (int)$endDate[0],
                                      "month" => (int)$endDate[1],
                                      "day"   => (int)$endDate[2]);
                        $vevent->setProperty( "dtend", $endDate, array('VALUE' => 'DATE'));
                    }

                    $vevent->setProperty( "summary", $obj['title']);
                    $vevent->setProperty( "description", $obj['text']);

                    if($obj['plugins']['location']['name'] || $obj['plugins']['location']['name']) {
                        $value = $obj['plugins']['location']['name'].', '.$obj['plugins']['location']['street'].' '.$obj['plugins']['location']['houseNumber'].', '.$obj['plugins']['location']['zip'].' '.$obj['plugins']['location']['city'].' '.$obj['plugins']['location']['country'];
                        $vevent->setLocation($value);
                    }

                    if($obj['plugins']['location']['lat'] && $obj['plugins']['location']['lng']) {
                        $vevent->setGeo($obj['plugins']['location']['lat'], $obj['plugins']['location']['lng']);
                    }

                    if($obj['plugins']['contact']['contactPerson'] || $obj['plugins']['contact']['email'] || $obj['plugins']['contact']['phoneNr']) {
                        $value = $obj['plugins']['contact']['contactPerson'].', '.$obj['plugins']['contact']['address'].', '.$obj['plugins']['contact']['zip'].' '.$obj['plugins']['contact']['city'].' '.$obj['plugins']['contact']['country'].', '.$obj['plugins']['contact']['email'].', '.$obj['plugins']['contact']['phoneNR'];
                        $vevent->setContact($value);
                    }


                    $vevent->setProperty( "uid", $obj['id'].'@'.pnServerGetVar('HTTP_HOST').pnGetBaseURI());
                    $vevent->setProperty( "url", pnModURL('TimeIt','user','display', array('ot'=>'event','id'=>(int)$obj['id']), null, null, true));

                    $cr_date = getdate(strtotime($obj['endDate']));
                    $vevent->setProperty( "dtstamp", array( "year" => (int)$cr_date['year'] ,
                                                                    "month" => (int)$cr_date['mon']  ,
                                                                    "day" => (int)$cr_date['mday'],
                                                                    'hour' => (int)$cr_date['hours'],
                                                                    'min' => (int)$cr_date['minutes'],
                                                                    'sec' => (int)$cr_date['seconds']));

                    $cats = array();
                    foreach($obj['__CATEGORIES__'] as $cat) {
                        $cats[] = $cat['name'];
                    }
                    if(!empty($cats)) {
                        $vevent->setProperty( "categories", $cats);
                    }

                    if($obj['sharing'] == '1')
                    {
                        $vevent->setProperty( "class", 'PRIVATE');
                    } else if($obj['sharing'] == '2' || $obj['sharing'] == '3')
                    {
                        $vevent->setProperty( "class", 'PUBLIC');
                    } else if($obj['sharing'] == '4')
                    {
                        $vevent->setProperty( "class", 'CONFIDENTIAL');
                    }

                    if((int)$obj['repeatType'] == 1)
                    {
                        if($obj['repeatSpec'] == 'year')
                        {
                            $freq = 'YEARLY';
                        } else if($obj['repeatSpec'] == 'month')
                        {
                            $freq = 'MONTHLY';
                        } else if($obj['repeatSpec'] == 'day')
                        {
                            $freq = 'DAILY';
                        }

                        $vevent->setProperty( "dtend", $startDate);
                        $vevent->setProperty("RRULE", array('FREQ'=>$freq,'INTERVAL'=>$obj['repeatFrec'],'UNTIL'=>$endDate));
                    } else if((int)$obj['repeatType'] == 2)
                    {
                        $data = explode(' ', $obj['repeatSpec']);
                        if($data[0] == '5')
                        {
                            $data[0] = '-1';
                        }
                        $byday = $data[0];
                        if($data[1] == '0')
                        {
                            $byday .= 'SO';
                        } else if($data[1] == '1')
                        {
                            $byday .= 'MO';
                        } else if($data[1] == '2')
                        {
                            $byday .= 'TU';
                        } else if($data[1] == '3')
                        {
                            $byday .= 'WE';
                        } else if($data[1] == '4')
                        {
                            $byday .= 'TH';
                        } else if($data[1] == '5')
                        {
                            $byday .= 'FR';
                        } else if($data[1] == '6')
                        {
                            $byday .= 'SA';
                        }

                        $vevent->setProperty( "dtend", $startDate);
                        $vevent->setProperty("RRULE", array('FREQ'=>'MONTHLY','BYDAY'=>$byday,'INTERVAL'=>$obj['repeatFrec'],'UNTIL'=>$endDate));
                    } else if((int)$obj['repeatType'] == 3)
                    {
                        $dates = array();
                        $datesExp = explode(',',$obj['repeatSpec']);
                        foreach($datesExp AS $d)
                        {
                            $d = explode('-', $d);
                            $dates[] = array( "year" => (int)$d[0],
                                              "month" => (int)$d[1] ,
                                              "day" => (int)$d[2]);
                        }

                        $vevent->setProperty("RDATE", $dates);
                    } else if((int)$obj['repeatType'] == 4)
                    {
                        $vevent->setProperty("RRULE", unserialize($obj['repeatSpec']));
                    }


                    //print_r($vevent);
                    $v->setComponent($vevent);
                }
            }
        }
    }

    $v->returnCalendar();
}