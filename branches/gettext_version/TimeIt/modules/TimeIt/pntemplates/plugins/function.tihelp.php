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

function smarty_function_tihelp($params, &$smarty)
{
    static $help_id = 1;

    if($params['mode'] == 'btn') {
        $html = '<span class="ti-help"><a onclick="$(\'tihelp_'.$help_id.'\').toggle();return false;"><img src="'.pnGetBaseURI().'/images/icons/extrasmall/info.gif"/></a></span>';
    } else {
        if (empty($params['text'])) {
            $smarty->trigger_error("tihelp: missing 'text' parameter");
            return;
        }

        $html = '<div style="display: none;" id="tihelp_'.$help_id.'" class="z-informationmsg z-formnote">'.$params['text'].'</div>';
        $help_id++;
    }

    if(!empty($params['assign'])) {
        $smarty->assign($params['assign'], $html);
    } else {
        return $html;
    }
}
