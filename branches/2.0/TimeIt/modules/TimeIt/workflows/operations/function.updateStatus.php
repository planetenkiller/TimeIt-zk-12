<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Workflows
 */

function TimeIt_operation_updateStatus(&$obj, $params)
{
    $online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = (int)$online;
    
    // only update status of item without changing the data
    $updateObj = array('id' => $obj['id'],
                       'status' => $online);
    
    return DBUtil::updateObject($updateObj, 'TimeIt_events');
}