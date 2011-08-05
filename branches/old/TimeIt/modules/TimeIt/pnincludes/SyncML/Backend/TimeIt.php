<?php

Loader::requireOnce('modules/TimeIt/pnimportapi.php');

class TimeItImportApiSyncML extends TimeItImportApi {
    private $timestamp;
    private $id;

    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    public function getId() {
        return $this->id;
    }

    protected function preInsert(&$obj) {
        $obj['__META__']['TimeIt']['preserve'] = true;
        $obj['cr_date'] = DateUtil::getDatetime($this->timestamp, _DATETIMEINPUT);
        $obj['lu_date'] = DateUtil::getDatetime($this->timestamp, _DATETIMEINPUT);
    }

    protected function postInsert(&$obj) {
        $this->id = $obj['id'];
    }
}

/**
 */
class SyncML_Backend_TimeIt extends SyncML_Backend {

    /**
     * Constructor.
     *
     * @param array $params  A hash with parameters. In addition to those
     *                       supported by the SyncML_Backend class one more
     *                       parameter is required for the database connection:
     *                       'dsn' => connection DSN.
     */
    function SyncML_Backend_TimeIT($params)
    {
        parent::SyncML_Backend($params);
        
    }

    /**
     * Returns whether a database URI is valid to be synced with this backend.
     *
     * @param string $databaseURI  URI of a database. Like calendar, tasks,
     *                             contacts or notes. May include optional
     *                             parameters:
     *                             tasks?options=ignorecompleted.
     *
     * @return boolean  True if a valid URI.
     */
    function isValidDatabaseURI($databaseURI)
    {
        $database = $this->_normalize($databaseURI);

        switch($database) {
        case 'calendar':
        case 'scal':
            return true;

        default:
            return false;
        }
    }

    /**
     * Returns entries that have been modified in the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param integer $from_ts     Start timestamp.
     * @param integer $to_ts       Exclusive end timestamp. Not yet
     *                             implemented.
     * @param array &$adds         Output array: hash of adds suid => 0
     * @param array &$mods         Output array: hash of modifications
     *                             suid => cuid
     * @param array &$dels         Output array: hash of deletions suid => cuid
     *
     * @return mixed  True on success or a PEAR_Error object.
     */
    function getServerChanges($databaseURI, $from_ts, $to_ts, &$adds, &$mods,
                              &$dels)
    {
        $database = $this->_normalize($databaseURI);
        //$adds = $mods = $dels = array();

        // Handle additions:

        $filter_obj = new TimeIt_Filter();
        $filter_obj->addGroup();
        if($from_ts)
            $filter_obj->addExp('cr_date:ge:'.DateUtil::getDatetime($from_ts, _DATETIMEINPUT));
        if($to_ts)
            $filter_obj->addExp('cr_date:lt:'.DateUtil::getDatetime($to_ts, _DATETIMEINPUT));
        $data = pnModAPIFunc('TimeIt','user','getAll',array('cid'=>1,'filter_obj'=>$filter_obj));

        foreach ($data as $d) {
            $suid = $d['id'];
            $suid_ts = strtotime($d['cr_date']);
            $sync_ts = $this->_getChangeTS($databaseURI, $suid);
            if ($sync_ts && $sync_ts >= $suid_ts) {
                // Change was done by us upon request of client, don't mirror
                // that back to the client.
                $this->logMessage("Added to server from client: $suid ignored",
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);
                continue;
            }
            $adds[$suid] = 0;
        }

        // Only compile changes on delta sync:
        if ($from_ts > 0) {
            // Handle replaces. We might get IDs that are already in the adds
            // array but that's ok: The calling code takes care to ignore
            // these.
            $filter_obj = new TimeIt_Filter();
            $filter_obj->addGroup();
            if($from_ts)
                $filter_obj->addExp('lu_date:ge:'.DateUtil::getDatetime($from_ts, _DATETIMEINPUT));
            if($to_ts)
                $filter_obj->addExp('lu_date:lt:'.DateUtil::getDatetime($to_ts, _DATETIMEINPUT));
            $data = pnModAPIFunc('TimeIt','user','getAll',array('cid'=>1,'filter_obj'=>$filter_obj));

            foreach($data as $d) {
                $suid = $d['id'];
                $suid_ts = strtotime($d['lu_date']);
                $sync_ts = $this->_getChangeTS($databaseURI, $suid);
                if ($sync_ts && $sync_ts >= $suid_ts) {
                    // Change was done by us upon request of client, don't
                    // mirror that back to the client.
                    $this->logMessage(
                        "Changed on server after sent from client: $suid ignored",
                        __FILE__, __LINE__, PEAR_LOG_DEBUG);
                    continue;
                }
                $mods[$suid] = $this->_getCuid($databaseURI, $suid);
            }
        }

        // Handle deletions:
        if ($from_ts > 0) {
            $rows = DBUtil::selectExpandedObjectArray ('TimeIt_syncml_map', array(array('join_table'         => 'TimeIt_events',
                                                                                'join_field'         => array('id'),
                                                                                'object_field_name'  => array('id'),
                                                                                'compare_field_table'=> 'eid',
                                                                                'compare_field_join' => 'id')), 'a.pn_id IS NULL', '', -1, -1,'', null, null, array('eid', 'cuid'));
            foreach($rows AS $row) {
                $dels[$rowq['eid']] = $row['cuid'];
            }
        }
    }

