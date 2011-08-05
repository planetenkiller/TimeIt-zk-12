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
 * Return an array of items to show in the your account panel
 *
 * @return   array   array of items, or false on failure
 */
function TimeIt_accountapi_getall($args)
{
	$array = array();
	
	if(pnModGetVar('TimeIt', 'allowSubscribe'))
	{
		$array[] = array('url'  	=> pnModURL('TimeIt','user','viewSubscribedEventsOfUser'),
						 'module' 	=> 'TimeIt',
						 'set'		=> 'pnimages',
						 'title'	=> _TIMEIT_ACCOUNT_SUBSCRIBED_EVENTS,
						 'icon'		=> 'subscribe.png'
						);
	}
	
	if(pnModGetVar('TimeIt', 'privateCalendar'))
	{
		$array[] = array('url'  	=> pnModURL('TimeIt','user','view',array('viewType'=>'month','user'=>pnUserGetVar('uname'))),
						 'module' 	=> 'TimeIt',
						 'set'		=> 'pnimages',
						 'title'	=> _TIMEIT_ACCOUNT_PRVATECALENDAR,
						 'icon'		=> 'admin.gif'
						);
	}
	
	
	return $array;
}