<?php

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
    
    
    function events($sql, $catFilter=null)
    {
    	$this->_objCategoryFilter = $catFilter;
        $ret = $this->get($sql);
        $this->_objCategoryFilter = null;
        
        return $ret;
    }

	function selectPostProcess ($obj=null)
    {
    	foreach ($this->_objData as $obj) {
    		$obj['data'] = unserialize($obj['data']);
    	} 	
    }
    
	function insertPreProcess ($data=null)
    {
    	foreach ($this->_objData as $obj) {
    		$obj['data'] = unserialize($obj['data']);
    	}
    }
    
	function updatePreProcess ($data=null)
    {
    	foreach ($this->_objData as $obj) {
    		$obj['data'] = unserialize($obj['data']);
    	}
    }
    
	function insertPostProcess ($data=null)
    {
    	foreach ($this->_objData as $obj) {
    		$obj['data'] = unserialize($obj['data']);
    	}
    }
    
	function updatePostProcess ($data=null)
    {
    	foreach ($this->_objData as $obj) {
    		$obj['data'] = unserialize($obj['data']);
    	}
    }
}

    
    
