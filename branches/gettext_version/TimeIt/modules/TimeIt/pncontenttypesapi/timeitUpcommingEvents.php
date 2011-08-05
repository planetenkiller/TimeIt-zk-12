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

class TimeIt_contenttypesapi_timeitUpcommingEventsPlugin extends contentTypeBase
{
    var $cid;
    var $tinumevents;
    var $timaxdays;
    var $cats; //  e.g. array('main' => array(50,60), 'secound'=>array(37))
    var $tiWidgetTitle;

    function getModule() 
    {
        return 'TimeIt';
    }

    function getName() 
    {
        return 'timeitUpcommingEvents';
    }

    function getTitle() 
    {
        return __('TimeIt list of upcomming events', ZLanguage::getModuleDomain('TimeIt'));
    }

    function getDescription()
    {
        return __('Displays a list of upcomming events.', ZLanguage::getModuleDomain('TimeIt'));
    }
    
    function display()
    {
        $filter_obj = new TimeItFilter('event');

        $catids = array();
        foreach($this->cats AS $cats) {
            foreach($cats AS $cat) {
                $filter_obj->addGroup()->addExp('category:in:'.$cat);
            }
        }

        $start = DateUtil::getDatetime(null, DATEONLYFORMAT_FIXED);
        $end = DateUtil::getDatetime(strtotime("+ " . $this->timaxdays ." days", time()), DATEONLYFORMAT_FIXED);

        $events = TimeItDomainFactory::getInstance('event')->getDailySortedEvents($start, $end, $this->cid, $filter_obj, array('limit' => $this->tinumevents));

        $render = pnRender::getInstance('TimeIt');
        $render->assign('calendar', TimeItDomainFactory::getInstance('calendar')->getObject($this->cid));
        $render->assign('objectArray', $events);
        $render->assign('tiConfig', pnModGetVar('TimeIt'));
        $render->assign('todayAsDate', DateUtil::getDatetime(null, DATEONLYFORMAT_FIXED));
        $render->assign('tiWidgetTitle', $this->tiWidgetTitle);
        return $render->fetch('contenttype/timeitUpcommingEvents_view.html');
    }

    function displayEditing()
    {
        return $this->display();
    }
    
    function loadData($data)
    {
        $this->cid = $data['calid'];
        $this->timaxdays = $data['timaxdays'];
        $this->tinumevents = $data['tinumevents'];

        unset($data['calid'], $data['timaxdays'], $data['tinumevents']);
        $this->cats = $data;

        $this->tiWidgetTitle = $data['tiWidgetTitle'];
    }

    function getDefaultData()
    {
        return array('calid'         => pnModGetVar('TimeIt', 'defaultCalendar'),
                     'tinumevents'   => 10,
                     'timaxdays'     => 0,
                     'tiWidgetTitle' => '');
    }
    
    function startEditing(&$render) 
    {
        if (!($class = Loader::loadClass('CategoryRegistryUtil')))
            pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
            
        $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
        $render->assign('tiregistries', $categories);

        
        $calendarsList = TimeItDomainFactory::getInstance('calendar')->getObjectList();
        $calendars = array();
        foreach($calendarsList AS $calendar) {
            $calendars[] = array('value' => $calendar['id'], 'text' => $calendar['name']);
        }
        $render->assign('calidItems', $calendars);
    }
}

function TimeIt_contenttypesapi_timeitUpcommingEvents($args)
{
    return new TimeIt_contenttypesapi_timeitUpcommingEventsPlugin();
}
