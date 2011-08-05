<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
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

    if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_COMMENT)) {
        $links[] = array('url' => pnModURL('TimeIt', 'admin', 'new'), 'text' => _TIMEIT_NEW);
    }
	if (TimeIt_adminPermissionCheck()) {
		$links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewpending'), 'text' => _TIMEIT_PENDING);
    }
	if (TimeIt_adminPermissionCheck()) {
      	$links[] = array('url' => pnModURL('TimeIt', 'admin', 'viewhidden'), 'text' => _TIMEIT_HIDDEN);
    }
    if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
    	$links[] = array('url' => pnModURL('TimeIt', 'admin', 'modifyconfig'), 'text' => _TIMEIT_CONFIG);
    }
	if (pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
    	$links[] = array('url' => pnModURL('TimeIt', 'admin', 'import'), 'text' => _TIMEIT_IMPORT);
    }

    return $links;
}



