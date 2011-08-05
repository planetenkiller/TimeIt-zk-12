<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage UI
 */

Loader::requireOnce('modules/TimeIt/common.php');

function TimeIt_admin_main()
{
    // Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    return $pnRender->fetch('TimeIt_admin_main.htm');
}

/*function TimeIt_admin_new()
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
}*/

function TimeIt_admin_translate()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt:Translate:', "::", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }
    
    $render = FormUtil::newpnForm('TimeIt');
    return $render->pnFormExecute('TimeIt_admin_translate.htm', new TimeIt_common_translateHandler());
}

/*function TimeIt_admin_modify($args=array())
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
}*/

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

function TimeIt_admin_viewall()
{
    // Security check
    if (!TimeIt_adminPermissionCheck()) {
        return LogUtil::registerPermissionError();
    }

    $cid = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;

    $pnRender = pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'user', 'countGetAll', array('cid'=>$cid)),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('events', pnModAPIFunc('TimeIt', 'user', 'getAll', array('cid'=>$cid,'startnum'=>$startnum,'numitems'=>$itemsperpage)));
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt','calendar','getAllForDropdown'));
    $pnRender->assign('calendar', $cid);
    return $pnRender->fetch('TimeIt_admin_viewall.htm');
}

function TimeIt_admin_delete()
{
    $objs = FormUtil::getPassedValue('delete', array(), 'POST');
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    
    foreach($objs AS $objid)
    {
        // get event
        $object = new $class();
        $obj = $object->getEvent($objid);

        WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events');
        WorkflowUtil::deleteWorkflow($obj);
    }

    if(FormUtil::getPassedValue('returnto', null, 'GET') == 'viewall') {
        return pnRedirect(pnModURL('TimeIt', 'admin', 'viewall'));
    } else {
        return pnRedirect(pnModURL('TimeIt', 'admin', 'viewpending'));
    }
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
    
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt','calendar','getAll'));
    
    return $pnRender->fetch('TimeIt_admin_import.htm');
}

function TimeIt_admin_doImport()
{
    $what = FormUtil::getPassedValue('btn', null, 'POST');
    $cid = (int)FormUtil::getPassedValue('cid', null, 'POST');
    if($what == 'postcalendar')
    {
        $prefix = FormUtil::getPassedValue('prefix', 'pn', 'POST');
        $pos = strrpos($prefix, "_");
        if(is_int($pos) && $pos)
        {
            $prefix = substr($prefix, 0, $pos);
        }
        pnModAPIFunc('TimeIt', 'import', 'postcalendar', array('prefix'=>$prefix,'cid'=>$cid));
    } else if($what == 'ical')
    {
        $sync = (boolean)FormUtil::getPassedValue('sync', false, 'POST');
        pnModAPIFunc('TimeIt', 'import', 'fromICal', array('sync'=>$sync,'path'=>$_FILES['ics']['tmp_name'],'cid'=>$cid));
    } else if($what == 'postschedule')
    {
        $prefix = FormUtil::getPassedValue('prefix2', 'pn', 'POST');
        $pos = strrpos($prefix, "_");
        if(is_int($pos) && $pos)
        {
            $prefix = substr($prefix, 0, $pos);
        }
        pnModAPIFunc('TimeIt', 'import', 'postschedule', array('prefix'=>$prefix,'cid'=>$cid));
    }
    return pnRedirect(pnModURL('TimeIt', 'admin', 'import'));
}

function TimeIt_admin_calendars()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    
    $page = (int)FormUtil::getPassedValue('page', 1, 'GET');
    $itemsperpage = (int)pnModGetVar('TimeIt', 'itemsPerPage', 25);
    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;
    
    $pnRender = pnRender::getInstance('TimeIt', false);
    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('TimeIt', 'calendar', 'countAll'),
                                     'itemsperpage' => $itemsperpage));
    $pnRender->assign('calendars', pnModAPIFunc('TimeIt', 'calendar', 'getAll', array('startnum'=>$startnum,'numitems'=>$itemsperpage)));
    return $pnRender->fetch('TimeIt_admin_calendars.htm');
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

function TimeIt_admin_calendarsAdd()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    return $render->pnFormExecute('TimeIt_admin_calendarsForm.htm', new TimeIt_admin_CalendarHandler());
}

function TimeIt_admin_calendarsModify()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    return $render->pnFormExecute('TimeIt_admin_calendarsForm.htm', new TimeIt_admin_CalendarHandler());
}

function TimeIt_admin_calendarsDelete()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = FormUtil::newpnForm('TimeIt');
    return $render->pnFormExecute('TimeIt_admin_calendarsDelete.htm', new TimeIt_admin_calendarDeleteHandler());
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
        $calendars = pnModAPIFunc('TimeIt','calendar','getAllForDropdown');
        $render->assign('defaultCalendarItems', $calendars);
        // find calendars with privateCalendar==1
        $calendarsPrivate =array(array('text'=>'-','value'=>0));
        foreach($calendars AS $cal) {
            $calObj = pnModAPIFunc('TimeIt','calendar','get',$cal['value']);
            if($calObj['privateCalendar']) {
                $calendarsPrivate[] = $cal;
            }
        }
        $render->assign('defaultPrivateCalendarItems', $calendarsPrivate);

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
        
        
        $TDays = explode(" ", _DAY_OF_WEEK_LONG);
        $firstWeekDayItems = array(array('text' => $TDays[0] , 'value' => 0),
                                   array('text' => $TDays[1] , 'value' => 1),
                                   array('text' => $TDays[2] , 'value' => 2),
                                   array('text' => $TDays[3] , 'value' => 3),
                                   array('text' => $TDays[4] , 'value' => 4),
                                   array('text' => $TDays[5] , 'value' => 5),
                                   array('text' => $TDays[6] , 'value' => 6));
        $render->assign('firstWeekDayItems', $firstWeekDayItems);
        
        // userdeletion support
        $render->assign('userdeletionModeItems', array(array('text'=>_TIMEIT_USERDELITON_ITEM_ANONYMIZE,'value'=>'anonymize'), 
                                                       array('text'=>_TIMEIT_USERDELITON_ITEM_DELETE,'value'=>'delete')));
        
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

        // formicula integration
        if (pnModAvailable('formicula')) 
        {
            $render->assign('formiculaModuleOk', true);
            $render->assign('formiculaModuleOkReadOnly', false);
        } else
        {
            $render->assign('formiculaModuleOk', false);
            $render->assign('formiculaModuleOkReadOnly', true);
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

                /*if($data['enableMapView'] && !$data['useLocations'] && empty($data['googleMapsApiKey']))
                {
                    $form = &$render->pnFormGetPluginById('googleMapsApiKey');
                    $form->setError(_PNFORM_MANDATORYERROR);
                    return false;
                }*/

                pnModSetVars('TimeIt',$data);
                $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
                
            }
        } else
        {
            $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
        }
    }
}

