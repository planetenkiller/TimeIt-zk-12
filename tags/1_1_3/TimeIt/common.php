<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

Loader::loadClass('UserUtil');

class Timeit_common_createHandler
{
    var $id;
    var $mode;
    var $type;

    function Timeit_common_createHandler($type='user')
    {
    	$this->type = $type;
    }
    
    function initialize(&$render)
    {
        if(FormUtil::getPassedValue('func', 'new', 'GET') == "modify")
        {
            $this->mode = "edit";
            if(($this->id=FormUtil::getPassedValue('eventid', null, 'GET'))==null)
            {
                return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
            }
            
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
              	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
            $object = new $class();
            $obj = $object->getEvent($this->id);
            if(empty(  $obj   ))
            {
                return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }
            
            if($obj['group'] == 'all')
            {
            	$groupObj = array('name'=>'all'); // gorup irrelevant
            } else {
            	$groupObj = UserUtil::getPNGroup((int)$obj['group']);
            }
            // Security check
    		if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_MODERATE) && 
    		    !SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", ACCESS_MODERATE)) 
    		{
        		return LogUtil::registerPermissionError();
    		}
            
            $render->assign('title', $obj['title']);
            $render->assign('group', $obj['group']);
            $render->assign('locations', (int)$obj['data']['locations']);
            $render->assign('subscribeWPend', $obj['subscribeWPend']);
            
            $render->assign('subscribeLimit', $obj['subscribeLimit']);
            $render->assign('startDate', $obj['startDate']);
            $render->assign('allDay', $obj['allDay']);
            if($obj['allDay'] == 0)
            {
                $temp = explode(":", $obj['allDayStart']);   
                $render->assign('allDayStart_h', $temp[0]);
                $render->assign('allDayStart_m', $temp[1]);
                $allDayDurTemp = explode(',', $obj['allDayDur']);
                $render->assign('allDayDur', (int)$allDayDurTemp[0]);
                $render->assign('allDayDurMin', (int)$allDayDurTemp[1]);
            } 
            $render->assign('text_type', (substr($obj['text'],0,11)=="#plaintext#")?0:1); 
        	if(substr($obj['text'],0,11) == "#plaintext#")
    		{
    			$obj['text'] = substr_replace($obj['text'],"",0,11);
    			//$obj['text'] = nl2br($obj['text']);
    		}
            $render->assign('text', $obj['text']);
            $render->assign('data', $obj['data']);
            $render->assign('endDate', $obj['endDate']);
            $render->assign('share', $obj['sharing']);
            $render->assign('repeat', $obj['repeatType']);
            if($obj['repeatType']==1)
            {            
                $render->assign('repeatFrec1', $obj['repeatSpec']);
                $render->assign('repeatFrec', $obj['repeatFrec']);
            } else if($obj['repeatType']==2)
            {
                $render->assign('repeatFrec2', $obj['repeatFrec']);
                $temp = explode(' ', $obj['repeatSpec']);
                $render->assign('repeat21', $temp[0]);
                $render->assign('repeat22', $temp[1]);
            }
            $render->assign('language', $obj['language']);
            
            // assign categroies
            if(isset($obj['__CATEGORIES__']))
            {
            	$cats = array();
            	foreach($obj['__CATEGORIES__'] AS $property => $cobj)
            	{
            		$cats['cat_'.$property] = $cobj['id']; 
            	}
            	$render->assign('cats', $cats);
            }
            
            // get all possible actions for this object in whatever workflow state it's in
    		$actions = WorkflowUtil::getActionsForObject($obj, 'TimeIt_events');
            $wfSchemaName = $obj['__WORKFLOW__']['schemaname'];
        } else
        {
            $this->mode = "create";
            $render->assign('allDay', 1);
            $render->assign('repeat', 0);
            $render->assign('text_type', 0);
            $render->assign('subscribeLimit', pnModGetVar('TimeIt', 'subscribeLimit', 0));
            $render->assign('subscribeWPend', pnModGetVar('TimeIt', 'subscribePending', 0));
            
            $tobj = array();
            $actions = WorkflowUtil::getActionsByState(pnModGetVar('TimeIt', 'workflow', 'standard'));
            $wfSchemaName = pnModGetVar('TimeIt', 'workflow', 'standard');
            
            $T_date = FormUtil::getPassedValue('date', false, 'GET');
            if($T_date)
            {
            	$render->assign('startDate', $T_date);
            	$render->assign('endDate', $T_date);
            }
        }
        
