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

class TimeIt_contenttypesapi_timeitEventsPlugin extends contentTypeBase
{
    var $calid;
    var $prop;
    var $catId;
    var $start;
    var $end;
    var $tiWidgetTitle;
    
    function getModule() 
    {
        return 'TimeIt';
    }

    function getName() 
    {
        return 'timeitEvents';
    }

    function getTitle() 
    {
        return __('TimeIt list of events', ZLanguage::getModuleDomain('TimeIt'));
    }

    function getDescription()
    {
        return __('Displays a list of events.', ZLanguage::getModuleDomain('TimeIt'));
    }
    
    function display()
    {
        $filter_obj = new TimeItFilter('event');
        $filter_obj->addGroup()->addExp('category:in:'.$this->catId);
        $events = TimeItDomainFactory::getInstance('event')->getDailySortedEvents($this->start, $this->end, $this->calid, $filter_obj);

        $render = pnRender::getInstance('TimeIt');
        $render->assign('calendar', TimeItDomainFactory::getInstance('calendar')->getObject($this->calid));
        $render->assign('events', $events);
        $render->assign('tiWidgetTitle', $this->tiWidgetTitle);
        return $render->fetch('contenttype/timeitEvents_view.html');
    }

    function displayEditing()
    {
        return $this->display();
    }
    
    function loadData($data)
    {
        $this->calid = $data['calid'];
        $this->prop  = $data['prop'];
        $this->catId = $data['catId'];
        $this->start = $data['start'];
        $this->end   = $data['end'];
        $this->tiWidgetTitle = $data['tiWidgetTitle'];
    }

    function getDefaultData()
    {
        return array('calid'         => '',
                     'prop'          => '',
                     'catId'         => '',
                     'start'         => DateUtil::getDatetime(time(), '%Y-%m-%d'),
                     'end'           => DateUtil::getDatetime(strtotime('+1 month'), '%Y-%m-%d'),
                     'tiWidgetTitle' => '');
    }

    function handleSomethingChanged(&$render, $data)
    {
        if (!($class = Loader::loadClass('CategoryUtil')))
            pn_exit ('Unable to load class [CategoryUtil] ...');

        $categories = CategoryUtil::getSubCategories($data['prop']);
        $cats = array();
        foreach($categories AS $c) {
            $cats[] = array('value' =>$c['id'],
                            'text'  => (isset($c['display_name'][pnUserGetLang()]) && !empty($c['display_name'][pnUserGetLang()]))?
                                            $c['display_name'][pnUserGetLang()]
                                         :
                                            $c['name']);
        }
        $render->pnFormGetPluginById('catId')->setItems($cats);
    }
    
    function startEditing(&$render) 
    {
        if (!($class = Loader::loadClass('CategoryRegistryUtil')))
            pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
            
        $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
        $cats = array();

        foreach($categories AS $prop => $catId) {
            $cats[] = array('text'=>$prop, 'value'=>$catId);
        }
        
        $render->assign('propItems', $cats);



        $calendarsList = TimeItDomainFactory::getInstance('calendar')->getObjectList();
        $calendars = array();
        foreach($calendarsList AS $calendar) {
            $calendars[] = array('value' => $calendar['id'], 'text' => $calendar['name']);
        }
        $render->assign('calidItems', $calendars);

        if($this->catId) {
            if (!($class = Loader::loadClass('CategoryUtil')))
                pn_exit ('Unable to load class [CategoryUtil] ...');
            $categories = CategoryUtil::getSubCategories($this->prop);
            $cats = array();
            foreach($categories AS $c)
            {
                $cats[] = array('value' => $c['id'],
                                'text'  => (isset($c['display_name'][pnUserGetLang()]) && !empty($c['display_name'][pnUserGetLang()]))?$c['display_name'][pnUserGetLang()]:$c['name']);
            }
            $render->assign('catIdItems', $cats);
        }
    }
}

function TimeIt_contenttypesapi_timeitEvents($args)
{
    return new TimeIt_contenttypesapi_timeitEventsPlugin();
}
