<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function TimeIt_userapi_getDailySortedEvents($args)
{
	if(!isset($args['start']) || !isset($args['end']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
	// valid Dates?
	if(!pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$args['start'])) || !pnModAPIFunc('TimeIt','user','checkDate',array('date'=>$args['end'])))
    {
    	return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }
    
    // load class CategoryUtil
    Loader::loadClass('CategoryUtil');
    
    $start = $args['start'];
    $end = $args['end'];
    $catFilter = (isset($args['catFilter']))? $args['catFilter']: null;
    $shareFilter = (isset($args['shareFilter']))? $args['shareFilter']: null;
    $userFilter = (isset($args['userFilter']))? $args['userFilter']: null;
    
        $pntable = pnDBGetTables();
        $column = $pntable['TimeIt_events_column'];
        
        $sql = '(( '.$column['startDate'].' >= "'.DataUtil::formatForStore($start).'" AND '.$column['endDate'].' <= "'.DataUtil::formatForStore($end).'" ) 
                 OR ('.$column['endDate'].' >= "'.DataUtil::formatForStore($start).'" AND '.$column['repeatType'].' > 0)
                 OR ('.$column['startDate'].' <= "'.DataUtil::formatForStore($start).'" AND '.$column['endDate'].' <= "'.DataUtil::formatForStore($end).'" AND '.$column['endDate'].' >= "'.DataUtil::formatForStore($start).'")
  				 OR ('.$column['startDate'].' >= "'.DataUtil::formatForStore($start).'" AND '.$column['startDate'].' <= "'.DataUtil::formatForStore($end).'" AND '.$column['endDate'].' >= "'.DataUtil::formatForStore($end).'")
  				 OR ('.$column['startDate'].' <= "'.DataUtil::formatForStore($start).'" AND '.$column['endDate'].' >= "'.DataUtil::formatForStore($end).'")
                 
                 ) AND '.$column['status'].' = 1';
        $User_ID = pnUserGetVar('uid',-1,1);// deafult 1 = Annonymous User
        
        if(!empty($userFilter))
        {
        	$sql .= ' AND (('.$column['sharing'].' = 2';
        	if((int)$userFilter == $User_ID)
        	{
        		$sql .= ' OR '.$column['sharing'].' = 1';
        	}
        	$sql .= ')';
        	$sql .= ' AND pn_cr_uid = '.DataUtil::formatForStore((int)$userFilter);
			$sql .= ')';
        } else if(!empty($shareFilter) && ((int)$shareFilter == 1 || (int)$shareFilter == 2))
        {
        	$sql .= ' AND '.$column['sharing'].' = '.DataUtil::formatForStore((int)$shareFilter).'';
			if(!empty($User_ID))
			{
        		$sql .= ' AND pn_cr_uid = '.DataUtil::formatForStore(pnUserGetVar('uid'));
			}
        } else if(!empty($shareFilter) && (int)$shareFilter == 3)
        {
            $sql .= ' AND '.$column['sharing'].' = 3';
        } else if(!empty($shareFilter) && (int)$shareFilter == 4)
        {
            $sql .= ' AND '.$column['sharing'].' = 4';
        } else
        {
        	$sql .= ' AND ( ('.$column['sharing'].' = 1';
			if(!empty($User_ID))
			{
        		$sql .= ' AND pn_cr_uid = '.DataUtil::formatForStore(pnUserGetVar('uid'));
			}
			
			$sql .= ') OR '.$column['sharing'].' = 2 OR '.$column['sharing'].' = 3 OR '.$column['sharing'].' = 4)';
        }
        
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
        }
        $class = new $class();
        $class->_objPermissionFilte = array ('realm'            =>  0,
                              'component_left'   =>  'TimeIt',
                              'component_middle' =>  '',
                              'component_right'  =>  'Event',
                              'instance_left'    =>  'id',
                              'instance_middle'  =>  '',
                              'instance_right'   =>  '',
                              'level'            =>  ACCESS_OVERVIEW);
        $array = $class->events($sql, $catFilter);
        $ret = array();
        //print_r($array);exit();
        
        // --------------- ContactList integration --------------------
        $buddys = array();
        $ignored = array();
        if (pnModAvailable('ContactList')) 
        {
        	if(pnModGetVar('TimeIt', 'friendCalendar'))
        	{
        		$buddys = pnModAPIFunc('ContactList','user','getBuddyList',array('uid'=>$User_ID));
        	}
        	
        	$ignored = pnModAPIFunc('ContactList','user','getallignorelist',array('uid'=>$User_ID));
        } 
        // --------------- ContactList integration --------------------
        
        foreach($array AS $obj)
        {
            // Has user got access to one category? 
        	if(pnModGetVar('TimeIt', 'filterByPermission', 0) && !CategoryUtil::hasCategoryAccess($obj['__CATEGORIES__'],'TimeIt'))
            {
            	// no access to any category in this object -> ignore event
            	continue;
            }
        	
        	// --------------- ContactList integration --------------------
        	if((int)$obj['sharing'] == 4 && $obj['cr_uid'] != $User_ID)
            {
            	$buddyFound = false;
            	foreach($buddys AS $buddy)
            	{
            		if($buddy['uid'] == $obj['cr_uid'])
            		{
            			$buddyFound = true;
            			break;
               		}
            	}
            	if(!$buddyFound)
            	{
            		continue; // no buddy connection to cr_uid -> ignore event
            	}
            }
            
        	$ignoredFound = false;
            foreach($ignored AS $ignore)
            {
            	if($ignore['iuid'] == $obj['cr_uid'])
            	{
            		$ignoredFound = true;
            		break;
               	}
            }
            if($ignoredFound)
            {
            	continue; // current user is ignoring cr_uid -> ignore event
            }
            // --------------- ContactList integration --------------------
        	
        	if(substr($obj['text'],0,11) == "#plaintext#")
    		{
    			$obj['text'] = substr_replace($obj['text'],"",0,11);
    			$obj['text'] = nl2br($obj['text']);
    		}
            
        	if($obj['repeatType'] == 0)
            {
                
                if($obj['endDate'] == $obj['startDate'])
                {
                    //$ret[ DateUtil::parseUIDate($obj['startDate'])  ][] = $obj;
                    TimeIt_privuserapi_addEventToArray($ret, DateUtil::parseUIDate($obj['startDate']), $obj);
                } else
                {
                    $diff = DateUtil::getDatetimeDiff($obj['startDate'], $obj['endDate']);
                    
                    $timestamp = DateUtil::parseUIDate($obj['startDate']);
                    $timestamp = mktime(0,0,0,date('n',$timestamp), date('j',$timestamp), date('Y',$timestamp));
                    for($i=0;$i<=$diff['d'];$i++)
                    {
						$timestamp += 86400;
                        //$ret[$timestamp][] = $obj;
                        TimeIt_privuserapi_addEventToArray($ret, $timestamp, $obj);
                    }
                }
            } else if($obj['repeatType'] == 1)
            {
                    $time = $start;
                    $diff = DateUtil::getDatetimeDiff($obj['startDate'], $time);
//print_r($time);exit();                    
                    
                    if($obj['repeatSpec'] == "week")
                    {
                    	$weeks = (int)date('W', strtotime($start)) - (int)date('W', strtotime($obj['startDate']));
                    	$weeks = (int)floor($weeks / $obj['repeatFrec']);
                    	if($weeks < 0) $weeks = 0;
                    	
                    	$date = DateUtil::getDatetime(strtotime('+'.($weeks*$obj['repeatFrec']).' week', strtotime($obj['startDate'])), _DATEINPUT);
                    	while($date <= $end && $date <= $obj['endDate'])
                    	{
                    		if($date >= $start && $date <= $end && $date >= $obj['startDate'])
                    		{
                    			$temp = getDate(strtotime($date));
                    			$temp = mktime(0,0,0, $temp['mon'], $temp['mday'], $temp['year']);
                        		//$ret[$temp][] = $obj;
                        		TimeIt_privuserapi_addEventToArray($ret, $temp, $obj);
                    		}
                    		$weeks++;
                    		$date = DateUtil::getDatetime(strtotime('+'.($weeks*$obj['repeatFrec']).' week', strtotime($obj['startDate'])), _DATEINPUT);
                    	}
                    	
                    } else if($obj['repeatSpec'] == "month")
                    {
                    	$years = ((int)date('Y', strtotime($end))) - (int)date('Y', strtotime($obj['startDate']));
                    	$months = $years * 12;
                    	$monthsTemp = ((int)date('n')) - (int)date('n', strtotime($obj['startDate']));
                    	$months = $months + $monthsTemp;
                    	$months = (int)floor($months / $obj['repeatFrec']);
                    	
                    	$date = DateUtil::getDatetime(strtotime('+'.($months*$obj['repeatFrec']).' month', strtotime($obj['startDate'])), _DATEINPUT);
                    	while($date <= $end && $date <= $obj['endDate'])
                    	{
                    		if($date >= $start && $date <= $end && $date >= $obj['startDate'])
                    		{
                    			$temp = getDate(strtotime($date));
                    			$temp = mktime(0,0,0, $temp['mon'], $temp['mday'], $temp['year']);
                        		//$ret[$temp][] = $obj;
                        		TimeIt_privuserapi_addEventToArray($ret, $temp, $obj);
                    		}
                    		$months++;
                    		$date = DateUtil::getDatetime(strtotime('+'.($months*$obj['repeatFrec']).' month', strtotime($obj['startDate'])), _DATEINPUT);
                    	}
                    } else if($obj['repeatSpec'] == "year")
                    {
                    	$years = ((int)date('Y', strtotime($end))) - (int)date('Y', strtotime($obj['startDate']));
                    	$years = (int)floor($years / $obj['repeatFrec']);
                    	
                    	if(date('n') == date('n', strtotime('+'.($years*$obj['repeatFrec']).' year',strtotime($obj['startDate']))))
                    	{
                    		$temp = getDate(strtotime('+'.($years*$obj['repeatFrec']).' year',strtotime($obj['startDate'])));
                    		$temp = mktime(0,0,0, $temp['mon'], $temp['mday'], $temp['year']);
                        	//$ret[$temp][] = $obj;
                        	TimeIt_privuserapi_addEventToArray($ret, $temp, $obj);
                    	}
                    } else
                    {
                    	$repeats = (int)floor($diff['d']/$obj['repeatFrec']);
                    	
                    	$repeats--;
                    	if($repeats < 0)
                    	{
                        	$daysToLastUnusedRepeat = (int)(-$obj['repeatFrec']);
                   		/* } else if($repeats == 0)
                    	{
                        	$daysToLastUnusedRepeat = 0 - $obj['repeatFrec'];*/
                    	} else
                    	{
                        $daysToLastUnusedRepeat = $repeats * $obj['repeatFrec'];
                    	}

	                    $timestamp = DateUtil::parseUIDate($obj['startDate']);
	                    $timestampEnd = DateUtil::parseUIDate($end);
	                    $counter = $obj['repeatFrec'];
//print_r($daysToLastUnusedRepeat);exit();
	                    while(true)
	                    {
	                        $temp = mktime(0,0,0,date('n',$timestamp), date('j',$timestamp)+$daysToLastUnusedRepeat+$counter, date('Y',$timestamp));
	                        $counter += $obj['repeatFrec'];
	                        if($temp > DateUtil::parseUIDate($obj['endDate']) || $temp > $timestampEnd)
	                        {
	                            break;
	                        }
	                        //$ret[$temp][] = $obj;
	                        TimeIt_privuserapi_addEventToArray($ret, $temp, $obj);
	                    }
                    
                    }
                
            } else if($obj['repeatType'] == 2)
            {
            	$typMap = array(1 => 'first',
                          		2 => 'second',
                          		3 => 'third',
                          		4 => 'fourth',
                          		5 => 'last');
                $weekdayMap = array(0 => 'sun',
                					1 => 'mon',
                          			2 => 'tue',
                          			3 => 'wed',
                          			4 => 'thu',
                          			5 => 'fri',
                          			6 => 'sat');
            	$spec = explode(' ', $obj['repeatSpec']);
            	
            	// calc unix timestamp
            	$a = $typMap[(int)$spec[0]];
            	$b = $weekdayMap[(int)$spec[1]];
            	if((int)$spec[0] == 5)
            	{	// special case
            		$temp = explode('-', $start);
            		$date = strtotime('+1 month', mktime(0,0,0, (int)$temp[1], 1, (int)$temp[0]));
            	} else
            	{
            		$temp = explode('-', $start);
            		$date = mktime(0,0,0, (int)$temp[1], 1, (int)$temp[0]);
            	}
            	$stamp = strtotime($a.' '.$b, $date);
            	
            	$months = 0;
            	$date = DateUtil::getDatetime($stamp, _DATEINPUT);
            	while($date <= $end && $date <= $obj['endDate'])
                {
                    if($date >= $start && $date <= $end && $date >= $obj['startDate'])
                    {
                    	$temp1 = getDate(strtotime($date));
                    	$temp1 = mktime(0,0,0, $temp1['mon'], $temp1['mday'], $temp1['year']);
                        //$ret[$temp1][] = $obj;
                        TimeIt_privuserapi_addEventToArray($ret, $temp1, $obj);
                    }
                    
                    $months++;
	                // calc unix timestamp
	            	if((int)$spec[0] == 5)
	            	{	// special case: last from strtotime(to the past) != last form TimeIt(to the future)
	            		$month_calc = $months*(int)$obj['repeatFrec']+1;
	            	} else
	            	{
	            		$month_calc = $months*(int)$obj['repeatFrec'];
	            	}
	            	$tdate = strtotime('+'.$month_calc.' month', mktime(0,0,0, (int)$temp[1], 1, (int)$temp[0]));
	            	$stamp = strtotime($a.' '.$b, $tdate);
                    $date = DateUtil::getDatetime($stamp, _DATEINPUT);
              	}
            }
        }
        
        ksort($ret); // sort keys in array
        //print_r($ret);exit();
        
        return $ret;
}

