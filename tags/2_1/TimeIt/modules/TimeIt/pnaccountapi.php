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
 * Return an array of items to show in the your account panel
 *
 * @return   array   array of items, or false on failure
 */
function TimeIt_accountapi_getall($args)
{
    $array = array();

    $array[] = array('url'    => pnModURL('TimeIt','user','viewSubscribedEventsOfUser'),
                     'module' => 'TimeIt',
                     'set'    => 'pnimages',
                     'title'  => _TIMEIT_ACCOUNT_SUBSCRIBED_EVENTS,
                     'icon'   => 'subscribe.png'
                    );

    // link to private calendar
    if((int)pnModGetVar('TimeIt', 'defaultPrivateCalendar') > 0) {
        $array[] = array('url'    => pnModURL('TimeIt','user','view',array('viewType'=>'month','filter'=>'cr_uid:eq:'.pnUserGetVar('uid').',sharing:le:2')),
                         'module' => 'TimeIt',
                         'set'    => 'pnimages',
                         'title'  => _TIMEIT_ACCOUNT_PRVATECALENDAR,
                         'icon'   => 'admin.gif'
                        );
    }

    return $array;
}