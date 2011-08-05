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

class PNCalendar extends PNObject
{
    var $preserve = false;
    var $dheobj = null;

    function PNCalendar($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObject();
         
        // set the tablename this object maps to
        $this->_objType  = 'TimeIt_calendars';
        
        // set the ID field for this object
        $this->_objField = 'id';
        
        // set the access path under which the object's
        // input data can be retrieved upon input
        $this->_objPath  = 'calendar';
        
        // Call initialization routing
        $this->_init($init, $where);
    }
 
    function selectPostProcess ($obj=null)
    {
        if($this->_objData) {
            $this->_objData['config'] = unserialize($this->_objData['config']);

            // unserialize returns 0 if $cache[$id]['config'] is NULL
            // array_merge only accepts arrays
            if(!$this->_objData['config']) {
                $this->_objData['config'] = array();
            }
            
            $this->_objData = array_merge($this->_objData, $this->_objData['config']);
        }
    }
    
    function insertPreProcess ($data=null)
    {
        if($this->_objData['config']) {
            $this->_objData['config'] = serialize($this->_objData['config']);
        }

        return true;
    }
    
    function updatePreProcess ($data=null)
    {
        if($this->_objData['config']) {
            $this->_objData['config'] = serialize($this->_objData['config']);
        }

        return true;
    }
    
    function insertPostProcess ($data=null)
    {
        if($this->_objData) {
            $this->_objData['config'] = unserialize($this->_objData['config']);

            // unserialize returns 0 if $cache[$id]['config'] is NULL
            // array_merge only accepts arrays
            if(!$this->_objData['config']) {
                $this->_objData['config'] = array();
            }

            $this->_objData = array_merge($this->_objData, $this->_objData['config']);
        }
    }
    
    function updatePostProcess ($data=null)
    {
       if($this->_objData) {
            $this->_objData['config'] = unserialize($this->_objData['config']);

            // unserialize returns 0 if $cache[$id]['config'] is NULL
            // array_merge only accepts arrays
            if(!$this->_objData['config']) {
                $this->_objData['config'] = array();
            }

            $this->_objData = array_merge($this->_objData, $this->_objData['config']);
        }
    }

    function deletePostProcess($data = null)
    {
        $t = pnDBGetTables();
        $id = $this->_objData['id'];

        // delete statements
        $sqls =  array("DELETE FROM ".$t['TimeIt_events']."
                        WHERE pn_id IN (SELECT DISTINCT eid
                                        FROM ".$t['TimeIt_date_has_events']."
                                        WHERE cid = ".(int)$id.")",
                       "DELETE FROM ".$t['TimeIt_events']."
                        WHERE pn_id IN (SELECT DISTINCT localeid
                                        FROM ".$t['TimeIt_date_has_events']."
                                        WHERE cid = ".(int)$id.")",
                       "DELETE FROM ".$t['TimeIt_regs']."
                        WHERE pn_eid IN (SELECT DISTINCT id
                                         FROM ".$t['TimeIt_date_has_events']."
                                         WHERE cid = ".(int)$id.")",
                       "DELETE FROM ".$t['TimeIt_date_has_events']."
                                        WHERE cid = ".(int)$id);

        // exec all deletets
        foreach($sqls AS $sql) {
            DBUtil::executeSQL($sql);
        }
    }
}

    
    