    /**
     * Retrieves an entry from the backend.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $suid         Server unique id of the entry: for horde
     *                             this is the guid.
     * @param string $contentType  Content-Type: the MIME type in which the
     *                             function should return the data.
     *
     * @return mixed  A string with the data entry or a PEAR_Error object.
     */
    function retrieveEntry($databaseURI, $suid, $contentType)
    {
        $row = pnModAPIFunc('TimeIt','user','get',array('id'=>(int)$suid));

        if($row) {
            return TimeIt_createIcal(array(array(array(array('data'=>array($row))))), false, true);
        } else {
            return false;
        }
    }

    /**
     * Adds an entry into the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $content      The actual data.
     * @param string $contentType  MIME type of the content.
     * @param string $cuid         Client ID of this entry.
     *
     * @return array  PEAR_Error or suid (Horde guid) of new entry
     */
    function addEntry($databaseURI, $content, $contentType, $cuid = null)
    {
        $tmp = 'BEGIN:VCALENDAR';
        if(substr($content, 0, strlen($tmp)) != $tmp) {
            $content = $tmp."\n".$content."\nEND:VCALENDAR";
        }


        $database = $this->_normalize($databaseURI);
        $ts = $this->getCurrentTimeStamp();

        $input = new TimeItImportApiInputICal($content, true);
        $importer = new TimeItImportApiSyncML($input, 1, false);
        $importer->setTimestamp($ts);
        $count = $importer->doImport();

        if($count == 0) {
            return false;
        }

        $suid = $importer->getId();
        $this->createUidMap($databaseURI, $cuid, $suid, $ts);

        return $suid;
    }

    /**
     * Replaces an entry in the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $content      The actual data.
     * @param string $contentType  MIME type of the content.
     * @param string $cuid         Client ID of this entry.
     *
     * @return string  PEAR_Error or server ID (Horde GUID) of modified entry.
     */
    function replaceEntry($databaseURI, $content, $contentType, $cuid)
    {
        return false;
        /*
        $database = $this->_normalize($databaseURI);

        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
            $suid = $this->_getSuid($databaseURI, $cuid);
        } else {
            $suid = $cuid;
        }

        if ($suid) {
            // Entry exists: replace current one.
            $modified_ts = $this->getCurrentTimeStamp();
            $r = $this->_db->exec(
                'UPDATE syncml_data '
                . 'SET syncml_modified_ts = '
                . $this->_db->quote($modified_ts, 'integer')
                . ', syncml_data = '
                . $this->_db->quote($content, 'text')
                . ', syncml_contenttype = '
                . $this->_db->quote($contentType, 'text')
                . 'WHERE syncml_db = '
                . $this->_db->quote($database, 'text')
                . ' AND syncml_uid = '
                . $this->_db->quote($this->_user, 'text')
                . ' AND syncml_id = '
                . $this->_db->quote($suid, 'text'));
            if ($this->_checkForError($r)) {
                return $r;
            }

            // Only the server needs to keep the map:
            if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
                $this->createUidMap($databaseURI, $cuid, $suid, $modified_ts);
            }
        } else {
            return PEAR::raiseError("No map entry found for client id $cuid replacing on server");
        }

        return $suid;*/
    }