class TimeIt_admin_CalendarHandler
{
    var $mode;
    var $cid;

    function initialize(&$render)
    {
        $func = FormUtil::getPassedValue('func', null, 'GET');
        if($func == 'calendarsModify')
        {
            $id = FormUtil::getPassedValue('id', false, 'GET');
            if(!$id)
            {
                return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
            }

            $this->mode = 'edit';
            $this->cid = $id;

            $obj = pnModAPIFunc('TimeIt','calendar','get', $id);
            if(empty($obj))
            {
              return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }
            $render->assign($obj);
        } else
        {
            $this->mode = 'add';
        }
        
        $render->assign('mode', $this->mode);
        
        // ContactList integration
        if (pnModAvailable('ContactList')) 
        {
            $render->assign('ContactListModuleOK', true);
        } else 
        {
            $render->assign('ContactListModuleOK', false);
        }


        $config = array();
        $config['workflowItems'] = array(array('text'=>'standard','value'=>'standard'),
                                               array('text'=>'moderate','value'=>'moderate'));
        $config['defaultViewItems'] = array(array('text'=>_MONTH,'value'=>'month'),
                                                  array('text'=>_WEEK,'value'=>'week'),
                                                  array('text'=>_DAY,'value'=>'day'));
        $config['defaultTemplateItems'] = array(array('text'=>'default','value'=>'default'),
                                                      array('text'=>'list','value'=>'list'));
         

        // ContactList integration
        if (pnModAvailable('ContactList'))
        {
            $render->assign('ContactListModuleOK', true);
        } else
        {
            $render->assign('ContactListModuleOK', false);
        }

        // locations integration
        $locationEventPlugins = array(array('text'=>'TimeIt','value'=>'TimeIt'));
        if (pnModAvailable('locations'))
        {
            $render->assign('locationsModuleOK', true);
            $locationEventPlugins[] = array('text'=>'Locations Module','value'=>'Locations');
        } else
        {
            $render->assign('locationsModuleOK', false);
        }

        // formicula integration
        $contactEventPlugins = array(array('text'=>'TimeIt','value'=>'TimeIt'));
        if (pnModAvailable('formicula'))
        {
            $render->assign('formiculaModuleOk', true);
            $render->assign('formiculaModuleOkReadOnly', false);
            $contactEventPlugins[] = array('text'=>'Formicula Module','value'=>'Formicula');
        } else
        {
            $render->assign('formiculaModuleOk', false);
            $render->assign('formiculaModuleOkReadOnly', true);
        }
         // Addressbook integration
        if (pnModAvailable('Addressbook'))
        {
            $render->assign('AddressbookModuleOK', true);
            $contactEventPlugins[] = array('text'=>'Addressbook Module','value'=>'Addressbook');
            $locationEventPlugins[] = array('text'=>'Addressbook Module','value'=>'Addressbook');
        } else
        {
            $render->assign('AddressbookModuleOK', false);
        }

        $config['eventPluginsLocationItems'] = $locationEventPlugins;
        $config['eventPluginsContactItems'] = $contactEventPlugins;

        $render->append('config', $config, true);
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'create')
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

                if($data['config']['subscribeMode'] == 'formicula' && empty($data['config']['formiculaFormId']))
                {
                    $form = &$render->pnFormGetPluginById('formiculaFormId');
                    $form->setError(_PNFORM_MANDATORYERROR);
                    return false;
                }
               
                
                if($this->mode == 'edit')
                {
                    $data['id'] = $this->cid;
                    $ret = pnModAPIFunc('TimeIt','calendar','update',$data);
                    if($ret)
                    {
                        LogUtil::registerStatus (_UPDATESUCCEDED);
                    } else 
                    {
                        LogUtil::registerError(_UPDATEFAILED);
                    }
                } else 
                {
                    $ret = pnModAPIFunc('TimeIt','calendar','create',$data);
                    if($ret)
                    {
                        LogUtil::registerStatus (_CREATESUCCEDED);
                    } else 
                    {
                        LogUtil::registerError(_CREATEFAILED);
                    }
                }
            }
        } 
        $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'calendars'));
    }
}

class TimeIt_admin_calendarDeleteHandler
{
    var $id;
    function initialize(&$render)
    {
        $id = FormUtil::getPassedValue('id', false, 'GET');
        if(!$id)
        {
            return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
        }
        $this->id = $id;
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'delete')
        {
            pnModAPIFunc('TimeIt','calendar','delete',$this->id);
            $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'calendars'));
        } else
        {
            $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'calendars'));
        }
    }
}
