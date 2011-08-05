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

/**
 * Imports all events from PostCalendar.
 * @param array $args 'prefix'=>the table prefix, 'cid'=>calendar id
 */
function TimeIt_importapi_postcalendar($args)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    
    if (!isset($args['prefix']) || !isset($args['cid'])) 
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    $prefix = $args['prefix'];
    // 30 sec aren't enough 
    ini_set('max_execution_time' , 3600);
    
    $input = new TimeItImportApiInputPC($prefix);
    $importer = new TimeItImportApi($input, $args['cid']);
    $ret = $importer->doImport();
    
    if($ret)
      LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
      LogUtil::registerStatus('Events: '.$ret);
      
    return $ret;
}

function TimeIt_importapi_importcats($prefix)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // load the admin language file
    // pull all data from the old tables
    //$prefix = pnConfigGetVar('prefix');
    $sql = "SELECT pc_catid, pc_catname, pc_catcolor, pc_catdesc FROM {$prefix}_postcalendar_categories";
    $result = DBUtil::executeSQL($sql);
    $categories = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $categories[] = $result->fields;
    }

    //$result->Close();
    //print_r($categories);
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = ZLanguage::getLanguageCode();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules');

    // create placeholder for all our migrated categories
    $cat = new PNCategory ();
    $cat->setDataField('parent_id', $rootcat['id']);
    $cat->setDataField('name', 'TimeIt');
    $cat->setDataField('value', '-1');
    $cat->insert();
    $cat->update();
    $rootid = $cat->getDataField('id');
    
    // add registry entry
    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname', 'TimeIt');
    $registry->setDataField('table', 'TimeIt_events');
    $registry->setDataField('property', 'pc_imports');
    $registry->setDataField('category_id', $rootid);
    $registry->insert();


    // migrate our root categories
    $categorymap = array();
    foreach ($categories as $category) {
        $cat = new PNCategory();
        $cat->setDataField('parent_id', $rootid);
        $cat->setDataField('name', $category[1]);
        $cat->setDataField('display_name', array($lang => $category[1]));
        $cat->setDataField('display_desc', array($lang => $category[3]));
        $cat->insert();
        $cat->update();
        $newcatid = $cat->getDataField('id');
        $categorymap[$category[0]] = $newcatid;

        $data = array('attribute_name' => 'color',
                      'object_id'      => $newcatid,
                      'object_type'    => 'categories_category',
                      'value'          => $category[2]);
        DBUtil::insertObject($data, 'objectdata_attributes');
    }

    return $categorymap;
}

/**
 * This function imports an iCalendar file (.ics).
 * @param arraa $args 'path'=>path to the file, 'cid'=>calendar id
 */
function TimeIt_importapi_fromICal($args)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    
    if (!isset($args['path']) || !isset($args['cid'])) 
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    $path = $args['path'];
    $cid = $args['cid'];
    $sync = (isset($args['sync']) && $args['sync'])? true : false;
    
    // 30 sec aren't enough 
    ini_set('max_execution_time' , 3600);
    
    $input = new TimeItImportApiInputICal($path);
    $importer = new TimeItImportApi($input, $cid, $sync);
    $ret = $importer->doImport();
    
    if($ret)
      LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
      LogUtil::registerStatus('Events: '.$ret);
      
    return $ret;
    
}

/**
 * TimeIt import api
 */
class TimeItImportApi
{
    var $input;
    var $sync;
    var $cid; // calendar id
    
    function TimeItImportApi($inputObj, $cid, $sync=false)
    {
        $this->input = $inputObj;
        $this->sync = $sync;
        $this->cid = $cid;
    }
    
