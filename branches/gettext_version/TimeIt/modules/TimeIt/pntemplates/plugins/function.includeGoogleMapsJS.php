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

function smarty_function_includeGoogleMapsJS($params, &$smarty)
{
    $apiKey = pnModGetVar('TimeIt', 'googleMapsApiKey');
    if($params['obj']['data']['eventplugin_location'] == 'LocationLocations') {
        $apiKey = pnModGetVar('locations', 'GoogleMapsAPIKey');
    }
    
    PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.$apiKey);

    return '';
}
