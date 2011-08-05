<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage API
 */

Loader::requireOnce('modules/TimeIt/common.php'); 

/**
 * Delete a user in the module "TimeIt"
 * 
 * @param	$args['uid']	int   user id
 * @return	array   
 */
function TimeIt_userdeletionapi_delUser($args)
{
    $uid = $args['uid'];
    if (!pnModAPIFunc('UserDeletion','user','SecurityCheck',array('uid' => $uid))) {
        $result = __('Nothing deleted by this module because you do not have permission to delete data', ZLanguage::getModuleDomain('TimeIt'));
    } else  {
        // Here you should write your userdeletion routine.
        // Delete your database entries or anonymize them.
        
        if(pnModGetVar('TimeIt', 'userdeletionMode') == 'anonymize') {
            TimeItDomainFactory::getInstance('event')->anonymizeEventsOfUser($uid);
            $result = __f('Events deleted for user %s', pnUserGetVar('uname', $uid), ZLanguage::getModuleDomain('TimeIt'));
            
        } else if(pnModGetVar('TimeIt', 'userdeletionMode') == 'delete') {
            TimeItDomainFactory::getInstance('event')->deleteEventsOfUser($uid);
            $result = __f('Events anonymized for user %s', pnUserGetVar('uname', $uid), ZLanguage::getModuleDomain('TimeIt'));
        }
    }

    return array( 'title' => __('TimeIt calendar', ZLanguage::getModuleDomain('TimeIt')),
                  'result'=> $result  );
}