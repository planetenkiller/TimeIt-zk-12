<?php

function smarty_function_navigationDateJumper($params, &$render)
{
    Loader::loadClass('HtmlUtil');
    $html =  HtmlUtil::getSelector_DatetimeMonth((isset($params['month'])?$params['month']: date("n")));
    $html .= HtmlUtil::getSelector_DatetimeDay((isset($params['day'])?$params['day']: date("j")));
    $html .= HtmlUtil::getSelector_DatetimeYear((isset($params['year'])?$params['year']: date("Y")), 'year', 1995, 2037);
    
    $html .= "<select name=\"viewType\" id=\"viewtype\">";
    $selected = (isset($params['viewType']))?$params['viewType']: '';
    $array = array('day', 'week', 'month');
    for ($i=0; $i<3; $i++) {
            $sel = ($array[$i]==$selected ? 'selected="selected"' : '');
            $html .= "<option value=\"$array[$i]\" $sel>".$array[$i]."</option>";
    }
    $html .= '</select>';
    
    
    return $html;
}
