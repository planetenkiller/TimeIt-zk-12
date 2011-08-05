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
    $domain = ZLanguage::getModuleDomain('TimeIt');

    $array[] = array('url'    => pnModURL('TimeIt','user','view', array('ot' => 'reg', 'uid' => pnUserGetVar('uid'))),
                     'module' => 'TimeIt',
                     'set'    => 'pnimages',
                     'title'  => __('Registrations to events', $domain),
                     'icon'   => 'subscribe.png'
                    );

    // link to private calendar
    if((int)pnModGetVar('TimeIt', 'defaultPrivateCalendar') > 0) {
        $array[] = array('url'    => pnModURL('TimeIt','user','view',array('filter'=>'cr_uid:eq:'.pnUserGetVar('uid').',sharing:le:2')),
                         'module' => 'TimeIt',
                         'set'    => 'pnimages',
                         'title'  => __('My private calendar', $domain),
                         'icon'   => 'admin.gif'
                        );
    }

    return $array;
}