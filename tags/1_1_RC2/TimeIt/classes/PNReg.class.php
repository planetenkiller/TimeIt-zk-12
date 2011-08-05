<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
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

}

    
    