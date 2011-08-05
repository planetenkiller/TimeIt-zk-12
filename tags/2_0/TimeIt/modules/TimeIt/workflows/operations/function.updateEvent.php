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

function TimeIt_operation_updateEvent(&$obj, $params)
{
    $online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = $online;
    //print_r($params);exit();
    $para = array();
    $para['obj'] = &$obj;
    if($params['repeat'] == '1') {
        $prozi = new TimeitRepeatProzessor($obj);
        $prozi->doInsert();
    } else if($params['deleterepeats'] == '1') {
        pnModAPIFunc('TimeIt', 'user', 'deleteAllRecurrences', array('obj'=>$obj));
    }
    
    return pnModAPIFunc('TimeIt', 'user', 'update', $para);
}