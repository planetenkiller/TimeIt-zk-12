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

function smarty_function_sortObjectsByCat($params, &$smarty)
{
    if (empty($params['ar'])) {
        $smarty->trigger_error("test2: missing 'ar' parameter");
        return;
    }
    
    if (!is_array($params['ar'])) {
        $smarty->trigger_error("test2: Parameter 'ar' is not an array");
        return;
    }
    
    if (empty($params['assign'])) {
        $smarty->trigger_error("test2: missing 'assign' parameter");
        return;
    }
    
    $array = array();
    
    // iterate over array of events
    foreach ($params['ar'] as $obj) 
    {
        // get category id
        $catID = $obj['__CATEGORIES__']['pc_imports']['id'];

        // isn't the category id set on $array?
        if(!isset($array[$catID]))
        {
            $array[$catID] = array();
            $array[$catID]['info'] = array('name'=>$obj['__CATEGORIES__']['pc_imports']['name'],'color'=>$obj['__CATEGORIES__']['pc_imports']['__ATTRIBUTES__']['color']);
            $array[$catID]['data'] = array();

        }

        // add event to category
        $array[$catID]['data'][] = $obj;
    }
    
    // assign variable to smarty
    $smarty->assign($params['assign'], $array);
}
