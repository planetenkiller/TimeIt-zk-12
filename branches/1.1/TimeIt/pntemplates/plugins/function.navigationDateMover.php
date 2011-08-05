<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_navigationDateMover($params, &$render)
{
    if (empty($params['date'])) {
        $render->trigger_error("navigationDateMover: missing 'date' parameter");
        return;
    }
    
    if (empty($params['viewType'])) {
        $render->trigger_error("navigationDateMover: missing 'viewType' parameter");
        return;
    }
    if($params['viewType'] == 'year')
    {
        $befor = strtotime('-1 year', strtotime($params['date']));
        $after = strtotime('+1 year', strtotime($params['date']));
    } else if($params['viewType'] == 'month')
    {
        $befor = strtotime('-1 month', strtotime($params['date']));
        $after = strtotime('+1 month', strtotime($params['date']));
    } else if($params['viewType'] == 'week')
    {
    	$befor = strtotime('-1 week', strtotime($params['date']));
        $after = strtotime('+1 week', strtotime($params['date']));
    } else if($params['viewType'] == 'day')
    {
    	$befor = strtotime('-1 day', strtotime($params['date']));
        $after = strtotime('+1 day', strtotime($params['date']));
    }
    
    $befor = getDate($befor);
    $after = getDate($after);
    
    
	if($params['viewType'] == 'year')
    {
    	$temp = getdate(strtotime($params['date']));
    	$text = $temp['year'];
    	
    	$befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'year' => $befor['year'])));
    	$after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'year' => $after['year'])));
    } else if($params['viewType'] == 'month')
    {
    	$temp = getdate(strtotime($params['date']));
    	$month_names = explode(' ', _MONTH_LONG);
    	$text = $month_names[$temp['mon']-1];
    	$text .= ' '.$temp['year'];
    	
    	$befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $befor['mday'], 'week'=>date('W', $befor[0]), 'month' => $befor['mon'], 'year' => $befor['year'])));
    	$after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $after['mday'], 'week'=>date('W', $after[0]), 'month' => $after['mon'], 'year' => $after['year'])));
    } else if($params['viewType'] == 'week')
    {
    	$temp = getdate(strtotime($params['date']));
    	$text = _WEEK .' '.date('W', $temp[0]);
    	$text .= ' '.$temp['year'];
    	
    	$befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'week'=>date('W', $befor[0]), 'year' => $befor['year'])));
    	$after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'week'=>date('W', $after[0]), 'year' => $after['year'])));
    } else if($params['viewType'] == 'day')
    {
    	$text = DateUtil::getDatetime(strtotime($params['date']), _DATELONG);
    	
    	$befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $befor['mday'], 'month' => $befor['mon'], 'year' => $befor['year'])));
    	$after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $after['mday'], 'month' => $after['mon'], 'year' => $after['year'])));
    }
    
    
    
    $html = '<div style="width:100%;margin: 0;text-align:center;font-size: large;"><strong><a href="';
    $html .= $befor;
    $html .= '">&lt;&lt;</a> ';
    $html .= $text;
    $html .= ' <a href="';
    $html .= $after;
    $html .= '">&gt;&gt;</a></strong></div>';
 
    return $html;
}
