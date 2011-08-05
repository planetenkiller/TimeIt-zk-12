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

function smarty_function_templateWithTheme($params, &$smarty)
{
    if (empty($params['file'])) {
        $smarty->trigger_error("templateWithTheme: missing 'file' parameter");
        return;
    }
    
    if (empty($params['theme'])) {
        $smarty->trigger_error("templateWithTheme: missing 'theme' parameter");
        return;
    }


    $template = TimeIt_templateWithTheme($smarty, $params['file']);
    if($params['assign'])
    {
        $smarty->assign($params['assign'], $template);
    } else {
        return $template;
    }
}
