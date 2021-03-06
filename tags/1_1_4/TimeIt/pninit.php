<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

/**
 * initialise the TimeIt module
 */
function TimeIt_init()
{
    // TimeIt_events
    if (!DBUtil::createTable('TimeIt_events')) {
        return false;
    }
    
 	if (!DBUtil::createTable('TimeIt_regs')) {
        return false;
    }
    
    // create our default category
    if (!_TimeIt_createdefaultcategory()) {
        return LogUtil::registerError (_CREATEFAILED);
    }
    
    if(pnModAvailable('PendingContent'))
    {
    	$prefix = pnConfigGetVar('prefix');
    	$sql= "select count(*) 
          from ".$prefix."_TimeIt_events 
          left join ".$prefix."_workflows AS wk on wk.obj_id = ".$prefix."_TimeIt_events.pn_id 
          where wk.module = 'TimeIt' 
          and wk.schemaname = 'moderate' 
          and wk.state = 'waiting'";
    	$obj = array('name'=>'TimeIt Pending Events','url'=>'index.php?module=TimeIt&type=admin&func=viewpending','sql'=>$sql);
    	pnModAPIFunc('PendingContent','admin','create',$obj);
    	
    }
    

    
    pnModSetVar('TimeIt', 'workflow', 'standard');
    pnModSetVar('TimeIt', 'monthtoday', '#FF3300');
    pnModSetVar('TimeIt', 'monthon', '');
    pnModSetVar('TimeIt', 'monthoff', '#d4d2d2');
    pnModSetVar('TimeIt', 'rssatomitems', 20);
    pnModSetVar('TimeIt', 'notifyEvents', 0);
    pnModSetVar('TimeIt', 'notifyEventsEmail', pnUserGetVar('email', 2));
    pnModSetVar('TimeIt', 'privateCalendar', 0);
    pnModSetVar('TimeIt', 'globalCalendar', 1);
    pnModSetVar('TimeIt', 'friendCalendar', 0);
    pnModSetVar('TimeIt', 'defaultView', 'month');
    pnModSetVar('TimeIt', 'defaultTemplate', 'default');
 	pnModSetVar('TimeIt', 'itemsPerPage', 25);
 	pnModSetVar('TimeIt', 'allowSubscribe', 0);
 	pnModSetVar('TimeIt', 'filterByPermission', 0);
 	pnModSetVar('TimeIt', 'popupOnHover', 0);
 	pnModSetVar('TimeIt', 'colorCats', 1);
 	pnModSetVar('TimeIt', 'enableMapView', 0);
 	pnModSetVar('TimeIt', 'googleMapsApiKey', '');
 	pnModSetVar('TimeIt', 'mapViewType', 'googleMaps');
 	pnModSetVar('TimeIt', 'mapHeight', 320);
 	pnModSetVar('TimeIt', 'mapWidth', 480);
 	pnModSetVar('TimeIt', 'colorCatsProp', 'Main');
 	pnModSetVar('TimeIt', 'hideTimeItAddress', 0);
 	pnModSetVar('TimeIt', 'useLocations', 0);
 	pnModSetVar('TimeIt', 'defalutCatColor', 'silver');
    pnModSetVar('TimeIt', 'subscribePending', 0);
    pnModSetVar('TimeIt', 'subscribeLimit', 0);
    pnModSetVar('TimeIt', 'truncateTitle', 25);
    pnModSetVar('TimeIt', 'enablecategorization', 1);
    pnModSetVar('TimeIt', 'userCanEditHisEvents', 0);
    
    return true;
}
 
  
/**
 * upgrade the module from an old version
 */
function TimeIt_upgrade($oldversion)
{
    switch ($oldversion)	
    {
    	case '1.1':
    		// We upadate all records with a invalid sharing or group.
    		$prefix = pnConfigGetVar('prefix');
    		$sql1 = "UPDATE {$prefix}_TimeIt_events SET pn_sharing = 3 WHERE pn_sharing = 0";
    		$sql2 = "UPDATE {$prefix}_TimeIt_events SET pn_group = 'all' WHERE pn_group = ''";
    		DBUtil::executeSQL($sql1);
    		DBUtil::executeSQL($sql2);
    		
    		// set new module var
    		pnModSetVar('TimeIt', 'popupOnHover', 0);
    	case '1.1.1':
    		pnModSetVar('TimeIt', 'colorCats', 1);
    		pnModSetVar('TimeIt', 'enableMapView', 0);
 			pnModSetVar('TimeIt', 'googleMapsApiKey', '');
 			pnModSetVar('TimeIt', 'mapViewType', 'googleMaps');
 			pnModSetVar('TimeIt', 'mapHeight', 320);
 			pnModSetVar('TimeIt', 'mapWidth', 480);
 			pnModSetVar('TimeIt', 'colorCatsProp', 'Main');
 			pnModSetVar('TimeIt', 'hideTimeItAddress', 0);
 			pnModSetVar('TimeIt', 'useLocations', 0);
 			pnModSetVar('TimeIt', 'defalutCatColor', 'silver');
    	case '1.1.2':
    		pnModSetVar('TimeIt', 'subscribePending', 0);
    		pnModSetVar('TimeIt', 'subscribeLimit', 0);
		    if(pnModAvailable('PendingContent'))
		    {
		    	$prefix = pnConfigGetVar('prefix');
		    	$sql= "select count(*) 
		          from ".$prefix."_TimeIt_events 
		          left join ".$prefix."_workflows on ".$prefix."_workflows.obj_id = ".$prefix."_TimeIt_events.pn_id 
		          where ".$prefix."_workflows.module = 'TimeIt' 
		          and ".$prefix."_workflows.schemaname = 'moderate' 
		          and ".$prefix."_workflows.state = 'waiting'";
		    	$obj = array('name'=>'TimeIt Pending Events','url'=>'index.php?module=TimeIt&type=admin&func=viewpending','sql'=>$sql);
		    	pnModAPIFunc('PendingContent','admin','create',$obj);
		    }
    	case '1.1.3':
    		pnModSetVar('TimeIt', 'truncateTitle', 25);
    		pnModSetVar('TimeIt', 'enablecategorization', 1);
    		pnModSetVar('TimeIt', 'userCanEditHisEvents', 0);
	}
	
	return true;
}

  
/**
 * delete the TimeIt module
 */
function TimeIt_delete()
{
    DBUtil::dropTable('TimeIt_events');
    DBUtil::dropTable('TimeIt_regs');
    pnModDelVar('pnTimeIt');
    WorkflowUtil::deleteWorkflowsForModule('TimeIt');
    pnModCallHooks('module', 'remove' ,'');
    
    // Delete entries from category registry 
    pnModDBInfoLoad ('Categories');
    Loader::loadArrayClassFromModule('Categories', 'CategoryRegistry');
    $registry = new PNCategoryRegistryArray();
    $registry->deleteWhere ('crg_modname=\'TimeIt\'');
 
    return true;
}

function _TimeIt_createdefaultcategory()
{
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');
    
    $rootcat    = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules/Global');
    
    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname', 'TimeIt');
    $registry->setDataField('table', 'TimeIt_events');
    $registry->setDataField('property', 'Main');
    $registry->setDataField('category_id', $rootcat['id']);
    $registry->insert();
    
    return true;
}
