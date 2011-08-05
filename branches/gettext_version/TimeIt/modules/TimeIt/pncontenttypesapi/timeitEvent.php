<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage ContentTypesAPI
 */

Loader::requireOnce('modules/TimeIt/common.php');

class TimeIt_contenttypesapi_timeitEventPlugin extends contentTypeBase
{
    var $id;
    
    function getModule()
    {
        return 'TimeIt';
    }

    function getName() 
    {
        return 'timeitEvent';
    }
    
    function getTitle() 
    {
        return __('TimeIt event', ZLanguage::getModuleDomain('TimeIt'));
    }

    function getDescription()
    {
        return __('Choose and display an TimeIt event.', ZLanguage::getModuleDomain('TimeIt'));
    }

    function display()
    {
        if(!empty($this->id)) {
            $render = pnRender::getInstance('TimeIt');
            
            // get event
            $obj = TimeItDomainFactory::getInstance('event')->getObject((int)$this->id);

            $dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.$obj['id'], 'date ASC');
            if(count($dheobj)) {
                $dheobj = $dheobj[0];
            } else {
                return LogUtil::registerError(__f('Item with id %s not found.', $id, ZLanguage::getModuleDomain('TimeIt')), 404);
            }


            // format columns in the event
            $obj = pnModAPIFunc('TimeIt', 'user', 'getEventPreformat', array('obj' => $obj));
            // Move this event back or forward if the timezone calculation needs a move
            if(isset($objectData['allDayStartLocalDateCorrection'])) {
                $timestamp = strtotime($dheobj['date']) + ($objectData['allDayStartLocalDateCorrection'] * (60 * 60 * 24));
                $dheobj['date'] = DateUtil::getDatetime($timestamp, DATEONLYFORMAT_FIXED);
            }


            $render->assign('event', $obj);
            $render->assign('dheobj', $dheobj);
            $render->assign('dayNames', array(__('Sun', $domain),
                                          __('Mon', $domain),
                                          __('Tue', $domain),
                                          __('Wed', $domain),
                                          __('Thu', $domain),
                                          __('Fri', $domain),
                                          __('Sat', $domain)));
            $render->assign('dayFrec', array('day'   => __('Days', $domain),
                                             'week'  => __('Weeks', $domain),
                                             'month' => __('Months', $domain),
                                             'year'  => __('Years', $domain)));
            $render->assign('frec', array(1 => __('First', $domain),
                                          2 => __('Second', $domain),
                                          3 => __('Third', $domain),
                                          4 => __('Fourth', $domain),
                                          5 => __('Last', $domain)));
            return $render->fetch('contenttype/timeitEvent_view.html');
        } else {
            return __f('Item with id %s not found.', $id, ZLanguage::getModuleDomain('TimeIt'));
        }
    }
    
    function loadData($data)
    {
        $this->id = $data['eventId'];
    }

    function displayEditing()
    {
        return $this->display();
    }
    
    function handleSomethingChanged(&$render, $data)
    { 
        $obj = TimeItDomainFactory::getInstance('event')->getObject((int)$data['eventId']);
        if(!empty($obj)) {
            $render->assign('eventTitle', $obj['title']); 
        } else {
            $render->assign('eventTitle', __f('Item with id %s not found.', (int)$data['eventId'], ZLanguage::getModuleDomain('TimeIt')));
        }
    }
    
    function startEditing(&$render) 
    { 
        if(!empty($this->id)) {
            // get event
            $obj = TimeItDomainFactory::getInstance('event')->getObject((int)$this->id);

            if(!empty($obj)) {
                $render->assign('eventTitle', $obj['title']); 
            } else {
                $render->assign('eventTitle', __f('Item with id %s not found.', (int)$this->id, ZLanguage::getModuleDomain('TimeIt')));
            }
        } else {
            $render->assign('eventTitle', __('No event selected.', ZLanguage::getModuleDomain('TimeIt')));
        }
    }
}

function TimeIt_contenttypesapi_timeitEvent($args)
{
    return new TimeIt_contenttypesapi_timeitEventPlugin();
}
