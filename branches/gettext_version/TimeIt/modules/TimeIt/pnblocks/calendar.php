<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Blocks
 */

Loader::requireOnce('modules/TimeIt/common.php');

// ensure that the TimeIt pntables.php is loaded
pnModDBInfoLoad('TimeIt');

/**
 * initialise block
 */
function TimeIt_calendarblock_init()
{
    pnSecAddSchema('TimeIt:blocks:calendarblock', 'Block title::');
}

/**
 * get information on block
 */
function TimeIt_calendarblock_info()
{
    // Values
    return array('text_type'            => 'calendar',
                 'module'               => 'TimeIt',
                 'text_type_long' 	=> 'Calendar Block',
                 'allow_multiple' 	=> true,
                 'form_content' 	=> false,
                 'form_refresh' 	=> false,
                 'show_preview' 	=> true,
                 'admin_tableless'      => false);
}


function TimeIt_calendarblock_display($blockinfo)
{
    if (!SecurityUtil::checkPermission('TimeIt:blocks:calendarblock', "$blockinfo[title]::", ACCESS_READ)) {
        return false;
    }

    $vars = pnBlockVarsFromContent($blockinfo['content']);
    $vars['maxEvents'] = (int)$vars['maxEvents'];
    $vars['maxMonths'] = (int)$vars['maxMonths'];
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->assign('tiConfig', pnModGetVar('TimeIt'));
    $processEvents = true;

    if($vars['forUser'] && !pnUserLoggedIn()) {
        // user isn't logged in -> private events aren't possible

        if($vars['forUserHide']) {
            return false;
        } else {
            $processEvents = false;
        }
    }

    $eventsNew = array();
    $eventFound = false;
    $date = getdate(time());
    if($processEvents) {
        $catFilter = explode('@#@', $vars['catFilter']);
        $filterObj = new TimeItFilter('event');
        $filterObj->addGroup();
        if($vars['forUser']) {
            $filterObj->addExp('cr_uid:eq:-1');
            $filterObj->addExp('sharing:le:2');
        }
        if($vars['catFilter']) {
            $filterObj->addExp('category:in:'.(int)$catFilter[1]);
        }
        if($vars['tifilter']) {
            TimeIt_Filter::getFilterFormString($vars['tifilter'], $filterObj);
        }


        $startStamp = DateUtil::getDatetime(mktime(0,0,0,$date['mon'], $date['mday'], $date['year']), DATEONLYFORMAT_FIXED);
        if($vars['viewType'] == 'upcoming') {
            $strtotimeStr = "+ ". $vars['maxMonths'] . " " . (isset($vars['maxUnit'])&&$vars['maxUnit']=='days'? "days" : "months");
            $endStamp = DateUtil::getDatetime(strtotime($strtotimeStr, $date[0]), DATEONLYFORMAT_FIXED);
        } else {
            $endStamp = $startStamp;
        }
        $events = TimeItDomainFactory::getInstance('event')->getDailySortedEvents($startStamp, $endStamp, (int)$vars['calendar'], $filterObj);

        if($vars['viewType'] == 'today') {
            $vars['maxEvents'] = 1000000; // big limit because we want to show all todays events
        }

        $count = 0;
        // loop over days
        foreach($events AS $day => $cats) {
            $eventsNew[$day] = $cats;
            // loop over cats
            foreach($cats AS $key1 => $objs) {
                $tmp_count = count($objs['data']);
                if(($tmp_count+$count) <= $vars['maxEvents']) {
                    $count += $tmp_count;
                } else if($count < $vars['maxEvents'] && ($tmp_count+$count) > $vars['maxEvents'] ) {
                    $max = $vars['maxEvents'] - $count;
                    $tmp_count -= $max;
                    $count += $max;
                }


                // loop over events
                foreach($objs['data'] AS $key => $obj) {
                    // limit arrived
                    if($tmp_count <= 0) {
                        unset($eventsNew[$day][$key1]['data'][$key]);
                    }

                    $eventFound = true;
                    if((int)$obj['allDay'] == 0) {
                        $now = (int)date('Hi');
                        $explDur = explode(',', $obj['allDayDur']);
                        $explDur = $explDur[0] . (((int)$explDur[1]<10)?'0'.$explDur[1]:$explDur[1]);
                        $time = (int)str_replace(':','',$obj['allDayStartLocal']);
                        $time += (int)$explDur;


                        if($time < $now) {
                            unset($events[$key1]['data'][$key]);
                            if(count($events[$key1]['data']) == 0) {
                                unset($events[$key1]);
                            }
                        }
                    }

                    $tmp_count--;
                }

                // limit arrived
                if($count >= $vars['maxEvents']) {
                    break 2; // maximum arrived
                }
            }
        }
    }
    
    $pnRender->assign('events', $eventsNew);
    $pnRender->assign('eventFound', $eventFound);
    $pnRender->assign('day', $date['mday']);
    $pnRender->assign('month', $date['mon']);
    $pnRender->assign('year', $date['year']);
    $pnRender->assign('tiConfig', pnModGetVar('TimeIt'));
    $pnRender->assign('asDate', DateUtil::getDatetime($date[0], DATEONLYFORMAT_FIXED));
    //echo $vars['viewType'];
    $pnRender->assign('viewType',$vars['viewType']);
    $blockinfo['content'] = $pnRender->fetch("block_calendar.htm");
    return themesideblock($blockinfo);
}