function TimeIt_privuserapi_addEventToArray(&$array, $tmestamp, $obj)
{
	$property = pnModGetVar('TimeIt', 'colorCatsProp', 'Main');
	// get category id
    $catID = $obj['__CATEGORIES__'][$property]['id'];
   		
    // isn't the category id set on $array?
   	if(!isset($array[$tmestamp][$catID]))
   	{
   			$array[$tmestamp][$catID] = array();
   			$array[$tmestamp][$catID]['info'] = array('name'=>$obj['__CATEGORIES__'][$property]['name'],'color'=>$obj['__CATEGORIES__'][$property]['__ATTRIBUTES__']['color']);
   			$array[$tmestamp][$catID]['data'] = array();
   			if(empty($array[$tmestamp][$catID]['info']['color']))
   			{
   				$array[$tmestamp][$catID]['info']['color'] = pnModGetVar('TimeIt', 'defalutCatColor');
   			}
   			
   	}
   		
   	// add event to category
   	$array[$tmestamp][$catID]['data'][] = $obj;
}

function TimeIt_userapi_yearEvents($args)
{
	if( !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
    	// valid Date?
		if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    	{
    		return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    	}
    	
    	$arrayOfMonths = array();
    	for($i=1;$i<=12;$i++)
    	{
    		$date = DateUtil::getDatetime(mktime(0,0,0,$i,DateUtil::getDaysInMonth($i, $args['year']),$args['year']), _DATEINPUT);
    		$arrayOfMonths[$date] = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month' => $i, 'year'=> $args['year']));
    	}
    	//asort($arrayOfMonths);
    	return $arrayOfMonths;
    }
}

