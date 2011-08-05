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

/**
 * Entry point of the module.
 * @return string Render output
 */
function TimeIt_user_main()
{
    return pnModFunc('TimeIt', 'user', 'view');
}

/**
 * Generic view function.
 */
function TimeIt_user_view($args=array())
{
    $gtdomain = ZLanguage::getModuleDomain('TimeIt');
    $tpl = null;
    $theme = null;
    $omitLayout = false;

    // load object type parameter
    if(isset($args['ot']))
        $objectType = $args['ot'];
    else
        $objectType = FormUtil::getPassedValue('ot', 'event', 'GET');

     // check object type
    if(!in_array($objectType, TimeItUtil::getObjectTypes('view'))) {
        return LogUtil::registerError(__f('Unkown object type %s.', DataUtil::formatForDisplay($objectType), $gtdomain), 404);
    }

    // get filter
    Loader::loadClass('TimeItFilter', 'modules/TimeIt/classes/filter');
    $filter_obj = TimeItFilter::getFilterFormGETPOST();

    
    $domain = TimeItDomainFactory::getInstance($objectType);


    // get pnRender instance for this module
    $render =& pnRender::getInstance('TimeIt', false); //TODO: add caching
    $render->assign('modvars', pnModGetVar('TimeIt'));

    // load the data
    if($objectType == 'event') {
        $calendar_id = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($calendar_id);
        if(empty($calendar)) {
            return LogUtil::registerError (__f('Calendar [%s] not found.', $calendar_id, $gtdomain), 404);
        }
        $year    = (int)FormUtil::getPassedValue('year', date("Y"), 'GETPOST');
        $month   = (int)FormUtil::getPassedValue('month', date("n"), 'GETPOST');
        $day     = (int)FormUtil::getPassedValue('day', date("j"), 'GETPOST');
        $tpl = FormUtil::getPassedValue('viewType', FormUtil::getPassedValue('viewtype', $calendar['defaultView'], 'GETPOST'), 'GETPOST');
        $firstDayOfWeek = (int)FormUtil::getPassedValue('firstDayOfWeek', -1, 'GETPOST');

        // get theme
        $theme = FormUtil::getPassedValue('template', $calendar['defaultTemplate'] , 'GETPOST');
        // backward compatibility
        if($theme == 'default')
            $theme = 'table';
        
        // check for a valid $tpl
        if($tpl != 'year' && $tpl != 'month' && $tpl != 'week' && $tpl != 'day') {
            $tpl = $calendar['defaultView'];
        }

        $render->assign('template', $theme);
        $render->assign('viewed_day', $day);
        $render->assign('viewed_month', $month);
        $render->assign('viewed_year', $year);
        $render->assign('viewType', $tpl);
        $render->assign('calendar', $calendar);
        $render->assign('viewed_date', DateUtil::getDatetime(mktime(0, 0, 0, $month, $day, $year), DATEONLYFORMAT_FIXED));
        $render->assign('date_today', DateUtil::getDatetime(null, DATEONLYFORMAT_FIXED));
        $render->assign('month_startDate', DateUtil::getDatetime(mktime(0, 0, 0, $month, 1, $year), DATEONLYFORMAT_FIXED)  );
        $render->assign('month_endDate', DateUtil::getDatetime(mktime(0, 0, 0, $month, DateUtil::getDaysInMonth($month, $year), $year), DATEONLYFORMAT_FIXED) );
        $render->assign('filter_obj_url', $filter_obj->toURL());
        $render->assign('firstDayOfWeek', $firstDayOfWeek);
        $render->assign('selectedCats', array());

        // data for the naviagtion
        Loader::loadClass('CategoryRegistryUtil');
        Loader::loadClass('CategoryUtil');

        $categories = CategoryRegistryUtil::getRegisteredModuleCategories('TimeIt', 'TimeIt_events');
        foreach($categories AS $property => $cid) {
            $cat = CategoryUtil::getCategoryByID($cid);

            if(isset($cat['__ATTRIBUTES__']['calendarid']) && !empty($cat['__ATTRIBUTES__']['calendarid'])) {
                if($cat['__ATTRIBUTES__']['calendarid'] != $calendar['id']) {
                    unset($categories[$property]);
                }
            }
        }

        $render->assign('categories', $categories);

        switch ($tpl) {
            case 'year':
                $objectData = $domain->getYearEvents($year, $calendar['id'], $firstDayOfWeek);
                break;
            case 'month':
                $objectData = $domain->getMonthEvents($year, $month, $day, $calendar['id'], $firstDayOfWeek, $filter_obj);
                break;
            case 'week':
                $objectData = $domain->getWeekEvents($year, $month, $day, $calendar['id'], $filter_obj);
                break;
            case 'day':
                $objectData = $domain->getDayEvents($year, $month, $day, $calendar['id'], $filter_obj);
                break;
        }

        if(FormUtil::getPassedValue('ical', false, 'GETPOST')) {
            TimeIt_createIcal($objectData, $tpl == 'day'? true : false);
            return true;
        }

    // reg specific logic
    } else if($objectType == 'reg') {
        $eid = (int)FormUtil::getPassedValue('eid', 0, 'GET');
        $uid = (int)FormUtil::getPassedValue('uid', 0, 'GET');

        // show users of event
        if(!empty($eid)) {
            $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $eid);
            $obj = TimeItDomainFactory::getInstance('event')->getObject($dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid'], $dheobj['id']);


            // check for permissions
            if(!TimeItPermissionUtil::canViewEvent($obj)) {
                return LogUtil::registerPermissionError();
            }
            $hasExtendedPerms = TimeItPermissionUtil::canViewRegDetails($obj);

            // load data
            $objectData = $domain->getUserOfAReg($id, false, $hasExtendedPerms? true : false);
            $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

            // assign data
            $render->assign('calendar', $calendar);
            $render->assign('event', $obj);
            $render->assign('uid', pnUserGetVar('uid'));
            $render->assign('eid', $eid);
            $render->assign('showAddress', $hasExtendedPerms? true : false);

            if(FormUtil::getPassedValue('format', null, 'GET') == 'csv') {
                $tpl = 'user_csv';
                $omitLayout = true;
                header( 'Content-Type: text/csv; charset=utf-8' );
                header( 'Content-Disposition: attachment; filename="users.csv"' );
            } else {
                $tpl = 'user';
            }

        // show events of user
        } else if(!empty($uid)) {
            $objectData = TimeItDomainFactory::getInstance('reg')->getEventsOfUser($uid);
            $tpl = 'events';
        } else {
            return LogUtil::registerError(__('Please specify an eid or uid!', $gtdomain));
        }
    } else {
        $objectData = $domain->getObjectList($filter_obj);
    }

    // assign the data
    $render->assign('objectArray', $objectData);

    // render the html
    $template = TimeItUtil::getTemplate($render, $objectType, 'user', 'view', $theme, $tpl, 'table');

    if(!$omitLayout) {
        return $render->fetch($template);
    } else {
        echo $render->fetch($template);
        return true;
    }
}

