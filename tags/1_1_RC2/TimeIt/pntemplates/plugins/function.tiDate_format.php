<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_tiDate_format($params, &$smarty)
{
    if (empty($params['date'])) {
        $smarty->trigger_error("tiDate_format: missing 'date' parameter");
        return;
    }

	if (empty($params['format'])) {
        $params['format'] = 'datebrief';
    }
    
    $value = DateUtil::formatDatetime(strtotime($params['date']), $params['format']);
    
    if($params['assign'])
    {
    	$smarty->assign($params['assign'], $value);
    } else {
    	return $value;
    }
}