    function doImport()
    {
//echo '<pre>';
        if($this->input->check() == false)
        {
            return false;
        } else
        {
            $this->input->load();
            $count = 0;
            while($this->input->next())
            {
                //print_r($this->input);
                $obj = array();
                $obj['cid'] = $this->cid;
                $obj['iid'] = $this->input->iid();
                $obj['title'] = $this->input->title();
                $obj['text'] = $this->input->text();
                $obj['data'] = $this->input->data();
                $obj['__CATEGORIES__'] = $this->input->categories();
                $obj['allDay'] = $this->input->allDay();
                $obj['allDayStart'] = $this->input->allDayStart();
                $obj['allDayDur'] = $this->input->allDayDur();
                $obj['repeatType'] = $this->input->repeatType();
                $obj['repeatSpec'] = $this->input->repeatSpec();
                $obj['repeatFrec'] = $this->input->repeatFrec();
                $obj['startDate'] = $this->input->startDate();
                $obj['endDate'] = $this->input->endDate();
                $obj['sharing'] = $this->input->sharing();
                $obj['group'] = $this->input->group();
                $obj['status'] = $this->input->status();
                $obj['subscribeLimit'] = $this->input->subscribeLimit();
                $obj['subscribeWPend'] = $this->input->subscribeWPend();
                $this->input->_special($obj);
                if( ($cuid=$this->input->cr_uid()) )
                {
                    $obj['cr_uid'] = $cuid;
                    $obj['__META__']['TimeIt']['preserve'] = true;
                }
                
                if($this->input->cr_date())
                {
                    $obj['cr_date'] = $this->input->cr_date();
                    $obj['__META__']['TimeIt']['preserve'] = true;
                }
                
                if($this->input->wk_schema())
                {
                    $schema = $this->input->wk_schema();
                } else
                {
                    $schema = 'standard';
                }
                
//                print_r($obj);

                if($this->doInsert($obj, $schema))
                {
                    $count++;
                }
            }
//echo '</pre>';
//           exit();
            return $count;
        }
    }
    
    /**
     * This function inserts an event into the DB.
     * @param array $obj db record (see DBUtil)
     * @param string $schema 'standard' or 'moderate'
     * @return mixed
     */
    function doInsert(&$obj, $schema)
    {
        // use synchronize?
        if($this->sync && $obj['iid']) {
            $id = $obj['iid'];
            $idkey = 'iid';
            $ends_with = '@'.pnServerGetVar('HTTP_HOST').pnGetBaseURI();
            // our event?
            if(substr($obj['iid'],strlen($obj['iid'])-strlen($ends_with)) == $ends_with) {
                $id = (int)substr($id, 0, strpos($id, '@')); // extract id
                $idkey = 'id';
            }
            // get existing event
            $objOrig = pnModAPIFunc('TimeIt','user','get'.($idkey=='iid'?'ByIID':''),array($idkey=>$id));

            // overwrite old event if we found an old one
            if(!empty($objOrig) && isset($objOrig['id'])) {
                unset($obj['id']);
                if($this->input instanceof TimeItImportApiInputICal) {
                    unset($obj['iid'], $obj['data'], $obj['__CATEGORIES__'],
                          $obj['subscribeLimit'], $obj['subscribeWPend'],
                          $obj['status'],$obj['group']); //sync $obj['data'] isn't a good idea because ical can't import location and contact correctly
                }
                $obj = array_merge_recursive_distinct($objOrig, $obj);
                return pnModAPIFunc('TimeIt','user','update',array('obj'=>$obj));
            } else {   
                // fallback: create event
                return WorkflowUtil::executeAction($schema, $obj, 'submit', 'TimeIt_events');
            }
        } else {
            // fallback: create event
            return WorkflowUtil::executeAction($schema, $obj, 'submit', 'TimeIt_events');
        }
    }
}

/**
 * PostCalendar input for TimeIt import api.
 */
class TimeItImportApiInputPC
{
    var $prefix;
    var $data;
    var $row;
    var $repeatSpecNumToStr;
    var $firstCall;
    var $maxDate;
    var $categorymap;
    
