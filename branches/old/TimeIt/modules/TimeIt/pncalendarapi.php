<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage API
 */

function TimeIt_calendarapi_getAll($args)
{
    // Optional arguments.
    if (!isset($args['startnum']) || empty($args['startnum'])) 
    {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems']) || empty($args['numitems'])) 
    {
        $args['numitems'] = -1;
    }

    if (!is_numeric($args['startnum']) || !is_numeric($args['numitems']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    $array = DBUtil::selectObjectArray('TimeIt_calendars', '', '', $args['startnum']-1, $args['numitems']);
    // unserialize config data
    foreach($array AS &$obj) {
        $obj['config'] = unserialize($obj['config']);
        if(!$obj['config']) {
            $obj['config'] = array();
        }
        $obj = array_merge($obj, $obj['config']);
    }

    return $array;
}

function TimeIt_calendarapi_getAllForDropdown($args)
{
    $calendars = DBUtil::selectObjectArray('TimeIt_calendars');
    $array = array();
    
    foreach($calendars AS $calendar)
    {
        $array[] = array('value'=>$calendar['id'],'text'=>$calendar['name']);
    }
    
    return $array;
}

function TimeIt_calendarapi_get($id)
{
    if(empty($id)) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    static $cache = array();

    // clear cache?
    if($id == -1) {
        $cache = array();
        return true;
    } else {

        if(!isset($cache[$id]))
        {
            $cache[$id] = DBUtil::selectObjectByID('TimeIt_calendars', (int)$id);
            $cache[$id]['config'] = unserialize($cache[$id]['config']);
            // unserialize returns 0 if $cache[$id]['config'] is NULL
            // array_merge only accepts arrays
            if(!$cache[$id]['config']) {
                $cache[$id]['config'] = array();
            }
            $cache[$id] = array_merge($cache[$id], $cache[$id]['config']);
        }

        return $cache[$id];
    }
}

function TimeIt_calendarapi_getLastMod($id)
{
    if(empty($id)) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    static $cache = array();

    // clear cache?
    if($id == -1) {
        $cache = array();
        return true;
    } else {
        if(!isset($cache[$id])) {
            $cache[$id] = DBUtil::selectScalar('SELECT MAX(e.pn_lu_date) FROM zk_TimeIt_date_has_events d RIGHT JOIN zk_TimeIt_events e ON d.eid = e.pn_id WHERE d.cid = '.(int)$id);
        }

        return $cache[$id];
    }
}

function TimeIt_calendarapi_create($obj)
{
    if(empty($obj)) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if(is_array($obj['config'])) {
        $obj['config'] = serialize($obj['config']);
    }
    
    return DBUtil::insertObject($obj, 'TimeIt_calendars');
}

function TimeIt_calendarapi_update($obj)
{
    if(empty($obj)) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    if(is_array($obj['config'])) {
        $obj['config'] = serialize($obj['config']);
    }

    return DBUtil::updateObject($obj, 'TimeIt_calendars');
}

/**
 * Deletes the calendar with all its content.
 * 
 * @param int $id calendar id
 * @return bool
 */
function TimeIt_calendarapi_delete($id)
{
    $t = pnDBGetTables();

    $sqls =  array("DELETE FROM ".$t['TimeIt_events']." WHERE pn_id IN (SELECT DISTINCT eid FROM ".$t['TimeIt_date_has_events']." WHERE cid = ".(int)$id.")",
                  "DELETE FROM ".$t['TimeIt_regs']." WHERE pn_eid IN (SELECT DISTINCT eid FROM ".$t['TimeIt_date_has_events']." WHERE cid = ".(int)$id.")");

    foreach($sqls AS $sql) {
        DBUtil::executeSQL($sql);
    }

    DBUtil::deleteWhere('TimeIt_date_has_events', 'cid = '.(int)$id);
    DBUtil::deleteObjectByID('TimeIt_calendars', (int)$id);

    return true;
}

function TimeIt_calendarapi_clear($args)
{
    if(!isset($args['cid']) || !isset($args['date'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    $rows = DBUtil::selectExpandedObjectArray ('TimeIt_date_has_events', array(array('join_table'    => 'TimeIt_events',
                                                                                'join_field'         => array('endDate'),
                                                                                'object_field_name'  => array('endDate'),
                                                                                'compare_field_table'=> 'eid',
                                                                                'compare_field_join' => 'id')), "the_date <= '".DataUtil::formatForStore($args['date'])."'", 'eid ASC', -1, -1, '', null, null, array('eid','date'));
    $ignoreEvents = array();
    $datesOfEvent = array();
    $currentEid = -1;
    // process all rows
    foreach($rows AS $row) {
        // prevent double delete of an event
        if(!in_array($row['eid'], $ignoreEvents)) {
            if($currentEid > 0 && $currentEid != $row['eid']) {
                pnModAPIFunc('TimeIt','user','deleteAllRecurrences', array('obj'=>array('id'=>$currentEid),'dates'=>$datesOfEvent));
                $ignoreEvents[] = $currentEid;
                $currentEid = -1;
                $datesOfEvent = array();
            }


            // delete event itself
            if($row['endDate'] <= $args['date']) {
                pnModAPIFunc('TimeIt','user','delete', array('id'=>$row['eid']));
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
        pnModAPIFunc('TimeIt','user','deleteAllRecurrences', array('obj'=>array('id'=>$row['eid']),'dates'=>$datesOfEvent));
        $currentEid = -1;
        $datesOfEvent = array();
    }

    return true;
}