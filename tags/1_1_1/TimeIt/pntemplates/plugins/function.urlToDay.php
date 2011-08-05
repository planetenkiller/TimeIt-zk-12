<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

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
