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

function smarty_function_eventplugincall($params, &$smarty)
{
    if (empty($params['obj'])) {
        $smarty->trigger_error("eventplugincall: missing 'obj' parameter");
        return;
    }
    
    if (empty($params['func'])) {
        $smarty->trigger_error("eventplugincall: missing 'func' parameter");
        return;
    }


    if($params['args'])
    {
        $ret = $params['obj']->$params['func']($params['args']);
    } else
    {
        $ret = $params['obj']->$params['func']();
    }

    if(!empty($params['assign']))
    {
        $smarty->assign($params['assign'], $ret);
    } else 
    {
        return $ret;
    }
}
