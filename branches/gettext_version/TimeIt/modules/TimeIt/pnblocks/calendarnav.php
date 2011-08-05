<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Blocks
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
    $date  = $GETYear.'/'.$GETMonth.'/'.$GETDay;


    $lang = ZLanguage::transformFS(ZLanguage::getLanguageCode());
    // map of the jscalendar supported languages
    $map = array('ca' => 'ca_ES', 'cz' => 'cs_CZ', 'da' => 'da_DK', 'de' => 'de_DE', 'el' => 'el_GR', 'es' => 'es_ES', 'fi' => 'fi_FI', 'fr' => 'fr_FR', 'he' => 'he_IL', 'hr' => 'hr_HR', 'hu' => 'hu_HU', 'it' => 'it_IT', 'ja' => 'ja_JP',
                 'ko' => 'ko_KR', 'lt' => 'lt_LT', 'lv' => 'lv_LV', 'nl' => 'nl_NL', 'no' => 'no_NO', 'pl' => 'pl_PL', 'pt' => 'pt_BR', 'ro' => 'ro_RO', 'ru' => 'ru_RU', 'si' => 'si_SL', 'sk' => 'sk_SK', 'sv' => 'sv_SE', 'tr' => 'tr_TR');
    if (isset($map[$lang])) {
        $lang = $map[$lang];
    }
    
    $pnRender = new pnRender('TimeIt');
    $pnRender->assign('date', $date);
    $pnRender->assign('lang', $lang);
    $pnRender->assign('firstweekday', ZI18n::getInstance()->locale->getFirstweekday());

    $blockinfo['content'] = $pnRender->fetch("block_calendarnav.htm");
    return themesideblock($blockinfo);
}

