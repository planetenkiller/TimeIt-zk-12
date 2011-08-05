<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage AJAX
 */

Loader::requireOnce('modules/TimeIt/common.php'); 

function TimeIt_ajax_viewUserOfSubscribedEvent()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false) {
        AjaxUtil::error(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else {
        $user = TimeItDomainFactory::getInstance('reg')->getUserOfAReg($id);

        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $id);
        $obj = TimeItDomainFactory::getInstance('event')->getObject($dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid']);
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

        $render =& pnRender::getInstance('TimeIt', false);
        $render->assign('users', $user);
        $render->assign('calendar', $calendar);
        $render->assign('event', $obj);
        $render->assign('uid', pnUserGetVar('uid'));
        $render->assign('eid', $id);

        $html = $render->fetch('ajax_view_userOfReg.htm');
        return array('html' => $html);
    }
}

function TimeIt_ajax_subscribe()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false) {
        AjaxUtil::error(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else {
        if($id !== false) {
            $result = TimeItDomainFactory::getInstance('reg')->create($id);

            if($result) {
                return array('result' => $result);
            } else {
                AjaxUtil::error(__('Error! Registration to the event faild.', ZLanguage::getModuleDomain('TimeIt')));
            }
        } else {
            AjaxUtil::error(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }
    }
}

function TimeIt_ajax_unsubscribe()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false) {
        AjaxUtil::error(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else {
        $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $id);
        $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

        if($id !== false && $calendar['allowSubscribe'])  {
            $result = TimeItDomainFactory::getInstance('reg')->deleteByEvent($dheobj['id']);
            return array('result' => $result);
        } else  {
            AjaxUtil::error(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
        }
    }
}

function TimeIt_ajax_ContactAddressbookSearch()
{
    $serch = FormUtil::getPassedValue('ContactAddressbook_search', false, 'GETPOST');
    $array = pnModAPIFunc('Addressbook','user','search',array('search'=>$serch));

    $ret = '<ul>';

    foreach($array AS $obj) {
        $text = $obj['fname'].' '.$obj['lname'].', '.$obj['zip'].' '.$obj['city'];
        $ret .= '<li id="'.(int)$obj['id'].'">'.DataUtil::formatForDisplay($text).'</li>';
    }

    $ret .= '</ul>';
    echo $ret;
    return true;
}