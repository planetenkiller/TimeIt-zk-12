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

function TimeIt_admin_main()
{
    // Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    return $pnRender->fetch('TimeIt_admin_main.htm');
}

function TimeIt_admin_translate()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:Translate:', "::", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }
    
    $render = FormUtil::newpnForm('TimeIt');

    Loader::requireOnce('modules/TimeIt/classes/FormHandler/Translate.php');
    return $render->pnFormExecute('TimeIt_admin_translate.htm', new TimeIt_FormHandler_translate());
}

function TimeIt_admin_viewpending()
{
    // Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countPendingEvents'),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', pnModAPIFunc('TimeIt', 'user', 'pendingEvents', array('startnum'=>$startnum,'numitems'=>$itemsperpage)));
    
    return $pnRender->fetch('TimeIt_admin_viewpending.htm');
}

function TimeIt_admin_viewall()
{
    // Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }

    $cid = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;

    $pnRender = pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countGetAll', array('cid'=>$cid)),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', pnModAPIFunc('TimeIt', 'user', 'getAll', array('cid'=>$cid,'startnum'=>$startnum,'numitems'=>$itemsperpage)));
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt','calendar','getAllForDropdown'));
    $pnRender->assign('calendar', $cid);
    return $pnRender->fetch('TimeIt_admin_viewall.htm');
}

function TimeIt_admin_delete()
{
    $objs = FormUtil::getPassedValue('delete', array(), 'POST');
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }

    // permission check
    if($obj['group'] == 'all' || empty($obj['group'])){
        $groupObj = array('name'=>'all'); // group irrelevant
    } else {
        $groupObj = UserUtil::getPNGroup((int)$obj['group']);
    }

    $perm = SecurityUtil::checkPermission('TimeIt::', '::', ACCESS_DELETE)
            || SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", ACCESS_DELETE);
    if(!$perm) {
        return LogUtil::registerPermissionError();
    }
    
    foreach($objs AS $objid) {
        // get event
        $object = new $class();
        $obj = $object->getEvent($objid);

        WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events');
        WorkflowUtil::deleteWorkflow($obj);
    }

    if(FormUtil::getPassedValue('returnto', null, 'GET') == 'viewall') {
        return pnRedirect(pnModURL('TimeIt', 'admin', 'viewall'));
    } else {
        return pnRedirect(pnModURL('TimeIt', 'admin', 'viewpending'));
    }
}

function TimeIt_admin_viewhidden()
{
    // Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countHiddenEvents'),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', pnModAPIFunc('TimeIt', 'user', 'hiddenEvents', array('startnum'=>$startnum,'numitems'=>$itemsperpage)));
    return $pnRender->fetch('TimeIt_admin_viewhidden.htm');
}

function TimeIt_admin_import()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt','calendar','getAll'));
    
    return $pnRender->fetch('TimeIt_admin_import.htm');
}

function TimeIt_admin_doImport()
{
    $what = FormUtil::getPassedValue('btn', null, 'POST');
    $cid = (int)FormUtil::getPassedValue('cid', null, 'POST');
    if($what == 'postcalendar')
    {
        $prefix = FormUtil::getPassedValue('prefix', 'pn', 'POST');
        $pos = strrpos($prefix, "_");
        if(is_int($pos) && $pos)
        {
            $prefix = substr($prefix, 0, $pos);
        }
        pnModAPIFunc('TimeIt', 'import', 'postcalendar', array('prefix'=>$prefix,'cid'=>$cid));
    } else if($what == 'ical')
    {
        $sync = (boolean)FormUtil::getPassedValue('sync', false, 'POST');
        pnModAPIFunc('TimeIt', 'import', 'fromICal', array('sync'=>$sync,'path'=>$_FILES['ics']['tmp_name'],'cid'=>$cid));
    } else if($what == 'postschedule')
    {
        $prefix = FormUtil::getPassedValue('prefix2', 'pn', 'POST');
        $pos = strrpos($prefix, "_");
        if(is_int($pos) && $pos)
        {
            $prefix = substr($prefix, 0, $pos);
        }
        pnModAPIFunc('TimeIt', 'import', 'postschedule', array('prefix'=>$prefix,'cid'=>$cid));
    }
    return pnRedirect(pnModURL('TimeIt', 'admin', 'import'));
}

function TimeIt_admin_calendars()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = (int)pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'calendar', 'countAll'),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt', 'calendar', 'getAll', array('startnum'=>$startnum,'numitems'=>$itemsperpage)));
    $pnRender->assign('defaultCalendar', pnModGetVar('TimeIt', 'defaultCalendar'));
    $pnRender->assign('defaultPrivateCalendar', pnModGetVar('TimeIt', 'defaultPrivateCalendar'));
    return $pnRender->fetch('TimeIt_admin_calendars.htm');
}

function TimeIt_admin_modifyconfig()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt', false);

    Loader::requireOnce('modules/TimeIt/classes/FormHandler/Config.php');
    return $render->pnFormExecute('TimeIt_admin_modifyconfig.htm', new Timeit_FormHandler_config());
}

function TimeIt_admin_calendarsAdd()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');

    Loader::requireOnce('modules/TimeIt/classes/FormHandler/Calendar.php');
    return $render->pnFormExecute('TimeIt_admin_calendarsForm.htm', new TimeIt_FormHandler_calendar());
}

function TimeIt_admin_calendarsModify()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    
    Loader::requireOnce('modules/TimeIt/classes/FormHandler/Calendar.php');
    return $render->pnFormExecute('TimeIt_admin_calendarsForm.htm', new TimeIt_FormHandler_calendar());
}

function TimeIt_admin_calendarsDelete()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    Loader::requireOnce('modules/TimeIt/classes/FormHandler/CalendarDelete.php');
    return $render->pnFormExecute('TimeIt_admin_calendarsDelete.htm', new TimeIt_FormHandler_calendarDelete());
}

function TimeIt_admin_calendarsClear()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    Loader::requireOnce('modules/TimeIt/classes/FormHandler/CalendarClear.php');
    return $render->pnFormExecute('TimeIt_admin_calendarsClear.htm', new TimeIt_FormHandler_calendarClear());
}

