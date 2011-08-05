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
 * Outputter that saves all occurrences in an array.
 */
class TimeIt_Recurrence_Output_Array implements TimeIt_Recurrence_Output
{
    private $data;

    /**
     * Creates a new array outputter.
     */
    public function __construct()
    {
        $this->data = array();
    }

    /**
     * Returns all dates as array.
     * @return array dates with the format _DATEINPUT (yyyy-mm-dd)
     */
    public function &getData()
    {
        return $this->data;
    }

    public function insert($timestamp, array &$obj)
    {
        $this->data[] = DateUtil::getDatetime($timestamp, _DATEINPUT);
    }
}