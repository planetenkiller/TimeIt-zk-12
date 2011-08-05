<?php
/**
 * TimeIt
 *
 * @copyright (c) 2008, planetenkiller
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

/**
 * initialise block
 */
function TimeIt_calendarnavblock_init()
{
    pnSecAddSchema('TimeIt:blocks:navblock', 'Block title::');
}

/**
 * get information on block
 */
function TimeIt_calendarnavblock_info()
{
    return array('text_type'		=> 'calendarnav',
                 'module' 		=> 'TimeIt',
                 'text_type_long' 	=> 'Calendar Navigation Block',
                 'allow_multiple' 	=> false,
                 'form_content' 	=> false,
                 'form_refresh' 	=> false,
                 'show_preview' 	=> true,
                 'admin_tableless'  	=> false);
}


function TimeIt_calendarnavblock_display($blockinfo)
{
    if (!SecurityUtil::checkPermission('TimeIt:blocks:navblock', "$blockinfo[title]::", ACCESS_READ)) {
        return false;
    }

    $vars = pnBlockVarsFromContent($blockinfo['content']);
    $GETYear = (int)FormUtil::getPassedValue('year', date("Y"), 'GETPOST');
    $GETMonth = (int)FormUtil::getPassedValue('month', date("n"), 'GETPOST');
    $GETDay = (int)FormUtil::getPassedValue('day', date("j"), 'GETPOST');


    $date  = $GETYear.','.$GETMonth.','.$GETDay;
    
    $pnRender = new pnRender('TimeIt');
    $pnRender->assign('date',$date);
    $blockinfo['content'] = $pnRender->fetch("TimeIt_block_calendarnav.htm");
    return themesideblock($blockinfo);
}

