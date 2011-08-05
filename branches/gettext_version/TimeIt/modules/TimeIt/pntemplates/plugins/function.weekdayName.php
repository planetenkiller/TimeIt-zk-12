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

function smarty_function_weekdayName($params, &$smarty)
{
    if (empty($params['weekday'])) {
        $smarty->trigger_error("weekdayName: missing 'weekday' parameter");
        return;
    }
    // make array
    $domain = ZLanguage::getModuleDomain('TimeIt');
    $namesOrig       = array( __('Sunday', $domain),
                              __('Monday', $domain),
                              __('Tuesday', $domain),
                              __('Wednesday', $domain),
                              __('Thursday', $domain),
                              __('Friday', $domain),
                              __('Saturday', $domain));

    $namesOrig_size1 = array( __('S', $domain),
                              __('M', $domain),
                              __('T', $domain),
                              __('W', $domain),
                              __('T', $domain),
                              __('F', $domain),
                              __('S', $domain));

    $namesOrig_size3 = array( __('Sun', $domain),
                              __('Mon', $domain),
                              __('Tue', $domain),
                              __('Wed', $domain),
                              __('Thu', $domain),
                              __('Fri', $domain),
                              __('Sat', $domain));
    $names = array();
    $names_size1 = array();
    $names_size3 = array();
    $nameToOrig = array();
    
    // get first day of week
    $firstDayOfWeek = (isset($params['firstDayOfWeek']) 
                           && (int)$params['firstDayOfWeek'] >= 0
                           &&(int)$params['firstDayOfWeek'] <= 6)?(int)$params['firstDayOfWeek'] : (int)pnModGetVar('TimeIt', 'firstWeekDay');
    
    $start = $firstDayOfWeek;
    while(true) {
        $names[] = $namesOrig[$start];
        $names_size1[] = $namesOrig_size1[$start];
        $names_size3[] = $namesOrig_size3[$start];
        $nameToOrig[] = $start;
        $start = ($start+1) % 7;
        if($start == $firstDayOfWeek) {
            break;
        }
    }
    
    if(isset($params['size']) && (int)$params['size'] == 1) {
        $name = $names_size1[(int)$params['weekday']-1];
    } else if(isset($params['size']) && (int)$params['size'] == 3) {
        $name = $names_size3[(int)$params['weekday']-1];
    } else {
        $name = $names[(int)$params['weekday']-1];
    }

    if(!empty($params['assign'])) {
        $smarty->assign($params['assign'], $name);
    } else  {
        if(isset($params['nameOnly']) && $params['nameOnly']) {
            return $name;
        } else {
            $request_uri = $_SERVER["REQUEST_URI"];
            if(isset($_GET['firstDayOfWeek'])) {
                $request_uri = str_replace('&firstDayOfWeek='.$_GET['firstDayOfWeek'], '',$request_uri);
            }

            return '<a href="'.$request_uri.'&firstDayOfWeek='.$nameToOrig[$params['weekday']-1].'">'.$name.'</a>';
        }
    }
}
