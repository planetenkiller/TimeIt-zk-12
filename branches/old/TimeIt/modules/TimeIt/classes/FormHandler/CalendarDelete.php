<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage FormHandler
 */

/**
 * Form handler for calendar deletion.
 */
class TimeIt_FormHandler_calendarDelete
{
    var $id;
    function initialize(&$render)
    {
        $id = FormUtil::getPassedValue('id', false, 'GET');
        if(!$id) {
            return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
        }
        $this->id = $id;
        $render->assign('calendar', pnModAPIFunc('TimeIt','calendar','get',$id));
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'delete') {
            pnModAPIFunc('TimeIt','calendar','delete',$this->id);
        } 

        $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'calendars'));
    }
}