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
class TimeIt_FormHandler_translate
{
    var $id;

    function initialize(&$render)
    {
        if(($this->id=FormUtil::getPassedValue('id', null, 'GET'))==null) {
            return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
        }

        // load event
        $obj = pnModAPIFunc('TimeIt','user','get',array('id' => $this->id));
        if(empty($obj)) {
            return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
        }
        $obj = pnModAPIFunc('TimeIt','user','getEventPreformat',array('obj' => $obj));

        $ids = array();
        // assign current translations
        $langlist = LanguageUtil::getInstalledLanguages();
        foreach($langlist AS $lang => $text) {
            if(isset($obj['title_translate'][$lang]) || isset($obj['text_translate'][$lang])) {
                $render->assign($lang, array('title' => $obj['title_translate'][$lang],
                                             'text'  => $obj['text_translate'][$lang]));
            }
            $ids[] = 'text_'.$lang;
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
        $render->assign('language', pnUserGetLang());
        $render->assign('languages', LanguageUtil::getInstalledLanguages());
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'update') {
            if(!$render->pnFormIsValid()) {
                return false;
            }

            $data = $render->pnFormGetValues();

            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event'))) {
              pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
            }
            $object = new $class();
            $obj = $object->getEvent($this->id);
            if(empty($obj)) {
                return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }

            foreach($data AS $lang => $translation) {
                $obj['title_translate'][$lang] = $translation['title'];
                $obj['text_translate'][$lang] = $translation['text'];
            }

            $object->setData($obj);
            $object->save();
        }

        $render->pnFormRedirect(pnModURL('TimeIt', 'user','event',array('id'=>$this->id)));
    }
}