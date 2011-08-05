<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage API
 */

Loader::requireOnce('modules/TimeIt/common.php'); 

/**
 * This function returns the name of the tab
 * If there should not be a tab but an integration into MyProfile
 * is neccessary (for the onLoad function e.g.) return "" as result
 *
 * @return string
 */
function TimeIt_myprofileapi_getTitle($args)
{
    pnModLangLoad('TimeIt');
    return __('Registrations to events', ZLanguage::getModuleDomain('TimeIt'));
}

/**
 * This function returns additional options that should be added to the plugin url
 * These options will be &key1=value1&key2=value2 etc.
 *
 * @return array or false otherwise
 */
function TimeIt_myprofileapi_getURLAddOn($args)
{
    return false;
}

/**
 * This function returns true if module should not be loaded via AJAX
 *
 * @return bool
 */
function TimeIt_myprofileapi_noAjax($args)
{
    return false;
}

/**
 * This function shows the content of the main MyProfile tab
 *
 * @param	$args['uid']		the user's id
 * @return 	void, output printed
 */
function TimeIt_myprofileapi_tab($args)
{
    // create output object
    $render = pnRender::getInstance('TimeIt');

    $regs = TimeItDomainFactory::getInstance('reg')->getEventsOfUser($args['uid']);
    
    $render->assign('events', $events);
    $render->assign('tiConfig', pnModGetVar('TimeIt'));
    // print output
    // i use utf8_encode because without utf8_encode öäü are displayed incorrect.
    return utf8_encode($render->fetch('TimeIt_myprofile_tab.htm'));
}

/**
 * This function will be called
 *
 */
function TimeIt_myprofileapi_onLoad($args)
{
    // code that is inserted here will be called with any profile page
    // this helps you if you have some additional PageVars in your module
    // or if you want some javascript links in the header for your output
    return;
}