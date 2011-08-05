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

function smarty_function_recurrenceNavBox($params, &$smarty)
{
    $gtdomain = ZLanguage::getModuleDomain('TimeIt');
    
    if (empty($params['eid'])) {
        $smarty->trigger_error("recurrenceNavBox: missing 'eid' parameter");
        return;
    }
    
    if (empty($params['dheid'])) {
        $smarty->trigger_error("recurrenceNavBox: missing 'dheid' parameter");
        return;
    }

    $dheids = TimeItDomainFactory::getInstance('event')->getPrevNexRecurrence($params['eid'], $params['dheid']);

    $html = '';

    if($dheids[0] != null || $dheids[1] != null) {
        $html .= '<div class="ti-recurrence-nav z-floatbox">';

        if($dheids[0] != null) {
            $html .= '
            <div class="z-floatleft">
                   <a href="'.pnModURL('TimeIt', 'user', 'display', array('ot' => 'event',
                                                                          'id' => (int)$params['eid'],
                                                                          'dheid' => $dheids[0])).'">&lt; '.__("Previous recurrence", $gtdomain).'</a>
            </div>';
        }

        if($dheids[1] != null) {
            $html .= '
            <div class="z-floatright">
                   <a href="'.pnModURL('TimeIt', 'user', 'display', array('ot' => 'event',
                                                                          'id' => (int)$params['eid'],
                                                                          'dheid' => $dheids[1])).'">'.__("Next recurrence", $gtdomain).' &gt;</a>
            </div>';
        }

        $html .= '</div>';
    }

    if(!empty($params['assign'])) {
        $smarty->assign($params['assign'], $html);
    } else {
        return $html;
    }
}