    function TimeItImportApiInputPC($prefix)
    {
        $this->prefix = $prefix;
        $this->repeatSpecNumToStr = array(0=>'day', 1=>'week', 2=>'month', 3=>'year');
        $this->firstCall = true;
        $this->maxDate = DateUtil::getDatetime(strtotime('+1 year',time()), DATEONLYFORMAT_FIXED);
        
        // convert categroies
    $this->categorymap = pnModAPIFunc('TimeIt', 'import', 'importcats', $prefix);
        pnModSetVar('TimeIt', 'colorCatsProp', 'pc_imports');
    }
    
    function check()
    {
        $sql1 = "show table status like '".$this->prefix."_postcalendar_%'";
    $result1 = DBUtil::executeSQL($sql1);
    if($result1->RecordCount() < 2)
    {
        return LogUtil::registerError(_TIMEIT_ERROR_PCTABLE);
    } else
        {
            return true;
        }
    }
    
    function load()
    {
        $this->data = DBUtil::executeSQL('SELECT * FROM '.$this->prefix.'_postcalendar_events');
        
        if($this->data)
        {
            $this->row = $this->data->GetRowAssoc(false);
        }
    }
    
    function next()
    {

        if($this->firstCall == false)
        {
            $ret = $this->data->MoveNext();
            if($ret)
            {
                $this->row = $this->data->GetRowAssoc(false);
            }
            return $ret;
        } else 
        {   // ignore first call, adodb called MoveNext for us
            $this->firstCall = false; // reset
            if($this->data === false)
            {
                return false;
            } else
            {
                return true;
            }
        }
    }
    
    // getter to get the data
    
    function _special(&$obj)
    {
    }
    
    function iid()
    {
        return '';
    }
    
    function title()
    {
        return $this->row['pc_title'];
    }
    
    function text()
    {
        return $this->row['pc_hometext'];
    }
    
    function categories()
    {
        return array('pc_imports' => $this->categorymap[(int)$this->row['pc_catid']]);
    }
    
    function startDate()
    {
        return $this->row['pc_eventdate'];
    }
    
    function endDate()
    {
        if($this->row['pc_enddate'] == "0000-00-00")
        {
            if($this->row['pc_recurrtype'] == 0)
            {
                return $this->startDate();
            } else
            {
                return $this->maxDate;//FIXME: better way?
            }
        } else
        {
            if($this->row['pc_recurrtype'] == 0)
            {
                 return $this->startDate();
            } else
            {
                if($this->row['pc_enddate'] <= $this->maxDate)
                {
                    return $this->row['pc_enddate'];
                } else
                {
                    return $this->maxDate;
                }
                
            }
        }
    }
    
    function data()
    {
        $location = unserialize($this->row['pc_location']);
        $data = array('eventplugin_contact'  => 'ContactTimeIt',
                      'eventplugin_location' => 'LocationTimeIt',
                      'fee'                  => $this->row['pc_fee'],
                      'plugindata' => array('ContactTimeIt'=> array('phoneNr'       => $this->row['pc_conttel'],
                                                                    'contactPerson' =>$this->row['pc_contname'],
                                                                    'email'         =>$this->row['pc_contemail'],
                                                                    'website'       =>$this->row['pc_website']),
                                            'LocationTimeIt' => array('name'    =>$location['event_location'],
                                                                      'city'    =>$location['event_city'],
                                                                      'streat'  =>$location['event_street1'].' '.$location['event_street2'],
                                                                      'country' =>$location['event_state'],
                                                                      'zip'     =>$location['event_postal'])));


        return $data;
    }
    
    function allDay()
    {
        return (int)$this->row['pc_alldayevent'];
    } 
    
    function allDayStart()
    {
        if($this->allDay() == 0) 
        { 
            return $this->row['pc_starttime'];
        } else 
        {
            return '00:00';
        }
    } 
    
