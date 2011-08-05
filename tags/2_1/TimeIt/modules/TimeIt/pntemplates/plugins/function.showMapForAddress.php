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

function smarty_function_showMapForAddress($params, &$smarty)
{
    if (empty($params['obj'])) {
        $smarty->trigger_error("showMapForAddress: missing 'obj' parameter");
        return;
    }

    $apiKey = pnModGetVar('TimeIt', 'googleMapsApiKey');
    if($params['obj']['data']['eventplugin_location'] == 'LocationLocations') {
        $apiKey = pnModGetVar('locations', 'GoogleMapsAPIKey');
    }
    
    $html = '';
    if($params['obj']['plugins']['location']['displayMap'] && $params['obj']['plugins']['location']['lat'] && $params['obj']['plugins']['location']['lng'])
    {
        if(pnModGetVar('TimeIt', 'mapViewType') == 'googleMaps')
        {
            PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.$apiKey);
            PageUtil::addVar('javascript', 'modules/TimeIt/pnjavascript/user_event.js');
            PageUtil::addVar('javascript', 'javascript/ajax/prototype.js');

            $zoom = $params['obj']['plugins']['location']['zoomFactor'];
            $html = '<div id="gmap" style="width:'.pnModGetVar('TimeIt', 'mapWidth').'px;height:'.pnModGetVar('TimeIt', 'mapHeight').'px;"></div>
<script type="text/javascript">
    showGoogleMapsMap('.$params['obj']['plugins']['location']['lat'].','.$params['obj']['plugins']['location']['lng'].','.($zoom==null? 13 : $zoom).')
</script>';
        } else
        {
            $coords = array();
            $coords[] = array(
                'lat'	=> $params['obj']['plugins']['location']['lat'],
                'lng'	=> $params['obj']['plugins']['location']['lng']
            );
            $zoom = $params['obj']['plugins']['location']['zoomFactor'];
            $html = pnModAPIFunc('MyMap','user','generateMap',array(
                                'coords'	=> $coords,
                                'maptype'	=> 'HYBRID', // HYBRID, SATELLITE or NORMAL
                                'width'		=> 640,
                                'height'	=> 480,
                                'zoomfactor' => $zoom==null? 13 : $zoom
                                ));

        }
    }

    if(isset($params['assign']) && !empty($params['assign']))
    {
        $smarty->assign($params['assign'], $html);
    } else 
    {
        return $html;
    }
}
