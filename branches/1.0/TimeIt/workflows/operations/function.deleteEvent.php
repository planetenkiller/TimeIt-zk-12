<?php

function TimeIt_operation_deleteEvent(&$obj, $params)
{
	$para = array();
	$para['obj'] = &$obj;
	return pnModAPIFunc('TimeIt', 'user', 'delete', $para);
}