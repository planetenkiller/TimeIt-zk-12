<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

/**
 * This function is called internally by the core whenever the module is loaded.
 */
function TimeIt_pntables()
{
    // Initialise table array
    $pntables = array();    
    
    $pntables['TimeIt_events'] = DBUtil::getLimitedTablename('TimeIt_events');
     
    // Table fields
    $columns = array ('id'         => 'pn_id',
                      'title'      => 'pn_title',
                      'text'       => 'pn_text',
    				  'data'	   => 'pn_data',
                      
                      'allDay'     => 'pn_allDay',
                      'allDayStart'=> 'pn_allDayStart',
                      'allDayDur'  => 'pn_allDayDur',   
                      
                      'repeatType' => 'pn_repeatType',
                      'repeatSpec' => 'pn_repeatSpec',
                      'repeatFrec' => 'pn_repeatFrec',
                      
                      'startDate'  => 'pn_startDate',  
                      'endDate'    => 'pn_endDate',
                      
                      'sharing'    => 'pn_sharing',
    				  'group'	   => 'pn_group',
                      'status'     => 'pn_status',
                      'language'   => 'pn_language',
    				  'subscribeLimit'=>'pn_subscribeLimit',
    				  'subscribeWPend'=>'pn_subscribeWPend'); // 1=with pending state 0=withount pending state
    ObjectUtil::addStandardFieldsToTableDefinition ($columns, 'pn_');
    $pntables['TimeIt_events_column'] = $columns;
    
    
    $columns  = array('id'       => 'I AUTO PRIMARY',
                      'title'    => "C(255) NOTNULL DEFAULT ''",
                      'text'     => "X NOTNULL DEFAULT ''",
    				  'data'	 => "X NOTNULL DEFAULT ''",
                      
                      'allDay'      => "L NOTNULL",
                      'allDayStart' => "C(5) NOTNULL DEFAULT '00:00'" ,
                      'allDayDur'   => "C(15) NOTNULL DEFAULT '0'",
                      
                      'repeatType'  => "I1 NOTNULL",
                      'repeatSpec'  => "X NOTNULL  DEFAULT ''",
                      'repeatFrec'  => "I NOTNULL  DEFAULT 0",
                      
                      'startDate'   => "D NOTNULL",
                      'endDate'     => "D NOTNULL",
                      
                      'sharing'     => "I1 NOTNULL DEFAULT 3",
    				  'group'		=> "C(255) NOTNULL DEFAULT 'all'",
                      'status'      => "L NOTNULL",
                      'language'    => "C(255) NOTNULL",
    				  'subscribeLimit'=>'I NOTNULL DEFAULT 0',
    				  'subscribeWPend'=>'I NOTNULL DEFAULT 0');
                      
    ObjectUtil::addStandardFieldsToTableDataDefinition ($columns, 'pn_');
    $pntables['TimeIt_events_column_def'] = $columns;
    $pntables['TimeIt_events_column_idx'] = array ('startDate' => 'startDate',
                                                   'endDate'   => 'endDate',
                                                   'status'    => 'status',
                                                   'sharing'   => 'sharing',
                                                   'cr_uid'    => 'cr_uid');
    
    $pntables['TimeIt_events_db_extra_enable_categorization'] = pnModGetVar('TimeIt', 'enablecategorization', true);
    
    
    //--------------- secound table ---------------------------------
    $pntables['TimeIt_regs'] = DBUtil::getLimitedTablename('TimeIt_regs');
     
    // Table fields
    $columns = array ('id'    	=> 'pn_id',
                      'eid'     => 'pn_eid',
                      'uid' 	=> 'pn_uid',
    				  'status'  => 'pn_status'); // 1 = ok, 0 = pending state
    ObjectUtil::addStandardFieldsToTableDefinition ($columns, 'pn_');
    $pntables['TimeIt_regs_column'] = $columns;
    
    $columns  = array('id'   	=> 'I AUTO PRIMARY',
                      'eid'    	=> "I NOTNULL",
                      'uid'    	=> "I NOTNULL",
    				  'status'  => "I NOTNULL DEFAULT 1");              
    ObjectUtil::addStandardFieldsToTableDataDefinition ($columns, 'pn_');
    $pntables['TimeIt_regs_column_def'] = $columns;
    
    return $pntables;
}
    
    
