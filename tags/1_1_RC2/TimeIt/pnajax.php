<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function TimeIt_ajax_viewUserOfSubscribedEvent()
{
	$id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

	if($id === false)
	{
		AjaxUtil::error(_MODARGSERROR);
	} else 
	{
		$html = pnModFunc('TimeIt','user','viewUserOfSubscribedEvent', $id);
		
		if($html !== false)
		{
			return array('html' => $html);
		} else 
		{
			AjaxUtil::error(_MODARGSERROR);
		}
	}
}

function TimeIt_ajax_subscribe()
{
	$id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

	if($id === false)
	{
		AjaxUtil::error(_MODARGSERROR);
	} else 
	{
		$result = pnModFunc('TimeIt','user','subscribe', array('noRedirect'=>true,'eid'=>$id));
		
		return array('result' => $result);
	}
}

function TimeIt_ajax_unsubscribe()
{
	$id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

	if($id === false)
	{
		AjaxUtil::error(_MODARGSERROR);
	} else 
	{
		$result = pnModFunc('TimeIt','user','unsubscribe', array('noRedirect'=>true,'eid'=>$id));
		
		return array('result' => $result);
	}
}