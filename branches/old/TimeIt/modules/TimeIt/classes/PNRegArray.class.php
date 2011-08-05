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

class PNRegArray extends PNObjectArray
{
    function PNRegArray($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObjectArray();
         
        // set the tablename this object maps to
        $this->_objType  = 'TimeIt_regs';
        
        // set the ID field for this object
        $this->_objField = 'id';
        
        // set the access path under which the object's
        // input data can be retrieved upon input
        $this->_objPath  = 'regs';
        
        // Call initialization routing
        $this->_init($init, $where);
    }

    function selectPostProcess ($obj=null, $reformat=false)
    {
        foreach ($this->_objData as &$obj) {
            // unserialze values
            $obj['data'] = unserialize($obj['data']);
}
    }

    function insertPreProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = serialize($obj['data']);
        }
    }
    
    function updatePreProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = serialize($obj['data']);
        }
    }
    
    function insertPostProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
        }
    }

    function updatePostProcess ($data=null)
    {
        foreach ($this->_objData as $key => $obj) {
            $this->_objData[$key]['data'] = unserialize($obj['data']);
        }
    }
}

    
    