function TimeIt_userapi_monthEvents($args)
{
    if(!isset($args['month']) || !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
    	// valid Date?
		if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    	{
    		return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    	}
    	
    	$GETMonth = (int)$args['month'];
        $GETYear = (int)$args['year'];
    
        // get usefull dates
        $navdates = pnModAPIFunc('TimeIt', 'user', 'navdates', array('month' => $GETMonth, 'year'=> $GETYear));

        // get events form db
        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => $navdates['dateFirstDayInWeek_FirstWeekOfMonth'],                   
                  'end' => $navdates['dateLastDayInWeek_LastWeekOfMonth'],
            	  'catFilter' => ((isset($args['catFilter']))? $args['catFilter']: null),
            	  'shareFilter' => ((isset($args['shareFilter']))? $args['shareFilter']: null),
                  'userFilter'  => ((isset($args['userFilter']))? $args['userFilter']: null) )
            );
            
        // get array from api function
        $events = pnModAPIFunc('TimeIt', 'user', 'arrayForMonthView', array('month' => $GETMonth, 'year'=> $GETYear));
    
        // insert events from data to the events array
        foreach($events AS $weeknr=>$days)
        {
            foreach($days AS $k=>$v)
            {
                $events[$weeknr][$k] = $data[strtotime($k)];
            }
        }
        
        return $events;
    }
}

