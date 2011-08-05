<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage API
 */

Loader::includeOnce('modules/TimeIt/common.php');


/**
 *
 * @param int $id TimeIt_date_has_events id
 * @param int $uid user id (optional)
 * @param mixed ... custom data
 * @return <type>
 */
function TimeIt_subscribeapi_subscribe($args)
{
    if(!isset($args['id']) || empty($args['id'])) {
        return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else {
        $uid = (isset($args['uid']) && !empty($args['uid']))? $args['uid']: pnUserGetVar('uid');
        if(empty($uid)) $uid = 1;

        $data = $args;
        unset($data['id']);
        if(isset($data['uid'])) {
            unset($data['uid']);
        }

        return TimeItDomainFactory::getInstance('reg')->create($args['id'], $uid, $args);
    }
}

/**
 *
 * @param array $args ['id'] TimeIt_date_has_events id
 *                    ['uid'] user id (optional)
 * @return bool
 */
function TimeIt_subscribeapi_isSubscribed($args)
{
    if(!isset($args['id'])) {
        return LogUtil::registerError(__('Error! Could not do what you wanted. Please check your input.', ZLanguage::getModuleDomain('TimeIt')));
    } else  {

        $uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
        if(empty($uid)) $uid = 1;

        return !TimeItDomainFactory::getInstance('reg')->canCreate($args['id'], $uid);
    }
}


function TimeIt_subscribeapi_countUserForEvent($args) {
    $filter = new TimeItFilter('reg');
    $filter->addGroup()->addExp('eid:eq:'.$args['eid']);
    return TimeItDomainFactory::getInstance('reg')->getListCount($filter);
}