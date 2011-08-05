<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Recurrence
 */

/**
 * Outputter that saves all occurrences in the DB.
 */
class TimeIt_Recurrence_Output_DB implements TimeIt_Recurrence_Output
{
    /**
     * Creates a new DB outputter.
     */
    public function __construct()
    {
        $this->data = array();
    }


    public function insert($timestamp, array &$obj)
    {
        $insert = array();
        $insert['eid'] = $obj['id'];
        $insert['date'] = DateUtil::getDatetime($timestamp, DATEONLYFORMAT_FIXED);
        $insert['cid'] = $obj['cid'];

        DBUtil::insertObject($insert, 'TimeIt_date_has_events');
    }
}