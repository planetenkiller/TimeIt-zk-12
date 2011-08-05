<?php


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
                      'language'   => 'pn_language');
    ObjectUtil::addStandardFieldsToTableDefinition ($columns, 'pn_');
    $pntables['TimeIt_events_column'] = $columns;
    
    
    $columns  = array('id'       => 'I AUTO PRIMARY',
                      'title'    => "C(255) NOTNULL DEFAULT ''",
                      'text'     => "X NOTNULL DEFAULT ''",
    				  'data'	 => "X NOTNULL DEFAULT ''",
                      
                      'allDay'      => "L NOTNULL",
                      'allDayStart' => "C(5) NOTNULL DEFAULT '00:00'" ,
                      'allDayDur'   => "I NOTNULL DEFAULT '0'",
                      
                      'repeatType'  => "I1 NOTNULL",
                      'repeatSpec'  => "X NOTNULL  DEFAULT ''",
                      'repeatFrec'  => "I NOTNULL  DEFAULT 0",
                      
                      'startDate'   => "D NOTNULL",
                      'endDate'     => "D NOTNULL",
                      
                      'sharing'     => "I1 NOTNULL",
    				  'group'		=> "C(255) NOTNULL DEFAULT ''",
                      'status'      => "L NOTNULL",
                      'language'    => "C(255) NOTNULL");
                      
    ObjectUtil::addStandardFieldsToTableDataDefinition ($columns, 'pn_');
    $pntables['TimeIt_events_column_def'] = $columns;
    $pntables['TimeIt_events_column_idx'] = array ('startDate' => 'startDate',
                                                   'endDate'   => 'endDate',
                                                   'status'    => 'status',
                                                   'sharing'   => 'sharing',
                                                   'cr_uid'    => 'cr_uid');
    
    $pntables['TimeIt_events_db_extra_enable_categorization'] = true;
    
    return $pntables;
}
    
    