    function allDayDur()
    {
        if($this->allDay() == 0) 
        { 
            $sec = (int)$this->row['pc_duration']; 
            $h = $sec / 3600; // hours 
            $sec = $sec % 3600; // rest 
            $min = $sec / 60; // minutes 
            return $h.','.$min; 
        } else 
        {
            return '0';
        }
    } 
    
    function repeatType()
    {
        if((int)$this->row['pc_recurrtype'] == 1)
        {
            return 1;
        } else if((int)$this->row['pc_recurrtype'] == 2)
        {
            return 2;
        }
    } 
    
    function repeatSpec()
    {
        if((int)$this->row['pc_recurrtype'] == 1)
        {
            $data = unserialize($this->row['pc_recurrspec']);
            return $this->repeatSpecNumToStr[(int)$data['event_repeat_freq_type']];
            
        } else if((int)$this->row['pc_recurrtype'] == 2)
        {
            $data = unserialize($this->row['pc_recurrspec']);
            return $data['event_repeat_on_num'].' '.$data['event_repeat_on_day'];
        }
    } 
    
    function repeatFrec()
    {
        if((int)$this->row['pc_recurrtype'] == 1)
        {
            $data = unserialize($this->row['pc_recurrspec']);
            return (int)$data['event_repeat_freq'];
            
        } else if((int)$this->row['pc_recurrtype'] == 2)
        {
            $data = unserialize($this->row['pc_recurrspec']);
            return (int)$data['event_repeat_on_freq'];
        }
    } 
    
    function sharing()
    {
        if((int)$this->row['pc_sharing'] == 0)
        {
            return 1;
        } else
        {
            return 2;
        }
    }
    
    function group()
    {
        return 'all';
    }
    
    function status()
    {
        if((int)$this->row['pc_eventstatus'] == -1)
        {
            return 0;
        } else 
        {
            return 1;
        }
    }
    
    function language()
    {
        return '';
    }
    
    function subscribeLimit()
    {
        return 0;
    }
    
    function subscribeWPend()
    {
        return 0;
    }
    
    function cr_uid()
    {
    }
    function cr_date()
    {
    }
    function wk_schema()
    {
    }
}

/**
 * iCalendar input for TimeIt import api.
 */
class TimeItImportApiInputICal
{
    var $path;
    var $data;
    var $row;
    
    function TimeItImportApiInputICal($path)
    {
        $this->path = $path;
    }
    
    function check()
    {
        if(!file_exists($this->path))
        {
            return LogUtil::registerError(_TIMEIT_ERROR_UPLOADINVALID);
        } else
        {
            return true;
        }
    }
    
    function load()
    {
        Loader::requireOnce('modules/TimeIt/pnincludes/iCalcreator.class.php');
        
        $this->data = new vcalendar();
        
        $pathinfo = pathinfo($this->path);
        $this->data->setConfig( 'directory', $pathinfo['dirname'] ); // identify directory
        $this->data->setConfig( 'filename', $pathinfo['basename'] ); // identify file name
        $this->data->parse();
        $this->data->sort();
    }
    
    function next()
    {
        $this->row = $this->data->getComponent('vevent');
        if(!$this->row)
        {
            return false;
        } else 
        {
            return true;
        }
    }
    
    // getter to get the data
    
    function _special(&$obj)
    {
    }
    
    function iid()
    {
        return $this->row->getProperty("UID");
    }
    
    function title()
    {
        return $this->row->getProperty("SUMMARY");
    }
    
    function text()
    {
        return $this->row->getProperty("DESCRIPTION");
    }
    
    function categories()
    {
        return array();
    }
    
    function startDate()
    {
        $property = $this->row->getProperty("DTSTART",1);
        $startDate = mktime(0,0,0,$property['month'], $property['day'], $property['year']);
        return DateUtil::getDatetime($startDate, DATEONLYFORMAT_FIXED);
    }
    
