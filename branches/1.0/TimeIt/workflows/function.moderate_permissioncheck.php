<?php

/**
 * Permission check for workflow schema 'standard'
 *
 * @param array $obj
 * @param int $permLevel
 * @param int $currentUser
 * @param int $actionId (optional)
 * @return bool
 */
function TimeIt_workflow_moderate_permissioncheck($obj, $permLevel, $currentUser, $actionId=null)
{
    // process $obj and calculate an instance
	if($obj['group'] == 'all' || empty($obj) || empty($obj['group']))
    {
     	$groupObj = array('name'=>'all'); // group irrelevant
    } else {
    	$groupObj = UserUtil::getPNGroup((int)$obj['group']);
    }
    return SecurityUtil::checkPermission('TimeIt::', '::', $permLevel, $currentUser) || SecurityUtil::checkPermission( 'TimeIt:Group:', $groupObj['name']."::", $permLevel, $currentUser);
}