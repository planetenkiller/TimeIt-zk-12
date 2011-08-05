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

/**
 * Base class for filter operators.
 * @author planetenkiller
 */
abstract class TimeIt_Filter_OperatorIf
{
    protected $field;
    protected $value;
    protected $table_columns;

    protected static $allowdFields = array('id','title','cr_uid','up_uid','cid','category','subscribeLimit',
                                           'subscribeWPend','repeatType','startDate','endDate','allDay',
                                           'allDayStart','allDayDur','sharing','status','group');

    public function __construct($field, $value)
    {
        if(empty($field)) {
            throw new InvalidArgumentException('$field is empty');
        } else if(!in_array($field, self::$allowdFields)) {
            throw new InvalidArgumentException('$field contains an unkown field ('.$field.').');
        }
        $this->field = $field;
        $this->value = $value;
        $this->table_columns = pnDBGetTables();
        $this->table_columns = $this->table_columns['TimeIt_events_column'];
    }

    /**
     * Called before getSQL().
     * @param all expressions $groups
     */
    public function prepare(&$groups)
    {
    }

    /**
     * Converts an expression (eg cruid:eq:2) to a SQL string (eg. pn_cr_ui = 2).
     */
    public abstract function toSQL($table);

    /**
     * Converts an expression to a URL compitable string (eg. cruid:eq:2).
     */
    public abstract function toURL();

    /**
     * Returns the value of this expression.
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the field of this expression.
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Returns an TimeIt_Filter_OperatorIf instance form an expression.
     * @param string $exp Expression in format: field:operator:value
     * @return TimeIt_Filter_OperatorIf 
     */
    public static function operatorFromExp($exp)
    {
        $pattern = '/^([0-9a-zA-Z_-]+):([0-9a-zA-Z_-]+):(.*)$/';
        
        // extract parts
        if(preg_match_all($pattern, $exp, $array)) {
            $field = $array[1][0];
            $operator = $array[2][0];
            $value = $array[3][0];

            if(strlen($value) > 0) {
                // check field
                if(in_array($field, self::$allowdFields)) {

                    $class = 'TimeIt_Filter_OP_'.DataUtil::formatForOS($operator);
                    $file = 'modules/TimeIt/classes/filter/operator/'.$class.'.php';
                    // check operator
                    if(file_exists($file)) {
                        Loader::requireOnce($file);
                        $rfclass = new ReflectionClass($class);
                        // check operator class
                        if($rfclass->isSubclassOf(new ReflectionClass('TimeIt_Filter_OperatorIf'))) {

                            if(($field == 'cr_uid' || $field == 'up_uid') && (int)$value == -1) {
                                $value = pnUserGetVar('uid',-1,1); // set uid of current user
                            } else if(($field == 'cr_uid' || $field == 'up_uid') && !preg_match('/^[0-9]+$/', $value)) {
                                if($value == 'User Name') {
                                    return null;
                                } else {
                                    $name = $value;
                                    $value = $uid = pnUserGetIDFromName($value); // get user id form user name
                                    if(empty($uid)) {
                                        // show error
                                        LogUtil::registerError(pnML('_TIMEIT_ERROR_USERNOFOUND',array('s'=>$name)));
                                        return null;
                                    }
                                }
                            } else if(($field == 'cr_uid' || $field == 'up_uid') && preg_match('/^[0-9]+$/', $value)) {
                                $value = (int)$value;
                            }

                            return new $class($field, $value);
                        } else {
                            throw new LogicException('Class of operator '.$operator.' ('.$class.') is not a subclass of TimeIt_Filter_OperatorIf.');
                        }

                    } else {
                        throw new InvalidArgumentException('Expression has got an invalid operator ('.$operator.').');
                    }
                } else {
                    throw new InvalidArgumentException('Expression has got an unkown field ('.$field.').');
                }
            } // ignore filter
        } else {
            throw new InvalidArgumentException('Expression has got an invalid format.');
        }
    }
}