/**
 * Generic display function.
 */
function TimeIt_user_display()
{
    $domain = ZLanguage::getModuleDomain('TimeIt');
    $tpl = null;

    // load object type parameter
    $objectType = FormUtil::getPassedValue('ot', 'event', 'GET');

     // check object type
    if(!in_array($objectType, TimeItUtil::getObjectTypes('display'))) {
        return LogUtil::registerError(__f('Unkown object type %s.', DataUtil::formatForDisplay($objectType)), 404);
    }

    // get the id
    $id = (int)FormUtil::getPassedValue('id', 0, 'GET');
    if (!$id) {
        pn_exit(__f('Invalid id [%s] received ...', DataUtil::formatForDisplay($id), $domain));
    }

    // get pnRender instance for this module
    $render =& pnRender::getInstance('TimeIt');
    $render->assign('modvars', pnModGetVar('TimeIt'));

    // get data form database
    $objdomain = TimeItDomainFactory::getInstance($objectType);
    if($objectType == 'event') {
        $dheid = (int)FormUtil::getPassedValue('dheid', null, 'GET');
        if($dheid) {
            $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $dheid);
        } else {
            $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$id, 'date ASC');
            if(count($dheobj)) {
                $dheobj = $dheobj[0];
            } else {
                return LogUtil::registerError(__f('Item with id %s not found.', $id, $domain), 404);
            }
        }

        $objectData = $objdomain->getObject($id, $dheobj['id']);

        if(!is_array($objectData)) {
            return LogUtil::registerError(__f('Item with id %s not found.', $id, $domain));
        }

        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($objectData['cid']);

        // check permissions
        if(!TimeItPermissionUtil::canViewEvent($objectData)) {
            return LogUtil::registerPermissionError();
        }

        // format columns in the event
        $objectData = pnModAPIFunc('TimeIt', 'user', 'getEventPreformat', array('obj' => $objectData));
        // Move this event back or forward if the timezone calculation needs a move
        if(isset($objectData['allDayStartLocalDateCorrection'])) {
            $timestamp = strtotime($dheobj['date']) + ($objectData['allDayStartLocalDateCorrection'] * (60 * 60 * 24));
            $dheobj['date'] = DateUtil::getDatetime($timestamp, DATEONLYFORMAT_FIXED);
        }

        if((int)$objectData['repeatType'] == 0 && $objectData['endDate'] > $objectData['startDate']) {
            $render->assign('dheobj2', pnModAPIFunc('TimeIt', 'user', 'getDHE', array('obj'=>array('id'=>$objectData['id']))));
        } else {
            $render->assign('dheobj2', $dheobj);
        }

        // assign data
        $render->assign('calendar', $calendar);
        $render->assign('dheobj', $dheobj);
        $render->assign('viewDate', $dheobj['date']);
        $render->assign('template', FormUtil::getPassedValue('template', 'table' , 'GETPOST'));
        $render->assign('dayNames', array(__('Sun', $domain),
                                          __('Mon', $domain),
                                          __('Tue', $domain),
                                          __('Wed', $domain),
                                          __('Thu', $domain),
                                          __('Fri', $domain),
                                          __('Sat', $domain)));
        $render->assign('dayFrec', array('day'   => __('Days', $domain),
                                         'week'  => __('Weeks', $domain),
                                         'month' => __('Months', $domain),
                                         'year'  => __('Years', $domain)));
        $render->assign('frec', array(1 => __('First', $domain),
                                      2 => __('Second', $domain),
                                      3 => __('Third', $domain),
                                      4 => __('Fourth', $domain),
                                      5 => __('Last', $domain)));
        $render->assign_by_ref('smarty_all_vars', $render->get_template_vars());
    } else {
        $objectData = $objdomain->getObject($id);

        if(!is_array($objectData)) {
            return LogUtil::registerError(__f('Item with id %s not found.', $id, $domain));
        }
    }

    
    $render->assign($objectType, $objectData);

    // render the html
    $template = TimeItUtil::getTemplate($render, $objectType, 'user', 'display', null, $tpl);
    return $render->fetch($template);
}


