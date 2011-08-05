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

function TimeIt_operation_createEvent(&$obj, $params)
{
    $online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = $online;
    
    $para = array();
    $para['obj'] = & $obj;
    if($params['repeat'] != '1') {
        $para['noRecurrenceCalculation'] = true;
        $obj['data']['cid'] = $obj['cid']; // backup calendar id because $obj['cid'] won't be saved
    }

    $ret = pnModAPIFunc('TimeIt', 'user', 'create', $para);

    if($ret) {
        $calendar = pnModAPIFunc('TimeIt','calendar','get', $obj['cid']);
        if($calendar['workflow'] == 'moderate') {
            LogUtil::registerStatus (_TIMEIT_WORKFLOW_CONFIRM_MODERATE);
        } else {
            LogUtil::registerStatus (_TIMEIT_WORKFLOW_CONFIRM);
        }
    }
    
    return $ret;
}