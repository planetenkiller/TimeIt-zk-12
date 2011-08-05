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
        $result = _NOTHINGDELETEDNOAUTH;  
    } else 
    {
        // Here you should write your userdeletion routine.
        // Delete your database entries or anonymize them.
        
        if(pnModGetVar('TimeIt', 'userdeletionMode') == 'anonymize')
        {
            pnModAPIFunc('TimeIt','user','anonymizeEventsOfUser', array('uid'=>$uid));
            $result = _TIMEIT_USERDELITON_ANONYMIZED." ".pnUserGetVar('uname',$uid);
            
        } else if(pnModGetVar('TimeIt', 'userdeletionMode') == 'delete')
        {
            pnModAPIFunc('TimeIt','user','deleteEventsOfUser', array('uid'=>$uid));
            $result = _TIMEIT_USERDELITON_DELETE." ".pnUserGetVar('uname',$uid);
        }
    }

    return array( 'title' => _TIMEIT_TITLE,
                  'result'=> $result  );
}