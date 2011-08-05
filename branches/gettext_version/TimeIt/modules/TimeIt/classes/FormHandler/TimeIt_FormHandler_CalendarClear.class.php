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
 * Form handler for clear a calendar.
 */
class TimeIt_FormHandler_CalendarClear
{
    var $id;
    
    function initialize(&$render)
    {
        $id = FormUtil::getPassedValue('id', false, 'GET');
        if(!$id) {
            return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
        }

        if(TimeItPermissionUtil::canCreateCalendar()) {
             return LogUtil::registerPermissionError();
        }

        $this->id = $id;
        $render->assign('calendar', TimeItDomainFactory::getInstance('calendar')->getObject($this->id));
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'delete') {
            $data = $render->pnFormGetValues();
            if(!$render->pnFormIsValid()) {
                return false;
            }

            TimeItDomainFactory::getInstance('calendar')->deleteOldEvents($this->id, $data['date']);
        } 

        $render->pnFormRedirect(pnModURL('TimeIt', 'user', 'view', array('ot' => 'calendar')));
    }
}