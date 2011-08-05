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


function smarty_function_pnusergetlangnew($params,&$smarty)
{
    $assign = isset($params['assign'])  ? $params['assign']  : null;

    $result = ZLanguage::getLanguageCode();

    if ($assign) {
        $smarty->assign($assign, $result);
        return;
    }
    return $result;
}
