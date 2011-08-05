<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_showMapForLocations($params, &$smarty)
{
	if (empty($params['obj'])) {
        $smarty->trigger_error("showMapForLocations: missing 'obj' parameter");
        return;
    }
    
    $latlng = explode(',', $params['obj']['latlng']);
    $lat = $latlng[0];
    $lng = $latlng[1];
    
    $html = '';
    if(pnModAvailable('locations'))
    {
    	if(pnModGetVar('TimeIt', 'mapViewType') == 'googleMaps')
    	{
    		
    		PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.pnModGetVar('locations', 'GoogleMapsAPIKey'));
    		PageUtil::addVar('javascript', 'modules/TimeIt/pnjavascript/user_event.js');
    		PageUtil::addVar('javascript', 'javascript/ajax/prototype.js');
    	
    		$html = '<div id="gmap" style="width:'.pnModGetVar('TimeIt', 'mapWidth').'px;height:'.pnModGetVar('TimeIt', 'mapHeight').'px;"></div>
<script type="text/javascript">
    showGoogleMapsMap('.$lat.','.$lng.')
</script>';
    	} else 
    	{
    		$coords = array();
    		$coords[] = array(
    			'lat'	=> $lat,
    			'lng'	=> $lng
			);
			$html = pnModAPIFunc('MyMap','user','generateMap',array(
								'coords'	=> $coords,
								'maptype'	=> 'HYBRID', // HYBRID, SATELLITE or NORMAL
								'width'		=> pnModGetVar('TimeIt', 'mapWidth'),
								'height'	=> pnModGetVar('TimeIt', 'mapHeight'),
								'zoomfactor' => 13
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
