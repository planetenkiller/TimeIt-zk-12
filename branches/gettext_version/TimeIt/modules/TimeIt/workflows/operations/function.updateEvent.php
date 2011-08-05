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

Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');

/**
 * This workflow operation updates an event.
 * @param array $obj
 * @param array $params
 * @return bool
 */
function TimeIt_operation_updateEvent(&$obj, $params)
{
    $online = isset($params['online']) ? $params['online'] : 0;
    $obj['status'] = $online;
    //print_r($params);exit();
    $para = array();
    $para['obj'] = &$obj;
    if($params['repeat'] == '1') {
        if(!isset($obj['cid']) && isset($obj['data']['cid'])) {
            $obj['cid'] = $obj['data']['cid'];
        } else if(!isset($obj['cid']) && !isset($obj['data']['cid'])) {
            $obj['cid'] = pnModGetVar('TimeIt', 'defaultCalendar');
        }
        $para['noRecurrences'] = true;
        $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $obj);
        $prozi->doCalculation();
    } else if($params['deleterepeats'] == '1') {
        TimeItDomainFactory::getInstance('event')->deleteAllOccurrences($obj['id']);
        $obj['data']['cid'] = $obj['cid']; // backup calendar id because $obj['cid'] won't be saved
        $para['noRecurrences'] = true;
    }
    
    return TimeItDomainFactory::getInstance('event')->updateObject($obj);
}