        // scribite! integration
        if (pnModAvailable('scribite')) 
        {
        	// load editor
       		$scribite = pnModFunc('scribite','user','loader', array('modname' => 'TimeIt',
                                                                	'editor'  => pnModGetVar('TimeIt', 'scribiteEditor'),
                                                                	'areas'   => array('text')
                                                                	/*'tpl'     => $args['areas']*/));
       		PageUtil::AddVar('rawtext', $scribite);
        }
        
    	if(pnModAvailable('locations') && pnModGetVar('TimeIt', 'useLocations'))
        {
        	$locationsItems = array(-1 => array('value'=>'','text'=>'---'));
        	$locationsItems2 = pnModAPIFunc('locations','user','getLocationsForDropdown');
        	$render->assign('locationsItems', array_merge($locationsItems, $locationsItems2));
        }
        
        // make pnformdropdownlist compitable array
        $wfactionsItems = array();
        foreach($actions AS $id)
        {
        	$wfactionsItems[] = array('text' => TimeIt_getTranslationForWorkflowActionId($wfSchemaName, $id), 'value' => $id);
        }
        $render->assign('wfactionsItems', $wfactionsItems);
        
        // load the categories system
        if (!($class = Loader::loadClass('CategoryRegistryUtil')))
            pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
        if (!($class = Loader::loadClass('CategoryUtil')))
            pn_exit ('Unable to load class [CategoryUtil] ...');
        $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
        foreach ($categories AS $property => $cid)
        {
        	$cat = CategoryUtil::getCategoryByID($cid);
        	$categories[$property] = array();
        	$categories[$property]['id'] = $cat['id'];
        	$categories[$property]['name'] = (isset($cat['display_name'][pnUserGetLang()]))?$cat['display_name'][pnUserGetLang()]:$cat['name'];
        }
        
        $render->assign('categories', $categories);
       
        
      	// create array with hours
        $hours = array();
        for($i=0;$i<24;$i++)
        {
        	$hours[] = array('text' => $i, 'value' => $i);
        } 
        
        // create array with minutes
        $mins = array();
        for($i=0;$i<60;$i++)
        {
            $value = ($i<10)? '0'.$i: $i;
        	$mins[] = array('text' => $value, 'value' => $value);
        } 
        // arrays for some pnformdropdownlists
        $share = array();
        // priate calendar allowed?               
        if(pnModGetVar('TimeIt', 'privateCalendar'))
        {
        	$share[] = array('text' => 'private' , 'value' => '1');
        	$share[] = array('text' => 'public' ,  'value' => '2');
        }
        if(pnModGetVar('TimeIt', 'globalCalendar'))
        {
        	$share[] = array('text' => 'global' ,  'value' => '3');
        }
    	if(pnModGetVar('TimeIt', 'friendCalendar'))
        {
        	$share[] = array('text' => 'friends only' ,  'value' => '4');
        }
        
        // one share level only?
        if(count($share) == 1)
        {
        	// hide share dropdown
        	$render->assign('shareItemsHide', true);
        } else 
        {
        	$render->assign('shareItemsHide', false);
        }
               
        $repeatFrec1 = array(array('text' => _DAYS , 'value' => 'day'),
                             array('text' => _WEEKS , 'value' => 'week'),
                             array('text' => _MONTHS , 'value' => 'month'),
                             array('text' => _YEARS , 'value' => 'year'));
      
        $repeat21 = array(array('text' => 'First' , 'value' => 1),
                          array('text' => 'Second' , 'value' => 2),
                          array('text' => 'Third' , 'value' => 3),
                          array('text' => 'Fourth' , 'value' => 4),
                          array('text' => 'Last' , 'value' => 5));
                             
        $TDays = explode(" ", _DAY_OF_WEEK_SHORT);
        $repeat22 = array(array('text' => $TDays[0] , 'value' => 0),
                          array('text' => $TDays[1] , 'value' => 1),
                          array('text' => $TDays[2] , 'value' => 2),
                          array('text' => $TDays[3] , 'value' => 3),
                          array('text' => $TDays[4] , 'value' => 4),
                          array('text' => $TDays[5] , 'value' => 5),
                          array('text' => $TDays[6] , 'value' => 6));

        // create array with groups
        $groupsConverted = array();
        if (SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) 
    	{
	       	$groups = UserUtil::getPNGroups();
	       	$groupsConverted[] = array('text' => 'all' , 'value' => 'all');
	       	foreach ($groups as $group) 
	       	{
	       		$groupsConverted[] = array('text' => $group['name'] , 'value' => $group['gid']);
	       	}
    	} else 
    	{
    		$groups = TimeIt_getGroupsForSelect();
    		foreach ($groups as $id => $name) 
	       	{
	       		$groupsConverted[] = array('text' => $name , 'value' => $id);
	       	}
    	}
    	
