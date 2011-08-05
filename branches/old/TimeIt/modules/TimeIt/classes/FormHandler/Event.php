<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage FormHandler
 */

/**
 * Form handler for event creation and manipulation.
 */
class Timeit_FormHandler_event
{
    var $id;
    var $cid;
    var $mode;
    var $type;
    var $recurrenceOnly;
    var $returnTo;

    function Timeit_common_createHandler($type='user')
    {
        $this->type = $type;
        $this->recurrenceOnly = false;
    }

    function initialize(&$render)
    {
        $this->cid = (int)FormUtil::getPassedValue('cid', pnModGetVar('TimeIt', 'defaultCalendar'), 'GETPOST');
        $this->recurrenceOnly = FormUtil::getPassedValue('recurrenceOnly', false, 'GET');
        $dheid = FormUtil::getPassedValue('dheid', null, 'GET');
        $GETCOPY = FormUtil::getPassedValue('copy', null, 'GET');
        $copyMode = false;
        $returnTo = FormUtil::getPassedValue('returnTo', null, 'GET');
        if($returnTo == 'adminPending' || $returnTo == 'eventDetail' || $returnTo == 'adminviewall') {
            $this->returnTo = $returnTo;
        }

        if(FormUtil::getPassedValue('func', 'new', 'GET') == "modify") {
            $this->mode = "edit";
            if(($this->id=FormUtil::getPassedValue('eventid', null, 'GET'))==null) {
                return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
            }

            $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$this->id));
            if(empty($obj)) {
                return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }

            if($obj['group'] == 'all') {
                $groupobj = array('name'=>'all'); // group irrelevant
            } else {
                $groupobj = UserUtil::getPNGroup((int)$obj['group']);
            }

            if($dheid) {
                $dheobj = pnModAPIFunc('TimeIt','user','getDHE',array('dheid'=>$dheid));
            } else {
                $dheobj = pnModAPIFunc('TimeIt','user','getDHE',array('obj'=>$obj));
            }

