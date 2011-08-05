<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage FormHandler
 */

/**
 * Form handler for event translation.
 */
class TimeIt_FormHandler_Edit_eventtranslate
{
    var $id;

    function initialize(&$render)
    {
        $gtdomain = ZLanguage::getModuleDomain('TimeIt');

        if(($this->id=FormUtil::getPassedValue('id', null, 'GET'))==null) {
            return LogUtil::registerError(__('Please add an id parameter!', $gtdomain), 404);
        }

        // load event
        $obj = TimeItDomainFactory::getInstance('event')->getObject($this->id, null, false);
        if(empty($obj)) {
            return LogUtil::registerError(__f('Item with id %s not found.', $this->id, $gtdomain), 404);
        }
        $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj' => $obj));

        if(!TimeItPermissionUtil::canTranslateEvent($obj)) {
            return LogUtil::registerPermissionError();
        }

        $ids = array();
        // assign current translations
        $langlist = ZLanguage::getInstalledLanguages();
        $langTexts = array();
        foreach($langlist AS $lang) {
            if(isset($obj['title_translate'][$lang]) || isset($obj['text_translate'][$lang])) {
                $render->assign($lang, array('title' => $obj['title_translate'][$lang],
                                             'text'  => $obj['text_translate'][$lang]));
            }

            $ids[] = 'text_'.$lang;
            $langTexts[$lang] = ZLanguage::getLanguageName($lang);
        }

        // scribite! integration
        if (pnModAvailable('scribite') && pnModGetVar('TimeIt', 'scribiteEditor') != '-') {
            // load editor
            $scribite = pnModFunc('scribite','user','loader', array('modname' => 'TimeIt',
                                                                    'editor'  => pnModGetVar('TimeIt', 'scribiteEditor'),
                                                                    'areas'   => $ids
                                                                    /*'tpl'     => $args['areas']*/));
            PageUtil::AddVar('rawtext', $scribite);
        }

        $render->assign('event', $obj);
        $render->assign('language', ZLanguage::getLanguageCode());
        $render->assign('languages', ZLanguage::getInstalledLanguages());
        $render->assign('languageNames', $langTexts);
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'update') {
            if(!$render->pnFormIsValid()) {
                return false;
            }

            $data = $render->pnFormGetValues();

            $obj = TimeItDomainFactory::getInstance('event')->getObject($this->id);

            foreach($data AS $lang => $translation) {
                $obj['title_translate'][$lang] = $translation['title'];
                $obj['text_translate'][$lang] = $translation['text'];
            }

            TimeItDomainFactory::getInstance('event')->updateObject($obj);
        }

        $render->pnFormRedirect(pnModURL('TimeIt', 'user','view',array('ot' => 'event', 'id' => $this->id)));
    }
}