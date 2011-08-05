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
    if (!TimeItPermissionUtil::adminAccessCheck()) {
        return LogUtil::registerPermissionError();
    }
    
    $render =& pnRender::getInstance('TimeIt', false);
    $render->assign('count', (int)TimeItDomainFactory::getInstance('event')->getNumberOfPendingEvents());
    return $render->fetch('admin_main.htm');
}

function TimeIt_admin_viewpending()
{
    // Security check
    if (!TimeItPermissionUtil::adminAccessCheck()) {
        return LogUtil::registerPermissionError();
    }

    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    $pnRender =& pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => TimeItDomainFactory::getInstance('event')->getNumberOfPendingEvents(),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', TimeItDomainFactory::getInstance('event')->getPendingEvents($startnum, $itemsperpage));
    
    return $pnRender->fetch('admin_viewpending.htm');
}

function TimeIt_admin_viewall()
{
    // Security check
    if (!TimeItPermissionUtil::adminAccessCheck()) {
        return LogUtil::registerPermissionError();
    }

    $cid = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;

    $pnRender =& pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => TimeItDomainFactory::getInstance('event')->getNumberOfEvents($cid),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', TimeItDomainFactory::getInstance('event')->getEvents($cid, null, $startnum, $itemsperpage));
    
    $calendars = TimeItDomainFactory::getInstance('calendar')->getObjectList();
    $calendarsNew = array();
    foreach($calendars AS $calendar) {
        $calendarsNew[] = array('value' => $calendar['id'], 'text' => $calendar['name']);
    }

    $pnRender->assign('calendars', $calendarsNew);
    $pnRender->assign('calendar', $cid);
    return $pnRender->fetch('admin_viewall.htm');
}

function TimeIt_admin_delete()
{
    $objs = FormUtil::getPassedValue('delete', array(), 'POST');
    $manager = TimeItDomainFactory::getInstance('event');
    
    foreach($objs AS $objid) {
        $obj = $manager->getObject($objid);
        if(TimeItPermissionUtil::canDeleteEvent($obj)) {
            $manager->deleteObject($obj);
        }
    }

    // display message
    LogUtil::registerStatus(__('Done! Items deleted.', $domain));

    if(FormUtil::getPassedValue('returnto', null, 'GET') == 'viewall') {
        return pnRedirect(pnModURL('TimeIt', 'admin', 'viewall'));
    } else {
        return pnRedirect(pnModURL('TimeIt', 'admin', 'viewpending'));
    }
}

function TimeIt_admin_viewhidden()
{
    // Security check
    if (!TimeItPermissionUtil::adminAccessCheck()) {
        return LogUtil::registerPermissionError();
    }
    
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    $pnRender =& pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => TimeItDomainFactory::getInstance('event')->getNumberOfOfflineEvents(),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', TimeItDomainFactory::getInstance('event')->getOfflineEvents($startnum, $itemsperpage));
    return $pnRender->fetch('admin_viewhidden.htm');
}

function TimeIt_admin_import()
{
    // Security check
    if(!TimeItPermissionUtil::isAdmin()) {
         return LogUtil::registerPermissionError();
    }
    
    $pnRender =& pnRender::getInstance('TimeIt', false);
    
    $pnRender->assign('calendars', TimeItDomainFactory::getInstance('calendar')->getObjectList());
    
    return $pnRender->fetch('admin_import.htm');
}

function TimeIt_admin_doImport()
{
    if(!TimeItPermissionUtil::isAdmin()) {
         return LogUtil::registerPermissionError();
    }

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

function TimeIt_admin_modifyconfig()
{
    // Security check
    if(!TimeItPermissionUtil::isAdmin()) {
         return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt', false);

    Loader::requireOnce('modules/TimeIt/classes/FormHandler/TimeIt_FormHandler_Edit_config.class.php');
    return $render->pnFormExecute('admin_modifyconfig.htm', new TimeIt_FormHandler_Edit_config());
}

function TimeIt_admin_calendarsClear()
{
    // Security check
    if(!TimeItPermissionUtil::isAdmin()) {
         return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    Loader::loadClass('TimeIt_FormHandler_CalendarClear', 'modules/TimeIt/classes/FormHandler');
    return $render->pnFormExecute('admin_calendarsClear.htm', new TimeIt_FormHandler_CalendarClear());
}

function TimeIt_admin_calendars()
{
    if(!TimeItPermissionUtil::isAdmin()) {
         return LogUtil::registerPermissionError();
    }

    return pnModFunc('TimeIt', 'user', 'view', array('ot' => 'calendar'));
}

function TimeIt_admin_calendarEdit()
{
    if(!TimeItPermissionUtil::isAdmin()) {
         return LogUtil::registerPermissionError();
    }

    return pnModFunc('TimeIt', 'user', 'edit', array('ot' => 'calendar'));
}

