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

function smarty_function_monthName($params, &$smarty)
{
    if (empty($params['month'])) {
        $smarty->trigger_error("monthName: missing 'month' parameter");
        return;
    }
    // make array
    $names = explode(' ', _MONTH_LONG);
    
    
    $name = $names[(int)$params['month']-1];

    if(!empty($params['assign']))
    {
        $smarty->assign($params['assign'], $name);
    } else 
    {
        return $name;
    }
}
