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

function TimeIt_operation_deleteEvent(&$obj, $params)
{
    $args = array('id'=>$obj['id']);

    if(isset($obj['__META__']['TimeIt']['recurrenceOnly']) && $obj['__META__']['TimeIt']['recurrenceOnly']) {
        if(isset($obj['dheid']) && !empty($obj['dheid'])) {
            $dheobj = pnModAPIFunc('TimeIt','user','getDHE', array('dheid'=>$obj['dheid']));
            if(!$dheobj['localid']) {
                // TODO Move into new method: deleteRecurrence
                DBUtil::deleteObjectByID('TimeIt_date_has_events', (int)$dheobj['id']);
                return true;
            }
        }
        $args['eventOnly'] = true;
    }
    
    return pnModAPIFunc('TimeIt', 'user', 'delete', $args);
}