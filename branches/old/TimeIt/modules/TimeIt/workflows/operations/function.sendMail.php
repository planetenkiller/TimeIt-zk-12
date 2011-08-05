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

function TimeIt_operation_sendMail(&$obj, $params)
{
     // send an E-Mail?
    if(pnModGetVar('TimeIt', 'notifyEvents'))
    {
            $pending = '';
            $link = pnModUrl('TimeIt','user','event',array('id'=>$obj['id']));
            if($obj['__WORKFLOW__']['schemaname'] == 'moderate' && $obj['__WORKFLOW__']['state'] == 'waiting')
            {
                    $pending = _TIMEIT_NOTIFYEVENTS_PENDING;
                    $link = pnModUrl('TimeIt','admin','viewpending');

            }
            $message = pnML('_TIMEIT_NOTIFYEVENTS_MESSAGE', array('user'=>pnUserGetVar('uname'), 
                                                                  'title'=>$obj['title'],
                                                                  'link'=>$link,
                                                                  'pending'=>$pending));
            pnMail(pnModGetVar('TimeIt', 'notifyEventsEmail'), _TIMEIT_EVENTADDED, $message);
    }
    
    return true;
}