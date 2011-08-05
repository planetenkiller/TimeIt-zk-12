<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

class PNEvent extends PNObject
{
    function PNEvent($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObject();
         
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
    
    function getEvent($id)
    {
        return $this->get($id);
    }


	function selectPostProcess ($obj=null)
    {
    	$this->_objData['data'] = unserialize($this->_objData['data']);
    }
    
	function insertPreProcess ($data=null)
    {
    	$this->_objData['data'] = serialize($this->_objData['data']);
    }
    
	function updatePreProcess ($data=null)
    {
        $this->_objData['data'] = serialize($this->_objData['data']);
    }
    
	function insertPostProcess ($data=null)
    {
    	$this->_objData['data'] = unserialize($this->_objData['data']);
    }
    
	function updatePostProcess ($data=null)
    {
        $this->_objData['data'] = unserialize($this->_objData['data']);
    }
}

    
    