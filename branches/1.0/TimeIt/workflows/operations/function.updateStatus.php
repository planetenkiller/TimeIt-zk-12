<?php

function TimeIt_operation_updateStatus(&$obj, $params)
{
	$online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = (int)$online;
    
    // only update status of item without changing the data
    $updateObj = array('id' => $obj['id'],
                       'status' => $online);
    
    return pnModAPIFunc('TimeIt', 'user', 'update', array('obj' => $updateObj));
}