<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Workflows
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
    if($permLevel == ACCESS_COMMENT)
        return TimeItPermissionUtil::canCreateEvent($obj['cid']);
    else if($permLevel == ACCESS_EDIT)
        return TimeItPermissionUtil::canEditEvent($obj);
    else if($permLevel == ACCESS_DELETE)
        return TimeItPermissionUtil::canDeleteEvent($obj);
    else if($permLevel == ACCESS_MODERATE)
        return TimeItPermissionUtil::canEditEvent($obj, true);
}