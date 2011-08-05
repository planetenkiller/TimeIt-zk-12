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
        
        // Call initialization routing
        $this->_init($init, $where);
    }
    
    
    function events($sql, $catFilter=null, $limitOffset=-1, $limitNumRows=-1)
    {
        $this->_objCategoryFilter = $catFilter;
        $ret = $this->get($sql, '', $limitOffset, $limitNumRows);
        $this->_objCategoryFilter = null;
        
        return $ret;
    }

    public function eventsNew($where, $joins=array()) {
        $this->_objData = DBUtil::selectExpandedObjectArray('TimeIt_events', array_merge(array(array ('join_table'        =>  'TimeIt_date_has_events',
                                                                                         'join_field'         =>  array('id','eid','localeid','date','cid', 'cid'),
                                                                                         'object_field_name'  =>  array('dhe_id','dhe_eid','dhe_localeid','dhe_date','dhe_cid','cid'),
                                                                                         'join_on'            =>  '((a.eid = tbl.pn_id AND a.localeid IS NULL) OR (a.localeid IS NOT NULL AND a.localeid = tbl.pn_id))',
                                                                                         'join_method'        =>  'RIGHT JOIN')),$joins), $where);
        
        $this->selectPostProcess();
        return $this->_objData;
    }
    
    function count($where='', $catFilter=NULL)
    {
        $this->_objCategoryFilter = $catFilter;
        $ret = $this->getCount($where);
        $this->_objCategoryFilter = null;
        
        return $ret;
    }

    function selectPostProcess ($obj=null)
    {
        foreach ($this->_objData as $key => $obj)
        {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
            $this->_objData[$key]['title_translate'] = unserialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = unserialize($obj['text_translate']);
            // save possible changes
            if(TimeIt_decorateWitEventPlugins($this->_objData[$key])) {
                if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
                    pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
                }
                $object = new $class();
                $object->getEvent($id);
                $object->setData($this->_objData[$key]);
                $object->save();
            }
        }
    }
    
    function insertPreProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj)
        {
            $this->_objData[$key]['data'] = serialize($obj['data']);
            $this->_objData[$key]['title_translate'] = serialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = serialize($obj['text_translate']);
        }
    }
    
    function updatePreProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj)
        {
            $this->_objData[$key]['data'] = serialize($obj['data']);
            $this->_objData[$key]['title_translate'] = serialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = serialize($obj['text_translate']);
        }
    }
    
    function insertPostProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj)
        {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
            $this->_objData[$key]['title_translate'] = unserialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = unserialize($obj['text_translate']);
        }
    }
    
    function updatePostProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj)
        {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
            $this->_objData[$key]['title_translate'] = unserialize($obj['title_translate']);
            $this->_objData[$key]['text_translate'] = unserialize($obj['text_translate']);
        }
    }
}

    
    
