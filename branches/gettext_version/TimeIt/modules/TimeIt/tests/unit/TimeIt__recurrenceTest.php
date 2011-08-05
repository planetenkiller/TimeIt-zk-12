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

    public function testProcessorBasic()
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

    public function testProcessor_Type0()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-01',
                     'endDate'    => '2009-01-01',
                     'repeatType' => 0,
                     'repeatSpec' => '',
                     'repeatFrec' => 0);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(1, count($dates));
        $this->assertEquals('2009-01-01', $dates[0]);
    }

    public function testProcessor_Type0_multiday()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-01',
                     'endDate'    => '2009-01-02',
                     'repeatType' => 0,
                     'repeatSpec' => '',
                     'repeatFrec' => 0);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(2, count($dates));
        $this->assertEquals('2009-01-01', $dates[0]);
        $this->assertEquals('2009-01-02', $dates[1]);
    }

    public function testProcessor_Type1_week()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-01',
                     'endDate'    => '2009-01-16',
                     'repeatType' => 1,
                     'repeatSpec' => 'week',
                     'repeatFrec' => 1);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(3, count($dates));
        $this->assertEquals('2009-01-01', $dates[0]);
        $this->assertEquals('2009-01-08', $dates[1]);
        $this->assertEquals('2009-01-15', $dates[2]);
    }

    public function testProcessor_Type1_month()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-02',
                     'endDate'    => '2009-03-01',
                     'repeatType' => 1,
                     'repeatSpec' => 'month',
                     'repeatFrec' => 1);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(2, count($dates));
        $this->assertEquals('2009-01-02', $dates[0]);
        $this->assertEquals('2009-02-02', $dates[1]);
    }

    public function testProcessor_Type1_year()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-01',
                     'endDate'    => '2010-01-01',
                     'repeatType' => 1,
                     'repeatSpec' => 'year',
                     'repeatFrec' => 1);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(2, count($dates));
        $this->assertEquals('2009-01-01', $dates[0]);
        $this->assertEquals('2010-01-01', $dates[1]);
    }

    public function testProcessor_Type3()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2009-01-01',
                     'endDate'    => '2009-01-01',
                     'repeatType' => 3,
                     'repeatSpec' => '2009-01-02,2009-01-03',
                     'repeatFrec' => 0);
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

    public function testBug_219()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2010-05-09',
                     'endDate'    => '2015-06-01',
                     'repeatType' => 2,
                     'repeatSpec' => '2 0',
                     'repeatFrec' => 12);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(6, count($dates));
        $this->assertEquals('2010-05-09', $dates[0]);
        $this->assertEquals('2011-05-08', $dates[1]);
        $this->assertEquals('2012-05-13', $dates[2]);
        $this->assertEquals('2013-05-12', $dates[3]);
        $this->assertEquals('2014-05-11', $dates[4]);
        $this->assertEquals('2015-05-10', $dates[5]);
    }

    public function testBug_236()
    {
        // create objects
        $out = new TimeIt_Recurrence_Output_Array();
        $obj = array('startDate'  => '2004-06-10',
                     'endDate'    => '2024-06-10',
                     'repeatType' => 1,
                     'repeatSpec' => 'year',
                     'repeatFrec' => 1);
        $p =  new TimeIt_Recurrence_Processor($out, $obj);

        // start calculation
        $p->doCalculation();

        // get array of dates
        $dates = &$out->getData();

        // check array
        $this->assertEquals(21, count($dates));
        $this->assertEquals('2004-06-10', $dates[0]);
        $this->assertEquals('2005-06-10', $dates[1]);
        $this->assertEquals('2006-06-10', $dates[2]);
        $this->assertEquals('2007-06-10', $dates[3]);
        $this->assertEquals('2008-06-10', $dates[4]);
        $this->assertEquals('2009-06-10', $dates[5]);
        $this->assertEquals('2010-06-10', $dates[6]);
        $this->assertEquals('2011-06-10', $dates[7]);
        $this->assertEquals('2012-06-10', $dates[8]);
        $this->assertEquals('2013-06-10', $dates[9]);
        $this->assertEquals('2014-06-10', $dates[10]);
        $this->assertEquals('2015-06-10', $dates[11]);
        $this->assertEquals('2016-06-10', $dates[12]);
        $this->assertEquals('2017-06-10', $dates[13]);
        $this->assertEquals('2018-06-10', $dates[14]);
        $this->assertEquals('2019-06-10', $dates[15]);
        $this->assertEquals('2020-06-10', $dates[16]);
        $this->assertEquals('2021-06-10', $dates[17]);
        $this->assertEquals('2022-06-10', $dates[18]);
        $this->assertEquals('2023-06-10', $dates[19]);
        $this->assertEquals('2024-06-10', $dates[20]);
    }
}