function TimeIt_userapi_weekEvents($args)
{
    if(!isset($args['week']) || !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
    	// valid Date?
		if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    	{
    		return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    	}
    	
    	$GETWeek = (int)$args['week'];
        $GETYear = (int)$args['year'];
    
        $startDateArray = getDate(pnModAPIFunc('TimeIt', 'user', 'getFirstDayOfWeek', array('year' => $GETYear, 'weeknr' => $GETWeek)));
        $startDate = DateUtil::getDatetime($startDateArray[0], _DATEINPUT);
        $endDate   = DateUtil::getDatetime(mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+6, $startDateArray['year']), _DATEINPUT);
    
        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => $startDate,                   
                  'end' => $endDate,
                  'catFilter' => ((isset($args['catFilter']))? $args['catFilter']: null),
                  'shareFilter' => ((isset($args['shareFilter']))? $args['shareFilter']: null),
                  'userFilter'  => ((isset($args['userFilter']))? $args['userFilter']: null) )
            ); 
        $week = array();
    
        for($i=0;$i<7;$i++)
        {
            $temp = mktime(0, 0, 0, $startDateArray['mon'], $startDateArray['mday']+$i, $startDateArray['year']);
            $week[DateUtil::getDatetime($temp, _DATEINPUT)] = $data[$temp];
        }
        return $week;
    }
}

