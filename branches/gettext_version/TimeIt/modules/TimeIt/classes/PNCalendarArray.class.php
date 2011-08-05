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

class PNCalendarArray extends PNObjectArray
{
    function PNCalendarArray($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObjectArray();
         
        // set the tablename this object maps to
        $this->_objType  = 'TimeIt_calendars';
        
        // set the ID field for this object
        $this->_objField = 'id';
        
        // set the access path under which the object's
        // input data can be retrieved upon input
        $this->_objPath  = 'calendar';

        // set permission filter
        $this->_objPermissionFilter = array('realm'            =>  0,
                                            'component_left'   =>  'TimeIt',
                                            'component_middle' =>  '',
                                            'component_right'  =>  'Calendar',
                                            'instance_left'    =>  'id',
                                            'instance_middle'  =>  '',
                                            'instance_right'   =>  '',
                                            'level'            =>  ACCESS_OVERVIEW);
        
        // Call initialization routing
        $this->_init($init, $where);
    }

    function selectPostProcess ($obj=null)
    {
        
    }
    
    function insertPreProcess ($data=null)
    {
        
    }
    
    function updatePreProcess ($data=null)
    {
        
    }
    
    function insertPostProcess ($data=null)
    {
        
    }
    
    function updatePostProcess ($data=null)
    {
       
    }
}