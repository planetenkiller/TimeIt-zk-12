<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Core
 */

/**
 * initialise the TimeIt module
 */
function TimeIt_init()
{
    // check for php5
    if (version_compare(PHP_VERSION, '5.0.0') < 0) {
        return LogUtil::registerError (_TIMEIT_INIT_PHP.'('.PHP_VERSION.' > 5.0.0)');
    }

    // TimeIt_events
    if (!DBUtil::createTable('TimeIt_events')) {
        return false;
    }
    
    if (!DBUtil::createTable('TimeIt_regs')) {
        return false;
    }
    
    // TimeIt_calendars
    if (!DBUtil::createTable('TimeIt_calendars')) {
        return false;
    }

    if (!DBUtil::createTable('TimeIt_date_has_events')) {
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

    
    
    // insert default calender
    $obj = array('name'=>'Default','desc' => 'Default Calendar',
                  'config'=>serialize(array('workflow'            => 'standard',
                                  'userCanEditHisEvents'=> false,
                                  'defaultView'         => 'month',
                                  'defaultTemplate'     => 'default',
                                  'useLocations'        => false,
                                  'enableMapView'       => false,
                                  'allowSubscribe'      => false,
                                  'subscribeMode'       => 'timeit',
                                  'allowSubscribeDelete'=> false,
                                  'subscribeLimit'      => 0,
                                  'subscribePending'    => false,
                                  'formiculaFormId'     => 10,
                                  'eventPluginsLocation'=> array('TimeIt'),
                                  'eventPluginsContact' => array('TimeIt')
                                 )),
                  'privateCalendar' => 0,
                  'globalCalendar'  => 1,
                  'friendCalendar'  => 0);
    if(false === DBUtil::insertObject($obj,'TimeIt_calendars'))
    {
        return false;
    }

    
    //pnModSetVar('TimeIt', 'workflow', 'standard');
    pnModSetVar('TimeIt', 'monthtoday', '#FF3300');
    pnModSetVar('TimeIt', 'monthon', '');
    pnModSetVar('TimeIt', 'monthoff', '#d4d2d2');
    pnModSetVar('TimeIt', 'rssatomitems', 20);
    pnModSetVar('TimeIt', 'notifyEvents', 0);
    pnModSetVar('TimeIt', 'notifyEventsEmail', pnUserGetVar('email', 2));
    //pnModSetVar('TimeIt', 'privateCalendar', 0);
    //pnModSetVar('TimeIt', 'globalCalendar', 1);
    //pnModSetVar('TimeIt', 'friendCalendar', 0);
    //pnModSetVar('TimeIt', 'defaultView', 'month');
    //pnModSetVar('TimeIt', 'defaultTemplate', 'default');
    pnModSetVar('TimeIt', 'itemsPerPage', 25);
    //pnModSetVar('TimeIt', 'allowSubscribe', 0);
    pnModSetVar('TimeIt', 'filterByPermission', 0);
    pnModSetVar('TimeIt', 'popupOnHover', 0);
    pnModSetVar('TimeIt', 'colorCats', 1);
    //pnModSetVar('TimeIt', 'enableMapView', 0);
    pnModSetVar('TimeIt', 'googleMapsApiKey', '');
    pnModSetVar('TimeIt', 'mapViewType', 'googleMaps');
    pnModSetVar('TimeIt', 'mapHeight', 320);
    pnModSetVar('TimeIt', 'mapWidth', 480);
    pnModSetVar('TimeIt', 'colorCatsProp', 'Main');
    pnModSetVar('TimeIt', 'hideTimeItAddress', 0);
    //pnModSetVar('TimeIt', 'useLocations', 0);
    pnModSetVar('TimeIt', 'defaultCalendar', 1);
    pnModSetVar('TimeIt', 'firstWeekDay', 1);
    pnModSetVar('TimeIt', 'defalutCatColor', 'silver');
    //pnModSetVar('TimeIt', 'subscribePending', 0);
    //pnModSetVar('TimeIt', 'subscribeLimit', 0);
    pnModSetVar('TimeIt', 'truncateTitle', 30);
    pnModSetVar('TimeIt', 'enablecategorization', 1);
    //pnModSetVar('TimeIt', 'userCanEditHisEvents', 0);
    pnModSetVar('TimeIt', 'userdeletionMode', 'anonymize'); // or delete
    //pnModSetVar('TimeIt', 'subscribeMode', 'timeit');
    //pnModSetVar('TimeIt', 'formiculaFormId', 10);
    pnModSetVar('TimeIt', 'dateformat', _DATEBRIEF);
    pnModSetVar('TimeIt', 'defaultPrivateCalendar', 0);
    pnModSetVar('TimeIt', 'sortMode', 'byname'); // or bysortvalue
    
    return true;
}
 
  
/**
 * upgrade the module from an old version
 */
function TimeIt_upgrade($oldversion)
{
    if (version_compare(PHP_VERSION, '5.0.0', '>') < 0) {
        return LogUtil::registerError (_TIMEIT_INIT_PHP);
    }

    switch($oldversion)
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
            pnModSetVar('TimeIt', 'truncateTitle', 30);
            pnModSetVar('TimeIt', 'enablecategorization', 1);
            pnModSetVar('TimeIt', 'userCanEditHisEvents', 0);
        case '1.1.4':
            // nothing to do
        case '1.1.5':
            // nothing to do 
        case '1.1.6':
                // upgrade events table
                if (!DBUtil::changeTable('TimeIt_events')) {
                    return false;
                }

                // TimeIt_calendars
                if (!DBUtil::createTable('TimeIt_calendars')) {
                    return false;
                }

                if (!DBUtil::createTable('TimeIt_date_has_events')) {
                    return false;
                }

                // remove old indexes
                DBUtil::dropIndex('startDate', 'TimeIt_events');
                DBUtil::dropIndex('endDate', 'TimeIt_events');
                DBUtil::dropIndex('status', 'TimeIt_events');
                DBUtil::dropIndex('sharing', 'TimeIt_events');

                // create default calendar with actual configuration
                $obj = array( 'name'=>'Default','desc' => 'Default Calendar',
                              'config'=>serialize(array('workflow'            => pnModGetVar('TimeIt', 'workflow'),
                                              'userCanEditHisEvents'=> pnModGetVar('TimeIt', 'userCanEditHisEvents'),
                                              'defaultView'         => pnModGetVar('TimeIt', 'defaultView'),
                                              'defaultTemplate'     => pnModGetVar('TimeIt', 'defaultTemplate'),
                                              'useLocations'        => pnModGetVar('TimeIt', 'useLocations'),
                                              'enableMapView'       => pnModGetVar('TimeIt', 'enableMapView'),
                                              'allowSubscribe'      => pnModGetVar('TimeIt', 'allowSubscribe'),
                                              'subscribeMode'       => 'timeit',
                                              'allowSubscribeDelete'=> pnModGetVar('TimeIt', 'allowSubscribeDelete'),
                                              'subscribeLimit'      => pnModGetVar('TimeIt', 'subscribeLimit'),
                                              'subscribePending'    => pnModGetVar('TimeIt', 'subscribePending'),
                                              'formiculaFormId'     => 10,
                                              'eventPluginsLocation'=> array_merge(array(), pnModGetVar('TimeIt','useLocations')?array('Locations'):array('TimeIt'), pnModGetVar('TimeIt','hideTimeItAddress')?array():array('TimeIt')),
                                              'eventPluginsContact' => array('TimeIt')
                                             )),
                              'privateCalendar' => pnModGetVar('TimeIt', 'privateCalendar'),
                              'globalCalendar'  => pnModGetVar('TimeIt', 'globalCalendar'),
                              'friendCalendar'  => pnModGetVar('TimeIt', 'friendCalendar'));
                // insert default calender
                if(false === DBUtil::insertObject($obj,'TimeIt_calendars')) {
                    return false;
                }
                
                // new var
                pnModSetVar('TimeIt', 'defaultPrivateCalendar', pnModGetVar('TimeIt', 'privateCalendar')? 1 : 0);

                // delete old vars
                pnModDelVar('TimeIt', 'workflow');
                pnModDelVar('TimeIt', 'userCanEditHisEvents');
                pnModDelVar('TimeIt', 'defaultView');
                pnModDelVar('TimeIt', 'defaultTemplate');
                pnModDelVar('TimeIt', 'useLocations');
                pnModDelVar('TimeIt', 'enableMapView');
                pnModDelVar('TimeIt', 'allowSubscribe');
                pnModDelVar('TimeIt', 'subscribeMode');
                pnModDelVar('TimeIt', 'subscribeLimit');
                pnModDelVar('TimeIt', 'subscribePending');
                pnModDelVar('TimeIt', 'formiculaFormId');
                pnModDelVar('TimeIt', 'privateCalendar');
                pnModDelVar('TimeIt', 'globalCalendar');
                pnModDelVar('TimeIt', 'friendCalendar');

                // new vars
                pnModSetVar('TimeIt', 'dateformat', _DATEBRIEF);
                pnModSetVar('TimeIt', 'defaultCalendar', 1);
                pnModSetVar('TimeIt', 'firstWeekDay', 1);
                pnModSetVar('TimeIt', 'userdeletionMode', 'anonymize');
                pnModSetVar('TimeIt', 'sortMode', 'byname'); // or bysortvalue
                

                // 30 seconds aren't maybe enough.
                ini_set('max_execution_time' , 3600);
                Loader::loadFile('pnuserapi.php', 'modules/TimeIt');
                Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
                Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');


                // update all events. We do the work in packages of 100 events because
                // a big TimeIt_events table causes memory problems.
                $size = DBUtil::selectObjectCount('TimeIt_events');
                for($offset = 0; $offset < $size; $offset+=100) {
                    $array = DBUtil::selectObjectArray('TimeIt_events','','', $offset, 100);
                    foreach($array AS $obj) {
                        $obj['cid'] = 1;
                        $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $obj);
                        $prozi->doCalculation();
                        
                        // update subscriptions
                        // We can't use pnModAPIFunc here because the module isn't active yet.
                        // We include the file and call the function manualy.
                        Loader::loadFile('pnuser.php','modules/TimeIt');
                        $dheobj = Timeit_userapi_getDHE(array('obj'=>$obj));
                        $upobj = array('eid'=>$dheobj['id']);
                        DBUtil::updateObject($upobj, 'TimeIt_regs', 'pn_eid = '.((int)$obj['id']));
                    }
                }

        case '2.0':
            // upgrade TimeIt_regs table
            if (!DBUtil::changeTable('TimeIt_regs')) {
                return false;
            }
        case '2.1':
            // nothing to do
    }
    
    // clear compiled and cached templates
    pnModAPIFunc('pnRender', 'user', 'clear_compiled');
    pnModAPIFunc('pnRender', 'user', 'clear_cache', array('module'=>'TimeIt'));
    
    return true;
}

  
/**
 * delete the TimeIt module
 */
function TimeIt_delete()
{
    DBUtil::dropTable('TimeIt_events');
    DBUtil::dropTable('TimeIt_regs');
    DBUtil::dropTable('TimeIt_calendars');
    DBUtil::dropTable('TimeIt_date_has_events');
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
