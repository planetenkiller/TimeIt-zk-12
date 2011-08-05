<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Core
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
                      'iid'        => 'pn_iid', // imported id
                      'title'      => 'pn_title',
                      'text'       => 'pn_text',
                      'title_translate'=> 'pn_title_translate',
                      'text_translate' => 'pn_text_translate',
                      'data'	   => 'pn_data',
                      
                      'allDay'     => 'pn_allDay',
                      'allDayStart'=> 'pn_allDayStart',
                      'allDayDur'  => 'pn_allDayDur',   
                      
                      'repeatType' => 'pn_repeatType',
                      'repeatSpec' => 'pn_repeatSpec',
                      'repeatFrec' => 'pn_repeatFrec',
                      'repeatIrg'  => 'pn_repeatIrg',
                      
                      'startDate'  => 'pn_startDate',  
                      'endDate'    => 'pn_endDate',
                      
                      'sharing'    => 'pn_sharing',
                      'group'	   => 'pn_group',
                      'status'     => 'pn_status',
                      'subscribeLimit'=>'pn_subscribeLimit',
                      'subscribeWPend'=>'pn_subscribeWPend'); // 1=with pending state 0=withount pending state
    ObjectUtil::addStandardFieldsToTableDefinition ($columns, 'pn_');
    $pntables['TimeIt_events_column'] = $columns;
    
    
    $columns  = array('id'       => 'I AUTO PRIMARY',
                      'iid'      => "C(255) NOTNULL DEFAULT ''",
                      'mid'      => 'C(32) NOTNULL',
                      'mmid'     => 'I1 NOTNULL DEFAULT 0',
                      'title'    => "C(255) NOTNULL DEFAULT ''",
                      'text'     => "X NOTNULL DEFAULT ''",
                      'title_translate'=> "X NOTNULL DEFAULT ''",
                      'text_translate' => "X NOTNULL DEFAULT ''",
                      'data'	 => "X NOTNULL DEFAULT ''",
                      
                      'allDay'      => "L NOTNULL",
                      'allDayStart' => "C(5) NOTNULL DEFAULT '00:00'" ,
                      'allDayDur'   => "C(15) NOTNULL DEFAULT '0'",
                      
                      'repeatType'  => "I1 NOTNULL",
                      'repeatSpec'  => "X NOTNULL  DEFAULT ''",
                      'repeatFrec'  => "I NOTNULL  DEFAULT 0",
                      'repeatIrg'   => "X NOTNULL  DEFAULT ''",
                      
                      'startDate'   => "D NOTNULL",
                      'endDate'     => "D NOTNULL",
                      
                      'sharing'     => "I1 NOTNULL DEFAULT 3",
                      'group'       => "C(255) NOTNULL DEFAULT 'all'",
                      'status'      => "L NOTNULL",
                      'subscribeLimit'=>'I NOTNULL DEFAULT 0',
                      'subscribeWPend'=>'I NOTNULL DEFAULT 0');
                      
    ObjectUtil::addStandardFieldsToTableDataDefinition ($columns, 'pn_');
    $pntables['TimeIt_events_column_def'] = $columns;
    // indexes removed because mysql doesn't use the indexes
    /*$pntables['TimeIt_events_column_idx'] = array ('startDate' => 'startDate',
                                                   'endDate'   => 'endDate',
                                                   'status'    => 'status',
                                                   'sharing'   => 'sharing',
                                                   'cid'       => 'cid');*/
    
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
    
    
    // ---------------- third table --------------------
    
    $pntables['TimeIt_calendars'] = DBUtil::getLimitedTablename('TimeIt_calendars');
     
    // Table fields
    $columns = array ('id'              => 'pn_id',
                      'name'            => 'pn_name',
                      'desc'            => 'pn_desc',
                      'privateCalendar' => 'pn_privateCalendar',
                      'globalCalendar'  => 'pn_globalCalendar',
                      'friendCalendar'  => 'pn_friendCalendar',
                      'config'          => 'pn_config'
                     );
    $pntables['TimeIt_calendars_column'] = $columns;
    
    
    $columns  = array('id'              => 'I AUTO PRIMARY',
                      'name'            => "C(255) NOTNULL DEFAULT ''",
                      'desc'            => "X NOTNULL DEFAULT ''",
                      'privateCalendar' => "L NOTNULL DEFAULT 0",
                      'globalCalendar'  => "L NOTNULL DEFAULT 1",
                      'friendCalendar'  => "L NOTNULL DEFAULT 0",
                      'config'          => "X NOTNULL DEFAULT ''"
                     );
    $pntables['TimeIt_calendars_column_def'] = $columns;

    // ---------------- fourth table --------------------

    $pntables['TimeIt_date_has_events'] = DBUtil::getLimitedTablename('TimeIt_date_has_events');

    // Table fields
    $columns = array ('id'              => 'id',
                      'eid'             => 'eid',
                      'localeid'        => 'localeid',
                      'date'            => 'the_date',
                      'cid'             => 'cid'
                     );
    $pntables['TimeIt_date_has_events_column'] = $columns;


    $columns  = array('id'              => 'I AUTO PRIMARY',
                      'eid'             => "I NOTNULL",
                      'localeid'        => "I NULL",
                      'date'            => "D NOTNULL",
                      'cid'             => "I NOTNULL"
                     );
    $pntables['TimeIt_date_has_events_column_def'] = $columns;
    
    
    return $pntables;
}
    
    
