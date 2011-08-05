<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

Loader::includeOnce('modules/TimeIt/common.php');

function TimeIt_admin_main()
{
	// Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    
	$pnRender = pnRender::getInstance('TimeIt', false);
	return $pnRender->fetch('TimeIt_admin_main.htm');
}

function TimeIt_admin_new()
{
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }
    
    if(pnModGetVar('TimeIt', 'enableMapView'))
    {
		PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.pnModGetVar('TimeIt', 'googleMapsApiKey'));
    }
    
	PageUtil::addVar("rawtext", "<style type=\"text/css\"> form#pnFormForm span { margin-left:1em; } input.error, textarea.error  { border-color:red; } 
    select#repeat21, select#repeat22, select#repeatFrec1, input#repeatFrec2, select#allDayStart_m{ margin-left:0em; }
    </style>");
    $render = FormUtil::newpnForm('TimeIt');
    $render->assign('adminMode', $type=='admin'?true:false);
    return $render->pnFormExecute('TimeIt_admin_new.htm', new TimeIt_common_createHandler('user'));
}

function TimeIt_admin_modify($args=array())
{
	if(pnModGetVar('TimeIt', 'enableMapView'))
    {
		PageUtil::addVar('javascript', 'http://maps.google.com/maps?file=api&v=2&key='.pnModGetVar('TimeIt', 'googleMapsApiKey'));
    }
	
	PageUtil::addVar("rawtext", "<style type=\"text/css\"> form#pnFormForm span { margin-left:1em; } input.error, textarea.error  { border-color:red; } 
    select#repeat21, select#repeat22, select#repeatFrec1, input#repeatFrec2, select#allDayStart_m{ margin-left:0em; }
    </style>");
    $render = FormUtil::newpnForm('TimeIt');
    $render->assign('adminMode', $type=='admin'?true:false);
    return $render->pnFormExecute('TimeIt_admin_new.htm', new TimeIt_common_createHandler('user'));
}

function TimeIt_admin_viewpending()
{
	// Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
	$pnRender = pnRender::getInstance('TimeIt', false);
	// Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countPendingEvents'),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', pnModAPIFunc('TimeIt', 'user', 'pendingEvents', array('startnum'=>$startnum,'numitems'=>$itemsperpage)));
	
	return $pnRender->fetch('TimeIt_admin_viewpending.htm');
}

function TimeIt_admin_viewhidden()
{
	// Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
	$pnRender = pnRender::getInstance('TimeIt', false);
	// Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countHiddenEvents'),
                                     'itemsperpage' => $itemsperpage));
	$pnRender->assign('events', pnModAPIFunc('TimeIt', 'user', 'hiddenEvents', array('startnum'=>$startnum,'numitems'=>$itemsperpage)));
	return $pnRender->fetch('TimeIt_admin_viewhidden.htm');
}

function TimeIt_admin_import()
{
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
	
	$pnRender = pnRender::getInstance('TimeIt', false);
	return $pnRender->fetch('TimeIt_admin_import.htm');
}

function TimeIt_admin_doImport()
{
	$what = FormUtil::getPassedValue('btn', null, 'POST');
	if($what == 'postcalendar')
	{
		$prefix = FormUtil::getPassedValue('prefix', 'pn', 'POST');
		pnModAPIFunc('TimeIt', 'import', 'postcalendar', $prefix);
	} else if($what == 'ical')
	{
		pnModAPIFunc('TimeIt', 'import', 'fromICal', $_FILES['ics']['tmp_name']);
	} else if($what == 'postschedule')
	{
		$prefix = FormUtil::getPassedValue('prefix', 'pn', 'POST');
		pnModAPIFunc('TimeIt', 'import', 'postschedule', $prefix);
	}
    return pnRedirect(pnModURL('TimeIt', 'admin', 'import'));
}


