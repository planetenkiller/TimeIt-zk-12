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
    $domain = ZLanguage::getModuleDomain('TimeIt');
    $names = array(__('January', $domain),
                   __('February', $domain),
                   __('March', $domain),
                   __('April', $domain),
                   __('May', $domain),
                   __('June', $domain),
                   __('July', $domain),
                   __('August', $domain),
                   __('September', $domain),
                   __('October', $domain),
                   __('November', $domain),
                   __('December', $domain),);
    
    
    $name = $names[(int)$params['month']-1];

    if(!empty($params['assign'])) {
        $smarty->assign($params['assign'], $name);
    } else  {
        return $name;
    }
}
