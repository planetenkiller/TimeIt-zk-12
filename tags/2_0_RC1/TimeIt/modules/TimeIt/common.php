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

Loader::loadClass('UserUtil');
Loader::includeOnce(WorkflowUtil::_findpath("function.standard_permissioncheck.php", 'TimeIt'));
Loader::includeOnce(WorkflowUtil::_findpath("function.moderate_permissioncheck.php", 'TimeIt'));
Loader::loadFile('EventPluginsContactTimeIt.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsContactFormicula.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsContactAddressbook.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsLocationTimeIt.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsLocationLocations.php','modules/TimeIt/EventPlugins');
Loader::loadFile('EventPluginsLocationAddressbook.php','modules/TimeIt/EventPlugins');
Loader::loadFile('Filter.class.php','modules/TimeIt/classes/filter');

/**
 * Class with some usefull methods.
 */
abstract class TimeIt {
    /**
     * Extendes DateUtil::getDatetime() with translations of the month and weekday names.
     */
    public static function getDatetime($time='', $format=DATEFORMAT_FIXED) {
        $format = str_replace('%A', '%%A', $format);
        $format = str_replace('%B', '%%B', $format);
        $format = str_replace('%a', '%%a', $format);
        $format = str_replace('%b', '%%b', $format);
        
        $text = DateUtil::getDatetime($time, $format);
        $weekday = date('w', $time);
        $month = date('n', $time);

        $weekdays = explode(' ', _DAY_OF_WEEK_LONG);
        $weekdays_short = explode(' ', _DAY_OF_WEEK_SHORT);
        $months = explode(' ', _MONTH_LONG);
        $months_short = explode(' ', _MONTH_SHORT);

        $text = str_replace('%A', $weekdays[(int)$weekday], $text);
        $text = str_replace('%a', $weekdays_short[(int)$weekday], $text);
        $text = str_replace('%B', $months[(int)$month-1], $text);
        $text = str_replace('%b', $months_short[(int)$month-1], $text);

        return $text;
    }
}

/**
 * FROM: http://ch2.php.net/manual/de/function.array-merge-recursive.php#89684
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param mixed $array2
 * @return array
 * @author daniel@danielsmedegaardbuus.dk
 */
function &array_merge_recursive_distinct(array &$array1, &$array2=null) {
  $merged = $array1;

  if (is_array($array2))
    foreach ($array2 as $key => $val)
      if (is_array($array2[$key]))
        $merged[$key] = is_array($merged[$key]) ? array_merge_recursive_distinct($merged[$key], $array2[$key]) : $array2[$key];
      else
        $merged[$key] = $val;

  return $merged;
}