    function endDate()
    {
        if($this->repeatType() == 4)
        {
            $rrule = $this->row->getProperty("RRULE",1);
            if(isset($rrule['UNTIL']))
            {
                $endDate = mktime(0,0,0,$property['month'], $property['day'], $property['year']);
                $endDate = DateUtil::getDatetime($endDate, DATEONLYFORMAT_FIXED);
                return $endDate;
            } else
            {
                return '2037-12-31'; //FIXME: Better solution?
            }
        } 
        
        $property = $this->row->getProperty("DTEND",1);
        if($property)
        {
            $endDate = mktime(0,0,0,$property['month'], $property['day'], $property['year']);
            $endDate = DateUtil::getDatetime($endDate, DATEONLYFORMAT_FIXED);

            if($this->allDay() == 0)
            {
                return $endDate;
            } else
            {
                // $endDate is non-inclusive
                return DateUtil::getDatetime(strtotime('-1 day', strtotime($endDate)), DATEONLYFORMAT_FIXED);
            }
        } else 
        {
            return $this->startDate();
        }
    }
    
    function data()
    {
        $geo = $this->row->getProperty("GEO");

        $data = array('eventplugin_contact'  => 'ContactTimeIt',
                      'eventplugin_location' => 'LocationTimeIt',
                      'plugindata' => array('ContactTimeIt'=> array('contactPerson' => $this->row->getProperty("CONTACT")),
                                            'LocationTimeIt' => array('name'    => $this->row->getProperty("LOCATION"))));

        if($geo) {
            $data['plugindata']['LocationTimeIt']['lat'] = $geo['latitude'];
            $data['plugindata']['LocationTimeIt']['lng'] = $geo['longitude'];
        }

        return $data;
    }
    
    function allDay()
    {
        $property = $this->row->getProperty("DTSTART",1);
        if(isset($property['hour']) && isset($property['min']))
        {
            return 0;
        } else
        {
            return 1;
        }
    } 
    
    function allDayStart()
    {
        
        if($this->allDay() == 0)
        {
            $property = $this->row->getProperty("DTSTART",1);
            
            return $property['hour'].':'.$property['min'];
        } else
        {
            return '';
        }
    } 
    
    function allDayDur()
    {
        if($this->allDay() == 0)
        {
            $property = $this->row->getProperty("DTSTART",1);
            $start = strtotime($this->startDate().' '.$property['hour'].':'.$property['min']);
            
            $property = $this->row->getProperty("DTEND",1);
            $end = strtotime($this->endDate().' '.$property['hour'].':'.$property['min']);
            
            $difInSec = $end - $start;
            $h = (int)($difInSec / 3600); // hours
            $difInSec = $difInSec % 3600; // rest 
            $min = (int)($difInSec / 60); // minutes
            return $h.','.$min; 
        } else
        {
            return 0;
        }
    } 
    
    function repeatType()
    {
        if($this->row->getProperty("RRULE",1))
        {
            return 4;
        } else 
        {
            return 0;
        }
    } 
    
    function repeatSpec()
    {
        if($this->repeatType() == 4)
        {
            return serialize($this->row->getProperty("RRULE",1));
        } else
        {
            return '';
        }
    } 
    
    function repeatFrec()
    {
        return 0;
    } 
    
    function sharing()
    {
        $property = $this->row->getProperty("CLASS");
        switch($property) {
        case 'PRIVATE':
            return 1;
        case 'PUBLIC':
            return 3;
        case 'CONFIDENTIAL':
            return 1;
        default:
            return 3;
        }

        if(!empty($property))
        {
            if($property == 'PRIVATE')
            {
                return 1;
            } else  
            { 
                return 3;
            }
        } else  
        { 
            return 3; 
        }
    }
    
    function group()
    {
        return 'all';
    }
    
    function status()
    {
        return 1;
    }
    
    function language()
    {
        return '';
    }
    
    function subscribeLimit()
    {
        return 0;
    }
    
    function subscribeWPend()
    {
        return 0;
    }
    
    function cr_uid()
    {
    }
    function cr_date()
    {
    }
    