    /**
     * Deletes an entry from the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $cuid         Client ID of the entry.
     *
     * @return boolean  True on success or false on failed (item not found).
     */
    function deleteEntry($databaseURI, $cuid)
    {
        return false;
        /*
        $database = $this->_normalize($databaseURI);

        // Find ID for this entry:
        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
            $suid = $this->_getSuid($databaseURI, $cuid);
        } else {
            $suid = $cuid;
        }

        if (!is_a($suid, 'PEAR_Error')) {
            // A clever backend datastore would store some information about a
            // deletion so this information can be extracted from the history.
            // However we do a "stupid" datastore here where deleted items are
            // simply gone. This allows us to illustrate the _trackDeletes()
            // bookkeeping mechanism.
            $r = $this->_db->queryOne(
                'DELETE FROM syncml_data '
                . ' WHERE syncml_db = '
                . $this->_db->quote($database, 'text')
                . ' AND syncml_uid = '
                . $this->_db->quote($this->_user, 'text')
                . ' AND syncml_id = '
                . $this->_db->quote($suid, 'text'));
            if ($this->_checkForError($r)) {
                return $r;
            }

            // Deleted bookkeeping is required for server and client, but not
            // for test mode:
            if ($this->_backendMode != SYNCML_BACKENDMODE_TEST) {
                $this->_removeFromSuidList($databaseURI, $suid);
            }

            // @todo: delete from map!
        } else {
            return false;
        }

        if (is_a($r, 'PEAR_Error')) {
            return false;
        }

        return true;*/
    }

    /**
     * Authenticates the user at the backend.
     *
     * @param string $username    A user name.
     * @param string $password    A password.
     *
     * @return boolean|string  The user name if authentication succeeded, false
     *                         otherwise.
     */
    function _checkAuthentication($username, $password)
    {
        $ret = pnUserLogIn($username, $password);
        if($ret) {
            return pnUserGetVar('uname');
        } else {
            return false;
        }
    }

    /**
     * Sets a user as being authenticated at the backend.
     *
     * @abstract
     *
     * @param string $username    A user name.
     * @param string $credData    Authentication data provided by <Cred><Data>
     *                            in the <SyncHdr>.
     *
     * @return string  The user name.
     */
    function setAuthenticated($username, $credData)
    {
        return $username;
    }

