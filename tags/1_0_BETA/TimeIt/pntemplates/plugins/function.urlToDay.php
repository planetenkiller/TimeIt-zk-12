<?php


function smarty_function_urlToDay($params, &$smarty)
{
    if (empty($params['date'])) {
        $smarty->trigger_error("urlToDay: missing 'date' parameter");
        return;
    }
    $date = getDate(strtotime($params['date']));
    
    // DataUtil::formatForDisplay converts & to &amp; in urls
    return DataUtil::formatForDisplay(pnModUrl('TimeIt', 'user', 'view', array('viewType' => 'day', 'day' => $date['mday'], 'month' => $date['mon'], 'year' => $date['year'])));
}