    function wk_schema()
    {
    }
}

/**
 * PostSchedule input for TimeIt import api.
 */
class TimeItImportApiInputPS
{
    var $prefix;
    var $data;
    var $row;
    var $repeatSpecNumToStr;
    var $firstCall;
    var $maxDate;
    var $categorymap;
    var $cache;
    
    function TimeItImportApiInputPS($prefix)
    {
        $this->prefix = $prefix;
        $this->repeatSpecNumToStr = array('d'=>'day', 'w'=>'week', 'm'=>'month', 'y'=>'year');
        $this->firstCall = true;
        $this->maxDate = DateUtil::getDatetime(strtotime('+1 year',time()), DATEONLYFORMAT_FIXED);
        $this->cache = array();
        
        // convert categroies
        $this->categorymap = pnModAPIFunc('TimeIt', 'import', 'psimportcats', $prefix);
        pnModSetVar('TimeIt', 'colorCatsProp', 'ps_imports');
    }
    
    function check()
    {
       $sql1 = "show table status like '".$this->prefix."_PostSchedule%'";
        $result1 = DBUtil::executeSQL($sql1);
        if($result1->RecordCount() < 2)
        {
            return LogUtil::registerError(_TIMEIT_ERROR_PSTABLE);
        } else
        {
            return true;
        }
    }
    
    function load()
    {
        $this->data = DBUtil::executeSQL('SELECT * FROM '.$this->prefix.'_PostSchedule');
        
        if($this->data)
        {
            $this->row = $this->data->GetRowAssoc(false);
        }
    }
    
    function next()
    {

        if($this->firstCall == false)
        {
            $ret = $this->data->MoveNext();
            if($ret)
            {
                $this->row = $this->data->GetRowAssoc(false);
            }
            $this->cache = array();
            return $ret;
        } else 
        {   // ignore first call, adodb called MoveNext for us
            $this->firstCall = false; // reset
            if($this->data === false)
            {
                return false;
            } else
            {
                return true;
            }
        }
    }
    
    // getter to get the data
    
    function _special(&$obj)
    {
    }
    
    function iid()
    {
        return $this->row['eid'];
    }
    
    function title()
    {
        return $this->row['title'];
    }
    
    function text()
    {
        return $this->row['body'];
    }
    
    function categories()
    {
        return array('ps_imports' => $this->categorymap[(int)$this->row['topic']]);
    }
    
    function startDate()
    {
        return $this->row['startdate'];
    }
    
    function endDate()
    {
        if($this->row['repeattype'] == 'n')
        {
            return $this->startDate();
        } else
        {
            return $this->row['enddate'];
        }
    }
    
    function data()
    {
        return array();
    }
    
    function allDay()
    {
        return (int)$this->row['alldayevent'];
    } 
    
    function allDayStart()
    {
        if($this->allDay() == 0) 
        { 
            $time = explode(':',$this->row[starttime]);
            return $time[0].':'.$time[1];
        } else 
        {
            return '00:00';
        }
    } 
    
    function allDayDur()
    {
        if($this->allDay() == 0) 
        { 
            $time_start = strtotime($this->row[starttime]);
            if($this->row[endtime] == '00:00:00')
            {   // 0 hour is the next day
                $time_end = strtotime('+1 day', strtotime($this->row[endtime]));
            } else 
            {
                $time_end = strtotime($this->row[endtime]);
            }
            
            $sec = $time_end - $time_start;
            $h = $sec / 3600; // hours
            $sec = $sec % 3600; // rest
            $min = $sec / 60; // minutes
            return floor($h).','.floor($min);
        } else 
        {
            return '0';
        }
    } 
    
    function repeatType()
    {
        if($this->row['repeattype'] != 'n')
        {
            return 1;
        } else
        {
            return 0;
        }
    } 
    
