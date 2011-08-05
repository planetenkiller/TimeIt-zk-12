<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Workflows
 */

function TimeIt_operation_createEvent(&$obj, $params)
{
    $obj['status'] = isset($params['online']) ? $params['online'] : 0;
    
    $args = array();
    if($params['repeat'] != '1') {
        $args['noRecurrenceCalculation'] = true;
        $obj['data']['cid'] = $obj['cid']; // backup calendar id because $obj['cid'] won't be saved
    }

    $ret = TimeItDomainFactory::getInstance('event')->createEvent($obj, $args);

    if($ret) {
        if(isset($obj['__META__']['TimeIt']['wfSchema'])) {
            $schema = $obj['__META__']['TimeIt']['wfSchema'];
        } else {
            $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($obj['cid']);
            $schema = $calendar['workflow'];
        }
        
        if($schema == 'moderate') {
            LogUtil::registerStatus(__('Event created successfully. Your event will be verified as soon as possible.', ZLanguage::getModuleDomain('TimeIt')));
        } else {
            LogUtil::registerStatus(__('Event created successfully.', ZLanguage::getModuleDomain('TimeIt')));
        }
    }
    
    return $ret;
}