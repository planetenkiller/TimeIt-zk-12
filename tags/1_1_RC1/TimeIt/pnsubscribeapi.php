<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function TimeIt_subscribeapi_isSubscribed($args)
{
	if(!isset($args['eid']) || !is_numeric($args['eid']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
    	
    	$uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    	if($uid)
    	{ 
    		if (!($class = Loader::loadClassFromModule('TimeIt', 'Reg'))) {
    			pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
    		}
    		
			$pntables = pnDBGetTables();
    		$timeit_regs_column = $pntables['TimeIt_regs_column'];
    		$class = new $class();
    		$obj = $class->getWhere('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['eid']).' AND '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
    		return (!empty($obj));
    	} else 
    	{
    		return LogUtil::registerError(_MODARGSERROR);
    	}
    }
}

function TimeIt_subscribeapi_subscribe($args)
{
	if(!isset($args['eid']) || !is_numeric($args['eid']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
    	$uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    	if($uid && !pnModAPIFunc('TimeIt','subscribe','isSubscribed',array('eid'=>$args['eid'])))
    	{
    		if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg'))) {
    			pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
    		}
    		
    		$class = new $class();
    		$class->setData(array('eid'=>$args['eid'],'uid'=>$uid));
    		$result = $class->insert();
    		return ($result === false)? false: true;
    	} else 
    	{
    		return false;
    	}
    }
}

function TimeIt_subscribeapi_countUserForEvent($args)
{
	if(!isset($args['eid']) || !is_numeric($args['eid']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
    	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
    		pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
    	}
    	
    	$pntables = pnDBGetTables();
    	$timeit_regs_column = $pntables['TimeIt_regs_column'];
    	$class = new $class();
    	return $class->getCount('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['eid']));
    }
}

function TimeIt_subscribeapi_userArrayForEvent($args)
{
	if(!isset($args['eid']) || !is_numeric($args['eid']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
    	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
    		pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
    	}
    	
    	$pntables = pnDBGetTables();
    	$timeit_regs_column = $pntables['TimeIt_regs_column'];
    	
    	$class = new $class();
    	$join = array(array ('join_table'   	 =>  'users',
     			   			 'join_field'         =>  array('uname'),
     			   			 'object_field_name'  =>  array('name'),
     			   			 'compare_field_table'=>  'uid',
     	 		   			 'compare_field_join' =>  'uid'));
    	$class->_objJoin = $join;
    	return $class->get('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['eid']));
    }
}

function TimeIt_subscribeapi_countEventsForUser($args)
{
	if(!isset($args['eid']) || !is_numeric($args['eid']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
    	$uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    	if($uid)
    	{
	    	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
	    		pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
	    	}
	    	
	    	$pntables = pnDBGetTables();
	    	$timeit_regs_column = $pntables['TimeIt_regs_column'];
	    	$class = new $class();
	    	return $class->getCount('WHERE '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
    	} else 
    	{
    		return LogUtil::registerError(_MODARGSERROR);
    	}
    }
}

function TimeIt_subscribeapi_arrayOfEventsForUser($args)
{
    $uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    if($uid)
    {
    	if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
    		pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
    	}
    	
    	$pntables = pnDBGetTables();
    	$timeit_regs_column = $pntables['TimeIt_regs_column'];
    	$class = new $class();
    	$join = array(array ('join_table'   	 =>  'TimeIt_events',
     			   			 'join_field'         =>  array('title'),
     			   			 'object_field_name'  =>  array('title'),
     			   			 'compare_field_table'=>  'eid',
     	 		   			 'compare_field_join' =>  'id'));
    	$class->_objJoin = $join;
    	return $class->get('WHERE '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
    } else 
    {
    	return LogUtil::registerError(_MODARGSERROR);
    }
}


function TimeIt_subscribeapi_unsubscribe($args)
{
	if(!isset($args['eid']) || !is_numeric($args['eid']))
    {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
    	$uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    	if($uid)
    	{
    		if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg'))) {
    			pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
    		}
    		
			$pntables = pnDBGetTables();
    		$timeit_regs_column = $pntables['TimeIt_regs_column'];
    		$class = new $class();
    		$class->getWhere('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['eid']).' AND '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
    		$result = $class->delete();
    		return ($result === false)? false: true;
       	} else 
    	{
    		return LogUtil::registerError(_MODARGSERROR);
    	}
    }
}