class Timeit_common_createHandler
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
            }

            if($this->recurrenceOnly == false && $dheobj['eid'] != $obj['id']) {
                return LogUtil::registerError(_MODARGSERROR, 404);
            }

            $this->cid = (int)$dheobj['cid'];
            $obj['cid'] = $this->cid;
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
            $this->setDefaultData($render, $obj);

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
            $actions = WorkflowUtil::getActionsByState(pnModGetVar('TimeIt', 'workflow', 'standard'));
            $wfSchemaName = $calendar['workflow'];
            if(!$wfSchemaName) {
                $wfSchemaName = 'standard';
            }
            
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
                $render->pnFormRedirect(pnModURL('TimeIt', 'user','event',array('id'=>$obj['id'],'dheid'=>(int)FormUtil::getPassedValue('dheid', null, 'GET'))));
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

    function getEventPlugins($obj, &$render, $postback=false, $copyMode=false)
    {
        $contact  = array('ContactTimeIt'=>new TimeItEventPluginsContactTimeIt(), 'ContactFormicula'=>new TimeItEventPluginsContactFormicula(),'ContactAddressbook'=>new TimeItEventPluginsContactAddressbook()); // contact plugins
        $location = array('LocationTimeIt'=>new TimeItEventPluginsLocationTimeIt(), 'LocationLocations'=>new TimeItEventPluginsLocationLocations(),'LocationAddressbook'=>new TimeItEventPluginsLocationAddressbook()); // location plugins
        $return = array('contact'=>array(),'location'=>array());

        foreach($contact AS $plugin) {
            if(($this->mode == 'edit' && !$postback) || $copyMode)
            {
                $plugin->loadData($obj);
                
            }

            if(!$postback) {
                $b = $plugin->edit($this->mode, $render);
            }
            
            $return['contact'][] = array('plugin'=>$plugin,'edit'=>$b,'name'=>$plugin->getName());
        }

        foreach($location AS $plugin) {
            if(($this->mode == 'edit' && !$postback) || $copyMode)
            {
                $plugin->loadData($obj);
            }

            if(!$postback) {
                $b = $plugin->edit($this->mode, $render);
            }

            $return['location'][] = array('plugin'=>$plugin,'edit'=>$b,'name'=>$plugin->getName());
        }


        return $return;
        /*// contact event plugin
        $subscribeMode = pnModGetVar('TimeIt', 'subscribeMode');
        if($subscribeMode == "timeit" || ($subscribeMode == "formicula" && !pnModAvailable('formicula'))
          || ($subscribeMode == "formicula" && $this->mode == 'edit' && !$obj['data']['formicula_cid']))
        {
            $tobj = new TimeItEventPluginsContactTimeIt();
        } else
        {
            $tobj = new TimeItEventPluginsContactFormicula();
        }
        if($this->mode == 'edit')
        {
            $tobj->loadData($obj);
        }

        if($tobj->edit($this->mode, $render))
        {
            $render->assign('eventplugin_contact', $tobj->getName());
        } else
        {
            $render->assign('eventplugin_contact', false);
        }

        // location event plugin
        if(!pnModGetVar('TimeIt', 'useLocations') || ($this->mode == 'edit' && !$obj['data']['locations']))
        {
            $locObj = new TimeItEventPluginsLocationTimeIt();
        } else
        {
            $locObj = new TimeItEventPluginsLocationLocations();
        }
        if($this->mode == 'edit')
        {
            $locObj->loadData($obj);
        }

        if($locObj->edit($this->mode, $render))
        {
            $render->assign('eventplugin_locations', $locObj->getName());
        } else
        {
            $render->assign('eventplugin_locations', false);
        }*/
    }
}

class TimeIt_common_translateHandler
{
    var $id;
    
    function initialize(&$render)
    {
        if(($this->id=FormUtil::getPassedValue('id', null, 'GET'))==null) {
            return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
        }

        // load event
        $obj = pnModAPIFunc('TimeIt','user','get',array('id' => $this->id));
        if(empty($obj)) {
            return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
        }
        $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj' => $obj));

        $ids = array();
        // assign current translations
        $langlist = LanguageUtil::getInstalledLanguages();
        foreach($langlist AS $lang => $text) {
            if(isset($obj['title_translate'][$lang]) || isset($obj['text_translate'][$lang])) {
                $render->assign($lang, array('title' => $obj['title_translate'][$lang],
                                             'text'  => $obj['text_translate'][$lang]));
            }
            $ids[] = 'text_'.$lang;
        }

        // scribite! integration
        if (pnModAvailable('scribite') && pnModGetVar('TimeIt', 'scribiteEditor') != '-') {
            // load editor
            $scribite = pnModFunc('scribite','user','loader', array('modname' => 'TimeIt',
                                                                    'editor'  => pnModGetVar('TimeIt', 'scribiteEditor'),
                                                                    'areas'   => $ids
                                                                    /*'tpl'     => $args['areas']*/));
            PageUtil::AddVar('rawtext', $scribite);
        }
        
        $render->assign('event', $obj);
        $render->assign('language', pnUserGetLang());
        $render->assign('languages', LanguageUtil::getInstalledLanguages());
    }
    
    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'update') {
            if(!$render->pnFormIsValid()) {
                return false;
            }
            
            $data = $render->pnFormGetValues();
            
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
              pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
            $object = new $class();
            $obj = $object->getEvent($this->id);
            if(empty($obj)) {
                return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }
            
            foreach($data AS $lang => $translation) {
                $obj['title_translate'][$lang] = $translation['title'];
                $obj['text_translate'][$lang] = $translation['text'];
            }

            $object->setData($obj);
            $object->save();
        }
        
        $render->pnFormRedirect(pnModURL('TimeIt', 'user','event',array('id'=>$this->id)));
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