    function repeatSpec()
    {
        if($this->repeatType() == 1)
        {
            return $this->repeatSpecNumToStr[$this->row['repeattype']];
        }
    } 
    
    function repeatFrec()
    {
        if($this->repeatType() == 1)
        {
            return 1;
        } 
    } 
    
    function sharing()
    {
        return 3;
    }
    
    function group()
    {
        return 'all';
    }
    
    function status()
    {
        return 1;
    }
    
    function language()
    {
        return '';
    }
    
    function subscribeLimit()
    {
        return 0;
    }
    
    function subscribeWPend()
    {
        return 0;
    }
    
    function cr_uid()
    {
        $user_count = DBUtil::selectObjectCount('users','pn_uid = '.(int)$this->row['uid']);
        if($user_count == 1)
        {
            $this->cache['cr_uid'] = true;
            return (int)$this->row['uid'];
        }
    }
    
    function cr_date()
    {
        if($this->cache['cr_uid'])
        {
            return $this->row['submitdate'];
        }
    }
    
    function wk_schema()
    {
        if((int)$this->row['aid'] == 0)
        {
            return 'moderate';
        }
    }
}

function TimeIt_importapi_postschedule($args)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    
    if (!isset($args['prefix']) || !isset($args['cid'])) 
    {
        return LogUtil::registerError (_MODARGSERROR);
    }
    
    $prefix = $args['prefix'];
    
    // 30 sec aren't enough 
    ini_set('max_execution_time' , 3600);
    
    $input = new TimeItImportApiInputPS($prefix);
    $importer = new TimeItImportApi($input, $args['cid']);
    $ret = $importer->doImport();
    
    if($ret)
    {
        LogUtil::registerStatus (_TIMEIT_IMPORTSUCESS);
        LogUtil::registerStatus('Events: '.$ret);
    }
      
    return $ret;
}

function TimeIt_importapi_psimportcats($prefix)
{
    if (!pnSecAuthAction(0, 'TimeIt::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // load the admin language file
    // pull all data from the old tables
    //$prefix = pnConfigGetVar('prefix');
    $sql = "SELECT pn_topicid, pn_topicname, pn_topictext FROM {$prefix}_PostSchedule_topics";
    $result = DBUtil::executeSQL($sql);
    $categories = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $categories[] = $result->fields;
    }

    //$result->Close();
    //print_r($categories);
    // load necessary classes
    Loader::loadClass('CategoryUtil');
    Loader::loadClassFromModule('Categories', 'Category');
    Loader::loadClassFromModule('Categories', 'CategoryRegistry');

    // get the language file
    $lang = ZLanguage::getLanguageCode();

    // get the category path for which we're going to insert our place holder category
    $rootcat = CategoryUtil::getCategoryByPath('/__SYSTEM__/Modules');

    // create placeholder for all our migrated categories
    $cat = new PNCategory ();
    $cat->setDataField('parent_id', $rootcat['id']);
    $cat->setDataField('name', 'TimeIt_PostSchedule');
    $cat->setDataField('value', '-1');
    $cat->insert();
    $cat->update();
    $rootid = $cat->getDataField('id');
    
    // add registry entry
    $registry = new PNCategoryRegistry();
    $registry->setDataField('modname', 'TimeIt');
    $registry->setDataField('table', 'TimeIt_events');
    $registry->setDataField('property', 'ps_imports');
    $registry->setDataField('category_id', $rootid);
    $registry->insert();

    
    // migrate our root categories
    $categorymap = array();
    foreach ($categories as $category) {
        $cat = new PNCategory();
        $cat->setDataField('parent_id', $rootid);
        $cat->setDataField('name', $category[1]);
        $cat->setDataField('display_name', array($lang => $category[1]));
        $cat->setDataField('display_desc', array($lang => $category[2]));
        $cat->insert();
        $cat->update();
        $newcatid = $cat->getDataField('id');
        $categorymap[$category[0]] = $newcatid;
    }

    return $categorymap;
}