    	// one group only?
    	if(count($groupsConverted) == 1)
    	{
    		// hide group dropdown
    		$render->assign('groupItemsHide', true);
    	} else 
    	{
    		$render->assign('groupItemsHide', false);
    	}
    	
       	$render->assign('groupItems', $groupsConverted);
       	$render->assign('mode', $this->mode);
        $render->assign('allDayStart_hItems', $hours);
        $render->assign('allDayStart_mItems', $mins);
        $render->assign('shareItems', $share);
        $render->assign('repeatFrec1Items', $repeatFrec1);
        $render->assign('repeat21Items', $repeat21);
        $render->assign('repeat22Items', $repeat22);
        
        return true;
    }
    

    function handleCommand(&$render, &$args)
    {
      if ($args['commandName'] == 'create')
      {
        $data = $render->pnFormGetValues();
        //print_r($this->addon);exit();
        $valid = $render->pnFormIsValid();
        
        // set endDate if it is empty
        if(empty($data['endDate']))
        {
        	$data['endDate'] = $data['startDate'];
        }
        
        // check for errors
        if($data['startDate'] > $data['endDate'])
        {
        	$p_startDate = &$render->pnFormGetPluginById('startDate');
        	$p_startDate->setError(_TIMEIT_ERROR_1);
        	$valid = false;
        }
        if($data['allDay'] == 0 && $data['allDayDur'] < 0)
        {
        	$p_allDayDur = &$render->pnFormGetPluginById('allDayDur');
        	$p_allDayDur->setError(_PNFORM_MANDATORYERROR);
        	$valid = false;
        }
        if((int)$data['repeat'] == 1 && empty($data['repeatFrec']))
        {
        	$p_repeatFrec = &$render->pnFormGetPluginById('repeatFrec');
        	$p_repeatFrec->setError(_PNFORM_MANDATORYERROR);
        	$valid = false;
        } else if((int)$data['repeat'] == 2 && empty($data['repeatFrec2']))
        {
        	$p_repeatFrec2 = &$render->pnFormGetPluginById('repeatFrec2');
        	$p_repeatFrec2->setError(_PNFORM_MANDATORYERROR);
        	$valid = false;
        }
        
      	// check permissions for categories
   		$permissionOk = true;
   		$permissionOkId = '';
	    foreach ($data['cats'] AS $prop=>$cat)
	    {
	    	$cid = $cat;
	            	
		    $permissionOk = SecurityUtil::checkPermission('TimeIt:Category:Add', $cid."::", ACCESS_OVERVIEW);
		    $permissionOkId = $prop;
		    if(!$permissionOk)
		    {
		      	break;
		    }
	    }
	    var_dump($permissionOk);
	    var_dump($permissionOkId);
	    // no permission
	    if(!$permissionOk)
	    {
	     	$p_repeatFrec2 = &$render->pnFormGetPluginById($permissionOkId);
        	$p_repeatFrec2->setError(_TIMEIT_ERROR_CAT);
        	$valid = false;
	    }
		
        
        if (!$valid)
        {
            return false;
        }

        //$data = $render->pnFormGetValues();
        //$data['id'] = $this->id;
        //print_r($data);exit();
        
        // create Array for Insert in DB
        $dataForDB = array();
        if($this->mode=="edit") $dataForDB['id'] = $this->id;
        $dataForDB['title'] = $data['title'];
        $dataForDB['text'] = (($data['text_type'])==0?'#plaintext#':'').$data['text'];
        $dataForDB['endDate'] = $data['endDate'];
        $dataForDB['sharing'] = $data['share'];
        $dataForDB['startDate'] = $data['startDate'];
        $dataForDB['allDay'] = $data['allDay'];
        $dataForDB['data'] = $data['data'];
        $dataForDB['data']['locations'] = $data['locations'];
        $dataForDB['group'] = $data['group'];
        $dataForDB['subscribeLimit'] = $data['subscribeLimit'];
        $dataForDB['subscribeWPend'] = $data['subscribeWPend'];
        if($dataForDB['allDay'] == 0)
        {
            $dataForDB['allDayStart'] = $data['allDayStart_h'].':'.$data['allDayStart_m'];
            $dataForDB['allDayDur'] = ((empty($data['allDayDur']))?0:$data['allDayDur']).','.((empty($data['allDayDurMin']))?0:$data['allDayDurMin']);
        }
        $dataForDB['repeatType'] = $data['repeat'];
        if($dataForDB['repeatType'] == 1)
        {
            $dataForDB['repeatSpec'] = $data['repeatFrec1'];
            $dataForDB['repeatFrec'] = $data['repeatFrec'];
        } else if($dataForDB['repeatType'] == 2)
        {
            $dataForDB['repeatSpec'] = $data['repeat21']." ".$data['repeat22'];
            $dataForDB['repeatFrec'] = $data['repeatFrec2'];
        }
        //$dataForDB['language'] = $data['language'];
        
        foreach($data['cats'] AS $key=>$val)
        {
            $tmp_name = explode('_', $key, 2);
            $dataForDB['__CATEGORIES__'][$tmp_name[1]] = $val;
        }
        //print_r($dataForDB);exit();
		$schema = pnModGetVar('TimeIt', 'workflow');
        if($this->mode == 'edit')
        {
        	// load object
        	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
              	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
            $object = new $class();
            $obj = $object->getEvent($this->id);
            if(empty(  $obj   ))
            {
                return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }
           	WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events');
            // obj has got a schema?
            if(isset($obj['__WORKFLOW__']['schemaname']) && !empty($obj['__WORKFLOW__']['schemaname']))
            {
            	$schema = $obj['__WORKFLOW__']['schemaname'];
            }
        }
      	WorkflowUtil::executeAction($schema, $dataForDB, $data['wfactions'], 'TimeIt_events');
      	
      	if($this->type == 'user')
      	{
      		$render->pnFormRedirect(pnModURL('TimeIt', 'user'));
      	} else
      	{
      		$render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
      	}
	  } else
	  {
	  	$render->pnFormRedirect(pnModURL('TimeIt', 'user'));
	  }

      return true;
    }
}

