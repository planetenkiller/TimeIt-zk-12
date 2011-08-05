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
 * Permission check for workflow schema 'standard'
 *
 * @param array $obj
 * @param int $permLevel
 * @param int $currentUser
 * @param int $actionId (optional)
 * @return bool
 */
function TimeIt_workflow_standard_permissioncheck($obj, $permLevel, $currentUser, $actionId=null)
{
    // process $obj and calculate an instance
	if($obj['group'] == 'all' || empty($obj) || empty($obj['group']))
    {
    	$groupObj = array('name'=>'all'); // group irrelevant
   	} else {
    	$groupObj = UserUtil::getPNGroup((int)$obj['group']);
   	}
   	
   	// can user edit his events?
   	$auth_self_edit = false;
   	
   	if($actionId == 'update')
   	{
   		$auth_self_edit = true;
   	}else if(isset($obj['__META__']['TimeIt']['wfActionId']))
   	{
   		if(pnModGetVar('TimeIt', 'userCanEditHisEvents'))
   		{
   		 	if($obj['__META__']['TimeIt']['wfActionId'] == 'update')
   			{
   			  	$auth_self_edit = true;
   			}
   		}
   	}
   	
    return SecurityUtil::checkPermission('TimeIt::', '::', $permLevel, $currentUser) 
           || SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", $permLevel, $currentUser)
           || $auth_self_edit;
}