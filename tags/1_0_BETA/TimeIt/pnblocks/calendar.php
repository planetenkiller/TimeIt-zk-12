<?php
/**
 * TimeIt
 *
 * @copyright (c) 2007, planetenkiller
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

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
    return array('text_type' 		=> 'calendar',
                 'module' 		=> 'TimeIt',
                 'text_type_long' 	=> 'Calendar Block',
                 'allow_multiple' 	=> false,
                 'form_content' 	=> false,
                 'form_refresh' 	=> false,
                 'show_preview' 	=> true,
                 'admin_tableless'  	=> false);
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

    // Type of the Block
    if($vars['viewType'] == 'today')
    {   // Show today Events
        $date = getdate(time());
        $events   = pnModAPIFunc('TimeIt','user','dayEvents', array('day'=>$date['mday'],'month'=>$date['mon'],'year'=>$date['year']));

        
        if(empty($events)) {
            $pnRender->assign('do', 'nothing');
        }
        $pnRender->assign('events', $events);
        $pnRender->assign('day', $date['mday']);
        $pnRender->assign('month', $date['mon']);
        $pnRender->assign('year', $date['year']);
    } else if($vars['viewType'] == 'upcoming')
    {   // Show Upcoming Events
        $date = getdate(time());
        $startStamp = DateUtil::getDatetime(mktime(0,0,0,$date['mon'], $date['mday'], $date['year']), _DATEINPUT);
        $endStamp = DateUtil::getDatetime(mktime(0,0,0,$date['mon']+$vars['maxMonths'], DateUtil::getDaysInMonth($date['mon'], $date['year']), $date['year']), _DATEINPUT);
        $events = pnModAPIFunc('TimeIt','user','getDailySortedEvents', array('start'=>$startStamp, 'end'=>$endStamp));
        
        $upcomingEvents = array();
        $count = 0;
        // get $vars['maxEvents'] events from $events
        foreach($events AS $stamp => $objs)
        {
        	$tmp_count = count($objs);
        	if(($tmp_count+$count) <= $vars['maxEvents'])
        	{
        		$upcomingEvents[$stamp] = $objs;
        		$count += $tmp_count;
        	} else if($count < $vars['maxEvents'] && ($tmp_count+$count) > $vars['maxEvents'] )
        	{
        		$max = $vars['maxEvents'] - $count;
        		$upcomingEvents[$stamp] = array_slice($objs, 0 , $max);
        		$count += $max;
        	}
        	
        	if($count >= $vars['maxEvents'])
        	{
        		break; // maximum arrived
        	}
        }
        $pnRender->assign('events', $upcomingEvents);
        if(empty($upcomingEvents)) {
            $pnRender->assign('do', 'nothing');
        }
    }
    //echo $vars['viewType'];
    $pnRender->assign('viewType',$vars['viewType']);
    $blockinfo['content'] = $pnRender->fetch("TimeIt_block_calendar.htm");
    return themesideblock($blockinfo);
}


/**
 * Display a Form to edit the Configuration.
 */
function TimeIt_calendarblock_modify($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    
    if (empty($vars['viewType'])) {
        $vars['viewType'] = 'today';
    }
    if (empty($vars['maxMonths'])) {
   		$vars['maxMonths'] = 3;
    }
    if (empty($vars['maxEvents'])) {
    	$vars['maxEvents'] = 10;
    }
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    $pnRender->assign($vars);
    return $pnRender->fetch('TimeIt_block_calendar_modify.htm');
}

/**
 * Update the Configuration of the Block.
 */
function TimeIt_calendarblock_update($blockinfo)
{
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    
        
    $vars['viewType'] = FormUtil::getPassedValue('viewType', 'today', 'POST');
    $vars['maxMonths'] = FormUtil::getPassedValue('maxMonths', 3, 'POST');
    $vars['maxEvents'] = FormUtil::getPassedValue('maxEvents', 10, 'POST');
    $blockinfo['content'] = pnBlockVarsToContent($vars);
    
    
    $pnRender = pnRender::getInstance('TimeIt');
    $pnRender->clear_cache('TimeIt_block_calendar.htm');
    
    return $blockinfo;
}