function TimeIt_getTranslationForWorkflowActionId($schema, $id)
{
	$array = WorkflowUtil::loadSchema($schema, 'TimeIt');
	$array = $array['actions'];
	
	foreach($array AS $actions)
	{
		foreach($actions AS $action)
		{
			if($action['id'] == $id)
			{
				return pnML($action['title']);
			}
		}
	}
}

function TimeIt_templateWithTheme($render, $template, $theme=false)
{
	if($theme === false)
	{
		$theme = pnModGetVar('TimeIt', 'defaultTemplate', 'default');
	}
	if($render->template_exists(DataUtil::formatForOS($theme).'/'.$template))
	{
		//echo DataUtil::formatForOS($theme).'/'.$template;
		//$render->assign('TiTheme', DataUtil::formatForOS($theme));
		return DataUtil::formatForOS($theme).'/'.$template;
	} else {
		//echo 'default/'.$template;
		//$render->assign('TiTheme', 'default');
		return 'default/'.$template;
	}
}

function TimeIt_adminPermissionCheck($return=false)
{
	if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_MODERATE)) 
    {
        Loader::loadClass('UserUtil');
        $groups = UserUtil::getGroupsForUser(pnUserGetVar('uid'));
        $groups[] = array('name' => 'all', 'gid'=>'all');
        // check each group for permission
        $ret = array();
        foreach ($groups as $group) 
        {
        	if(isset($group['gid']) && $group['gid'] == 'all')
        	{
        		$name = 'all';
        	} else {
        		$group = UserUtil::getPNGroup((int)$group);
        		$name = $group['name'];
        	}
        	if(SecurityUtil::checkPermission( 'TimeIt:Group:', $name."::", ACCESS_MODERATE))
        	{
        		if(!$return)
        		{
        			return true;
        		} else {
        			$ret[] = $group['gid'];
        		}
        	}
        }
    	if(!$return)
       	{
        	return false;
       	} else {
        	return $ret;
        }
    } else 
    {
    	return true;
    }
}

function TimeIt_groupPermissionCheck($obj, $secLevel=ACCESS_READ)
{
	Loader::loadClass('UserUtil');
          
	if($obj['group'] != 'all')
	{
   		$group = UserUtil::getPNGroup((int)$obj['group']);
        $obj['group'] = $group['name'];
    }
        
  	if(SecurityUtil::checkPermission( 'TimeIt:Group:', $obj['group']."::", $secLevel))
    {
    	return true;
    } else 
    {	
   		return false;
    }
}

function TimeIt_getGroupsForSelect()
{
	$array = array();
	
	Loader::loadClass('UserUtil');
    $groups = UserUtil::getGroupsForUser(pnUserGetVar('uid'));
    foreach ($groups as $group) 
    {
    	$groupDB = UserUtil::getPNGroup((int)$group);
    	$array[$group] = $groupDB['name'];
    }
    
    return $array;
}
