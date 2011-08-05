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

// when open a TimeIt module function smarty loaded the file before. Load it again produces a fatal error.
if(!function_exists('smarty_function_weekdayName'))
    Loader::requireOnce('modules/TimeIt/pntemplates/plugins/function.weekdayName.php');

/**
 * initialise block
 */
function TimeIt_calendarnav2block_init()
{
    pnSecAddSchema('TimeIt:blocks:navblock2', 'Block title::');
}

/**
 * get information on block
 */
function TimeIt_calendarnav2block_info()
{
    return array('text_type'            => 'calendarnav2',
                 'module'               => 'TimeIt',
                 'text_type_long'       => 'Calendar Navigation Block 2(No JS)',
                 'allow_multiple'       => true,
                 'form_content'         => false,
                 'form_refresh'         => false,
                 'show_preview'         => true,
                 'admin_tableless'      => false);
}


function TimeIt_calendarnav2block_display($blockinfo)
{
    if (!SecurityUtil::checkPermission('TimeIt:blocks:navblock2', "$blockinfo[title]::", ACCESS_READ)) {
        return false;
    }

    $vars = pnBlockVarsFromContent($blockinfo['content']);
    $dITE = $vars['displayIfThereEvents'];
    $date = getDate();

    $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthViewV2', array('month'=>$date['mon'],'year'=>$date['year']));
    // get start date
    $startDate = $events[0];
    reset($startDate);
    $startDate = each($startDate);
    $startDate = $startDate['key'];

    // get end date
    $endDate = $events[count($events)-1];
    reset($endDate);
    end($endDate);
    $endDate = each($endDate);
    $endDate = $endDate['key'];

    if($dITE)
    {
        // get usefull dates
        $navdates = pnModAPIFunc('TimeIt', 'user', 'navdates', array('month' => $date['mon'], 'year'=> $date['year']));
        // get events form db
        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents',
            array('start' => $startDate,
                  'end'   => $endDate,
                  'cid'   => (int)$vars['calendar']
                 )
        );
            
        // insert events from data to the events array
        foreach($events AS $weeknr=>$days)
        {
            foreach($days AS $k=>$v)
            {
                if(count($data[strtotime($k)]) > 0)
                {
                    $events[$weeknr][$k] = $data[strtotime($k)];
                }
            }
        }
    }
    
    
    $weekDayNames = array();
    for($i=1; $i <= 7; $i++) {
        $smarty = null; // dumy
        $weekDayNames[] = smarty_function_weekdayName(array('weekday'=>$i,'nameOnly'=>true,'size'=>3), $smarty);
    }
    $monthNames = explode(' ', _MONTH_LONG);
    
    $befor1 = getDate(strtotime('-1 month', $date[0]));
    $after1 = getDate(strtotime('+1 month', $date[0]));
    $befor2 = getDate(strtotime('-1 year', $date[0]));
    $after2 = getDate(strtotime('+1 year', $date[0]));
    
    // DataUtil::formatForDisplay converts & to &amp; in urls
    $befor1 = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => 'month', 'month' => $befor1['mon'], 'year' => $befor1['year'])));
    $after1 = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => 'month', 'month' => $after1['mon'], 'year' => $after1['year'])));
    $befor2 = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => 'month', 'month' => $befor2['mon'], 'year' => $befor2['year'])));
    $after2 = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => 'month', 'month' => $after2['mon'], 'year' => $after2['year'])));
    
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->assign('daysArray', $events);
    $pnRender->assign('day', $date['mday']);
    $pnRender->assign('dayAsDate', DateUtil::getDatetime(null, _DATEINPUT));
    $pnRender->assign('month', $date['mon']);
    $pnRender->assign('monthName', $monthNames[(int)$date['mon']-1]);
    $pnRender->assign('year', $date['year']);
    $pnRender->assign('vars', $vars);
    $pnRender->assign('weekDayNames', $weekDayNames);
    $pnRender->assign('month_startDate', DateUtil::getDatetime(mktime(0, 0, 0, $date['mon'], 1, $date['year']), _DATEINPUT)  );
    $pnRender->assign('month_endDate', DateUtil::getDatetime(mktime(0, 0, 0, $date['mon'], DateUtil::getDaysInMonth($date['mon'], $date['year']), $date['year']), _DATEINPUT) );
    
    // colors
    $pnRender->assign('monthtoday', pnModGetVar('TimeIt', 'monthtoday'));
    $pnRender->assign('monthoff', pnModGetVar('TimeIt', 'monthoff'));
    $pnRender->assign('monthon', pnModGetVar('TimeIt', 'monthon'));

    // naviation links
    
    $pnRender->assign('befor2', $befor2);
    $pnRender->assign('befor1', $befor1);
    $pnRender->assign('after1', $after1);
    $pnRender->assign('after2', $after2);
    
    $blockinfo['content'] = $pnRender->fetch("TimeIt_block_calendarnav2.htm");
    return themesideblock($blockinfo);
}

/**
 * Display a Form to edit the Configuration.
 */
function TimeIt_calendarnav2block_modify($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    
    if (empty($vars['displayIfThereEvents'])) {
        $vars['displayIfThereEvents'] = 0;
    }
    if (!isset($vars['calendar'])) {
        $vars['calendar'] = pnModGetVar('TimeIt', 'defaultCalendar');
    }
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    $pnRender->assign($vars);
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt','calendar','getAllForDropdown'));
    return $pnRender->fetch('TimeIt_block_calendarnav2_modify.htm');
}

/**
 * Update the Configuration of the Block.
 */
function TimeIt_calendarnav2block_update($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // update configuration
    $vars['calendar'] = FormUtil::getPassedValue('calendar', pnModGetVar('TimeIt', 'defaultCalendar'), 'POST');
    $vars['displayIfThereEvents'] = FormUtil::getPassedValue('displayIfThereEvents', 0, 'POST');
    $blockinfo['content'] = pnBlockVarsToContent($vars);
    
    // clear cache
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->clear_cache('TimeIt_block_calendarnav2.htm');
    
    return $blockinfo;
}