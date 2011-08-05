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
    
    $themes[] = array('folder' => 'table','displayName' => __(/*!display events in a table*/'Table', ZLanguage::getModuleDomain('TimeIt')));
    $themes[] = array('folder' => 'list', 'displayName' => __(/*!display events in a list*/'List', ZLanguage::getModuleDomain('TimeIt')));

    $currentTemplate = $params['currentTemplate'];
    $viewtype        = $params['viewtype'];
    $filter_obj_url  = $params['filter_obj_url'];
    unset($params['viewtype'], $params['filter_obj_url'], $params['currentTemplate']);

    $html = __('View as', ZLanguage::getModuleDomain('TimeIt')) . ': ';

    foreach($themes AS $theme) {
        if($currentTemplate != $theme['folder']) {
            $html .= '<a href="'.pnmodurl("TimeIt","user","main",array_merge($params, array("viewType"=>$viewtype,"template"=>$theme['folder']))).'&'.$filter_obj_url.'">'.$theme['displayName'].'</a> ';
        } else {
            $html .= $theme['displayName'].' ';
        }
    }

    return $html;
}

