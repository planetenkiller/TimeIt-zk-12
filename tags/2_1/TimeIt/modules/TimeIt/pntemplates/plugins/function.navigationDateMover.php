<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Template Plugins
 */

function smarty_function_navigationDateMover($params, &$render)
{
    if (empty($params['date'])) {
        $render->trigger_error("navigationDateMover: missing 'date' parameter");
        return;
    }
    
    if (empty($params['cid'])) {
        $render->trigger_error("navigationDateMover: missing 'cid' parameter");
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
    
    $befor = $befor_date = getDate($befor);
    $after = $after_date = getDate($after);
    $filter_obj_url = isset($params['filter_obj_url'])&&!empty($params['filter_obj_url'])? '&'.$params['filter_obj_url'] : '';
    
    if($params['viewType'] == 'year')
    {
        $temp = getdate(strtotime($params['date']));
        $text = $temp['year'];

        $befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $befor['mday'], 'month' => $befor['mon'], 'year' => $befor['year'], 'cid'=>$params['cid'])).$filter_obj_url);
        $after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $after['mday'], 'month' => $after['mon'], 'year' => $after['year'], 'cid'=>$params['cid'])).$filter_obj_url);
    } else if($params['viewType'] == 'month')
    {
        $temp = getdate(strtotime($params['date']));
        $month_names = explode(' ', _MONTH_LONG);
        $text = $month_names[$temp['mon']-1];
        $text .= ' '.$temp['year'];

        $morebefor = strtotime('-2 month', strtotime($params['date']));
        $moreafter = strtotime('+2 month', strtotime($params['date']));
        $morebefor = $morebefor_date = getDate($morebefor);
        $moreafter = $moreafter_date = getDate($moreafter);

        $morebefor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $morebefor['mday'], 'month' => $morebefor['mon'], 'year' => $morebefor['year'], 'cid'=>$params['cid'])).$filter_obj_url);
        $befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $befor['mday'], 'month' => $befor['mon'], 'year' => $befor['year'], 'cid'=>$params['cid'])).$filter_obj_url);
        $after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $after['mday'], 'month' => $after['mon'], 'year' => $after['year'], 'cid'=>$params['cid'])).$filter_obj_url);
        $moreafter = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $moreafter['mday'], 'month' => $moreafter['mon'], 'year' => $moreafter['year'], 'cid'=>$params['cid'])).$filter_obj_url);
    } else if($params['viewType'] == 'week')
    {
        $temp = getdate(strtotime($params['date']));
        $temp_endWeek = getdate(strtotime('+6 days',strtotime($params['date'])));
        $text = _WEEK .' '.date('W', $temp[0]);
        $text .= ' '.($temp_endWeek['year'] > $temp['year']? $temp_endWeek['year'] : $temp['year']);

        $befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $befor['mday'], 'month' => $befor['mon'], 'week'=>date('W', $befor[0]), 'year' => $befor['year'], 'cid'=>$params['cid'])).$filter_obj_url);
        $after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $after['mday'], 'month' => $after['mon'], 'week'=>date('W', $after[0]), 'year' => $after['year'], 'cid'=>$params['cid'])).$filter_obj_url);
    } else if($params['viewType'] == 'day')
    {
        $text = TimeIt::getDatetime(strtotime($params['date']), pnModGetVar('TimeIt', 'dateformat'));

        $befor = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $befor['mday'], 'month' => $befor['mon'], 'year' => $befor['year'], 'cid'=>$params['cid'])).$filter_obj_url);
        $after = DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => $params['viewType'], 'day' => $after['mday'], 'month' => $after['mon'], 'year' => $after['year'], 'cid'=>$params['cid'])).$filter_obj_url);
    }
    
    if($params['viewType'] == 'month') {
        $html = '<div style="width:100%;margin: 0;text-align:center;font-size: large;"><strong><span style="font-size:70%;"><a href="';
        $html .= $morebefor;
        $html .= '">'.$month_names[$morebefor_date['mon']-1].'</a></span> <span style="font-size:80%;">&lt; <a href="';
        $html .= $befor;
        $html .= '">'.$month_names[$befor_date['mon']-1].'</a></span> &lt; ';
        $html .= $text;
        $html .= ' &gt; <span style="font-size:80%;"><a href="';
        $html .= $after;
        $html .= '">'.$month_names[$after_date['mon']-1].'</a> &gt;</span> <span style="font-size:70%;"><a href="';
        $html .= $moreafter;
        $html .= '">'.$month_names[$moreafter_date['mon']-1].'</a></span> </strong></div>';
    } else {
        $html = '<div style="width:100%;margin: 0;text-align:center;font-size: large;"><strong><a href="';
        $html .= $befor;
        $html .= '">&lt;&lt;</a> ';
        $html .= $text;
        $html .= ' <a href="';
        $html .= $after;
        $html .= '">&gt;&gt;</a></strong></div>';
    }
    
    return $html;
}
