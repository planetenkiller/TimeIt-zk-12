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
    $gtdomain = ZLanguage::getModuleDomain('TimeIt');

     // send an E-Mail?
    if(pnModGetVar('TimeIt', 'notifyEvents')) {
            $pending = '';
            $link = pnModUrl('TimeIt','user','display',array('ot'=>'event', 'id' => $obj['id']), null, null, true);

            if($obj['__WORKFLOW__']['schemaname'] == 'moderate' && $obj['__WORKFLOW__']['state'] == 'waiting') {
                    $pending = __('Event is in waiting state.', $gtdomain);
                    $link = pnModUrl('TimeIt','admin','viewpending', array() , null, null, true);

            }

            $vars = array(pnUserGetVar('uname'),
                          $obj['title'],
                          $link,
                          $pending);
            //! %1$s is an username, %2$s the event title, %3$s an url to the event, %4$s empty or an additional message
            $message = __f("Hello,\n\nUser %1\$s added a new event.\n\nTitle: %2\$s\nLink:  %3\$s\n%4\$s", $vars, $gtdomain);
            $subject = __(/*!email subject*/'[TimeIt] New event', $gtdomain);

            // send mail (Mailer is an core module so we can use its api)
            pnModAPIFunc('Mailer', 'user', 'sendmessage', array('toaddress' => pnModGetVar('TimeIt', 'notifyEventsEmail'),
                                                                'subject'   => $subject,
                                                                'body'      => $message));
    }
    
    return true;
}