function TimeIt_templateWithTheme($render, $template, $theme)
{
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

function TimeIt_createIcal($events, $single=false)
{
    Loader::requireOnce('modules/TimeIt/pnincludes/iCalcreator.class.php');
    //print_r($events); exit();
    if($single) {
            $events = array($events);
    }

    $v = new vcalendar();
    $v->setConfig( 'unique_id', 'TimeIt 2.0 Calendar' );
    $v->setProperty( 'method', 'PUBLISH' );

    $ids_already_done = array();
    foreach($events AS $_week) {
        foreach($_week AS $_day) {
            if(empty($_day)) continue;
            foreach($_day AS $cat) {
                foreach ($cat['data'] AS $obj) {
                    // ignore recurrences of an event
                    if(in_array($obj['id'], $ids_already_done)) {
                        continue;
                    }
                    $ids_already_done[] = $obj['id'];

                    $vevent = new vevent();

                    $h = 0;
                    $m = 0;
                    $h2 = 0;
                    $m2 = 0;

                    if(!$obj['allDay']) {
                        $temp = explode(':', $obj['allDayStart']);
                        $h = (int)$temp[0];
                        $m = (int)$temp[1];

                        $temp = !is_array($obj['allDayDur'])? explode(',', $obj['allDayDur']) : $obj['allDayDur'];
                        $t_h = (int)$temp[0];
                        $t_m = (int)$temp[1];

                        $h2 = $h + $t_h;
                        $m2 = $m + $t_m;

                        if($m2 >= 60) {
                            $h2++;
                            $m2 = $m2 - 60;
                        }
                    }

                    $startDate1 = explode('-', $obj['startDate']);
                            $startDate = array( "year"  => (int)$startDate1[0] ,
                                                "month" => (int)$startDate1[1]  ,
                                                "day"   => (int)$startDate1[2],
                                                'hour'  => $h,
                                                'min'   => $m,
                                                'sec'=>0);
                                            //print_r($startDate);exit();
                    $vevent->setProperty( "dtstart", $startDate);

                    if($obj['allDay']) {
                        // if the event is a all day event the end date is the next day.
                        $endDate = DateUtil::getDatetime(strtotime('+1 day', strtotime($obj['endDate'])), _DATEINPUT);
                    } else {
                        $endDate = explode('-', $obj['endDate']);
                    }
                            $endDate = array( "year"  => (int)$endDate[0],
                                              "month" => (int)$endDate[1],
                                              "day"   => (int)$endDate[2],
                                              'hour'  =>$h2,
                                              'min'   =>$m2,
                                              'sec'=>0);
                    $vevent->setProperty( "dtend", $endDate);

                    $vevent->setProperty( "summary", $obj['title']);
                    $vevent->setProperty( "description", $obj['text']);

                    if($obj['plugins']['location']['name'] || $obj['plugins']['location']['name']) {
                        $value = $obj['plugins']['location']['name'].', '.$obj['plugins']['location']['street'].' '.$obj['plugins']['location']['houseNumber'].', '.$obj['plugins']['location']['zip'].' '.$obj['plugins']['location']['city'].' '.$obj['plugins']['location']['country'];
                        $vevent->setLocation($value);
                    }

                    if($obj['plugins']['location']['lat'] && $obj['plugins']['location']['lng']) {
                        $vevent->setGeo($obj['plugins']['location']['lat'], $obj['plugins']['location']['lng']);
                    }

                    if($obj['plugins']['contact']['contactPerson'] || $obj['plugins']['contact']['email'] || $obj['plugins']['contact']['phoneNr']) {
                        $value = $obj['plugins']['contact']['contactPerson'].', '.$obj['plugins']['contact']['address'].', '.$obj['plugins']['contact']['zip'].' '.$obj['plugins']['contact']['city'].' '.$obj['plugins']['contact']['country'].', '.$obj['plugins']['contact']['email'].', '.$obj['plugins']['contact']['phoneNR'];
                        $vevent->setContact($value);
                    }


                    $vevent->setProperty( "uid", $obj['id'].'@'.pnServerGetVar('HTTP_HOST').pnGetBaseURI());
                    $vevent->setProperty( "url", pnModURL('TimeIt','user','event', array('id'=>(int)$obj['id']), null, null, true));

                    $cr_date = getdate(strtotime($obj['endDate']));
                    $vevent->setProperty( "dtstamp", array( "year" => (int)$cr_date['year'] ,
                                                                    "month" => (int)$cr_date['mon']  ,
                                                                    "day" => (int)$cr_date['mday'],
                                                                    'hour' => (int)$cr_date['hours'],
                                                                    'min' => (int)$cr_date['minutes'],
                                                                    'sec' => (int)$cr_date['seconds']));

                    $cats = array();
                    foreach($obj['__CATEGORIES__'] as $cat) {
                        $cats[] = $cat['name'];
                    }
                    if(!empty($cats)) {
                        $vevent->setProperty( "categories", $cats);
                    }

                    if($obj['sharing'] == '1')
                    {
                        $vevent->setProperty( "class", 'PRIVATE');
                    } else if($obj['sharing'] == '2' || $obj['sharing'] == '3')
                    {
                        $vevent->setProperty( "class", 'PUBLIC');
                    } else if($obj['sharing'] == '4')
                    {
                        $vevent->setProperty( "class", 'CONFIDENTIAL');
                    }

                    if((int)$obj['repeatType'] == 1)
                    {
                        if($obj['repeatSpec'] == 'year')
                        {
                            $freq = 'YEARLY';
                        } else if($obj['repeatSpec'] == 'month')
                        {
                            $freq = 'MONTHLY';
                        } else if($obj['repeatSpec'] == 'day')
                        {
                            $freq = 'DAILY';
                        }

                        $vevent->setProperty( "dtend", $startDate);
                        $vevent->setProperty("RRULE", array('FREQ'=>$freq,'INTERVAL'=>$obj['repeatFrec'],'UNTIL'=>$endDate));
                    } else if((int)$obj['repeatType'] == 2)
                    {
                        $data = explode(' ', $obj['repeatSpec']);
                        if($data[0] == '5')
                        {
                            $data[0] = '-1';
                        }
                        $byday = $data[0];
                        if($data[1] == '0')
                        {
                            $byday .= 'SO';
                        } else if($data[1] == '1')
                        {
                            $byday .= 'MO';
                        } else if($data[1] == '2')
                        {
                            $byday .= 'TU';
                        } else if($data[1] == '3')
                        {
                            $byday .= 'WE';
                        } else if($data[1] == '4')
                        {
                            $byday .= 'TH';
                        } else if($data[1] == '5')
                        {
                            $byday .= 'FR';
                        } else if($data[1] == '6')
                        {
                            $byday .= 'SA';
                        }

                        $vevent->setProperty( "dtend", $startDate);
                        $vevent->setProperty("RRULE", array('FREQ'=>'MONTHLY','BYDAY'=>$byday,'INTERVAL'=>$obj['repeatFrec'],'UNTIL'=>$endDate));
                    } else if((int)$obj['repeatType'] == 3)
                    {
                        $dates = array();
                        $datesExp = explode(',',$obj['repeatSpec']);
                        foreach($datesExp AS $d)
                        {
                            $d = explode('-', $d);
                            $dates[] = array( "year" => (int)$d[0],
                                              "month" => (int)$d[1] ,
                                              "day" => (int)$d[2]);
                        }

                        $vevent->setProperty("RDATE", $dates);
                    } else if((int)$obj['repeatType'] == 4)
                    {
                        $vevent->setProperty("RRULE", unserialize($obj['repeatSpec']));
                    }


                    //print_r($vevent);
                    $v->setComponent($vevent);
                }
            }
        }
    }

    $v->returnCalendar();
}

function TimeIt_decorateWitEventPlugins(&$obj)
{
    $return = false;
    $contact  = array('ContactTimeIt'=>new TimeItEventPluginsContactTimeIt(),    'ContactFormicula'=>new TimeItEventPluginsContactFormicula(),'ContactAddressbook'=>new TimeItEventPluginsContactAddressbook()); // contact plugins
    $location = array('LocationTimeIt'=>new TimeItEventPluginsLocationTimeIt(), 'LocationLocations'=>new TimeItEventPluginsLocationLocations(),'LocationAddressbook'=>new TimeItEventPluginsLocationAddressbook()); // location plugins

    // contact event plugin
    if(!$obj['data']['eventplugin_contact']) {
        $obj['data']['plugindata']['ContactTimeIt'] = array();
        $obj['data']['plugindata']['ContactTimeIt']['contactPerson'] = $obj['data']['contactPerson'];
        $obj['data']['plugindata']['ContactTimeIt']['email']         = $obj['data']['email'];
        $obj['data']['plugindata']['ContactTimeIt']['phoneNr']       = $obj['data']['phoneNr'];
        $obj['data']['plugindata']['ContactTimeIt']['website']       = $obj['data']['website'];
        $obj['data']['eventplugin_contact'] = 'ContactTimeIt';
        unset($obj['data']['contactPerson'],
              $obj['data']['email'],
              $obj['data']['phoneNr'],
              $obj['data']['website']);
        $return = true;
    }
    $eventplugin_c = $contact[$obj['data']['eventplugin_contact']];
    $eventplugin_c->loadData($obj);
    $obj['plugins']['contact'] =& $eventplugin_c;

    // location event plugin
    // old locations data format? -> convert to new format
    if(isset($obj['data']['locations']) && (int)$obj['data']['locations'] > 0) {
        $obj['data']['plugindata']['LocationLocations'] = array();
        $obj['data']['plugindata']['LocationLocations']['id'] = (int)$obj['data']['locations'];
        $obj['data']['plugindata']['LocationLocations']['displayMap']= $obj['data']['displayMap'];
        unset($obj['data']['locations'],
              $obj['data']['displayMap']);
        $obj['data']['eventplugin_location'] = 'LocationLocations';
        $return = true;
    } else if(!$obj['data']['eventplugin_location']) {
        $obj['data']['plugindata']['LocationTimeIt'] = array();
        $obj['data']['plugindata']['LocationTimeIt']['name'] = $obj['data']['name'];
        $obj['data']['plugindata']['LocationTimeIt']['street'] = $obj['data']['streat'];
        $obj['data']['plugindata']['LocationTimeIt']['houseNumber'] = $obj['data']['houseNumber'];
        $obj['data']['plugindata']['LocationTimeIt']['zip'] = $obj['data']['zip'];
        $obj['data']['plugindata']['LocationTimeIt']['city'] = $obj['data']['city'];
        $obj['data']['plugindata']['LocationTimeIt']['country']= $obj['data']['country'];
        $obj['data']['plugindata']['LocationTimeIt']['lat']= $obj['data']['lat'];
        $obj['data']['plugindata']['LocationTimeIt']['lng']= $obj['data']['lng'];
        $obj['data']['plugindata']['LocationTimeIt']['displayMap']= $obj['data']['displayMap'];

        unset(  $obj['data']['name'],
                $obj['data']['street'],
                $obj['data']['houseNumber'],
                $obj['data']['zip'],
                $obj['data']['city'],
                $obj['data']['country'],
                $obj['data']['lat'],
                $obj['data']['lng'],
                $obj['data']['displayMap']);
        $obj['data']['eventplugin_location'] = 'LocationTimeIt';
        $return = true;
    }

    $eventplugin_loc = $location[$obj['data']['eventplugin_location']];
    $eventplugin_loc->loadData($obj);
    $obj['plugins']['location'] =& $eventplugin_loc;

    return $return;
}