function TimeIt_userapi_dayEvents($args)
{
    if(!isset($args['day']) || !isset($args['month']) || !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    } else
    {
    	// valid Date?
		if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    	{
    		return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    	}
    	
    	$GETDay = (int)$args['day'];
        $GETMonth = (int)$args['month'];
        $GETYear = (int)$args['year'];
    
    
        $getDate = getDate(mktime(0, 0, 0, $GETMonth, $GETDay, $GETYear));
    
        $data =  pnModAPIFunc('TimeIt', 'user', 'getDailySortedEvents', 
            array('start' => DateUtil::getDatetime($getDate[0], _DATEINPUT),                   
                  'end' => DateUtil::getDatetime($getDate[0], _DATEINPUT),
                  'catFilter' => ((isset($args['catFilter']))? $args['catFilter']: null),
                  'shareFilter' => ((isset($args['shareFilter']))? $args['shareFilter']: null),
                  'userFilter'  => ((isset($args['userFilter']))? $args['userFilter']: null) )
            );      
    
        return $data[$getDate[0]];
    }
}

function TimeIt_userapi_create($args)
{
	if(!isset($args['obj']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else
    {
    	if(isset($args['obj']['__WORKFLOW__']))
    	{
    		
    		if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        		pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    		}
    		$object = new $class();
    		$object->setData($args['obj']);
    		$ret = $object->insert();
    		$args['obj'] = $object->getData(); // set current data
    		
    		//$ret = DBUtil::insertObject($args['obj'], 'TimeIt_events');
    		// Let any hooks know that we have created a new item
    		pnModCallHooks('item', 'create', $ret['id'], array('module' => 'TimeIt'));
    		
	    	// send an E-Mail?
	      	if(pnModGetVar('TimeIt', 'notifyEvents'))
	      	{
	      		$pending = '';
	      		$link = pnModUrl('TimeIt','user','event',array('id'=>$ret['id']));
	      		if($args['obj']['__WORKFLOW__']['schemaname'] == 'moderate' && $args['obj']['__WORKFLOW__']['state'] == 'waiting')
	      		{
	      			$pending = 'Event is in waiting state.';
	      			$link = pnModUrl('TimeIt','admin','viewpending');
	      			
	      		}
	      		$message = pnML('_TIMEIT_NOTIFYEVENTS_MESSAGE', array('user'=>pnUserGetVar('uname'), 
	      															  'title'=>$args['obj']['title'],
	      															  'link'=>$link,
	      															  'pending'=>$pending));
	      		pnMail(pnModGetVar('TimeIt', 'notifyEventsEmail'), _TIMEIT_EVENTADDED, $message);
	      	}
    	} else
    	{
    		$ret = false;
    	}
    	
    	return $ret;
	}
}

function TimeIt_userapi_update($args)
{
	if(!isset($args['obj']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else
    {
    	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
        	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    	}
    	$object = new $class();
    	$object->setData($args['obj']);
    	$ret = $object->update();
    	$args['obj'] = $object->getData(); // set current data
    	
    	//$ret = DBUtil::updateObject($args['obj'], 'TimeIt_events');
    	// Let any hooks know that we have updated an item.
    	pnModCallHooks('item', 'update', $args['obj']['id'], array('module' => 'TimeIt'));
    	return $ret;
	}
}

function TimeIt_userapi_delete($args)
{
	if(!isset($args['obj']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else
    {
    	$bool = false;
    	// we can't delete events without a workflow entry
    	if(!isset($args['obj']['__WORKFLOW__']))
    	{
    		$bool = WorkflowUtil::getWorkflowForObject($args['obj'], 'TimeIt_events');
    	} else
    	{
    		$bool = true;
    	}
    	
    	if($bool)
    	{
    		$bool = WorkflowUtil::deleteWorkflow($args['obj']);
    		// Let any hooks know that we have deleted an item
    		pnModCallHooks('item', 'delete', $args['obj']['id'], array('module' => 'TimeIt'));
    	} 
    	
    	return $bool;
	}
}

function TimeIt_userapi_pendingEvents($args)
{
	// Optional arguments.
    if (!isset($args['startnum']) || empty($args['startnum'])) 
    {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems']) || empty($args['numitems'])) 
    {
        $args['numitems'] = -1;
    }
    
	if (!is_numeric($args['startnum']) || !is_numeric($args['numitems']))
	{
        return LogUtil::registerError (_MODARGSERROR);
    }
    
	$groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }
	pnModDBInfoLoad('Workflow');
	$pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];
    
    $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='waiting'";
	if($groups !== true && count($groups) > 0)
   	{
   		$where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
   	}
                    
  	$join = array(array ('join_table'   =>  'workflows',
     			   'join_field'         =>  array('obj_id'),
     			   'object_field_name'  =>  array('obj_id'),
     			   'compare_field_table'=>  'id',
     	 		   'compare_field_join' =>  'obj_id'));
  	
  	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
    	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();
    
    $class->_objJoin = $join;
    return $class->get($where, '', $args['startnum']-1, $args['numitems']);
}

function TimeIt_userapi_countPendingEvents($args)
{
	$groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }
	
	pnModDBInfoLoad('Workflow');
	$pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];
    
    $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='waiting'";
	if($groups !== true && count($groups) > 0)
   	{
   		$where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
   	}
                    
  	$join = array(array ('join_table'   =>  'workflows',
     			   'join_field'         =>  array('obj_id'),
     			   'object_field_name'  =>  array('obj_id'),
     			   'compare_field_table'=>  'id',
     	 		   'compare_field_join' =>  'obj_id'));
  	
  	
  	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
      	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class(); // make object
    $class->_objJoin = $join; // set join array
    return $class->getCount($where, true);// count items
}

function TimeIt_userapi_hiddenEvents($args)
{
	// Optional arguments.
    if (!isset($args['startnum']) || empty($args['startnum'])) 
    {
        $args['startnum'] = 0;
    }
    if (!isset($args['numitems']) || empty($args['numitems'])) 
    {
        $args['numitems'] = -1;
    }
    
	if (!is_numeric($args['startnum']) || !is_numeric($args['numitems']))
	{
        return LogUtil::registerError (_MODARGSERROR);
    }
    
	$groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }
	
	pnModDBInfoLoad('Workflow');
	$pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];
    
    $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='approved'
                    AND $timeit_events_column[status]=0";
	if($groups !== true && count($groups) > 0)
   	{
   		$where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
   	}
                    
  	$join = array(array ('join_table'   =>  'workflows',
     			   		'join_field'         =>  array('obj_id'),
     			   		'object_field_name'  =>  array('obj_id'),
     			   		'compare_field_table'=>  'id',
     	 		   		'compare_field_join' =>  'obj_id'));
  	
  	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
      	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();
    
    $class->_objJoin = $join;
    return $class->get($where, '', $args['startnum']-1, $args['numitems']);
}

