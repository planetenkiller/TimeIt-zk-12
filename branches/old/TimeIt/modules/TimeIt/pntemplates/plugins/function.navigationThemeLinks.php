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

function smarty_function_navigationThemeLinks($params, &$smarty)
{
    if(file_exists('modules/TimeIt/config/config.php')) {
    include 'modules/TimeIt/config/config.php';
    } else {
        $themes = array();
    }
    
    $themes[] = array('folder' => 'default','displayName' => _TIMEIT_TEMPLATE_TABLE);
    $themes[] = array('folder' => 'list',   'displayName' => _TIMEIT_TEMPLATE_LIST);

    $viewtype       = $params['viewtype'];
    $filter_obj_url = $params['filter_obj_url'];
    unset($params['viewtype'], $params['filter_obj_url']);

    $html = _TIMEIT_TEMPLATE . ': ';

    foreach($themes AS $theme) {
        $html .= '<a href="'.pnmodurl("TimeIt","user","main",array_merge($params, array("viewType"=>$viewtype,"template"=>$theme['folder']))).'&'.$filter_obj_url.'">'.$theme['displayName'].'</a> ';
    }

    return $html;
}

