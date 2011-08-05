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

/** Make sure to use require_once() instead of Loader::requireOnce() since "function.pnformdropdownlist.php"
 is loaded by Smarty (the base render class) with the use of require_once(). We do not want to
 get in conflict with that.*/
if(file_exists('system/pnForm/plugins/function.pnformcategoryselector.php'))
    require_once 'system/pnForm/plugins/function.pnformcategoryselector.php';

/**
 * Category selector
 *
 * This plugin creates a category selector using a dropdown list.
 * The selected value of the base dropdown list will be set to ID of the selected category.
 *
 * @package pnForm
 * @subpackage Plugins
 */
class tiFormCategorySelector extends pnFormCategorySelector
{
    function getFilename() {
        return __FILE__; // FIXME: may be found in smarty's data???
    }
    
    function addItem($text, $value) {
        if(SecurityUtil::checkPermission('TimeIt:Category:', $value."::", ACCESS_READ) || empty($value)) {
            parent::addItem($text, $value);
        }
    }
}
   
    
function smarty_function_tiformcategoryselector($params, &$render)
{
    return $render->pnFormRegisterPlugin('tiFormCategorySelector', $params);
}