/**
 * Display a Form to edit the Configuration.
 */
function TimeIt_calendarblock_modify($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    
    if (!isset($vars['calendar'])) {
        $vars['calendar'] = pnModGetVar('TimeIt', 'defaultCalendar');
    }
    if (empty($vars['viewType'])) {
        $vars['viewType'] = 'today';
    }
    if (empty($vars['maxMonths'])) {
        $vars['maxMonths'] = 3;
    }
    if (empty($vars['maxEvents'])) {
        $vars['maxEvents'] = 10;
    }
    if (!isset($vars['forUser'])) {
        $vars['forUser'] = 0;
    }
    if (!isset($vars['forUserHide'])) {
        $vars['forUserHide'] = 1;
    }
    if (!isset($vars['catFilter'])) {
        $vars['catFilter'] = null;
    }
    if (!isset($vars['tifilter'])) {
        $vars['tifilter'] = null;
    }
    if (!isset($vars['maxUnit'])) {
        $vars['maxUnit'] = "";
    }

    Loader::loadClass('CategoryRegistryUtil');
    Loader::loadClass('CategoryUtil');
    $pnRender = pnRender::getInstance('TimeIt', false);
    $pnRender->assign($vars);
    $pnRender->assign('calendars', TimeItDomainFactory::getInstance('calendar')->getObjectListForDropdown());
    
    $props = CategoryRegistryUtil::getRegisteredModuleCategories('TimeIt','TimeIt_events');
    $cats = array();
    foreach ($props AS $property => $cid)
    {
        $cat = CategoryUtil::getSubCategories($cid);
        foreach($cat AS $c)
        {
            $cats[] = array('value'=>$property.'@#@'.$c['id'],
                            'text'=>$property.'->'.((isset($c['display_name'][pnUserGetLang()]) && !empty($c['display_name'][pnUserGetLang()]))?$c['display_name'][pnUserGetLang()]:$c['name']));
        }
    }
    $pnRender->assign('regedcats', $cats);
    
    return $pnRender->fetch('block_calendar_modify.htm');
}

/**
 * Update the Configuration of the Block.
 */
function TimeIt_calendarblock_update($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    
    $vars['calendar'] = FormUtil::getPassedValue('calendar', pnModGetVar('TimeIt', 'defaultCalendar'), 'POST');
    $vars['viewType'] = FormUtil::getPassedValue('viewType', 'today', 'POST');
    $vars['maxMonths'] = FormUtil::getPassedValue('maxMonths', 3, 'POST');
    $vars['maxEvents'] = FormUtil::getPassedValue('maxEvents', 10, 'POST');
    $vars['forUser'] = FormUtil::getPassedValue('forUser', false, 'POST');
    $vars['forUserHide'] = FormUtil::getPassedValue('forUserHide', false, 'POST');
    $vars['catFilter'] =  FormUtil::getPassedValue('catFilter', null, 'POST');
    $vars['tifilter'] =  FormUtil::getPassedValue('tifilter', null, 'POST');
    $vars['maxUnit'] =  FormUtil::getPassedValue('maxUnit', "", 'POST');
    $blockinfo['content'] = pnBlockVarsToContent($vars);
    
    
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->clear_cache('block_calendar.htm');
    
    return $blockinfo;
}