    /**
     * Stores Sync anchors after a successful synchronization to allow two-way
     * synchronization next time.
     *
     * The backend has to store the parameters in its persistence engine
     * where user, syncDeviceID and database are the keys while client and
     * server anchor ar the payload. See readSyncAnchors() for retrieval.
     *
     * @param string $databaseURI       URI of database to sync. Like calendar,
     *                                  tasks, contacts or notes. May include
     *                                  optional parameters:
     *                                  tasks?options=ignorecompleted.
     * @param string $clientAnchorNext  The client anchor as sent by the
     *                                  client.
     * @param string $serverAnchorNext  The anchor as used internally by the
     *                                  server.
     */
    function writeSyncAnchors($databaseURI, $clientAnchorNext,
                              $serverAnchorNext)
    {
        $database = $this->_normalize($databaseURI);

        $row = $this->readSyncAnchors($databaseURI, true);
        // Check if entry exists. If not insert, otherwise update.
        if (!$row) {
            $obj = array('deviceid' => $this->_syncDeviceID,
                         'uid'      => (int)pnUserGetVar('uid'),
                         'clianchor'=> $clientAnchorNext,
                         'srvanchor'=> $serverAnchorNext);
            DBUtil::insertObject($obj, 'TimeIt_syncml_anchors');
        } else {
            $row['clianchor'] = $clientAnchorNext;
            $row['srvanchor'] = $serverAnchorNext;
            DBUtil::updateObject($row, 'TimeIt_syncml_anchors', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND uid = ".(int)pnUserGetVar('uid'));
        }

        return true;
    }

    /**
     * Reads the previously written sync anchors from the database.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     *
     * @return mixed  Two-element array with client anchor and server anchor as
     *                stored in previous writeSyncAnchor() calls. False if no
     *                data found.
     */
    function readSyncAnchors($databaseURI, $raw=false)
    {
        $r = DBUtil::selectObject('TimeIt_syncml_anchors', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND uid = ".(int)pnUserGetVar('uid'));

        if (!is_array($r)) {
            return false;
        }

        if($raw) {
            return $r;
        } else {
            return array($r['clianchor'], $r['srvanchor']);
        }
    }

    /**
     * Creates a map entry to map between server and client IDs.
     *
     * If an entry already exists, it is overwritten.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $cuid         Client ID of the entry.
     * @param string $suid         Server ID of the entry.
     * @param integer $timestamp   Optional timestamp. This can be used to
     *                             'tag' changes made in the backend during the
     *                             sync process. This allows to identify these,
     *                             and ensure that these changes are not
     *                             replicated back to the client (and thus
     *                             duplicated). See key concept "Changes and
     *                             timestamps".
     */
    function createUidMap($databaseURI, $cuid, $suid, $timestamp = 0)
    {
        $database = $this->_normalize($databaseURI);

        $row = $this->_getSuid($databaseURI, $cuid);
        // Check if entry exists. If not insert, otherwise update.
        if (!$row) {
           $obj = array('deviceid'  => $this->_syncDeviceID,
                        'uid'       => (int)pnUserGetVar('uid'),
                        'cuid'      => $cuid,
                        'eid'       => (int)$suid,
                        'timestamp' => $timestamp);
           DBUtil::insertObject($obj, 'TimeIt_syncml_map');
        } else {
            $obj = array('timestamp' => $timestamp);
            DBUtil::updateObject($obj, 'TimeIt_syncml_map', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND ".' uid = '.(int)pnUserGetVar('uid').' AND cuid = \''.DataUtil::formatForStore($cuid).'\'');
        }

        return true;
    }

    /**
     * Retrieves the Server ID for a given Client ID from the map.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $cuid         The client ID.
     *
     * @return mixed  The server ID string or false if no entry is found.
     */
    function _getSuid($databaseURI, $cuid)
    {
        return DBUtil::selectField('TimeIt_syncml_map','eid', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND ".' uid = '.(int)pnUserGetVar('uid').' AND cuid = \''.DataUtil::formatForStore($cuid).'\'');
    }

    /**
     * Retrieves the Client ID for a given Server ID from the map.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $suid         The server ID.
     *
     * @return mixed  The client ID string or false if no entry is found.
     */
    function _getCuid($databaseURI, $suid)
    {
        return DBUtil::selectField('TimeIt_syncml_map','cuid', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND ".' uid = '.(int)pnUserGetVar('uid').' AND eid = '.(int)$suid);
    }

    /**
     * Returns a timestamp stored in the map for a given Server ID.
     *
     * The timestamp is the timestamp of the last change to this server ID
     * that was done inside a sync session (as a result of a change received
     * by the server). It's important to distinguish changes in the backend a)
     * made by the user during normal operation and b) changes made by SyncML
     * to reflect client updates.  When the server is sending its changes it
     * is only allowed to send type a). However the history feature in the
     * backend my not know if a change is of type a) or type b). So the
     * timestamp is used to differentiate between the two.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $suid         The server ID.
     *
     * @return mixed  The previously stored timestamp or false if no entry is
     *                found.
     */
    function _getChangeTS($databaseURI, $suid)
    {
        return DBUtil::selectField('TimeIt_syncml_map','timestamp', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND ".' uid = '.(int)pnUserGetVar('uid').' AND eid = '.(int)$suid);
    }

    /**
     * Erases all mapping entries for one combination of user, device ID.
     *
     * This is used during SlowSync so that we really sync everything properly
     * and no old mapping entries remain.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     */
    function eraseMap($databaseURI)
    {
        DBUtil::deleteWhere('TimeIt_syncml_map', "deviceid = '".DataUtil::formatForStore($this->_syncDeviceID)."' AND uid = ".(int)pnUserGetVar('uid'));

        return true;
    }

    /**
     * Cleanup function called after all message processing is finished.
     *
     * Allows for things like closing databases or flushing logs.  When
     * running in test mode, tearDown() must be called rather than close.
     */
    function close()
    {
        parent::close();
        
    }


    /**
     * Checks if the parameter is a PEAR_Error object and if so logs the
     * error.
     *
     * @param mixed $o  An object or value to check.
     *
     * @return mixed  The error object if an error has been passed or false if
     *                no error has been passed.
     */
    function _checkForError($o)
    {
        if (is_a($o, 'PEAR_Error')) {
            $this->logMessage($o);
            return $o;
        }
        return false;
    }

    public function logMessage($message, $file, $line, $priority) {
        file_put_contents('/tmp/timeit_syncml.log', DateUtil::formatDatetime().' '.basename($file).':'.$line.' - '.$message."\n", FILE_APPEND);
    }
}
