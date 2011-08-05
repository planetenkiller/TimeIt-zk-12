<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Model
 */

Loader::requireOnce('modules/TimeIt/common.php');

class PNEventArray extends PNObjectArray
{
    protected static $tableCols = array ('id','iid','title','text', 'title_translate', 'text_translate','data','allDay','allDayStart','allDayDur','repeatType','repeatSpec','repeatFrec','repeatIrg', 'startDate','endDate','sharing','group','status','subscribeLimit','subscribeWPend','cr_date','cr_uid','lu_date','lu_uid');

    function PNEventArray($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObjectArray();
         
        // set the tablename this object maps to
        $this->_objType  = 'TimeIt_events';
        
        // set the ID field for this object
        $this->_objField = 'id';
        
        // set the access path under which the object's
        // input data can be retrieved upon input
        $this->_objPath  = 'event';

        // set permission filter
        $this->_objPermissionFilter = array('realm'            =>  0,
                                            'component_left'   =>  'TimeIt',
                                            'component_middle' =>  '',
                                            'component_right'  =>  'Event',
                                            'instance_left'    =>  'id',
                                            'instance_middle'  =>  '',
                                            'instance_right'   =>  '',
                                            'level'            =>  ACCESS_OVERVIEW);
        
        // Call initialization routing
        $this->_init($init, $where);
    }

    public function get($where)
    {
        $this->_objData = DBUtil::selectExpandedObjectArray('TimeIt_date_has_events',             array(array ('join_table'         => 'TimeIt_events',
                                                                                                               'join_field'         =>  self::$tableCols,
                                                                                                               'object_field_name'  =>  array ('a_id','a_iid','a_title','a_text', 'a_title_translate', 'a_text_translate','a_data','a_allDay','a_allDayStart','a_allDayDur','a_repeatType','a_repeatSpec','a_repeatFrec','a_repeatIrg', 'a_startDate','a_endDate','a_sharing','a_group','a_status','a_subscribeLimit','a_subscribeWPend','a_cr_date','a_cr_uid','a_lu_date','a_lu_uid'),
                                                                                                               'compare_field_table'=> 'eid',
                                                                                                               'compare_field_join' => 'id',
                                                                                                               'join_method'        => 'LEFT JOIN'),
                                                                                                        array ('join_table'         => 'TimeIt_events',
                                                                                                               'join_field'         =>  self::$tableCols,
                                                                                                               'object_field_name'  =>  array ('b_id','b_iid','b_title','b_text', 'b_title_translate', 'b_text_translate','b_data','b_allDay','b_allDayStart','b_allDayDur','b_repeatType','b_repeatSpec','b_repeatFrec','b_repeatIrg', 'b_startDate','b_endDate','b_sharing','b_group','b_status','b_subscribeLimit','b_subscribeWPend','b_cr_date','b_cr_uid','b_lu_date','b_lu_uid'),
                                                                                                               'compare_field_table'=> 'localeid',
                                                                                                               'compare_field_join' => 'id',
                                                                                                               'join_method'        => 'LEFT JOIN')), $where);


        $this->selectPostProcess(null, true);
        ObjectUtil::expandObjectArrayWithCategories($this->_objData, 'TimeIt_events');
        return $this->_objData;
    }
    
    function selectPostProcess ($obj=null, $reformat=false)
    {
        $events = count($this->_objData);
        if($events >= 100 && pnModAvailable('locations') && pnModDBInfoLoad('locations') && DBUtil::selectObjectCount('locations_location') <= $events*3) {
            // There are over 100 events to display. Locations integration can prefill the cache.
            TimeItEventPluginsUtil::getEventPluginInstance('LocationLocations'); // load class
            TimeItEventPluginsLocationLocations::setPrefilLocationCache(true);
        }

        foreach ($this->_objData as &$obj) {
            if($reformat) {
                // reformat array
                $obj['dhe_id']       = $obj['id'];
                $obj['dhe_eid']      = $obj['eid'];
                $obj['dhe_localeid'] = $obj['localeid'];
                $obj['dhe_date']     = $obj['date'];
                $obj['dhe_cid']      = $obj['cid'];
                // delete old values
                unset($obj['id'], $obj['eid'], $obj['localeid'], $obj['localeid'], $obj['date']);


                if(!empty($obj['b_id'])) {
                    $mode = 'b';
                } else {
                    $mode = 'a';
                }
                // reformat array
                foreach(self::$tableCols AS $col) {
                    $obj[$col] = $obj[$mode.'_'.$col];
                    unset($obj['a_'.$col], $obj['b_'.$col]);
                }
            }

            // unserialze values
            if(!empty($obj['data']))
                $obj['data'] = unserialize($obj['data']);
            else
                $obj['data'] = array();

            if(!empty($obj['title_translate']))
                $obj['title_translate'] = unserialize($obj['title_translate']);
            else
                $obj['title_translate'] = array();

            if(!empty($obj['text_translate']))
                $obj['text_translate'] = unserialize($obj['text_translate']);
            else
                $obj['text_translate'] = array();

            // save possible changes
            if(TimeIt_decorateWitEventPlugins($obj)) {
                if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                    pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
                }
                $tobj = $obj;
                // ObjectUtil:storeObjectCategories needs the format property=>categoryid
                foreach($tobj['__CATEGORIES__'] AS $prop => $cat) {
                    if(is_array($cat)) {
                        $tobj['__CATEGORIES__'][$prop] = $cat['id'];
                    }
                }
                $object = new $class();
                $object->getEvent($id);
                $object->setData($tobj);
                $object->save();
            }
        }
    }
    
    function insertPreProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = serialize($obj['data']);
            $this->_objData[$key]['title_translate'] = serialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = serialize($obj['text_translate']);
        }
    }
    
    function updatePreProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = serialize($obj['data']);
            $this->_objData[$key]['title_translate'] = serialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = serialize($obj['text_translate']);
        }
    }
    
    function insertPostProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
            $this->_objData[$key]['title_translate'] = unserialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = unserialize($obj['text_translate']);
        }
    }
    
    function updatePostProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
            $this->_objData[$key]['title_translate'] = unserialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = unserialize($obj['text_translate']);
        }
    }
}