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

Loader::requireOnce(dirname(__FILE__).'/Calculator.php');
Loader::requireOnce(dirname(__FILE__).'/Output.php');

/**
 * Recurrence processor.
 *
 * This processor calculates all recurrences of a TimeIt event.
 * An TimeIt_Recurrence_Output object saves the recurrences in the DB or in an arrray.
 */
class TimeIt_Recurrence_Processor
{
    protected $out;
    protected $obj;
    protected $types;

    /**
     * Creates a new recurrence processor.
     *
     * @param TimeIt_Recurrence_Output $out The output object to use
     * @param array $obj The event for which the occurrences are calculated.
     */
    public function __construct(TimeIt_Recurrence_Output $out, array $obj, $noIgnoreDateFilter=false)
    {
        if($out == null || $obj == null) {
            throw new InvalidArgumentException('$out or $obj is null!');
        }
        
        if(!$noIgnoreDateFilter) {
            $out = new TimeIt_Recurrence_IgnoreDateFilter($out, explode(',', $obj['repeatIrg']));
        }

        $this->out   = $out;
        $this->obj   = $obj;
        $this->types = array();

        $this->addDefaultCalculators();
    }

    /**
     * Performs the calculation
     *
     * @param string $start date with format _DATEINPUT(yyyy-mm-dd) or null
     * @param string $end date with format _DATEINPUT(yyyy-mm-dd) or null
     */
    public function doCalculation($start=null, $end=null)
    {
        // set dates if start or end is null
        $start = ($start != null)? $start : $this->obj['startDate'];
        $end = ($end != null)? $end : $this->obj['endDate'];

        if(isset($this->types[$this->obj['repeatType']])) {
            $this->types[$this->obj['repeatType']]->calculate($start, $end, $this->obj, $this->out);
        } else {
            throw new LogicException('Unkown repeatType "'.$this->obj['repeatType'].'"!');
        }
    }

    /**
     * Adds a new calculator.
     * @param int $type int representation of the calculator (repeatType column)
     * @param TimeIt_Recurrence_Calculator $calc The calculator
     */
    public function addCalculator($type, TimeIt_Recurrence_Calculator $calc)
    {
        if(!is_int($type) || $calc == null) {
            throw new InvalidArgumentException('$type is not an int or $cals is null!');
        }

        $this->types[$type] = $calc;
    }

    /**
     * Add all default calculators
     */
    protected function addDefaultCalculators()
    {
        Loader::requireOnce(dirname(__FILE__).'/calculators/Type0.php');
        Loader::requireOnce(dirname(__FILE__).'/calculators/Type1.php');
        Loader::requireOnce(dirname(__FILE__).'/calculators/Type2.php');
        Loader::requireOnce(dirname(__FILE__).'/calculators/Type3.php');
        Loader::requireOnce(dirname(__FILE__).'/calculators/Type4.php');

        $this->addCalculator(0, new TimeIt_Recurrence_Calculator_Type0());
        $this->addCalculator(1, new TimeIt_Recurrence_Calculator_Type1());
        $this->addCalculator(2, new TimeIt_Recurrence_Calculator_Type2());
        $this->addCalculator(3, new TimeIt_Recurrence_Calculator_Type3());
        $this->addCalculator(4, new TimeIt_Recurrence_Calculator_Type4());
    }
}

/**
 * This ouput delegates all calls to the $out output but omits all dates that are in $dates.
 */
class TimeIt_Recurrence_IgnoreDateFilter implements TimeIt_Recurrence_Output
{
    /**
     * @var TimeIt_Recurrence_Output
     */
    private $out;
    private $dates;

    /**
     * Creates a new IgnoreDateFilter outputter.
     * @param TimeIt_Recurrence_Output $out original output
     * @param array $dates Dates to ignore
     */
    public function __construct(TimeIt_Recurrence_Output $out, array $dates)
    {
        $this->out = $out;
        $this->dates = $dates;
    }

    public function insert($timestamp, array &$obj)
    {
        if(!in_array( DateUtil::getDatetime($timestamp, _DATEINPUT), $this->dates)) {
            $this->out->insert($timestamp, $obj);
        }
    }
}