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