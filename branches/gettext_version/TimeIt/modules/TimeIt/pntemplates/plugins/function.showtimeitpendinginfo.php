<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Template Plugins
 */

function smarty_function_showtimeitpendinginfo($params, &$smarty)
{
    if(TimeItPermissionUtil::adminAccessCheck()) {
        $count = (int) TimeItDomainFactory::getInstance('event')->getNumberOfPendingEvents();

        if($count > 0) {
            $gtdom = ZLanguage::getModuleDomain('TimeIt');
            //! %1$s is the nummber of pending events, %2$s an link to the pending events admin page
            return _fn('To all moderator: There is %1$s new pending event. %2$s',
                       'To all moderator: There are %1$s new pending events. %2$s',
                       $count, array($count, '<a href="' . pnModURL('TimeIt', 'admin', 'viewpending') . '">' .
                                             __('Pending events admin page', $gtdom) . '</a>'),
                       $gtdom);
        }
    }
}
