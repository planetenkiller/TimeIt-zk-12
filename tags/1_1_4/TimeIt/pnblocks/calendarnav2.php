<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

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
    return array('text_type'		=> 'calendarnav2',
                 'module' 			=> 'TimeIt',
                 'text_type_long' 	=> 'Calendar Navigation Block 2(No JS)',
                 'allow_multiple' 	=> false,
                 'form_content' 	=> false,
                 'form_refresh' 	=> false,
                 'show_preview' 	=> true,
                 'admin_tableless'  	=> false);
}


function TimeIt_calendarnav2block_display($blockinfo)
{
    if (!SecurityUtil::checkPermission('TimeIt:blocks:navblock2', "$blockinfo[title]::", ACCESS_READ)) {
        return false;
    }

    $vars = pnBlockVarsFromContent($blockinfo['content']);
    $dITE = $vars['displayIfThereEvents'];
    $date = getDate();
    
    $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month'=>$date['mon'],'year'=>$date['year']));
    
    if($dITE)
    {
		// get usefull dates
        $navdates = pnModAPIFunc('TimeIt', 'user', 'navdates', array('month' => $date['mon'], 'year'=> $date['year']));
        
    	// get events form db
 		$data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => $navdates['dateFirstDayInWeek_FirstWeekOfMonth'],                   
                  'end' => $navdates['dateLastDayInWeek_LastWeekOfMonth'])
   		);
            
    	// insert events from data to the events array
    	foreach($events AS $weeknr=>$days)
   		{
     		foreach($days AS $k=>$v)
        	{
           		if(count($data[strtotime($k)]) > 0)
           		{
           			$events[$weeknr][$k] = true;
           		}
        	}
   		}
    }
    
    
    $weekDayNames = explode(' ', _DAY_OF_WEEK_SHORT);
    foreach($weekDayNames AS $key => $val)
    {
        $weekDayNames[$key] = substr($val, 0, 2);
    }
    $weekDayNames[] = $weekDayNames[0];
    unset($weekDayNames[0]);
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
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    $pnRender->assign($vars);
    return $pnRender->fetch('TimeIt_block_calendarnav2_modify.htm');
}

/**
 * Update the Configuration of the Block.
 */
function TimeIt_calendarnav2block_update($blockinfo)
{
	$vars = pnBlockVarsFromContent($blockinfo['content']);

    // update configuration
    $vars['displayIfThereEvents'] = FormUtil::getPassedValue('displayIfThereEvents', 0, 'POST');
    $blockinfo['content'] = pnBlockVarsToContent($vars);
    
    // clear cache
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->clear_cache('TimeIt_block_calendarnav2.htm');
    
    return $blockinfo;
}