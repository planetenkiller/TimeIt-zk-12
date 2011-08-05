<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_tiexplode($params, &$smarty)
{
    if (empty($params['zeichen'])) {
        $smarty->trigger_error("tiexplode: missing 'zeichen' parameter");
        return;
    }
    
	if (empty($params['string'])) {
        $smarty->trigger_error("tiexplode: missing 'string' parameter");
        return;
    }
    
    $array = explode($params['zeichen'], $params['string']);
    
    if(isset($params['castToInt']) && $params['castToInt'])
    {
    	foreach($array AS $key=>$val)
    	{
    		$array[$key] = (int)$val;
    	}
    }
    
    if(!empty($params['assign']))
    {
    	$smarty->assign($params['assign'], $array);
    } else {
    	return $array;
    }
}
