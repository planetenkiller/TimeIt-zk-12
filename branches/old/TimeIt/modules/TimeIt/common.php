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

Loader::loadClass('UserUtil');
Loader::includeOnce(WorkflowUtil::_findpath("function.standard_permissioncheck.php", 'TimeIt'));
Loader::includeOnce(WorkflowUtil::_findpath("function.moderate_permissioncheck.php", 'TimeIt'));
Loader::loadFile('EventPluginsContactTimeIt.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsContactFormicula.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsContactAddressbook.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsLocationTimeIt.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsLocationLocations.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsLocationAddressbook.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsContactTiFormiCombi.php','modules/TimeIt/EventPlugins');
Loader::loadFile('Filter.class.php','modules/TimeIt/classes/filter');

/**
 * Class with some usefull methods.
 */
abstract class TimeIt
{
    /**
     * Extendes DateUtil::getDatetime() with translations of the month and weekday names.
     */
    public static function getDatetime($time='', $format=DATEFORMAT_FIXED)
    {
        $format = str_replace('%A', '%%A', $format);
        $format = str_replace('%B', '%%B', $format);
        $format = str_replace('%a', '%%a', $format);
        $format = str_replace('%b', '%%b', $format);
        
        $text = DateUtil::getDatetime($time, $format);
        $weekday = date('w', $time);
        $month = date('n', $time);

        $weekdays = explode(' ', _DAY_OF_WEEK_LONG);
        $weekdays_short = explode(' ', _DAY_OF_WEEK_SHORT);
        $months = explode(' ', _MONTH_LONG);
        $months_short = explode(' ', _MONTH_SHORT);
        
        $text = str_replace('%A', $weekdays[(int)$weekday], $text);
        $text = str_replace('%a', $weekdays_short[(int)$weekday], $text);
        $text = str_replace('%B', $months[(int)$month-1], $text);
        $text = str_replace('%b', $months_short[(int)$month-1], $text);

        return $text;
    }

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
            Loader::loadFile($filename.'.php','modules/TimeIt/EventPlugins');
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
 * FROM: http://ch2.php.net/manual/de/function.array-merge-recursive.php#89684
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param mixed $array2
 * @return array
 * @author daniel@danielsmedegaardbuus.dk
 */
function &array_merge_recursive_distinct(array &$array1, &$array2=null)
{
  $merged = $array1;

  if (is_array($array2))
    foreach ($array2 as $key => $val)
      if (is_array($array2[$key]))
        $merged[$key] = is_array($merged[$key]) ? array_merge_recursive_distinct($merged[$key], $array2[$key]) : $array2[$key];
      else
        $merged[$key] = $val;

  return $merged;
}

function TimeIt_getTranslationForWorkflowActionId($schema, $id)
{
    $array = WorkflowUtil::loadSchema($schema, 'TimeIt');
    $array = $array['actions'];

    foreach($array AS $actions)
    {
        foreach($actions AS $action)
        {
            if($action['id'] == $id)
            {
                return pnML($action['title']);
            }
        }
    }
}

/**
 * Returns the Path to a valid template based on a template and theme name.
 * @param PNRender $render the renderer
 * @param string $template template name
 * @param string $theme theme to search the template in
 * @return string template with theme
 */
function TimeIt_templateWithTheme($render, $template, $theme)
{
    if($render->template_exists(DataUtil::formatForOS($theme).'/'.$template))
    {
        //echo DataUtil::formatForOS($theme).'/'.$template;
        //$render->assign('TiTheme', DataUtil::formatForOS($theme));
        return DataUtil::formatForOS($theme).'/'.$template;
    } else {
        //echo 'default/'.$template;
        //$render->assign('TiTheme', 'default');
        return 'default/'.$template;
    }
}

function TimeIt_adminPermissionCheck($return=false)
{
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_MODERATE))
    {
        Loader::loadClass('UserUtil');
        $groups = UserUtil::getGroupsForUser(pnUserGetVar('uid'));
        $groups[] = array('name' => 'all', 'gid'=>'all');
        // check each group for permission
        $ret = array();
        foreach ($groups as $group) 
        {
            if(isset($group['gid']) && $group['gid'] == 'all')
            {
                $name = 'all';
            } else {
                $group = UserUtil::getPNGroup((int)$group);
                $name = $group['name'];
            }
            if(SecurityUtil::checkPermission( 'TimeIt:Group:', $name."::", ACCESS_MODERATE))
            {
                if(!$return)
                {
                    return true;
                } else {
                    $ret[] = $group['gid'];
                }
            }
        }
        if(!$return)
        {
            return false;
        } else {
            return $ret;
        }
    } else 
    {
        return true;
    }
}

/**
 * Checks the group column of an event.
 * This function returns true when the current user has got access to the event based on the group column.
 * @param array $obj the event
 * @param int $secLevel min. security level eg. ACCESS_READ
 * @return bool
 */
function TimeIt_groupPermissionCheck($obj, $secLevel=ACCESS_READ)
{
    Loader::loadClass('UserUtil');
          
    if($obj['group'] != 'all')
    {
        $group = UserUtil::getPNGroup((int)$obj['group']);
        $obj['group'] = $group['name'];
    }
        
    if(SecurityUtil::checkPermission( 'TimeIt:Group:', $obj['group']."::", $secLevel))
    {
        return true;
    } else 
    {	
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

/**
 * Creates and displays the events in the iCalendar format.
 * @param array $events
 * @param array $single
 */
function TimeIt_createIcal($events, $single=false, $return=false)
{
    Loader::requireOnce('modules/TimeIt/pnincludes/iCalcreator.class.php');
    //print_r($events); exit();
    if($single) {
            $events = array($events);
    }

    $v = new vcalendar();
    $v->setConfig( 'unique_id', 'TimeIt 2.0 Calendar' );
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
                        $temp = explode(':', $obj['allDayStart']);
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
                            $startDate = array( "year"  => (int)$startDate1[0] ,
                                                "month" => (int)$startDate1[1]  ,
                                                "day"   => (int)$startDate1[2],
                                                'hour'  => $h,
                                                'min'   => $m,
                                                'sec'=>0);
                                            //print_r($startDate);exit();
                    $vevent->setProperty( "dtstart", $startDate);

                    if($obj['allDay']) {
                        // if the event is a all day event the end date is the next day.
                        $endDate = DateUtil::getDatetime(strtotime('+1 day', strtotime($obj['endDate'])), _DATEINPUT);
                    } 

                    $endDate = explode('-', $obj['endDate']);
                    $endDate = array( "year"  => (int)$endDate[0],
                                      "month" => (int)$endDate[1],
                                      "day"   => (int)$endDate[2],
                                      'hour'  =>$h2,
                                      'min'   =>$m2,
                                      'sec'=>0);
                    $vevent->setProperty( "dtend", $endDate);

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
                    $vevent->setProperty( "url", pnModURL('TimeIt','user','event', array('id'=>(int)$obj['id']), null, null, true));

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

    if($return) {
        return $v->createCalendar();
    } else {
        $v->returnCalendar();
    }
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
    $eventplugin_c = TimeIt::getEventPluginInstance($obj['data']['eventplugin_contact']);
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

    $eventplugin_loc = TimeIt::getEventPluginInstance($obj['data']['eventplugin_location']);
    $eventplugin_loc->loadData($obj);
    $obj['plugins']['location'] =& $eventplugin_loc;

    return $return;
}