/**
 * Generic edit function.
 */
function TimeIt_user_edit($args=array())
{
    $domain = ZLanguage::getModuleDomain('TimeIt');

     // load object type parameter
    if(isset($args['ot']))
        $objectType = $args['ot'];
    else
        $objectType = FormUtil::getPassedValue('ot', 'event', 'GET');

    $tpl = FormUtil::getPassedValue('tpl', null, 'GET');

    // check object type
    if(!in_array($objectType, TimeItUtil::getObjectTypes('edit'))) {
        return LogUtil::registerError(__f('Unkown object type %s.', DataUtil::formatForDisplay($objectType), $domain), 404);
    }

    $render = FormUtil::newpnForm('TimeIt');
    $handlerClass = 'TimeIt_FormHandler_Edit_'.$objectType.(!empty($tpl)? $tpl : '');
    
    Loader::loadClass($handlerClass, 'modules/TimeIt/classes/FormHandler');

    return $render->pnFormExecute('user_edit_'.$objectType.(!empty($tpl)?'_'.DataUtil::formatForOS($tpl) : '').'.htm', new $handlerClass());
}

/**
 * Generic delete function.
 */
function TimeIt_user_delete()
{
    $domain = ZLanguage::getModuleDomain('TimeIt');

     // load object type parameter
    $objectType = FormUtil::getPassedValue('ot', 'event', 'GETPOST');

    // check object type
    if(!in_array($objectType, TimeItUtil::getObjectTypes('delete'))) {
        return LogUtil::registerError(__f('Unkown object type %s.', DataUtil::formatForDisplay($objectType), $domain), 404);
    }

    // get the id
    $id = (int)FormUtil::getPassedValue('id', 0, 'GETPOST');
    if (!$id) {
        pn_exit(__f('Invalid id [%s] received ...', DataUtil::formatForDisplay($id), $domain));
    }
    
    // get data form database
    $objdomain = TimeItDomainFactory::getInstance($objectType);

    if($objectType == 'event') {
        if(!TimeItPermissionUtil::canDeleteEvent($objdomain->getObject($id))) {
            return LogUtil::registerPermissionError();
        }
    } else if($objectType == 'calendar') {
        if(!TimeItPermissionUtil::canDeleteCalendar($id)) {
            return LogUtil::registerPermissionError();
        }
    } else if($objectType == 'reg') {
        if(!TimeItPermissionUtil::canDeleteReg($id)) {
            return LogUtil::registerPermissionError();
        }
    }

    if($objectType == 'reg' && FormUtil::getPassedValue('pendingState', 0, 'GETPOST')) {
        $objdomain->deletePendingState($id);
    } else {
        $objdomain->deleteObject($id);
    }

    if($objectType == 'reg')
        $objectType = 'event';

    // display message
    LogUtil::registerStatus(__('Done! Item deleted.', $domain));

    // return to view-page.
    return pnRedirect(pnModURL('TimeIt', 'user', 'main', array('ot' => $objectType)));
}

