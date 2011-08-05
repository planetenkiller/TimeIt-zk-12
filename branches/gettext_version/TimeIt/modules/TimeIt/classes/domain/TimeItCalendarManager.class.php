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
 * Calendar manager.
 *
 * @author planetenkiller
 */
class TimeItCalendarManager
{
    public function getObjectList($filter_obj=null)
    {
        // load the object array class
        if (!($class = Loader::loadArrayClassFromModule('TimeIt', 'calendar'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'calendar', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $objectArray = new $class();


        $where = '';
        // add filter to sql
        if($filter_obj != null) {
            $filter_sql = $filter_obj->toSQL();
            
            if(!empty($filter_sql)) {
                $where = $filter_sql;
            }
        }
        // get data form database
        $array = $objectArray->get($where);

        return $array;
    }

    public function getObjectListForDropdown($filter_obj=null) {
        $calendars = $this->getObjectList($filter_obj);
        $calendarsNew = array();
        foreach($calendars AS $calendar) {
            $calendarsNew[] = array('value' => $calendar['id'], 'text' => $calendar['name']);
        }

        return $calendarsNew;
    }

    public function getObject($id)
    {
        // load the object array class corresponding to $objectType
        if (!($class = Loader::loadClassFromModule('TimeIt', 'calendar'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'calendar', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $object = new $class();

        return $object->get($id);
    }

    public function deleteObject($id)
    {
        // load the object class
        if (!($class = Loader::loadClassFromModule('TimeIt', 'calendar'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'calendar', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $object = new $class();
        $object->get($id);
        return $object->delete() !== false;
    }

    public function createCalendar($obj)
    {
        // load the object array class corresponding to $objectType
        if (!($class = Loader::loadClassFromModule('TimeIt', 'calendar'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'calendar', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $object = new $class();
        $object->setData($obj);
        return $object->save();
    }

    public function updateCalendar($obj)
    {
        // load the object array class corresponding to $objectType
        if (!($class = Loader::loadClassFromModule('TimeIt', 'calendar'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'calendar', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $object = new $class();
        $object->setData($obj);
        return $object->save();
    }

    /**
     * Deletes all events which are lower or equals to $toDate.
     *
     * @param int $cid
     * @param string $toDate
     * @return bool
     */
    function deleteOldEvents($cid, $toDate)
    {
        if(empty($cid) || empty($toDate)) {
            return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }

        $rows = DBUtil::selectExpandedObjectArray ('TimeIt_date_has_events', array(array('join_table'          => 'TimeIt_events',
                                                                                         'join_field'          => array('endDate'),
                                                                                         'object_field_name'   => array('endDate'),
                                                                                         'compare_field_table' => 'eid',
                                                                                         'compare_field_join'  => 'id')), "the_date <= '".DataUtil::formatForStore($toDate)."'", 'eid ASC', -1, -1, '', null, null, array('eid','date'));
        $ignoreEvents = array();
        $datesOfEvent = array();
        $currentEid = -1;
        $eventManager = TimeItDomainFactory::getInstance('event');
        // process all rows
        foreach($rows AS $row) {
            // prevent double delete of an event
            if(!in_array($row['eid'], $ignoreEvents)) {
                if($currentEid > 0 && $currentEid != $row['eid']) {
                    $eventManager->deleteAllOccurrences($currentEid, $datesOfEvent);
                    $ignoreEvents[] = $currentEid;
                    $currentEid = -1;
                    $datesOfEvent = array();
                }


                // delete event itself
                if($row['endDate'] <= $toDate) {
                    $eventManager->deleteObject($row['eid']);
                    $ignoreEvents[] = $row['eid'];
                } else {
                    // delete occurrences of an event
                    if($currentEid == -1) {
                        $currentEid = $row['eid'];
                    }

                    $datesOfEvent[] = $row ['date'];
                }
            }
        }

        if($row['endDate'] > $args['date'] && $currentEid != $row['eid']) {
            $eventManager->deleteAllOccurrences($row['eid'], $datesOfEvent);
            $currentEid = -1;
            $datesOfEvent = array();
        }

        return true;
    }
}

