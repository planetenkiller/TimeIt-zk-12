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

function smarty_function_navigationDateJumper($params, &$render)
{
    Loader::loadClass('HtmlUtil');
    $html = HtmlUtil::getSelector_DatetimeDay((isset($params['day'])?$params['day']: date("j")));
    $html .=  HtmlUtil::getSelector_DatetimeMonth((isset($params['month'])?$params['month']: date("n")));
    $minYear = pnModAPIFunc('TimeIt','user','getOldestDate');
    $minYear = (int)substr($minYear, 0, 4);
    $html .= HtmlUtil::getSelector_DatetimeYear((isset($params['year'])?$params['year']: date("Y")), 'year', $minYear, 2037);
    
    $html .= "<select name=\"viewType\" id=\"viewtype\">";
    $selected = (isset($params['viewType']))?$params['viewType']: '';
    $array = array('day'=>_DAY, 'week'=>_WEEK, 'month'=>_MONTH,'year'=>_YEAR);
    foreach ($array AS $key => $value) {
            $sel = ($key==$selected ? ' selected="selected"' : '');
            $html .= "<option value=\"$key\"$sel>".$value."</option>";
    }
    $html .= '</select>';
    
    
    return $html;
}
