<?php


function smarty_function_weekdayName($params, &$smarty)
{
    if (empty($params['weekday'])) {
        $smarty->trigger_error("weekdayName: missing 'weekday' parameter");
        return;
    }
    // make array
    $names = explode(' ', _DAY_OF_WEEK_LONG);
    // move sunday(index 0) to the end of the array
    $names[] = $names[0];
	$names = array_slice($names,1,7);
    
    if($params['size'])
    {
    	$name = $names[(int)$params['weekday']-1];
    	$name = substr($name,0, (int)$params['size']);
    } else 
    {
    	$name = $names[(int)$params['weekday']-1];
    }
   	
    if(!empty($params['assign']))
    {
    	$smarty->assign($params['assign'], $name);
    } else 
    {
    	return $name;
    }
}
