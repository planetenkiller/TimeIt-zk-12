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

function smarty_function_urlToDay($params, &$smarty)
{
    if (empty($params['date'])) {
        $smarty->trigger_error("urlToDay: missing 'date' parameter");
        return;
    }
    
    if (empty($params['cid'])) {
        $smarty->trigger_error("urlToDay: missing 'date' parameter");
        return;
    }
    
    $date = getDate(strtotime($params['date']));
    $filter_obj_url = isset($params['filter_obj_url'])&&!empty($params['filter_obj_url'])? '&'.$params['filter_obj_url'] : '';

    // DataUtil::formatForDisplay converts & to &amp; in urls
    return DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => 'day', 
           'day' => $date['mday'], 'month' => $date['mon'], 'year' => $date['year'],
           'cid'=> $params['cid'])).$filter_obj_url);
}