function TimeIt_userapi_countHiddenEvents($args)
{
	$groups = TimeIt_adminPermissionCheck(true);
    // Security check
    if ($groups === false) {
        return array();
    }
	
	pnModDBInfoLoad('Workflow');
	$pntables = pnDBGetTables();

    $workflows_column = $pntables['workflows_column'];
    $timeit_events_column = $pntables['TimeIt_events_column'];
    
    $where = "WHERE $workflows_column[module]='TimeIt'
                    AND $workflows_column[obj_table]='TimeIt_events'
                    AND $workflows_column[obj_idcolumn]='id'
                    AND $workflows_column[state]='approved'
                    AND $timeit_events_column[status]=0";
   	if($groups !== true && count($groups) > 0)
   	{
   		$where .= " AND $timeit_events_column[group] IN('".implode("','",$groups)."')";
   	}
                    
  	$join = array(array ('join_table'   =>  'workflows',
     			   		'join_field'         =>  array('obj_id'),
     			   		'object_field_name'  =>  array('obj_id'),
     			   		'compare_field_table'=>  'id',
     	 		   		'compare_field_join' =>  'obj_id'));
  	
  	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
      	pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
    }
    $class = new $class();
    $class->_objJoin = $join;
    return $class->getCount($where, true);
}

/**
 * @return Unix Timestamp
 */
