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

Loader::includeOnce('modules/TimeIt/common.php');

/**
 * Get available admin panel links
 *
 * @return array array of admin links
 */
function TimeIt_adminapi_getlinks()
{
    $links = array();
    $domain = ZLanguage::getModuleDomain('TimeIt');
    $hasPerms = TimeItPermissionUtil::adminAccessCheck();
    $isAdmin  = pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN);

    if ($hasPerms) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewpending'),  'text' => __('Pending', $domain));
    }
    if ($hasPerms) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewhidden'),   'text' => __('Offline', $domain));
    }
    if ($hasPerms) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewall'),      'text' => __('View all', $domain));
    }
    if ($isAdmin) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'modifyconfig'), 'text' => __('Settings', $domain));
    }
    if ($isAdmin) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'import'),       'text' => __('Import', $domain));
    }
    if ($isAdmin) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'calendars'),    'text' => __('Calendars', $domain));
    }

    return $links;
}



