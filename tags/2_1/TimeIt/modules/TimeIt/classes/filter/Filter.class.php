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
 * This filter converts filters like title:eq:Hello to its SQL/URL representation.
 */
class TimeIt_Filter
{
    /**
     * @var array
     */
    protected $groups;

    protected $prepared;

    /**
     * Create a new empty TimeIt_Filter.
     */
    public function __construct()
    {
        $this->groups = array();
        $this->prepared = false;
    }

    /**
     * Returns true if this filter contains a expression with the field $field.
     * @param stirng $field a fieldname
     * @return boolean
     */
    public function hasFilterOnField($field)
    {
        foreach($this->groups AS $group) {
            foreach($group AS $op) {
                if($op->getField() == $field) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool true when there is min. one group, false otherwise
     */
    public function hasGroup()
    {
        return count($this->groups) > 0;
    }

    /**
     * Add a expression group. All exInvalidArgumentExceptionpressions in the same group are linked with AND.
     * Groups are linked with OR:
     * @return TimeIt_Filter current TimeIt_Filter
     */
    public function addGroup()
    {
        $this->groups[] = array();

        return $this;
    }

    /**
     *
     * @param string $exps a expression in the format field:oper:value
     * @return TimeIt_Filter current TimeIt_Filter
     */
    public function addExp($string)
    {
        if(count($this->groups) === 0) {
            throw new LogicException('No groups found!');
        }
        $op = TimeIt_Filter_OperatorIf::operatorFromExp($string);
        if($op) {
            $this->groups[count($this->groups)-1][] = $op;
            $this->prepared = false;

            // security: add cr_uid expression if there is a share filter for private events.
            if($op->getField() == 'sharing' && (
                    ($op instanceof TimeIt_Filter_OP_le || $op instanceof TimeIt_Filter_OP_lt)
                 || ((int)$op->getValue() <= 2 && ($op instanceof TimeIt_Filter_OP_eq || TimeIt_Filter_OP_ne))
                 || ($op instanceof TimeIt_Filter_OP_like && ($op->getValue() == '%2%' || $op->getValue() == '2%' || $op->getValue() == '2' || $op->getValue() == '%1%' || $op->getValue() == '1%' || $op->getValue() == '1'))
                 )){
                $op2 = TimeIt_Filter_OperatorIf::operatorFromExp('cr_uid:eq:-1');
                if($op2 != null) {
                    $this->groups[count($this->groups)-1][]  = $op2;
                }
            }
        }
        
        return $this;
    }

    /**
     * Calls prepare() on every operation.
     */
    protected function prepareOps()
    {
        if(!$this->prepared) {
            foreach($this->groups AS $group) {
                foreach($group AS $op) {
                    $op->prepare($this->groups);
                }
            }
            $this->prepared = true;
        }
    }

    /**
     * Converts this TimeIt_Filter to its SQL representation.
     * @return string SQL WHERE part.
     */
    public function toSQL($table=null)
    {
        $this->prepareOps();
        $sql = array();
        $sql_s = '';

        foreach($this->groups AS $group) {
            $sql_sub = array();
            foreach($group AS $op) {
                $sql_sub[] = $op->toSQL($table);
            }

            if(!empty($sql_sub)) {
                $sql_sub = implode(' AND ', $sql_sub);
                $sql[] = '('.$sql_sub.')';
            }
        }
        if(!empty($sql)) {
            $sql_s = implode(' OR ', $sql);
            if(count($sql) > 1) {
                $sql_s = '('.$sql_s.')';
            }
        }

        return $sql_s;
    }

    /**
     * Converts this TimeIt_Filter to its URL parameter representation.
     * @return string Format: filter1=exp,exp&filter1=exp,exp
     */
    public function toURL()
    {
        $this->prepareOps();
        $url = array();
        $url_s = '';

        foreach($this->groups AS $group) {
            $url_sub = array();
            foreach($group AS $op) {
                $url_sub[] = $op->toURL();
            }

            if(!empty($url_sub)) {
                $url_sub = implode(',', $url_sub);
                $url[] = $url_sub;
            }
        }
        
        if(!empty($url)) {
            if(count($url) == 1) {
                $url_s = 'filter='.$url_sub;
            } else {
                $url2 = array();
                for($i=1;$i<=count($url); $i++) {
                    $url2[] = 'filter'.$i.'='.$url[$i-1];
                }
                $url_s = implode('&', $url2);
            }
        }

        return $url_s;
    }

    /**
     * Creates a TimeIt_Filter form GET and POST values.
     * @return TimeIt_Filter created TimeIt_Filter
     */
    public static function getFilterFormGETPOST()
    {
        $ret = new TimeIt_Filter();
        $filter = FormUtil::getPassedValue('filter', null, 'GETPOST');

        if(!empty($filter)) {
            self::substituteVariables($filter);
            $expressions = explode(',', $filter);
            $ret->addGroup();
            foreach($expressions AS $ex) {
                $ret->addExp($ex);
            }

        } else {
            $filter1 = FormUtil::getPassedValue('filter1', null, 'GETPOST');
            if(!empty($filter1)) {
                $i = 1;
                while($filter = FormUtil::getPassedValue('filter'.$i, null, 'GETPOST')) {
                    self::substituteVariables($filter);
                    $expressions = explode(',', $filter);
                    $ret->addGroup();
                    foreach($expressions AS $ex) {
                        $ret->addExp($ex);
                    }

                    $i++;
                }
            }
        }

        return $ret;
    }

    /**
     * Creates a TimeIt_Filter form GET and POST values.
     * @return TimeIt_Filter created TimeIt_Filter
     */
    public static function getFilterFormString($string, TimeIt_Filter $filter=null)
    {
        if($filter) {
            $ret = $filter;
        } else {
            $ret = new TimeIt_Filter();
        }
        if(!$ret->hasGroup()) {
            $ret->addGroup();
        }

        if(!empty($string)) {
            $first = true;
            $filters = explode('&', $string);
            foreach($filters AS $filter) {
                if(!$first) {
                    $ret->addGroup();
                }

                $expressions = explode(',', $filter);
                foreach($expressions AS $ex) {
                    $ret->addExp($ex);
                }
            }

        }

        return $ret;
    }


    protected static function substituteVariables(&$string)
    {
        if(preg_match_all('/(\$[0-9a-zA-Z_-]+)/', $string, $array) !== false) {
            $vars = $array[0];

            foreach($vars AS $var) {
                $value = FormUtil::getPassedValue(substr($var, 1, strlen($var)-1), '', 'GETPOST');
                $string = self::str_replace_once($var, $value, $string);
            }
        }
    }

    public static function str_replace_once($needle , $replace , $haystack)
    {
        // Looks for the first occurence of $needle in $haystack
        // and replaces it with $replace.
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            // Nothing found
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }
}