            // load local event if there is already one and the user want to edit a occurrence
            if($this->recurrenceOnly && $dheobj['localeid']) {
                $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$dheobj['localeid']));
                $this->id = $dheobj['localeid'];
            } else if($this->recurrenceOnly && !$dheobj) {
                return LogUtil::registerError(_MODARGSERROR, 404);
            }

            if($this->recurrenceOnly == false && $dheobj && $dheobj['eid'] != $obj['id']) {
                return LogUtil::registerError(_MODARGSERROR, 404);
            } else if($this->recurrenceOnly == false && $dheobj && $dheobj['eid'] == $obj['id']) {
                $this->cid = (int)$dheobj['cid'];
                $obj['cid'] = $this->cid;
            } else if($this->recurrenceOnly == false && !$dheobj) {
                if(isset($obj['data']['cid'])) {
                    $this->cid = (int)$obj['data']['cid'];
                } else {
                    // backword compatibility: events in pending state that has been created befor 2.0 do not contain data.cid.
                    // We use the standard calendar in $this->cid.
                    $obj['data']['cid'] = $this->cid;
                }
            }

           
            $calendar = pnModAPIFunc('TimeIt','calendar','get', $this->cid);
            // Security check
            if (!SecurityUtil::checkPermission( 'TimeIt::', "::", ACCESS_MODERATE) &&
                !SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", ACCESS_MODERATE) &&
                !($calendar['userCanEditHisEvents'] && $obj['cr_uid'] = pnUserGetVar('uid',-1,1)))
            {
                return LogUtil::registerPermissionError();
            }

            //$masterEvent = pnModAPIFunc('TimeIt','user','getMasterEvent',array('mid'=>$obj['mid']));
           // $render->assign('masterEvent', $masterEvent);
            $render->assign('event', $obj);
            $this->setDefaultData($render, $obj, $calendar);

            if($this->recurrenceOnly) {
                $render->assign('startDate', $dheobj['date']);
                $render->assign('endDate', $dheobj['date']);
            }

            // get all possible actions for this object in whatever workflow state it's in
            $actions = WorkflowUtil::getActionsForObject($obj, 'TimeIt_events');
            $wfSchemaName = $obj['__WORKFLOW__']['schemaname'];
        } else {
            $this->mode = "create";
            $calendar = pnModAPIFunc('TimeIt','calendar','get', $this->cid);
            $render->assign('allDay', 1);
            $render->assign('repeat', 0);
            $render->assign('text_type', 0);
            $render->assign('subscribeLimit', $calendar['subscribeLimit']);
            $render->assign('subscribeWPend', $calendar['subscribePending']);
            $render->assign('formicula_contact','choose');
            $render->append('data', array('eventplugin_contact'=>'Contact'.$calendar['eventPluginsContact'][0]), true);
            $render->append('data', array('eventplugin_location'=>'Location'.$calendar['eventPluginsLocation'][0]), true);

            $tobj = array();

            // set workflow schema
            $wfSchemaName = $calendar['workflow'];
            if(!$wfSchemaName) {
                $wfSchemaName = 'standard';
            }

            // get workflow actions
            $actions = WorkflowUtil::getActionsByState($wfSchemaName);
            
            // prefill start and end dates
            $T_date = FormUtil::getPassedValue('date', false, 'GET');
            if($T_date) {
                $render->assign('startDate', $T_date);
                $render->assign('endDate', $T_date);
            }

            if($GETCOPY && (int)$GETCOPY > 0) {
                if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                    pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
                }
                $object = new $class();
                $obj = $object->getEvent($GETCOPY);
                $obj['cid'] = $this->cid;
                // delete uncopyable data
                if(!empty($obj)) {
                    $copyMode = true;
                    unset($obj['id']);

                    $this->setDefaultData($render, $obj, $calendar, true);
                }
            }
        }

        $eventPlugins = $this->getEventPlugins($obj, $render, false, $copyMode);
        $allowedLocationPlugins = $calendar['eventPluginsLocation'];
        foreach($eventPlugins['location'] AS $pos => $plg) {
            $plg = $plg['name'];
            if(strpos($plg, 'Location') !== false && !in_array(TimeIt_Filter::str_replace_once('Location', '', $plg), $allowedLocationPlugins)) {
                unset($eventPlugins['location'][$pos]);
            }
        }
        $allowedContactPlugins = $calendar['eventPluginsContact'];
        foreach($eventPlugins['contact'] AS $pos => $plg) {
            $plg = $plg['name'];
            if(strpos($plg, 'Contact') !== false && !in_array(TimeIt_Filter::str_replace_once('Contact', '', $plg), $allowedContactPlugins)) {
                unset($eventPlugins['contact'][$pos]);
            }
        }
        $render->assign('eventPlugins', $eventPlugins);

        // scribite! integration
        if (pnModAvailable('scribite') && pnModGetVar('TimeIt', 'scribiteEditor') != '-') {
            // load editor
            $scribite = pnModFunc('scribite','user','loader', array('modname' => 'TimeIt',
                                                                    'editor'  => pnModGetVar('TimeIt', 'scribiteEditor'),
                                                                    'areas'   => array('text')
                                                                    /*'tpl'     => $args['areas']*/));
            PageUtil::AddVar('rawtext', $scribite);
            $render->assign('text_type', 1);
        }

        // make pnformdropdownlist compitable array
        $wfactionsItems = array();
        foreach($actions AS $id) {
            $wfactionsItems[] = array('text' => TimeIt_getTranslationForWorkflowActionId($wfSchemaName, $id), 'value' => $id);
        }
        $render->assign('wfactionsItems', $wfactionsItems);
        $render->assign('wfactionsItemsCount', count($wfactionsItems));


        // is categorization enabled?
        if(pnModGetVar('TimeIt', 'enablecategorization')) {
            // load the categories system
            if (!($class = Loader::loadClass('CategoryRegistryUtil')))
                pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
            if (!($class = Loader::loadClass('CategoryUtil')))
                pn_exit ('Unable to load class [CategoryUtil] ...');
            $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
            foreach ($categories AS $property => $cid) {
                $cat = CategoryUtil::getCategoryByID($cid);
                $categories[$property] = array();
                $categories[$property]['id'] = $cat['id'];
                $categories[$property]['name'] = (isset($cat['display_name'][pnUserGetLang()]))?$cat['display_name'][pnUserGetLang()]:$cat['name'];
            }

            $render->assign('categories', $categories);
        }

        // create array with hours
        $hours = array();
        for($i=0;$i<24;$i++) {
            $hours[] = array('text' => $i, 'value' => $i);
        }

        // create array with minutes
        $mins = array();
        for($i=0;$i<60;$i++) {
            $value = ($i<10)? '0'.$i: $i;
            $mins[] = array('text' => $value, 'value' => $value);
        }
        // arrays for some pnformdropdownlists
        $share = array();
        // priate calendar allowed?
        if($calendar['privateCalendar']) {
            $share[] = array('text' => _TIMEIT_SHARING_PRIVATE , 'value' => '1');
            $share[] = array('text' => _TIMEIT_SHARING_PUBLIC ,  'value' => '2');
        }
        if($calendar['globalCalendar']) {
            $share[] = array('text' => _TIMEIT_SHARING_GLOBAL ,  'value' => '3');
        }
        if($calendar['friendCalendar']) {
            $share[] = array('text' => _TIMEIT_SHARING_FRIENDSONLY ,  'value' => '4');
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

        $repeat21 = array(array('text' => _TIMEIT_MONTH_FIRST , 'value' => 1),
                          array('text' => _TIMEIT_MONTH_SECOND , 'value' => 2),
                          array('text' => _TIMEIT_MONTH_THIRD , 'value' => 3),
                          array('text' => _TIMEIT_MONTH_FOURTH , 'value' => 4),
                          array('text' => _TIMEIT_MONTH_LAST , 'value' => 5));

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
            $groupsConverted[] = array('text' => 'all', 'value' => 'all');
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

        $render->assign('recurrenceOnly', $this->recurrenceOnly);
        $render->assign('groupItems', $groupsConverted);
        $render->assign('mode', $this->mode);
        $render->assign('modifyAll', true);
        $render->assign('allDayStart_hItems', $hours);
        $render->assign('allDayStart_mItems', $mins);
        $render->assign('shareItems', $share);
        $render->assign('repeatFrec1Items', $repeatFrec1);
        $render->assign('repeat21Items', $repeat21);
        $render->assign('repeat22Items', $repeat22);
        $render->assign('calendar', $calendar);

        return true;
    }

    function setDefaultData(&$render, &$obj, $calendar, $copyMode=false)
    {
        $render->assign('title', $obj['title']);
        if(!$copyMode) {
            $render->assign('group', $obj['group']);
        }
        //$render->assign('locations', (int)$obj['data']['locations']);
        $render->assign('subscribeWPend', $obj['subscribeWPend']);
        $render->assign('subscribeLimit', $obj['subscribeLimit']);

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
            $render->assign('repeat21',  explode(',',$temp[0]));
            $render->assign('repeat22', $temp[1]);
        } else if($obj['repeatType']==3)
        {
            $render->assign('repeat3Dates', $obj['repeatSpec']);
        }
        $render->assign('noReapeats', $obj['repeatIrg']);

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

        $render->assign('endDate', $obj['endDate']);
        $render->assign('startDate', $obj['startDate']);

        $render->append('data', array('eventplugin_contact'=>!empty($obj['data']['eventplugin_contact'])? $obj['data']['eventplugin_contact'] : 'Contact'.$calendar['eventPluginsContact'][0]), true);
        $render->append('data', array('eventplugin_location'=>!empty($obj['data']['eventplugin_location'])? $obj['data']['eventplugin_location'] : 'Location'.$calendar['eventPluginsLocation'][0]), true);
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
        } else if((int)$data['repeat'] == 2 && empty($data['repeat21']))
        {
            $p_repeatFrec2 = &$render->pnFormGetPluginById('repeat21');
            $p_repeatFrec2->setError(_PNFORM_MANDATORYERROR);
            $valid = false;
        } else if((int)$data['repeat'] == 3 && empty($data['repeat3Dates']))
        {
            $p_repeatFrec2 = &$render->pnFormGetPluginById('repeat3Dates');
            $p_repeatFrec2->setError(_PNFORM_MANDATORYERROR);
            $valid = false;
        }

        /*if($data['formicula_contact'] == 'choose' && empty($data['formiculaContact']))
        {
            $p_formiculaContact = &$render->pnFormGetPluginById('formiculaContact');
            $p_formiculaContact->setError(_PNFORM_MANDATORYERROR);
            $valid = false;
        }*/

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
        // set default eventplugin for contact because this avoids problems with the function TimeIt_decorateWitEventPlugins().
        if(!isset($dataForDB['data']['eventplugin_contact'])) {
            $dataForDB['data']['eventplugin_contact'] = 'ContactTimeIt';
            $dataForDB['data']['plugindata']['ContactTimeIt'] = array();
        }
        if(!isset($dataForDB['data']['eventplugin_location'])) {
            $dataForDB['data']['eventplugin_location'] = 'LocationLocations';
            $dataForDB['data']['plugindata']['LocationLocations'] = array();
        }
        $dataForDB['group'] = $data['group'];
        $dataForDB['subscribeLimit'] = $data['subscribeLimit'];
        $dataForDB['subscribeWPend'] = $data['subscribeWPend'];
        if($dataForDB['allDay'] == 0)
        {
            $dataForDB['allDayStart'] = $data['allDayStart_h'].':'.$data['allDayStart_m'];
            $dataForDB['allDayDur'] = ((empty($data['allDayDur']))?0:$data['allDayDur']).','.((empty($data['allDayDurMin']))?0:$data['allDayDurMin']);
        }

        // If the user edits a recurrence he can't change the repeat of the event.
        if(!$this->recurrenceOnly)
        {
            $dataForDB['repeatType'] = $data['repeat'];
            if($dataForDB['repeatType'] == 1)
            {
                $dataForDB['repeatSpec'] = $data['repeatFrec1'];
                $dataForDB['repeatFrec'] = $data['repeatFrec'];
            } else if($dataForDB['repeatType'] == 2)
            {
                $dataForDB['repeatSpec'] = implode(',',$data['repeat21'])." ".$data['repeat22'];
                $dataForDB['repeatFrec'] = $data['repeatFrec2'];
            } else if($dataForDB['repeatType'] == 3)
            {
                $dataForDB['repeatSpec'] = $data['repeat3Dates'];
            }

            $dataForDB['repeatIrg'] = $data['noReapeats'];
        } else {
            // default data
            $dataForDB['repeatType'] = 0;
            $dataForDB['repeatSpec'] = '';
            $dataForDB['repeatFrec'] = 0;
        }

        // is categorization enabled?
        if(pnModGetVar('TimeIt', 'enablecategorization'))
        {
            foreach($data['cats'] AS $key=>$val)
            {
                $tmp_name = explode('_', $key, 2);
                $dataForDB['__CATEGORIES__'][$tmp_name[1]] = $val;
            }
        }

        // eventplugins
        $eventPlugins = $this->getEventPlugins($obj, $render, true);
        foreach($eventPlugins AS $type) {
            foreach($type AS $plugin) {
                $plugin['plugin']->editPostBack($data, $dataForDB);
            }
        }

        //print_r($dataForDB);exit();
        $calendar = pnModAPIFunc('TimeIt','calendar','get', $this->cid);
        $schema = $calendar['workflow'];
        if(!$schema || $dataForDB['sharing'] == 1 || $dataForDB['sharing'] == 2) {
            $schema = 'standard';
        }

        if($this->mode == 'edit')
        {
            // load object
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
            $object = new $class();
            $obj = $object->getEvent($this->id);

            foreach ($dataForDB AS $key => $val)
            {
                if($dataForDB[$key] != $obj[$key])
                {
                    $obj[$key] = $val;
                }
            }
            $dataForDB = $obj;
            if(empty($obj)) {
                return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }
            WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events');
            // has obj got a schema?
            if(isset($obj['__WORKFLOW__']['schemaname']) && !empty($obj['__WORKFLOW__']['schemaname']))
            {
                $schema = $obj['__WORKFLOW__']['schemaname'];
            }

            if($this->recurrenceOnly) {
                $dataForDB['__META__']['TimeIt']['recurrenceOnly'] = true;
            }
        } else
        {
            $dataForDB['mid'] = md5(time());
        }
        $dataForDB['cid'] = $this->cid;
        $dataForDB['dheid'] = (int)FormUtil::getPassedValue('dheid', null, 'GET');
        /*$dataForDB['_WORKFLOW_']['TIschemaname'] = $schema;
        $dataForDB['_WORKFLOW_']['TIAction'] = $data['wfactions'];*/
        $dataForDB['__META__']['TimeIt']['wfActionId'] = $data['wfactions'];
        $dataForDB['__META__']['TimeIt']['wfSchema'] = $schema;

        WorkflowUtil::executeAction($schema, $dataForDB, $data['wfactions'], 'TimeIt_events');

        if($this->returnTo)
        {
            if($this->returnTo == 'adminPending') {
                $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'viewpending'));
            } else if($this->returnTo == 'adminviewall') {
                $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'viewall', array('cid'=>$this->cid)));
            } else if($data['wfactions'] != 'delete' && $data['wfactions'] != 'reject') {
                $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', (int)FormUtil::getPassedValue('dheid', null, 'GET'));
                $dheobj_exist = !empty($dheobj);
                // after a new recurrence calculation dheid may be invalid. So append dheid onyl if it is valid
                if($dheobj_exist) {
                $render->pnFormRedirect(pnModURL('TimeIt', 'user','event',array('id'=>$obj['id'],'dheid'=>(int)FormUtil::getPassedValue('dheid', null, 'GET'))));
            } else {
                    $render->pnFormRedirect(pnModURL('TimeIt', 'user','event',array('id'=>$obj['id'])));
                }
            } else {
                $render->pnFormRedirect(pnModURL('TimeIt', 'user','view',array('cid'=>$this->cid)));
            }
        } else
        {
            $render->pnFormRedirect(pnModURL('TimeIt', 'user','view',array('cid'=>$this->cid)));
        }
      } else
      {
          $render->pnFormRedirect(pnModURL('TimeIt', 'user','view',array('cid'=>$this->cid)));
      }

      return true;
    }

    /**
     * Creates and inits all available event plugins.
     * @param array $obj current event
     * @param pnRender $render current renderer
     * @param bool $postback
     * @param bool $copyMode
     * @return array eventplugins
     */
    function getEventPlugins($obj, &$render, $postback=false, $copyMode=false)
    {
        $plugins = TimeIt::getEventPluginInstances();
        $contact  =& $plugins['contact'];
        $location =& $plugins['location'];
        $return = array('contact'=>array(),'location'=>array());

        foreach($contact AS $plugin) {
            if(($this->mode == 'edit' && !$postback) || $copyMode)
            {
                $plugin->loadData($obj);

            }

            if(!$postback) {
                $b = $plugin->edit($this->mode, $render);
            }

            $return['contact'][] = array('plugin'=>$plugin,'edit'=>$b,'name'=>$plugin->getName(),'displayname'=>$plugin->getDisplayname());
        }

        foreach($location AS $plugin) {
            if(($this->mode == 'edit' && !$postback) || $copyMode)
            {
                $plugin->loadData($obj);
            }

            if(!$postback) {
                $b = $plugin->edit($this->mode, $render);
            }

            $return['location'][] = array('plugin'=>$plugin,'edit'=>$b,'name'=>$plugin->getName(),'displayname'=>$plugin->getDisplayname());
        }


        return $return;
        }
        }
