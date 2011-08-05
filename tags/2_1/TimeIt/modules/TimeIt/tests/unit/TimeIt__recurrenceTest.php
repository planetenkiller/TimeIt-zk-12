<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Tests
 */

Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/Array.php');

/**
 * Tests for the pnuserapi.php file.
 *
 * @author planetenkiller
 */
class TimeIt__recurrenceTest extends ZkUnitTestCase
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function testProcessorSimple()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-01',
                     'endDate'    => '2009-01-03',
                     'repeatType' => 1,
                     'repeatSpec' => 'day',
                     'repeatFrec' => 1);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(3, count($dates));
        $this->assertEquals('2009-01-01', $dates[0]);
        $this->assertEquals('2009-01-02', $dates[1]);
        $this->assertEquals('2009-01-03', $dates[2]);
    }
}