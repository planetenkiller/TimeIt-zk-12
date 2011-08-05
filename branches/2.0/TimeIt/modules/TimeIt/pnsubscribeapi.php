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
 * Deletes a subscription.
 * @param int $id TimeIt_regs id
 * @return bool 
 */
function TimeIt_subscribeapi_delete($id)
{
    if(!isset($id)) {
        return LogUtil::registerError(_MODARGSERROR);
    } else  {
        if (!($class = Loader::loadClassFromModule('TimeIt', 'Reg'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        $class = new $class();
        $class->get((int)$id);
        return $class->delete();
    }
}

/**
 * Deletes the pending state.
 * @param int $id TimeIt_regs id
 * @return bool
 */
function TimeIt_subscribeapi_deletePendingState($id)
{
    if(!isset($id) || empty($id)) {
        return LogUtil::registerError(_MODARGSERROR);
    } else  {
        if (!($class = Loader::loadClassFromModule('TimeIt', 'Reg'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        $class = new $class();
        $class->get((int)$id);
        $class->setDataField('status', 1);
        $class->save();
        return true;
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
        return LogUtil::registerError(_MODARGSERROR);
    } else  {

        $uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
        //if(empty($uid)) $uid = 1;
        if($uid) {
            if (!($class = Loader::loadClassFromModule('TimeIt', 'Reg'))) {
                pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
            }

            $pntables = pnDBGetTables();
            $timeit_regs_column = $pntables['TimeIt_regs_column'];
            $class = new $class();
            $obj = $class->getWhere('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['id']).' AND '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
            return (!empty($obj));
        } else {
            return false;
        }
    }
}

/**
 *
 * @param array $args ['id'] TimeIt_date_has_events id
 *                    ['uid'] user id (optional)
 * @return <type>
 */
function TimeIt_subscribeapi_subscribe($args)
{
    if(!isset($args['id']) || empty($args['id'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else 
    {
        $uid = (isset($args['uid']) && !empty($args['uid']))? $args['uid']: pnUserGetVar('uid');
        if(empty($uid)) $uid = 1;

        if($uid && !pnModAPIFunc('TimeIt','subscribe','isSubscribed',array('id'=>(int)$args['id']))) {
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg'))) {
                pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
            }

            $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $args['id']);
            $obj = pnModAPIFunc('TimeIt','user','get',array('id'=>$dheobj['eid'],'dheobj'=>$dheobj));
            if(empty($obj) || (isset($obj) && $obj['status'] == 0)) {
                return LogUtil::registerError(pnML('_TIMEIT_IDNOTEXIST',array('s'=>(int)$args['id'])), 404);
            }
            if($obj['subscribeWPend']) {
                $status = 0;
            } else {
                $status = 1;
            }

            $data = $args;
            unset($data['id']);
            if(isset($data['uid'])) {
                unset($data['uid']);
            }

            $class = new $class();
            $class->setData(array('eid'=>$args['id'],'uid'=>$uid,'status'=>$status,'data'=>$data));
            $result = $class->insert();
            return ($result === false)? false: true;
        } else {
            return false;
        }
    }
}

/**
 *
 * @param array $args ['id'] TimeIt_date_has_events id
 * @return int
 */
function TimeIt_subscribeapi_countUserForEvent($args)
{
    if(!isset($args['id'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        $pntables = pnDBGetTables();
        $timeit_regs_column = $pntables['TimeIt_regs_column'];
        $class = new $class();
        return $class->getCount('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore((int)$args['id']));
    }
}

/**
 *
 * @param array $args ['id'] TimeIt_date_has_events id
 *                    ['withPending'] true all subscriptions (default true)
 * @return array
 */
function TimeIt_subscribeapi_userArrayForEvent($args)
{
    if(!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        $pntables = pnDBGetTables();
        $timeit_regs_column = $pntables['TimeIt_regs_column'];

        $class = new $class();
        $join = array(array ('join_table'        =>  'users',
                             'join_field'         =>  array('uname','email'),
                             'object_field_name'  =>  array('name','email'),
                             'compare_field_table'=>  'uid',
                             'compare_field_join' =>  'uid'));
        $class->_objJoin = $join;
        $where = 'WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['id']);
        if(!isset($args['withPending']) || !$args['withPending']) {
            $where .= ' AND '.$timeit_regs_column['status'].' = 1';
        }
        return $class->get($where);
    }
}

function TimeIt_subscribeapi_countEventsForUser($args)
{
    $uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    if($uid) {
        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        $pntables = pnDBGetTables();
        $timeit_regs_column = $pntables['TimeIt_regs_column'];
        $class = new $class();
        return $class->getCount('WHERE '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
    } else {
        return LogUtil::registerError(_MODARGSERROR);
    }
}

function TimeIt_subscribeapi_arrayOfEventsForUser($args)
{
    $uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
    if($uid) {
        if (!($class1 = Loader::loadClassFromModule ('TimeIt', 'Reg', true))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
        }

        if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Event')));
        }

        $class1 = new $class1();
        $class1->_objJoin = array(array ('join_table'           =>  'TimeIt_date_has_events',
                                       'join_field'         =>  array('eid','localeid'),
                                       'object_field_name'  =>  array('dhe_eid','dhe_localeid'),
                                       'compare_field_table'=>  'eid',
                                       'compare_field_join' =>  'id'));
        $array = $class1->getWhere('a.the_date >= '.DateUtil::getDatetime(null, _DATEINPUT).' AND tbl.pn_uid = '.DataUtil::formatForStore($uid));
        $ids = array();
        foreach($array AS $reg) {
            $ids[] = (int)($reg['dhe_localeid']? $reg['dhe_localeid'] : $reg['dhe_eid']);
        }

        if(!empty($ids)) {
            $pntables = pnDBGetTables();
            $class = new $class();
            return $class->getWhere('WHERE pn_id IN('.implode(',', $ids).')');
        } else {
            return array();
        }
    } else 
    {
        return LogUtil::registerError(_MODARGSERROR);
    }
}

function TimeIt_subscribeapi_unsubscribe($args)
{
    if(!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerError(_MODARGSERROR);
    } else {
        $uid = (isset($args['uid']))? $args['uid']: pnUserGetVar('uid');
        //if(empty($uid)) $uid = 1;
        if($uid) {
            if (!($class = Loader::loadClassFromModule ('TimeIt', 'Reg'))) {
                    pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'Reg')));
            }

            $pntables = pnDBGetTables();
            $timeit_regs_column = $pntables['TimeIt_regs_column'];
            $class = new $class();
            $class->getWhere('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($args['id']).' AND '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
            $result = $class->delete();
            return ($result === false)? false: true;
        } else {
            return LogUtil::registerError(_MODARGSERROR);
        }
    }
}