/**
 * Custom formicula send function that redirects to the event insted to the formicula view.form function
 */
function TimeIt_user_formicula_send()
{
    $ret = pnModFunc('formicula','user','send');

    // test against true because formicula uses pnRedirect() which returns true
    if($ret === true) {
        return pnRedirect(pnModURL('TimeIt','user','display', array('ot'    => 'event',
                                                                    'id'    => FormUtil::getPassedValue('timeit_eid', null, 'GETPOST'),
                                                                    'dheid' => FormUtil::getPassedValue('timeit_dheid', null, 'GETPOST'))));
    } else {
        return $ret;
    }
}

function TimeIt_user_subscribe()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id == false) {
        LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else {
        if($id !== false) {
            $result = TimeItDomainFactory::getInstance('reg')->create($id);

            if(!$result) {
                LogUtil::registerError(__('Error! Registration to the event faild.', ZLanguage::getModuleDomain('TimeIt')));
            }
        } else {
             LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }
    }

    return pnRedirect(pnModURL('TimeIt','user','display', array('ot'    => 'event',
                                                                'id'    => FormUtil::getPassedValue('eid', null, 'GETPOST'),
                                                                'dheid' => FormUtil::getPassedValue('id', null, 'GETPOST'))));
}

function TimeIt_user_unsubscribe()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false) {
        LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else {
        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $id);
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

        if($id !== false && $calendar['allowSubscribe'])  {
            $result = TimeItDomainFactory::getInstance('reg')->deleteByEvent($dheobj['id']);
            if(!$result)
                 LogUtil::registerError(__('Error! De-Registration from the event faild.', ZLanguage::getModuleDomain('TimeIt')));
        } else  {
            LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }
    }

    return pnRedirect(pnModURL('TimeIt','user','display', array('ot'    => 'event',
                                                                'id'    => FormUtil::getPassedValue('eid', null, 'GETPOST'),
                                                                'dheid' => FormUtil::getPassedValue('id', null, 'GETPOST'))));
}

function TimeIt_user_feed()
{
    $GETCID = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GET');
    $GETMODE = FormUtil::getPassedValue('mode', null, 'GET');
    $GETFEEDTYPE = FormUtil::getPassedValue('theme', 'RSS', 'GET');
    if($GETFEEDTYPE != 'RSS' && $GETFEEDTYPE != 'Atom') {
        $GETFEEDTYPE = 'RSS';
    }

    // include class
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
        pn_exit ("Unable to load class [Event] ...");
    }

    // get tables array
    $pntable = pnDBGetTables();
    $cols = $pntable['TimeIt_events_column'];

    $filter_obj = TimeItFilter::getFilterFormGETPOST();

    // build sql where part
    if($GETMODE == 'last') {
        // get events
        $array = TimeItDomainFactory::getInstance('event')->getEvents($GETCID, $filter_obj, 0, (int)pnModGetVar('TimeIt', 'rssatomitems'), true, true, $cols['lu_date'].' DESC');
    } else if($GETMODE == 'today') {
        $date = DateUtil::getDatetime(null, DATEONLYFORMAT_FIXED);
        $events = TimeItDomainFactory::getInstance('event')->getDailySortedEvents($date, $date, $GETCID, $filter_obj);

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
        $startDateArray = getDate(pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', array('day'   => $date['mday'],
                                                                                            'month' => $date['mon'],
                                                                                            'year'  => $date['year'])));
        $startDate = DateUtil::getDatetime($startDateArray[0], DATEONLYFORMAT_FIXED);
        $endDate   = DateUtil::getDatetime(mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+6, $startDateArray['year']), DATEONLYFORMAT_FIXED);
        
        $events = TimeItDomainFactory::getInstance('event')->getDailySortedEvents($startDate, $endDate, $GETCID, $filter_obj);
        
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
        $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month'=>$date['mon'],'year'=>$date['year']));
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


        $events = TimeItDomainFactory::getInstance('event')->getDailySortedEvents($start, $end, $GETCID, $filter_obj);
        
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