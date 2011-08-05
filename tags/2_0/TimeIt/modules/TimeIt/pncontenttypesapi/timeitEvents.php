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

class TimeIt_contenttypesapi_timeitEventsPlugin extends contentTypeBase
{
    var $calid;
    var $prop;
    var $catId;
    var $start;
    var $end;
    
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
        return _TIMEIT_CONTENTTYPESAPI_TITLE2;
    }

    function getDescription()
    {
        return _TIMEIT_CONTENTTYPESAPI_DESC2;
    }
    
    function display()
    {
        $filter_obj = new TimeIt_Filter();
        $filter_obj->addGroup()->addExp('category:in:'.$this->catId);
        $events = pnModAPIFunc('TimeIt','user','getDailySortedEvents', array('start'=>$this->start,
                                                                             'end'=>$this->end,
                                                                             'cid'=>$this->calid,
                                                                             'filter_obj'=>$filter_obj));

        $render = pnRender::getInstance('TimeIt');
        $render->assign('calendar', pnModAPIFunc('TimeIt','calendar','get',$this->calid));
        $render->assign('events', $events);
        return $render->fetch('contenttype/timeitEvents_view.html');
    }

    function displayEditing()
    {
        return $this->display();
    }
    
    function loadData($data)
    {
        $this->calid = $data['calid'];
        $this->prop = $data['prop'];
        $this->catId = $data['catId'];
        $this->start = $data['start'];
        $this->end = $data['end'];
    }

    function getDefaultData()
    {
        return array('calid'=>'',
                     'prop'=>'',
                     'catId'=>'',
                     'start'=>DateUtil::getDatetime(time(), _DATEINPUT),
                     'end'=>DateUtil::getDatetime(strtotime('+1 month'), _DATEINPUT));
    }

    function handleSomethingChanged(&$render, $data)
    {
        if (!($class = Loader::loadClass('CategoryUtil')))
            pn_exit ('Unable to load class [CategoryUtil] ...');

        $categories = CategoryUtil::getSubCategories($data['prop']);
        $cats = array();
        foreach($categories AS $c)
        {
            $cats[] = array('value'=>$c['id'],
                            'text'=>(isset($c['display_name'][pnUserGetLang()]) && !empty($c['display_name'][pnUserGetLang()]))?$c['display_name'][pnUserGetLang()]:$c['name']);
        }
        $render->pnFormGetPluginById('catId')->setItems($cats);
    }
    
    function startEditing(&$render) 
    {
        if (!($class = Loader::loadClass('CategoryRegistryUtil')))
            pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
            
        $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
        $cats = array();

        foreach($categories AS $prop => $catId)
        {
            $cats[] = array('text'=>$prop, 'value'=>$catId);
        }
        $render->assign('propItems', $cats);
        $render->assign('calidItems', pnModAPIFunc('Timeit','calendar','getAllForDropdown'));

        if($this->catId)
        {
            if (!($class = Loader::loadClass('CategoryUtil')))
                pn_exit ('Unable to load class [CategoryUtil] ...');
            $categories = CategoryUtil::getSubCategories($this->prop);
            $cats = array();
            foreach($categories AS $c)
            {
                $cats[] = array('value'=>$c['id'],
                                'text'=>(isset($c['display_name'][pnUserGetLang()]) && !empty($c['display_name'][pnUserGetLang()]))?$c['display_name'][pnUserGetLang()]:$c['name']);
            }
            $render->assign('catIdItems', $cats);
        }
    }
}

function TimeIt_contenttypesapi_timeitEvents($args)
{
    return new TimeIt_contenttypesapi_timeitEventsPlugin();
}
