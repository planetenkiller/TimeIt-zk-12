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

class PNReg extends PNObject
{
    function PNReg($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObject();
         
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

    function selectPostProcess ($obj=null)
    {
        // do the things only when there's a row
        if($this->_objData) {
            $this->_objData['data'] = unserialize($this->_objData['data']);
}
    }

    function insertPreProcess ($data=null)
    {
        if($this->_objData['data'])
            $this->_objData['data'] = serialize($this->_objData['data']);
    }
    
    function updatePreProcess ($data=null)
    {
        if($this->_objData['data'])
            $this->_objData['data'] = serialize($this->_objData['data']);
    }
    
    function insertPostProcess ($data=null)
    {
        if(!empty($obj)) {
            $this->_objData['data'] = unserialize($this->_objData['data']);
        }
    }

    function updatePostProcess ($data=null)
    {
        if(!empty($obj)) {
            $this->_objData['data'] = unserialize($this->_objData['data']);
        }
    }
}

    
    