<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Filter
 */

Loader::requireOnce('modules/TimeIt/classes/filter/operator/TimeIt_Filter_OperatorIf.php');

/**
 * Equal operator
 *
 * @author planetenkiller
 */
class TimeIt_Filter_OP_gt extends TimeIt_Filter_OperatorIf
{
    public function toSQL($table)
    {
        return($table?  $table.'.' : ''). $this->table_columns[$this->field]." > '".DataUtil::formatForStore($this->value)."'";
    }

    public function toURL()
    {
        return $this->field.":gt:".DataUtil::formatForStore($this->value);
    }
}

