<?php

/**
 * initialise the TimeIt module
 */
function TimeIt_init()
{
    // TimeIt_events
    if (!DBUtil::createTable('TimeIt_events')) {
        return false;
    }
    
    // create our default category
    if (!_TimeIt_createdefaultcategory()) {
        return LogUtil::registerError (_CREATEFAILED);
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
    pnModSetVar('TimeIt', 'defaultView', 'month');
 	pnModSetVar('TimeIt', 'itemsPerPage', 25);
    
    return true;
}
 
  
/**
 * upgrade the module from an old version
 */
function TimeIt_upgrade($oldversion)
{
    return true;
}

  
/**
 * delete the TimeIt module
 */
function TimeIt_delete()
{
    DBUtil::dropTable('TimeIt_events');
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
