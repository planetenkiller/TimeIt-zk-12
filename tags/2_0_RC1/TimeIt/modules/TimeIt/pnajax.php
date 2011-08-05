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

function TimeIt_ajax_viewUserOfSubscribedEvent()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false)
    {
        AjaxUtil::error(_MODARGSERROR);
    } else
    {
        $html = pnModFunc('TimeIt','user','viewUserOfSubscribedEvent', array('id'=>$id));

        if($html !== false)
        {
            return array('html' => $html);
        } else
        {
            AjaxUtil::error(_MODARGSERROR);
        }
    }
}

function TimeIt_ajax_subscribe()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false)
    {
        AjaxUtil::error(_MODARGSERROR);
    } else
    {
        $result = pnModFunc('TimeIt','user','subscribe', array('noRedirect'=>true,'id'=>$id));

        return array('result' => $result);
    }
}

function TimeIt_ajax_unsubscribe()
{
    $id = (int)FormUtil::getPassedValue('id', false, 'GETPOST');

    if($id === false || $date === false)
    {
        AjaxUtil::error(_MODARGSERROR);
    } else
    {
        $result = pnModFunc('TimeIt','user','unsubscribe', array('noRedirect'=>true,'eid'=>$id));

        return array('result' => $result);
    }
}

function TimeIt_ajax_ContactAddressbookSearch() {
    $serch = FormUtil::getPassedValue('ContactAddressbook_search', false, 'GETPOST');
    $array = pnModAPIFunc('Addressbook','user','search',array('search'=>$serch));

    $ret = '<ul>';

    foreach($array AS $obj) {
        $text = $obj['fname'].' '.$obj['lname'].', '.$obj['zip'].' '.$obj['city'];
        $ret .= '<li id="'.(int)$obj['id'].'">'.utf8_encode(DataUtil::formatForDisplay($text)).'</li>';
    }

    $ret .= '</ul>';
    echo $ret;
    return true;
}