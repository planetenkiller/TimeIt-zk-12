<?php

class TimeIt_contenttypesapi_timeitEventPlugin extends contentTypeBase
{
    var $id;
    
    function getModule() { return 'TimeIt'; }
    function getName() { return 'timeitEvent'; }
    function getTitle() { return _TIMEIT_CONTENTTYPESAPI_TITLE; }
    function getDescription() { return _TIMEIT_CONTENTTYPESAPI_DESC; }
    
    function display()
    {
    	if(!empty($this->id))
        {
    		$render = pnRender::getInstance('TimeIt');
    		
    		// get event
    		if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
            	pn_exit ("Unable to load class [Event] ...");
        	}
        	$object = new $class();
        	$obj = $object->getEvent((int)$this->id);
        	
        	// prepare
        	$obj['cr_name'] = pnUserGetVar('uname', $obj['cr_uid']);
    		$obj['cr_datetime'] = DateUtil::getDatetime($info['cr_date'], _DATETIMEBRIEF);
    		$obj['text'] = pnModCallHooks('item', 'transform', '', array($obj['text']));
   	 		$obj['text'] = $obj['text'][0];
        	
   	 		
        	$render->assign('event', $obj);
        	return $render->fetch('contenttype/timeitEvent_view.html');
        } else
        {
        	return _TIMEIT_NOIDSET;
        }
    }
    
    function loadData($data)
    {
        $this->id = $data['eventId'];
    }

    function displayEditing()
    {
        if(!empty($this->id))
        {
    		$render = pnRender::getInstance('TimeIt');
    		
    		// get event
    		if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
            	pn_exit ("Unable to load class [Event] ...");
        	}
        	$object = new $class();
        	$obj = $object->getEvent((int)$this->id);
        	
        	// prepare
        	$obj['cr_name'] = pnUserGetVar('uname', $obj['cr_uid']);
    		$obj['cr_datetime'] = DateUtil::getDatetime($info['cr_date'], _DATETIMEBRIEF);
    		$obj['text'] = pnModCallHooks('item', 'transform', '', array($obj['text']));
   	 		$obj['text'] = $obj['text'][0];
        	
   	 		
        	$render->assign('event', $obj);
        	return $render->fetch('contenttype/timeitEvent_view.html');
        } else
        {
        	return _TIMEIT_NOIDSET;
        }
    }
    
    function handleSomethingChanged(&$render, $data)
    { 
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
            pn_exit ("Unable to load class [Event] ...");
        }
        $object = new $class();
        $obj = $object->getEvent((int)$data['eventId']);
        if(!empty($obj))
        {
            $render->assign('eventTitle', $obj['title']); 
        } else
        {
            $render->assign('eventTitle', 'event not found'); 
        }
    }
    
    function startEditing(&$render) 
    { 
        if(!empty($this->id))
        {
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                pn_exit ("Unable to load class [Event] ...");
            }
            $object = new $class();
            $obj = $object->getEvent((int)$this->id);
            if(!empty($obj))
            {
                $render->assign('eventTitle', $obj['title']); 
            } else
            {
                $render->assign('eventTitle', 'event not found'); 
            }
        } else
        {
            $render->assign('eventTitle', 'no event selected'); 
        }
    }
}

function TimeIt_contenttypesapi_timeitEvent($args)
{
    return new TimeIt_contenttypesapi_timeitEventPlugin();
}
