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

function smarty_function_tiarrayfirstkey($params, &$smarty)
{
    if (empty($params['array'])) {
        $smarty->trigger_error("tiarrayfirstkey: missing 'array' parameter");
        return;
    }

    if (empty($params['assign'])) {
        $smarty->trigger_error("tiarrayfirstkey: missing 'assign' parameter");
        return;
    }
    
    $array = $params['array'];
    
    reset($array);
    $entry = each($array);
    $smarty->assign($params['assign'], $entry['key']);
}