function TimeIt_userapi_getFirstDayOfWeek($args)
{
    if(empty($args['year']) || empty($args['weeknr']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    }
	// valid Date?
	if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    {
    	return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }
    
    $year = $args['year'];
    $weeknr = $args['weeknr'];

    $offset = date('w', mktime(0,0,0,1,1,$year));
    $offset = ($offset < 5) ? 1-$offset : 8-$offset;
    $monday = mktime(0,0,0,1,1+$offset,$year);

    return strtotime('+' . ($weeknr - 1) . ' weeks', $monday);
}

function TimeIt_userapi_navdates($args)
{
    // needed args set?
	if(!isset($args['month']) || !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    // valid Date?
	if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    {
    	return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }
    
    $month = $args['month'];
    $year = $args['year'];
    
    $ret = array();
    $ret['daysInMonth'] = DateUtil::getDaysInMonth($month, $year);
    $ret['firstWeekOfMonth'] = date("W", mktime(0, 0, 0, $month, 1, $year));
    $ret['lastWeekOfMonth'] = date("W", mktime(0, 0, 0, $month,  $ret['daysInMonth'], $year));
    
    $temp = getDate( mktime(0, 0, 0, $month, 1, $year) );
    $temp = $temp['wday'];
    $temp = ($temp == 0)? 6: $temp-1;
    $ret['startLastMonth'] = date("j", mktime(0, 0, 0, $month,1-$temp , $year));
    
    if($ret['startLastMonth'] != 1)
    { 
        // december was one year before
        $tYear = ($month == 1)? $year-1: $year;
        
        $ret['dateFirstDayInWeek_FirstWeekOfMonth'] = DateUtil::getDatetime(mktime(0, 0, 0, $month-1, $ret['startLastMonth'], $tYear), _DATEINPUT);
    } else
    {
        $ret['dateFirstDayInWeek_FirstWeekOfMonth'] = DateUtil::getDatetime(mktime(0, 0, 0, $month, 1, $year), _DATEINPUT);
    }
    
    $temp = getDate( mktime(0, 0, 0, $month,  $ret['daysInMonth'], $year) );
    $temp = $temp['wday'];
    $daysTo = 7-$temp;
    if((-($daysTo-7)) != 0)
    {
        // januar is in next year
        $tYear = ($month == 12)? $year+1: $year;
        // december +1 month == januar
        $tMonth = ($month == 12)? 1: $month+1;
        $ret['dateLastDayInWeek_LastWeekOfMonth'] = DateUtil::getDatetime(mktime(0, 0, 0, $tMonth+1, $daysTo, $tYear), _DATEINPUT);
    } else
    {
        $ret['dateLastDayInWeek_LastWeekOfMonth'] = DateUtil::getDatetime(mktime(0, 0, 0, $month, $ret['daysInMonth'], $year), _DATEINPUT);
    }
    
    return $ret;
}

/**
 *
 *@return array 2 dimensional array. 
 * e.g.: array[week Nr.][YYYY-MM-DD] = NULL;
 *
 */
function TimeIt_userapi_arrayForMonthView($args)
{
    if(!isset($args['month']) || !isset($args['year']))
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    if(!pnModAPIFunc('TimeIt','user','checkDate',$args))
    {
    	return LogUtil::registerError (_MODARGSERROR._TIMEIT_INVALIDDATE);
    }
    
    $month = $args['month'];
    $year = $args['year'];
    
    $daysInMonth = DateUtil::getDaysInMonth($month, $year);
    
    $firstWeekOfMonth = (int)date("W", mktime(0, 0, 0, $month, 1, $year));
    $lastWeekOfMonth = (int)date("W", mktime(0, 0, 0, $month, $daysInMonth, $year));
    
    
    $temp = getDate( mktime(0, 0, 0, $month, 1, $year) );
    $temp = $temp['wday'];
    $temp = ($temp == 0)? 6: $temp-1;
    $startLastMonth = date("j", mktime(0, 0, 0, $month,1-$temp , $year));
    $weeks = array();
    $start = 1;
    $daysToFirstWeek = 7-$temp;
    
    $temp1 = getDate( mktime(0, 0, 0, $month, $daysInMonth, $year) );
    $temp1 = $temp1['wday'];
    $daysTo = 7-$temp1;
       
    if($startLastMonth != 1)
    {
    	$ttYear = ($month == 1)? $year-1: $year;
    	$ttMonth = ($month == 1)? 12: $month-1;
    } else
    {
    	$ttYear = $year;
    	$ttMonth = $month;
    }
    $ts = mktime(0, 0, 0, $ttMonth, $startLastMonth, $ttYear, 0);

    if((int)date('w', mktime(0, 0, 0, $month, $daysInMonth, $year)) == 0)
    {
    	$end = mktime(0, 0, 0, $month, $daysInMonth, $year, 0);
    } else
    {
    	$tYear = ($month == 12)? $year+1: $year;
    	$tMonth = ($month == 12)? 1: $month+1;
    	$end = mktime(0, 0, 0, $tMonth, $daysTo, $tYear, 0);
    }
      
    while(true)
    {
        $week = date('W', $ts);
        if($weeks[$week] == NULL)
        {
            $weeks[$week] = array();
        }
        $weeks[$week][DateUtil::getDatetime($ts, _DATEINPUT)] = NULL;
        
        $ts = $ts+86400;
            
        
        if($ts > $end)
        {
            break;
        }
    }
    
    return $weeks;
}

function TimeIt_userapi_checkDate($args)
{
	if(isset($args['date']))
	{
		$a = explode('-', $args['date']);
		$args['day'] = (int)$a[2];
		$args['month'] = (int)$a[1];
		$args['year'] = (int)$a[0];
	}
	
	if(isset($args['week']))
	{
		$args['weeknr'] = $args['week'];
	}
	
	if(isset($args['day']))
	{
		$i = (int)$args['day']; // cast to int
		// invalid day=
		if($i < 1 || $i > 31)
		{
			return false;
		}
	}
	
	if(isset($args['weeknr']))
	{
		$i = (int)$args['weeknr']; // cast to int
		// invalid day=
		if($i < 1 || $i > 53)
		{
			return false;
		}
	}
	
	if(isset($args['month']))
	{
		$i = (int)$args['month']; // cast to int
		// invalid day=
		if($i < 1 || $i > 12)
		{
			return false;
		}
	}
	
	if(isset($args['year']))
	{
		$i = (int)$args['year']; // cast to int
		// invalid day=
		if($i < 1970 || $i > 2037)
		{
			return false;
		}
	}
	
	if(isset($args['day']) && isset($args['month']) && isset($args['year']))
	{
		$i = (int)DateUtil::getDaysInMonth($args['month'], $args['year']);
		if((int)$args['day'] > $i)
		{
			return false;
		}
	}
	
	return true;
}
