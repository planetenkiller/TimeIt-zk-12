<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Manager
 */

/**
 * Reg(Registrations) Manager
 *
 * @author planetenkiller
 */
class TimeItRegManager
{
    public function getObjectList($filter_obj=null, $getUser=false)
    {
        // load the object array class
        if (!($class = Loader::loadArrayClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'reg', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $objectArray = new $class();
        if($getUser) {
            $objectArray->_objJoin = array(array('join_table'          =>  'users',
                                                 'join_field'          =>  array('uname','email'),
                                                 'object_field_name'   =>  array('name','email'),
                                                 'compare_field_table' =>  'uid',
                                                 'compare_field_join'  =>  'uid'));
        }

        $where = '';
        // add filter to sql
        if($filter_obj != null) {
            $filter_sql = $filter_obj->toSQL();
            
            if(!empty($filter_sql)) {
                $where = $filter_sql;
            }
        }
        // get data form database
        $array = $objectArray->get($where);

        return $array;
    }

    /**
     *
     * @param TimeItFilter $filter_obj
     * @return int
     */
    public function getListCount(TimeItFilter $filter_obj=null)
    {
        // load the object array class
        if (!($class = Loader::loadArrayClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'reg', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $objectArray = new $class();

        $where = '';
        // add filter to sql
        if($filter_obj != null) {
            $filter_sql = $filter_obj->toSQL();

            if(!empty($filter_sql)) {
                $where = $filter_sql;
            }
        }
        // get count form database
        return (int)$objectArray->getCount($where);
    }

    public function getObject($id, $isEventId=false)
    {
        // load the object class
        if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load array class of the object type %s.', 'reg', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $object = new $class();

        if($isEventId)
            return $object->get($id, 'eid');

        return $object->get($id);
    }

    public function deleteObject($id)
    {
        // load the object class
        if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'reg', ZLanguage::getModuleDomain('TimeIt')));
        }

        // instantiate the object type
        $object = new $class();
        $object->get($id);
        
        return $object->delete() !== false;
    }

    public function deleteByEvent($dheid, $uid=0)
    {
        $uid = $uid > 0 ? $uid : pnUserGetVar('uid');

        // load the object class
        if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'reg', ZLanguage::getModuleDomain('TimeIt')));
        }

        $pntables = pnDBGetTables();
        $timeit_regs_column = $pntables['TimeIt_regs_column'];

        $class = new $class();
        $class->getWhere('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($dheid).' AND '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
        $result = $class->delete();
        return ($result === false)? false: true;
    }

    /**
     * Deletes the pending state.
     * @param int $id TimeIt_regs id
     * @return bool
     */
    function deletePendingState($id)
    {
        // load the object class
        if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'reg', ZLanguage::getModuleDomain('TimeIt')));
        }

        $class = new $class();
        $class->get((int)$id);
        $class->setDataField('status', 1);
        return $class->save() !== false;
    }

    public function create($dheid, $uid=-1, $dynamicData=array())
    {
        // set user id
        $uid = $uid > 0? $uid : pnUserGetVar('uid');
        if(empty($uid)) $uid = 1;

        // create possible?
        if($uid && $this->canCreate($dheid, $uid)) {
            // load the object class
            if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
                pn_exit(__f('Unable to load class of the object type %s.', 'reg'));
            }

            $dheobj = DBUtil::selectObjectByID('TimeIt_date_has_events', $dheid);
            $eid = $dheobj['localeid']? $dheobj['localeid'] : $dheobj['eid'];
            $obj = TimeItDomainFactory::getInstance('event')->getObject($eid, $dheid);
            $calendar = TimeItDomainFactory::getInstance('calendar')->getObject($dheobj['cid']);

            
            if(empty($obj) || $obj['status'] == 0) {
                return LogUtil::registerError(__f('Item with id %s not found.', $eid, ZLanguage::getModuleDomain('TimeIt')), 404);
            } else if(!$calendar['allowSubscribe']) {
                return false;
            }

            if($obj['subscribeWPend']) {
                $status = 0;
            } else {
                $status = 1;
            }

            $class = new $class();
            $class->setData(array('eid'    => $dheid,
                                  'uid'    => $uid,
                                  'status' => $status,
                                  'data'   => $dynamicData));
            $result = $class->insert();

            if($result !== false) {
                if($status == 0)
                    LogUtil::registerStatus(__('Thanks for your registration. Your registration will be verified as soon as possible.', ZLanguage::getModuleDomain('TimeIt')));
                else
                    LogUtil::registerStatus (__('Thanks for your registration.', ZLanguage::getModuleDomain('TimeIt')));
            }


            return ($result === false)? false: true;
        } else {
            return false;
        }
    }

    /**
     * @param int dheid TimeIt_date_has_events id
     * @param int  user id (optional)
     * @return bool
     */
    public function canCreate($dheid, $uid=0) {
        $uid = $uid > 0 ? $uid : pnUserGetVar('uid');

        // Guests always can register to events
        if($uid <= 1)
            return true;

        if($uid) {
            // load the object array class corresponding to $objectType
            if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
                pn_exit(__f('Unable to load class of the object type %s.', 'reg'));
            }

            $pntables = pnDBGetTables();
            $timeit_regs_column = $pntables['TimeIt_regs_column'];
            $class = new $class();
            $obj = $class->getWhere('WHERE '.$timeit_regs_column['eid'].' = '.DataUtil::formatForStore($dheid).' AND '.$timeit_regs_column['uid'].' = '.DataUtil::formatForStore($uid));
            return empty($obj);
        } else {
            return false;
        }
    }

    public function updateCalendar($obj)
    {
        // load the object array class corresponding to $objectType
        if (!($class = Loader::loadClassFromModule('TimeIt', 'reg'))) {
            pn_exit(__f('Unable to load class of the object type %s.', 'reg'));
        }

        // instantiate the object type
        $object = new $class();
        $object->setData($obj);
        return $object->save();
    }

    public function getUserOfAReg($dheid, $count=false, $withPendingUsers=false) {
        $filter = new TimeItFilter('reg');
        $filter->addGroup()
            ->addExp('eid:eq:'.$dheid);

        if(!$withPendingUsers) {
            $filter->addExp('status:eq:1');
        }

        if(!$count) {
            return TimeItDomainFactory::getInstance('reg')->getObjectList($filter, true);
        } else {
            return TimeItDomainFactory::getInstance('reg')->getListCount($filter);
        }
    }

    /**
     * Returns all events of an the user $userId is registred to.
     * @param int $userId id of an user
     * @return array
     */
    public function getEventsOfUser($userId) {
        $joinInfo = array(array ('join_table'          => 'TimeIt_date_has_events',
                                 'join_field'          => array('eid','localeid'),
                                 'object_field_name'   => array('dhe_eid','dhe_localeid'),
                                 'compare_field_table' => 'eid',
                                 'compare_field_join'  => 'id'));
        $where = 'a.the_date >= \'' . DateUtil::getDatetime(null, DATEONLYFORMAT_FIXED) . '\' AND tbl.pn_uid = ' . DataUtil::formatForStore($userId);

        $events = DBUtil::selectExpandedObjectArray('TimeIt_regs', $joinInfo, $where, '', -1, -1, '', null, null, array());

        $ids = array();
        foreach($events AS $reg) {
            $ids[] = (int)($reg['dhe_localeid']? $reg['dhe_localeid'] : $reg['dhe_eid']);
        }

        if(count($ids) > 0) {
            $filter = new TimeItFilter('event');
            $filter->addGroup()->addExp('id:in:' . implode(',', $ids));
            return TimeItDomainFactory::getInstance('event')->getEvents(-1, $filter);
        } else {
            return array();
        }
    }
}

