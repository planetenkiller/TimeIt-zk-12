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
    pnModLangLoad('TimeIt', 'admin');

    /*if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_COMMENT)) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'new'), 'text' => _TIMEIT_NEW);
    }*/
    if (TimeIt_adminPermissionCheck()) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewpending'), 'text' => _TIMEIT_PENDING);
    }
    if (TimeIt_adminPermissionCheck()) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewhidden'), 'text' => _TIMEIT_HIDDEN);
    }
    if (TimeIt_adminPermissionCheck()) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewall'), 'text' => _TIMEIT_VIEWALL);
    }
    if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'modifyconfig'), 'text' => _TIMEIT_CONFIG);
    }
    if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'import'), 'text' => _TIMEIT_IMPORT);
    }
    if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'calendars'), 'text' => _TIMEIT_CALENDARS);
    }

    return $links;
}



