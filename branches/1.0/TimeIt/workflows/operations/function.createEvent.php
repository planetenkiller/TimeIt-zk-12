<?php

function TimeIt_operation_createEvent(&$obj, $params)
{
	$online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = $online;
    
    $para = array();
    $para['obj'] = &$obj;
    return pnModAPIFunc('TimeIt', 'user', 'create', $para);
}