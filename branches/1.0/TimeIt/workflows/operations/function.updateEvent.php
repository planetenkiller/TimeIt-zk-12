<?php

function TimeIt_operation_updateEvent(&$obj, $params)
{
	$online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = $online;
    
    $para = array();
    $para['obj'] = &$obj;
    return pnModAPIFunc('TimeIt', 'user', 'update', $para);
}