function TimeIt_admin_modifyconfig()
{
	// Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
	
	$render = FormUtil::newpnForm('TimeIt', false);
	return $render->pnFormExecute('TimeIt_admin_modifyconfig.htm', new Timeit_admin_modifyconfigHandler());									     
}

class Timeit_admin_modifyconfigHandler
{
	function initialize(&$render)
	{
		$render->assign('workflowItems', array(array('text'=>'standard','value'=>'standard'),
										         array('text'=>'moderate','value'=>'moderate')));
		$render->assign('defaultViewItems', array(array('text'=>_MONTH,'value'=>'month'),
										          array('text'=>_WEEK,'value'=>'week'),
										          array('text'=>_DAY,'value'=>'day')));
		$render->assign('defaultTemplateItems', array(array('text'=>'default','value'=>'default'),
										         	  array('text'=>'list','value'=>'list')));
		$mapViewType = array(array('text'=>'Google Maps(TimeIt)','value'=>'googleMaps'));							         	  
		if (pnModAvailable('MyMap')) 
        {
        	$mapViewType[] = array('text'=>'MyMap','value'=>'mymap');
        	$render->assign('MyMapModuleOk', true);
        } else
        {
        	$render->assign('MyMapModuleOk', false);
        }
        $render->assign('mapViewTypeItems', $mapViewType);
        
        
		// load the categories system
        if (!($class = Loader::loadClass('CategoryRegistryUtil')))
            pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
        if (!($class = Loader::loadClass('CategoryUtil')))
            pn_exit ('Unable to load class [CategoryUtil] ...');
        $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
        $cats = array();
        foreach ($categories AS $property => $cid)
        {
        	$cat = CategoryUtil::getCategoryByID($cid);
        	$cats[] = array('value'=>$property,'text'=>(isset($cat['display_name'][pnUserGetLang()]))?$cat['display_name'][pnUserGetLang()]:$cat['name']);
        }
        
        $render->assign('colorCatsPropItems', $cats);
        
		// scribite! integration
        if (pnModAvailable('scribite')) 
        {
       		$editors = pnModAPIFunc('scribite','user','getEditors',array('editorname' => 'list'));// get editors
       		$editorsConverted = array();
       		// convert to pnform compitable format
       		foreach ($editors AS $key=>$value)
       		{
       			$editorsConverted[] = array('text' => $key , 'value' => $value);
       		}
       		$render->assign('scribiteEditorItems', $editorsConverted);
		} else 
		{
			$render->assign('scribiteEditorItems', false);
		}	

		// ContactList integration
        if (pnModAvailable('ContactList')) 
        {
        	$render->assign('ContactListModuleOK', true);
        } else 
        {
        	$render->assign('ContactListModuleOK', false);
        }
        
		// locations integration
        if (pnModAvailable('locations')) 
        {
        	$render->assign('locationsModuleOK', true);
        } else 
        {
        	$render->assign('locationsModuleOK', false);
        }
										          
		$render->assign(pnModGetVar('TimeIt'));
	}
	
	function handleCommand(&$render, &$args)
	{
		
    	if ($args['commandName'] == 'update')
      	{
        	if (!$render->pnFormIsValid())
        	{
        		return false;
        	} else
        	{
        		$data = $render->pnFormGetValues();
        		if($data['privateCalendar'] == false && $data['globalCalendar'] == false && $data['friendCalendar'] == false)
        		{
        			return LogUtil::registerError(_TIMEIT_ERROR_2);
        		} 
        		
        		if($data['enableMapView'] && !$data['useLocations'] && empty($data['googleMapsApiKey']))
        		{
        			$form = &$render->pnFormGetPluginById('googleMapsApiKey');
        			$form->setError(_PNFORM_MANDATORYERROR);
        			return false;
        		}
        		
        		
        		if($data['scribiteEditor'] == "-")
        		{
        			$data['scribiteEditor'] = '';
        		}
        		pnModSetVars('TimeIt',$data);
        		$render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
        		
        	}
      	} else
      	{
      		$render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
      	}
	}
}
