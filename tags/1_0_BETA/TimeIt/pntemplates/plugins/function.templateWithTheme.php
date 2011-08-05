<?php


function smarty_function_templateWithTheme($params, &$smarty)
{
    if (empty($params['file'])) {
        $smarty->trigger_error("templateWithTheme: missing 'file' parameter");
        return;
    }
   	$template = TimeIt_templateWithTheme($smarty, $params['file']);
    if($params['assign'])
    {
    	$smarty->assign($params['assign'], $template);
    } else {
    	return $template;
    }
}
