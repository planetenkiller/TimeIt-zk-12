<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function TimeIt_operation_createEvent(&$obj, $params)
{
	$online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = $online;
    
    $para = array();
    $para['obj'] = &$obj;
    return pnModAPIFunc('TimeIt', 'user', 'create', $para);
}