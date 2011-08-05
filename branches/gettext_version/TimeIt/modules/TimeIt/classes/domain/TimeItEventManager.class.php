<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Manager
 */

/**
 * Event Manager
 *
 * @author planetenkiller
 */
class TimeItEventManager
{
    public function createEvent(&$obj, $args=array())
    {
        if(empty($obj)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        if(!isset($obj['__WORKFLOW__'])) {
            throw new InvalidArgumentException('Do not call this function directly! use WorkflowUtil::executeAction("standard", $obj, "submit", "TimeIt_events"); insted.');
        }
        
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
             pn_exit(__f('Unable to load class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }

        $object = new $class();
        $object->setData($obj);
        $ret = $object->insert();
            
        $obj = $object->getData();

        if(!isset($obj['__META__']['TimeIt']['recurrenceOnly']) || !$obj['__META__']['TimeIt']['recurrenceOnly']) {
            if(!isset($args['noRecurrenceCalculation']) || !$args['noRecurrenceCalculation']) {
                Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
                Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
                $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $obj);
                $prozi->doCalculation();
            }

            // Let any hooks know that we have created a new item
            pnModCallHooks('item', 'create', $obj['id'], array('module' => 'TimeIt'));
        }

        return $ret;
    }

    /**
     * Save the changes of an event.
     * @param array $obj Timeit event
     * @return boolean
     */
    function updateObject($obj)
    {
        if(empty($obj)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
             pn_exit(__f('Unable to load class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }

        if(isset($obj['__META__']['TimeIt']['recurrenceOnly']) && $obj['__META__']['TimeIt']['recurrenceOnly']) {
            //$dheobj = DBUtil::selectObjectArray('TimeIt_date_has_events', '(eid = '.(int)$obj['id'].' OR localeid = '.(int)$obj['id'].') AND the_date = \''.DataUtil::formatForStore($obj['startDate']).'\'');
            $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $obj['dheid']);
            //$dheobj = $dheobj[0];

            if($dheobj['localeid']) {
                $object = new $class();
                $object->setData($obj);
                $ret = $object->save();
            } else {
                unset($obj['id'],
                      $obj['__WORKFLOW__']);

                WorkflowUtil::executeAction('standard', $obj, "submit", "TimeIt_events", "TimeIt");

                $dheobj['localeid'] = $obj['id'];
                $dheobj['date']     = $obj['startDate'];
                DBUtil::updateObject($dheobj, 'TimeIt_date_has_events');
                $ret = true;
            }
        } else {
            $master = $this->getObject($obj['id']); // get current event from DB

            if(!isset($args['noRecurrences']) || !$args['noRecurrences']) {
                if( $obj['repeatType']    != $master['repeatType']
                    || $obj['repeatSpec'] != $master['repeatSpec']
                    || $obj['repeatFrec'] != $master['repeatFrec']
                    || $obj['startDate']  != $master['startDate']
                    || $obj['endDate']    != $master['endDate']
                    || $obj['repeatIrg']  != $master['repeatIrg']
                    )
                {
                    $this->updateRecurrences($obj);
                }
            }

            pnModCallHooks('item', 'update', $obj['id'], array('module' => 'TimeIt'));

            $object = new $class();
            $object->setData($obj);
            $ret = $object->save();
        }

        return $ret;
    }

    function updateRecurrences($obj)
    {
        if(empty($obj)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        $master = $this->getObject($obj['id']); // get current event from DB

        if(($obj['repeatType'] == $master['repeatType'] || (empty($obj['repeatType']) && empty($master['repeatType'])) )
           && ($obj['repeatSpec'] == $master['repeatSpec'] || (empty($obj['repeatSpec']) && empty($master['repeatSpec'])) )
           && ($obj['repeatFrec'] == $master['repeatFrec'] || (empty($obj['repeatFrec']) && empty($master['repeatFrec'])) )
           && $obj['startDate'] == $master['startDate']
           && ($obj['repeatIrg'] == $master['repeatIrg'] || empty($master['repeatIrg']) )
         ) {
            if($obj['endDate'] < $master['endDate']) {
                $temp = $obj;
                $temp['endDate'] = $master['endDate'];
                $temp['startDate'] = DateUtil::getDatetime(strtotime('+1 day', strtotime($obj['endDate'])), DATEONLYFORMAT_FIXED);

                Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
                Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
                $out = new TimeIt_Recurrence_Output_Array();
                $prozi = new TimeIt_Recurrence_Processor($out, $temp);
                $prozi->doCalculation();
                $array =& $out->getData();

                // delete the old occurrences
                foreach($array AS $toDelDate) {
                    $toDelObj = DBUtil::selectObject('TimeIt_date_has_events', "eid = '".(int)$obj['id']."' AND the_date = '".DataUtil::formatForStore($toDelDate)."'");

                    if(!empty($toDelObj)) {
                        // delete all subcribtions
                        DBUtil::deleteWhere('TimeIt_regs', 'pn_eid = '.(int)$toDelObj['id']);

                        // delete recurrence
                        DBUtil::deleteWhere('TimeIt_date_has_events', 'id = '.(int)$toDelObj['id']);

                        // delete modified occurrence if there is one
                        if($toDelObj['localeid']) {
                            pnModAPIFunc('TimeIt','user','delete', array('id'=>(int)$toDelObj['localeid'],'eventOnly'=>true));
                        }
                    }
                }
            } else if($obj['endDate'] > $master['endDate']) {
                // calculate the new occurrences
                $temp = $obj;
                // Bug fix: We cant change the start date because this can result in an incorrect calculation.
                // We simply add the end date of the original event to the ignored date list.
                // This way the orignial end date would not be calculated a secound time.
                //$temp['startDate'] = DateUtil::getDatetime(strtotime('+1 day', strtotime($master['endDate'])), DATEONLYFORMAT_FIXED);
                Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
                Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
                $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $temp, false, array($master['endDate']));
                $prozi->doCalculation();
            }

            if(!empty($obj['repeatIrg']) && empty($master['repeatIrg'])) {
                $dates = explode(',', $obj['repeatIrg']);
                pnModAPIFunc('TimeIt','user','deleteAllRecurrences', array('obj'=>$obj,'dates'=>$dates));
            }
        } else {
            $this->deleteAllOccurrences($obj['id']);
            $temp = $obj;
            Loader::requireOnce('modules/TimeIt/classes/recurrence/Processor.class.php');
            Loader::requireOnce('modules/TimeIt/classes/recurrence/outputter/DB.php');
            $prozi = new TimeIt_Recurrence_Processor(new TimeIt_Recurrence_Output_DB(), $temp);
            $prozi->doCalculation();
        }
    }

    public function getObject($id, $dheid=null, $translate=true)
    {
        if(empty($id)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
             pn_exit(__f('Unable to load class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }

        $site_multilingual = pnConfigGetVar('multilingual');
        $object = new $class();
        $dheobj = null;

        if($dheid) {
            $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', (int)$dheid);
        }

        $eid = ($dheobj != null & !empty($dheobj['localeid'])) ? $dheobj['localeid'] : $id;

        return $object->getEvent((int)$eid, $site_multilingual && $translate, $dheobj);
    }

    public function deleteObject($id, $onlyEvent=false) {
        if(empty($id)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        if($onlyEvent) {
            if(is_array($id)) {
                $obj = $id;
            } else {
                $obj = $this->getObject($id);
            }

            if(!empty($obj)) {
                WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events', 'id', 'TimeIt');
                WorkflowUtil::deleteWorkflow($obj);
                $delobj = array('localeid'=>null);
                
                DBUtil::updateObject($delobj, 'TimeIt_date_has_events', 'localeid = '.(int)$id);
                return true;
            } else {
                return false;
            }
        } else {
            if(is_array($id)) {
                $id = $id['id'];
            }
            $dhearray = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$id);

            $dheids = array();
            $eids = array($id);

            foreach($dhearray AS $dheobj) {
                $dheids[] = $dheobj['id'];
                if($dheobj['localeid']) {
                    $eids[] = $dheobj['localeid'];
                }
            }

            if(!empty($dheids)) {
                // delete all subcribtions
                DBUtil::deleteWhere('TimeIt_regs', 'pn_eid IN('.implode(',', $dheids).')');

                // delete recurrences
                DBUtil::deleteWhere('TimeIt_date_has_events', 'id IN('.implode(',', $dheids).')');
            }

            // get all events to delete
            $events = DBUtil::selectObjectArray('TimeIt_events','pn_id IN('.implode(',', $eids).')');
            // delete all events
            foreach($events AS $obj) {
                WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events', 'id', 'TimeIt');
                WorkflowUtil::deleteWorkflow($obj);
            }

            // Let any hooks know that we have deleted an item
            pnModCallHooks('item', 'delete', $id, array('module' => 'TimeIt'));

            return true;
        }
    }

    /**
     * Delets all recurrences in the DB. This function deletes all modified occurrences(=separate events) too.
     * @param int id TimeIt event id
     * @param array dates Deletes only these recurences (Optional)
     * @return boolean
     */
    function deleteAllOccurrences($id, $dates=array())
    {
        if(empty($id)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        } else {
            if(is_array($dates) && !empty($dates)) {
                $datessql = ' AND the_date IN(';
                foreach($dates AS $date) {
                    $datessql .= "'".DataUtil::formatForStore($date)."',";
                }
                $datessql = substr($datessql, 0, strlen($datessql)-1); // remove last ,
                $datessql .= ')';
            } else {
                $datessql = '';
            }
            $dhearray = DBUtil::selectObjectArray('TimeIt_date_has_events', 'eid = '.(int)$id.$datessql);

            $dheids = array();
            $localeids = array();

            foreach($dhearray AS $dheobj) {
                $dheids[] = $dheobj['id'];
                if($dheobj['localeid']) {
                    $localeids[] = $dheobj['localeid'];
                }
            }

            if(!empty($dheids)) {
                // delete all subcribtions
                DBUtil::deleteWhere('TimeIt_regs', 'pn_eid IN('.implode(',', $dheids).')');

                // delete recurrences
                DBUtil::deleteWhere('TimeIt_date_has_events', 'id IN('.implode(',', $dheids).')');
            }

            if(!empty($localeids)) {
                // get all events to delete
                $events = DBUtil::selectObjectArray('TimeIt_events','pn_id IN('.implode(',', $localeids).')');

                // delete all events
                foreach($events AS $obj) {
                    WorkflowUtil::getWorkflowForObject($obj, 'TimeIt_events', 'id', 'TimeIt');
                    WorkflowUtil::deleteWorkflow($obj);
                }
            }

            // clear the cache because otherwise non exist rows are in the cache
            DBUtil::objectCache(true, 'TimeIt_date_has_events');

            return true;
        }
    }

    function getYearEvents($year, $cid, $firstDayOfWeek=-1)
    {
        if(empty($year) || empty($cid)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        } else {
            // valid Date?
            if(!pnModAPIFunc('TimeIt','user','checkDate', array('year' => $year))) {
                 return LogUtil::registerError(__f('Error! The date %s is not valid.', $year.'-01-01', ZLanguage::getModuleDomain('TimeIt')));
            }

            $arrayOfMonths = array();
            for($i=1;$i<=12;$i++) {
                $date = DateUtil::getDatetime(mktime(0, 0, 0, $i, DateUtil::getDaysInMonth($i, $year), $year), DATEONLYFORMAT_FIXED);
                $arrayOfMonths[$date] = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month' => $i, 'year'=> $year, 'firstDayOfWeek' => $firstDayOfWeek));
            }

            $datesWithEvents = pnModAPIFunc('TimeIt', 'user', 'getDatesWithEvents', array('cid' => $cid, 'start' => $year.'-01-01', 'end' => $year.'-12-31'));
            foreach($datesWithEvents AS $date) {
                list($year, $month, $day) = explode('-', $date);

                $index = DateUtil::getDatetime(mktime(0, 0, 0, (int)$month, DateUtil::getDaysInMonth((int)$month, $year), $year), DATEONLYFORMAT_FIXED);
                foreach($arrayOfMonths[$index] AS $week => $days) {
                    if(array_key_exists($date, $days)) {
                        $arrayOfMonths[$index][$week][$date] = true;
                        break;
                    }
                }

            }

            return $arrayOfMonths;
        }
    }

    public function getMonthEvents($year, $month, $day, $cid, $firstDayOfWeek=-1, TimeItFilter $filter_obj=null, $args=array())
    {
        if(empty($year) || empty($month) || empty($day) || empty($cid)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }
        
        // valid Date?
        if(!pnModAPIFunc('TimeIt','user','checkDate',array('year' => $year, 'month' => $month, 'day' => $day))) {
            return LogUtil::registerError(__f('Error! The date %s is not valid.', $year.'-'.$month.'-'.$day, ZLanguage::getModuleDomain('TimeIt')));
        }

        // get array for month
        $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month' => $month, 'year' => $year, 'firstDayOfWeek' => $firstDayOfWeek));

        // extract start date of first week
        reset($events[0]);
        $start = each($events[0]);
        $start = $start['key'];

        // extract end date of last week
        end($events);
        $end = each($events); // last week
        $key = $end['key']; // key of last week
        end($events[$key]); // last day in last week
        $end = each($events[$key]);
        $end = $end['key'];

        // get events form db
        $data = $this->getDailySortedEvents($start, $end, $cid, $filter_obj, $args);
        
        // insert events from data to the events array
        foreach($events AS $weeknr=>$days)
        {
            foreach($days AS $k=>$v)
            {
                $timestamp = strtotime($k);
                $events[$weeknr][$k] = isset($data[$timestamp]) ? $data[$timestamp] : null;
            }
        }

        return $events;
    }

    public function getWeekEvents($year, $month, $day, $cid, TimeItFilter $filter_obj=null, $args=array())
    {
        if(!$year || !$month || !$day || !$cid) {
             return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        } else {
            // valid Date?
            if(!pnModAPIFunc('TimeIt','user','checkDate', array('month' => $month, 'year' => $year, 'day' => $day))) {
                return LogUtil::registerError(__f('Error! The date %s is not valid.', $year.'-'.$month.'-'.$day, ZLanguage::getModuleDomain('TimeIt')));
            }

            $startDateArray = getDate(pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', array('month' => $month, 'year' => $year, 'day' => $day)));
            $startDate = DateUtil::getDatetime($startDateArray[0], DATEONLYFORMAT_FIXED);
            $endDate   = DateUtil::getDatetime(mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+6, $startDateArray['year']), DATEONLYFORMAT_FIXED);

            $data = $this->getDailySortedEvents($startDate, $endDate, $cid, $filter_obj, $args);
            $week = array();

            for($i=0;$i<7;$i++) {
                $temp = mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+$i, $startDateArray['year']);
                $week[DateUtil::getDatetime($temp, DATEONLYFORMAT_FIXED)] = $data[$temp];
            }
            return $week;
        }
    }

    public function getDayEvents($year, $month, $day, $cid, TimeItFilter $filter_obj=null, $args=array())
    {
        if(!$year || !$month || !$day || !$cid) {
             return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        } else {
            // valid Date?
            if(!pnModAPIFunc('TimeIt','user','checkDate', array('month' => $month, 'year' => $year, 'day' => $day))) {
                return LogUtil::registerError(__f('Error! The date %s is not valid.', $year.'-'.$month.'-'.$day, ZLanguage::getModuleDomain('TimeIt')));
            }

            $getDate = getDate(mktime(0, 0, 0, $month, $day, $year));
            $date    = DateUtil::getDatetime($getDate[0], DATEONLYFORMAT_FIXED);

            $data = $this->getDailySortedEvents($date, $date, $cid, $filter_obj, $args);

            return $data[$getDate[0]];
        }
    }

    public function getDailySortedEvents($start, $end, $cid, TimeItFilter $filter_obj=null, $args=array())
    {
        // valid Dates?
        if(!pnModAPIFunc('TimeIt','user','checkDate',array('date' => $start)) || !pnModAPIFunc('TimeIt','user','checkDate',array('date' => $end))) {
            return LogUtil::registerError (__f('Error! The date %s is not valid.', $start.' / '.$end, ZLanguage::getModuleDomain('TimeIt')));
        }

        // default values
        if(!isset($args['preformat']))
            $args['preformat'] = true;
            
        // load class CategoryUtil
        Loader::loadClass('CategoryUtil');

        $prozessRepeat = (isset($args['prozessRepeat']))? $args['prozessRepeat']: true;
        $pntable = pnDBGetTables();
        $column = $pntable['TimeIt_events_column'];

        // build where
        $sql = 'tbl.cid = '.DataUtil::formatForStore((int)$cid).' '; // first joion
        $sql .= ' AND ((b.pn_id IS NOT NULL AND b.pn_status = 1) OR (b.pn_id IS NULL AND a.pn_status = 1)) '; // secound join
        $sql .= ' AND tbl.the_date >= \''.DataUtil::formatForStore($start).'\' AND tbl.the_date <= \''.DataUtil::formatForStore($end).'\''; // first join
        $User_ID = pnUserGetVar('uid');
        $user_lang = ZLanguage::getLanguageCode();

        // add sharing conditions to where if the filter contains no sharing expression
        if($filter_obj == null || !$filter_obj->hasFilterOnField('sharing')) {
            $sql .= ' AND (';
            if(!empty($User_ID)) {
                $sql .= '((b.pn_id IS NOT NULL AND b.pn_cr_uid = '.DataUtil::formatForStore(pnUserGetVar('uid')).' AND (b.'.$column['sharing'].' = 1 OR b.'.$column['sharing'].' = 2)) OR (a.pn_cr_uid = '.DataUtil::formatForStore(pnUserGetVar('uid')).' AND (a.'.$column['sharing'].' = 1 OR a.'.$column['sharing'].' = 2))) OR ';
            }

            $sql .= 'a.pn_sharing = 3 OR a.pn_sharing = 4 OR b.pn_sharing = 3 OR b.pn_sharing = 4)';
        }

        // add filter to sql
        if($filter_obj != null) {
            $filter_sql = $filter_obj->toSQL('b');

            if(!empty($filter_sql)) {
                $sql .= ' AND ((b.pn_id IS NOT NULL AND '.$filter_sql.') OR';
                $sql .= '( b.pn_id IS NULL AND '.$filter_obj->toSQL('a').'))';
            }
        }

        // load the object array class corresponding to $objectType
        if (!($class = Loader::loadArrayClassFromModule('TimeIt', 'event'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $objectArray = new $class();

        // get data form database
        $array = $objectArray->get($sql);
        $ret = array();
        
        // --------------- ContactList integration --------------------
        $buddys = array();
        $ignored = array();
        if (pnModAvailable('ContactList'))  {
            if(pnModGetVar('TimeIt', 'friendCalendar')) {
                $buddys = pnModAPIFunc('ContactList','user','getBuddyList',array('uid'=>$User_ID));
            }

            $ignored = pnModAPIFunc('ContactList','user','getallignorelist',array('uid'=>$User_ID));
        }
        // --------------- end ContactList integration --------------------

        $site_multilingual = pnConfigGetVar('multilingual');
        $eventsAdded = 0;
        foreach($array AS $obj) {
            if(!TimeItPermissionUtil::canViewEvent($obj, ACCESS_OVERVIEW)) {
                continue; // no permission to this event so ignore it
            }


            if(isset($args['preformat']) && $args['preformat']) {
                $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj'=>$obj));
            } else if(substr($obj['text'],0,11) == "#plaintext#") {
                $obj['text'] = substr_replace($obj['text'],"",0,11);
                $obj['text'] = nl2br($obj['text']);
            }

            if($site_multilingual && (!isset($args['translate']) || ( isset($args['translate']) && $args['translate'] == true))) {
                if(isset($obj['title_translate'][$user_lang]) && !empty($obj['title_translate'][$user_lang])) {
                    $obj['title'] = $obj['title_translate'][$user_lang];
                }

                if(isset($obj['text_translate'][$user_lang]) && !empty($obj['text_translate'][$user_lang])) {
                    $obj['text'] = $obj['text_translate'][$user_lang];
                }
            }

            $timestamp = strtotime($obj['dhe_date']);
           // Move this event back or forward if the timezone calculation needs a move
            if(isset($obj['allDayStartLocalDateCorrection'])) {
                $timestamp = $timestamp + ($obj['allDayStartLocalDateCorrection'] * (60 * 60 * 24));
            }
            $this->getDailySortedEvents_addToArray($ret, $timestamp, $obj);
            $eventsAdded++;

            // limit control
            if(isset($args['limit']) && $eventsAdded >= $args['limit']) {
                break;
            }
        }

        ksort($ret); // sort keys in array
        //print_r($ret);exit();

        foreach($ret as $key => $events) {
            usort($ret[$key], array($this, "getDailySortedEvents_usort"));
        }

        return $ret;
    }

    private function getDailySortedEvents_usort($a1 ,$b1)
    {
        if(pnModGetVar('TimeIt','sortMode') == 'byname') {
            $a = $a1['info']['name'];
            $b = $b1['info']['name'];
            return strcasecmp($a, $b);
        } else {
            $a = $a1['info']['sort_value'];
            $b = $b1['info']['sort_value'];
            if($a == $b) {
                return 0;
            } else if($a < $b) {
                return -1;
            } else {
                return 1;
            }
        }
    }

    private function getDailySortedEvents_addToArray(&$array, $tmestamp, $obj)
    {
        $property = pnModGetVar('TimeIt', 'colorCatsProp', 'Main');
        // get category id
        $catID = isset($obj['__CATEGORIES__'][$property]['id'])? $obj['__CATEGORIES__'][$property]['id'] : 0;
        // There are events out there which aren't in any category
        if(empty($catID)) {
            $catID = 0;
        }
        // isn't the category id set on $array?
        if(!isset($array[$tmestamp][$catID])) {
                $array[$tmestamp][$catID] = array();
                $name = isset($obj['__CATEGORIES__'][$property]['name'])? $obj['__CATEGORIES__'][$property]['name'] : "";
                if(isset($obj['__CATEGORIES__'][$property]['display_name'][ZLanguage::getLanguageCode()])) {
                    $name = $obj['__CATEGORIES__'][$property]['display_name'][ZLanguage::getLanguageCode()];
                }
                $array[$tmestamp][$catID]['info'] = array('name'       => $name,
                                                          'color'      => isset($obj['__CATEGORIES__'][$property]['__ATTRIBUTES__']['color'])? $obj['__CATEGORIES__'][$property]['__ATTRIBUTES__']['color'] : null,
                                                          'sort_value' =>isset($obj['__CATEGORIES__'][$property]['sort_value'])? (int)$obj['__CATEGORIES__'][$property]['sort_value'] : 0);
                $array[$tmestamp][$catID]['data'] = array();
                if(empty($array[$tmestamp][$catID]['info']['color']) && $name) {
                    $array[$tmestamp][$catID]['info']['color'] = pnModGetVar('TimeIt', 'defalutCatColor');
                }

        }


        // add event to category
        $array[$tmestamp][$catID]['data'][] = $obj;

        if(count($array[$tmestamp][$catID]['data']) > 1) {
            // search best pos in $array
            for ($i = count($array[$tmestamp][$catID]['data'])-1; $i > 0; $i--) {
                $item = $array[$tmestamp][$catID]['data'][$i];
                $itembe = $array[$tmestamp][$catID]['data'][$i-1];
                if($itembe['allDayStartLocal'] > $item['allDayStartLocal']) {
                    $objbe = $array[$tmestamp][$catID]['data'][$i-1];
                    $array[$tmestamp][$catID]['data'][$i-1] = $array[$tmestamp][$catID]['data'][$i];
                    $array[$tmestamp][$catID]['data'][$i] = $objbe;
                }
            }
        }
    }

    /**
     * Returns a list of pending events.
     *
     * @param int $startnum
     * @param int $numitems
     * @return array
     */
    public function getPendingEvents($startnum=0, $numitems=-1)
    {
        pnModDBInfoLoad('Workflow');
        $pntables = pnDBGetTables();

        $workflows_column = $pntables['workflows_column'];
        $timeit_events_column = $pntables['TimeIt_events_column'];

        $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='waiting'";
        if($groups !== true && count($groups) > 0) {
            $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
        }

        $join = array(array('join_table'        =>  'workflows',
                            'join_field'         =>  array('obj_id'),
                            'object_field_name'  =>  array('obj_id'),
                            'compare_field_table'=>  'id',
                            'compare_field_join' =>  'obj_id'));

        if (!($class = Loader::loadArrayClassFromModule ('TimeIt', 'Event'))) {
             pn_exit(__f('Unable to load array class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }
        $class = new $class();
        $class->_objJoin = $join;
        
        return $class->getWhere($where, '', $startnum-1, $numitems);
    }

    /**
     * Returns the number of pending events.
     *
     * @return int
     */
    public function getNumberOfPendingEvents()
    {
        pnModDBInfoLoad('Workflow');
        $pntables = pnDBGetTables();

        $workflows_column = $pntables['workflows_column'];
        $timeit_events_column = $pntables['TimeIt_events_column'];

        $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='waiting'";
        if($groups !== true && count($groups) > 0) {
            $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
        }

        $join = array(array ('join_table'        =>  'workflows',
                            'join_field'         =>  array('obj_id'),
                            'object_field_name'  =>  array('obj_id'),
                            'compare_field_table'=>  'id',
                            'compare_field_join' =>  'obj_id'));


        if (!($class = Loader::loadArrayClassFromModule ('TimeIt', 'Event'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }
        $class = new $class(); // make object
        $class->_objJoin = $join; // set join array
        
        return $class->getCount($where, true);// count items
    }

    /**
     * Returns a list of events which are not viewable by normal view/display functions.
     *
     * @param int $startnum
     * @param int $numitems
     * @return array
     */
    public function getOfflineEvents($startnum=0, $numitems=-1)
    {
        pnModDBInfoLoad('Workflow');
        $pntables = pnDBGetTables();

        $workflows_column = $pntables['workflows_column'];
        $timeit_events_column = $pntables['TimeIt_events_column'];

        $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='approved'
                    AND $timeit_events_column[status]=0";
        if($groups !== true && count($groups) > 0)
        {
            $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
        }

        $join = array(array('join_table'   =>  'workflows',
                            'join_field'         =>  array('obj_id'),
                            'object_field_name'  =>  array('obj_id'),
                            'compare_field_table'=>  'id',
                            'compare_field_join' =>  'obj_id'));

        if (!($class = Loader::loadArrayClassFromModule ('TimeIt', 'Event'))) {
             pn_exit(__f('Unable to load array class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }
        $class = new $class();
        $class->_objJoin = $join;
        
        return $class->getWhere($where, '', $startnum-1, $numitems);
    }

    /**
     * Returns the number of events which are not viewable by normal view/display functions.
     *
     * @return int
     */
    public function getNumberOfOfflineEvents($args)
    {
        pnModDBInfoLoad('Workflow');
        $pntables = pnDBGetTables();

        $workflows_column = $pntables['workflows_column'];
        $timeit_events_column = $pntables['TimeIt_events_column'];

        $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='approved'
                    AND $timeit_events_column[status]=0";
        if($groups !== true && count($groups) > 0)
        {
            $where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
        }

        $join = array(array('join_table'   =>  'workflows',
                            'join_field'         =>  array('obj_id'),
                            'object_field_name'  =>  array('obj_id'),
                            'compare_field_table'=>  'id',
                            'compare_field_join' =>  'obj_id'));

        if (!($class = Loader::loadArrayClassFromModule ('TimeIt', 'Event'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }
        $class = new $class();
        $class->_objJoin = $join;
        
        return $class->getCount($where, true);
    }

    /**
     * Returns all events.
     * @param cat calendar id oder -1 to ignore calendars and get all events of all calendars
     * @param filter_obj TimeIt_Filter object
     * @param startnum page number (Default: 0)
     * @param numitems items per page (Default: -1)
     * @param preformat true to preformat all events (Default: true)
     * @param translate true to replace title,text with translations (Default: true)
     * @return array all events
     */
    public function getEvents($cid, TimeItFilter $filter_obj=null, $startnum=0, $numitems=-1, $preformat=true, $translate=true, $order='pn_title ASC')
    {
        if(empty($cid)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        // laod class
        if (!($class = Loader::loadArrayClassFromModule ('TimeIt', 'Event'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'event', ZLanguage::getModuleDomain('TimeIt')));
        }

        // get current lang
        $user_lang = ZLanguage::getLanguageCode();
        // load events
        $class = new $class();
        $class->_objPermissionFilter = array ('realm'            =>  0,
                                              'component_left'   =>  'TimeIt',
                                              'component_middle' =>  '',
                                              'component_right'  =>  'Event',
                                              'instance_left'    =>  'id',
                                              'instance_middle'  =>  '',
                                              'instance_right'   =>  '',
                                              'level'            =>  ACCESS_OVERVIEW);
        $class->_objJoin = array( array ('join_table'         =>  'TimeIt_date_has_events',
                                         'join_field'         =>  array('cid'),
                                         'object_field_name'  =>  array('cid'),
                                         'join_method'        =>  'LEFT JOIN',
                                         'compare_field_table'=>  'pn_id',
                                         'compare_field_join' =>  'eid'));

        // build where sql part
        $where = "pn_status = 1";

        if($cid >= 0) {
            $where .= " AND cid = ".(int)$cid;
        }

        if($filter_obj) {
            $sql = $filter_obj->toSQL();
            if($sql) {
                $where .= ' AND '.$sql;
            }
        }
        $where .= " GROUP BY pn_id";

        // load event form DB
        $array = $class->getWhere($where, $order, $startnum-1, $numitems);
        $ret = array();
        //print_r($array);exit();

        // process all events
        foreach($array AS $obj) {
            if(!TimeItPermissionUtil::canViewEvent($obj)) {
                continue;
            }

            if($translate) {
                if(isset($obj['title_translate'][$user_lang]) && !empty($obj['title_translate'][$user_lang])) {
                    $obj['title'] = $obj['title_translate'][$user_lang];
                }

                if(isset($obj['text_translate'][$user_lang]) && !empty($obj['text_translate'][$user_lang])) {
                    $obj['text'] = $obj['text_translate'][$user_lang];
                }
            }

            if($preformat) {
                $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj'=>$obj));
            }

            $ret[] = $obj;
        }

        // why sort the array?
        //usort($ret, "TimeIt_cat_usort");

        return $ret;
    }

    /**
     * @param int $cid calendar id
     * @return int found events
     */
    public function getNumberOfEvents($cid, TimeItFilter $filter_obj=null)
    {
        if(empty($cid)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }
        
        $t =& pnDBGetTables();

        $sql = "SELECT COUNT(DISTINCT pn_id)
                FROM ".$t['TimeIt_events']." tbl
                LEFT JOIN ".$t['TimeIt_date_has_events']." a
                    ON a.eid = tbl.pn_id ";

        // build where sql part
        $where = "a.cid = ".(int)$cid." AND tbl.pn_status = 1";
        if($filter_obj != null) {
            $fsql = $filter_obj->toSQL();
            if($sql) {
                $where .= ' AND '.$fsql;
            }
        }
        // count events
        return (int)DBUtil::selectScalar($sql." WHERE ".$where);
    }

    /**
     * Returns a list of dates. Each date in the list has min. one event.
     * @param array $args ['start'] start date
     *                    ['end'] end date
     *                    ['cid'] calendar id
     * @return array Dates(Format: yyyy-mm-dd) with events.
     */
    public function getDatesWithEvents($cid, $start, $end)
    {
        // valid Dates?
        if(!pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$start))
           || !pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$end))){
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        return DBUtil::selectFieldArray('TimeIt_date_has_events', 'date', "the_date >= '".DataUtil::formatForStore($start)."' AND the_date <= '".DataUtil::formatForStore($end)."' AND cid = ".(int)$cid, '', true);
    }

    /**
     * Returns the dheid's of the previous and next recurrences.
     * @param int $event_id
     * @param int $base_dhe_id
     * @return array [0] = dheid previous
     *               [1] = dheid next
     *               The value may be null
     */
    public function getPrevNexRecurrence($event_id, $base_dhe_id) {
        $values = DBUtil::selectFieldArray('TimeIt_date_has_events', 'date', 'eid = '.((int)$event_id), 'the_date ASC', false, 'id');
        $ids = array_keys($values);

        $pos = array_search($base_dhe_id, $ids);


        $dheid_prev = null;
        if($pos > 0)
            $dheid_prev = $ids[$pos-1];

        $dheid_next = null;
        if($pos < count($ids)-1)
            $dheid_next = $ids[$pos+1];

        return array($dheid_prev, $dheid_next);
    }

    /**
     * This function deletes all events of an user.
     * @param array $uid user id
     */
    function deleteEventsOfUser($uid)
    {
        // search events of user
        $events = DBUtil::selectFieldArray('TimeIt_events','id', 'pn_cr_uid = '.DataUtil::formatForStore($uid));

        // only continue work when $events contains at least one id
        if(!empty($events)) {
            // delete all registred users of these events
            DBUtil::deleteWhere('TimeIt_regs','pn_eid IN('.implode(',', $events).')');

            foreach($events AS $id) {
                $this->deleteObject($id);
            }
        }

        return true;
    }

    /**
     * This function anonymizes all events of an user.
     * @param array $uid user id
     */
    function anonymizeEventsOfUser($uid)
    {
        // search events of user
        $events = DBUtil::selectFieldArray('TimeIt_events','id', 'pn_cr_uid = '.DataUtil::formatForStore($args['uid']));

        // only continue work when $events contains at least one id
        if(!empty($events)) {
            // anonymize the cr_uid of all those events
            $obj = array('cr_uid' => 1);
            DBUtil::updateObject($obj, 'TimeIt_events','pn_id IN('.implode(',', $events).')');
        }
        
        return true;
    }
}
