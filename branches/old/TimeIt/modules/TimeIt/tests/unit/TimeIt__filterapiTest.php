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

Loader::requireOnce('modules/TimeIt/classes/filter/Filter.class.php');

/**
 * Tests for the pnuserapi.php file.
 *
 * @author planetenkiller
 */
class TimeIt__filterapiTest extends ZkUnitTestCase
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function testFilterBasic()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:eq:1')
            ->addGroup()
                ->addExp('id:eq:2');
        $this->assertEquals('((pn_id = \'1\') OR (pn_id = \'2\'))', $f->toSQL());
        $this->assertEquals('filter1=id:eq:1&filter2=id:eq:2', $f->toURL());
    }

    public function testFilter_op_ge()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:ge:2');
        $this->assertEquals('(pn_id >= \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:ge:2', $f->toURL());
    }

    public function testFilter_op_gt()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:gt:2');
        $this->assertEquals('(pn_id > \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:gt:2', $f->toURL());
    }

    public function testFilter_op_le()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:le:2');
        $this->assertEquals('(pn_id <= \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:le:2', $f->toURL());
    }

    public function testFilter_op_like()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('title:like:allday%');
        $this->assertEquals('(pn_title LIKE \'allday%\')', $f->toSQL());
        $this->assertEquals('filter=title:like:allday%', $f->toURL());
    }

    public function testFilter_op_lt()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:lt:2');
        $this->assertEquals('(pn_id < \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:lt:2', $f->toURL());
    }

    public function testFilter_op_ne()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:ne:2');
        $this->assertEquals('(pn_id != \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:ne:2', $f->toURL());
    }

    public function testFilterFromGET()
    {
        $_GET['filter1'] = 'id:eq:1';
        $_GET['filter2'] = 'id:eq:2';

        $f = TimeIt_Filter::getFilterFormGETPOST();
        $this->assertEquals('((pn_id = \'1\') OR (pn_id = \'2\'))', $f->toSQL());
        $this->assertEquals('filter1=id:eq:1&filter2=id:eq:2', $f->toURL());

        unset($_GET['filter1']);
        unset($_GET['filter2']);

        $_GET['filter'] = 'id:eq:1,id:eq:2';

        $f = TimeIt_Filter::getFilterFormGETPOST();
        $this->assertEquals('(pn_id = \'1\' AND pn_id = \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:eq:1,id:eq:2', $f->toURL());

        unset($_GET['filter']);
    }

    public function testFilterFromGETWithVariables()
    {
        $_GET['filter1'] = 'id:eq:$id1';
        $_GET['filter2'] = 'id:eq:$id2';
        $_GET['id1'] = 1;
        $_GET['id2'] = 2;

        $f = TimeIt_Filter::getFilterFormGETPOST();
        $this->assertEquals('((pn_id = \'1\') OR (pn_id = \'2\'))', $f->toSQL());
        $this->assertEquals('filter1=id:eq:1&filter2=id:eq:2', $f->toURL());

        unset($_GET['filter1']);
        unset($_GET['filter2']);

        $_GET['filter'] = 'id:eq:$id1,id:eq:$id2';

        $f = TimeIt_Filter::getFilterFormGETPOST();
        $this->assertEquals('(pn_id = \'1\' AND pn_id = \'2\')', $f->toSQL());
        $this->assertEquals('filter=id:eq:1,id:eq:2', $f->toURL());

        unset($_GET['filter']);
        unset($_GET['id1']);
        unset($_GET['id2']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilterError()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id:hoho:1');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilterError2()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('hoho:eq:1');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilterError3()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('id::1');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilterError4()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp(':eq:1');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilterError5()
    {
        $f = new TimeIt_Filter();
        $f->addGroup()
                ->addExp('::1');
    }
}
