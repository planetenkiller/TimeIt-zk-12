<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_templateWithTheme($params, &$smarty)
{
    if (empty($params['file'])) {
        $smarty->trigger_error("templateWithTheme: missing 'file' parameter");
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
