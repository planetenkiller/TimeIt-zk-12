<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_showMapForAddress($params, &$smarty)
{
    if (empty($params['obj'])) {
        $smarty->trigger_error("showMapForAddress: missing 'obj' parameter");
        return;
    }
    
    $html = '';
    if(pnModGetVar('TimeIt', 'enableMapView') && $params['obj']['data']['displayMap'])
    {
    	if(pnModGetVar('TimeIt', 'mapViewType') == 'googleMaps')
    	{
    		PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.pnModGetVar('TimeIt', 'googleMapsApiKey'));
    		PageUtil::addVar('javascript', 'modules/TimeIt/pnjavascript/user_event.js');
    		PageUtil::addVar('javascript', 'javascript/ajax/prototype.js');
    	
    		$html = '<div id="gmap" style="width:300px;height:300px;"></div>
<script type="text/javascript">
    showGoogleMapsMap('.$params['obj']['data']['lat'].','.$params['obj']['data']['lng'].')
</script>';
    	} else 
    	{
    		$coords = array();
    		$coords[] = array(
    			'lat'	=> $params['obj']['data']['lat'],
    			'lng'	=> $params['obj']['data']['lng']
			);
			$html = pnModAPIFunc('MyMap','user','generateMap',array(
								'coords'	=> $coords,
								'maptype'	=> 'HYBRID', // HYBRID, SATELLITE or NORMAL
								'width'		=> 640,
								'height'	=> 480,
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
