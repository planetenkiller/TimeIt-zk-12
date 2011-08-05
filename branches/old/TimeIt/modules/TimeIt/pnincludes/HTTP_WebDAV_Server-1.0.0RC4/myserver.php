<?php

require_once dirname(__FILE__).'/Server.php';

class MyServer extends HTTP_WebDAV_Server
{
    protected function PROPFIND_root(&$options, &$files, $i) {
        $files["files"][$i]['path'] = '/roland/zk_timeit_3-0/TimeIt/groupdav/';
        foreach($options['props'] AS $prop) {
            if($prop['xmlns'] == 'urn:ietf:params:xml:ns:caldav') {
                if($prop['name'] == 'supported-calendar-component-set') {
                    $files["files"][$i]['props'][] = $this->mkprop('urn:ietf:params:xml:ns:caldav','supported-calendar-component-set',$this->mkprop('urn:ietf:params:xml:ns:caldav','comp','VEVENT'));
                } else if($prop['name'] == 'supported-calendar-data') {
                    $files["files"][$i]['props'][] = $this->mkprop('urn:ietf:params:xml:ns:caldav','supported-calendar-data', $this->mkprop('urn:ietf:params:xml:ns:caldav','calendar-data','', array('content-type'=>'text/calendar','version'=>'2.0')));
                } else if($prop['name'] == 'calendar-description') {
                    $files["files"][$i]['props'][] = $this->mkprop('urn:ietf:params:xml:ns:caldav','calendar-description','timeit calendar', array('xml:lang'=>'de_DE'));
                }
            } else if($prop['xmlns'] == 'DAV:') {
                if($prop['name'] == 'displayname') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','displayname','timeit calendar');
                } else if($prop['name'] == 'resourcetype') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','resourcetype',$this->mkprop('DAV:','collection',''));
                } else if($prop['name'] == 'getetag') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','getetag','my-etag111');
                } /*else if($prop['name'] == 'getcontenttype') {
                    $files["files"][0]['props'][] = $this->mkprop('DAV:','getcontenttype','httpd/unix-directory');
                }*else if($prop['name'] == 'current-user-privilege-set') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','current-user-privilege-set', $this->mkprop('DAV:','read',''));
                }*/
            } /*else if($prop['xmlns'] == 'http://calendarserver.org/ns/') {
                if($prop['name'] == 'getctag') {
                    $files["files"][$i]['props'][] = $this->mkprop('http://calendarserver.org/ns/','getctag','my-etag111');
                }
            }*/
        }
    }

    protected function PROPFIND_calendar(&$options, &$files, $i, $calendar) {
        $files["files"][$i]['path'] = '/roland/zk_timeit_3-0/TimeIt/groupdav/'.$calendar['name'];
        $etag = md5(pnModAPIFunc('TimeIt','calendar','getLastMod',$calendar['id']).$calendar['id']);
        foreach($options['props'] AS $prop) {
            if($prop['xmlns'] == 'urn:ietf:params:xml:ns:caldav') {
                if($prop['name'] == 'supported-calendar-component-set') {
                    $files["files"][$i]['props'][] = $this->mkprop('urn:ietf:params:xml:ns:caldav','supported-calendar-component-set',$this->mkprop('urn:ietf:params:xml:ns:caldav','comp','VEVENT'));
                } else if($prop['name'] == 'supported-calendar-data') {
                    $files["files"][$i]['props'][] = $this->mkprop('urn:ietf:params:xml:ns:caldav','supported-calendar-data', $this->mkprop('urn:ietf:params:xml:ns:caldav','calendar-data','', array('content-type'=>'text/calendar','version'=>'2.0')));
                } else if($prop['name'] == 'calendar-description') {
                    $files["files"][$i]['props'][] = $this->mkprop('urn:ietf:params:xml:ns:caldav','calendar-description', $calendar['desc'], array('xml:lang'=>'de_DE'));
                }
            } else if($prop['xmlns'] == 'DAV:') {
                if($prop['name'] == 'displayname') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','displayname', $calendar['id']);
                } else if($prop['name'] == 'resourcetype') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','resourcetype',array($this->mkprop('DAV:','collection',''),$this->mkprop('urn:ietf:params:xml:ns:caldav','calendar','')));
                } else if($prop['name'] == 'getetag') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','getetag', $etag);
                } /*else if($prop['name'] == 'getcontenttype') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','getcontenttype','httpd/unix-directory');
                } else if($prop['name'] == 'current-user-privilege-set') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','current-user-privilege-set', $this->mkprop('DAV:','read',''));
                }*/
            } else if($prop['xmlns'] == 'http://calendarserver.org/ns/') {
                if($prop['name'] == 'getctag') {
                    $files["files"][$i]['props'][] = $this->mkprop('http://calendarserver.org/ns/','getctag',$etag);
                }
            }
        }
    }

    protected function PROPFIND_cfiles(&$options, &$files, $i, $calendar) {
        $files["files"][$i]['path'] = '/roland/zk_timeit_3-0/TimeIt/groupdav/'.$calendar['id'].'/data.ics';
        $etag = md5(pnModAPIFunc('TimeIt','calendar','getLastMod',$calendar['id']).$calendar['id']);
        foreach($options['props'] AS $prop) {
            if($prop['xmlns'] == 'DAV:') {
                if($prop['name'] == 'displayname') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','displayname','data.ics');
                } else if($prop['name'] == 'resourcetype') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','resourcetype','');
                } else if($prop['name'] == 'getetag') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','getetag', $etag);
                } else if($prop['name'] == 'getcontenttype') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','getcontenttype','text/calendar');
                } /*else if($prop['name'] == 'current-user-privilege-set') {
                    $files["files"][$i]['props'][] = $this->mkprop('DAV:','current-user-privilege-set', $this->mkprop('DAV:','read',''));
                }*/
            }
        }
    }

    function PROPFIND(&$options, &$files) {

        $cid = FormUtil::getPassedValue('cid', null, 'GETPOST');

        // prepare property array
        $files["files"] = array();
        $files["files"][] = array();

        $i = 0;

        // propfind of the root?
        if(empty($cid)) {
            $this->PROPFIND_root($options, $files, $i);
            $i++;

            if((int)$options['depth'] > 0) {
                // add all calendars
                $calendars = pnModAPIFunc('TimeIt','calendar','getAll');
                foreach($calendars AS $calendar) {
                    $this->PROPFIND_calendar($options, $files, $i, $calendar);
                    $i++;
                }
            }
        // propfind of a calendar?
        } else {
            $calendar = pnModAPIFunc('TimeIt','calendar','get', $cid);
            $this->PROPFIND_calendar($options, $files, $i, $calendar);
            $i++;
            $this->PROPFIND_cfiles($options, $files, $i, $calendar);
        }

        file_put_contents('/tmp/myserver.log', print_r($options, true));
        file_put_contents('/tmp/myserver.log', print_r($files, true), FILE_APPEND);
        return true;
    }

    function PUT(&$params)
    {
        
    }

    function GET(&$params)
    {
        $fspath = dirname(__FILE__).'/test.ics';

        $params['mimetype'] = 'text/calendar';
        $params['mtime']    = filemtime($fspath);
        $params['size']     = filesize($fspath);
        $params['stream']   = fopen($fspath, "r");
        file_put_contents('/tmp/myserver.log', $fspath.'|'.print_r($params, true));
        return true;
    }

    function http_REPORT()
    {
        $this->http_status('404 Not Found');
        return;

        $xml = file_get_contents('php://input');

        $sxml = simplexml_load_string($xml);
        $childs_dav = $sxml->children('DAV:');
        $childs_c = $sxml->children('urn:ietf:params:xml:ns:caldav');

        echo <<<EOT
?xml version="1.0" encoding="utf-8" ?>
   <D:multistatus xmlns:D="DAV:"
                  xmlns:C="urn:ietf:params:xml:ns:caldav">
     <D:response>
       <D:href>http://localhost/roland/zikula_11/index.php?module=TimeIt&type=caldav</D:href>
       <D:propstat>
         <D:prop>
           <D:getetag>fffff-abcd4</D:getetag>
           
EOT;
   /* <C:calendar-data>
        readfile(dirname(__FILE__).'/test.ics');*/
        echo <<<EOT
         </D:prop>
         <D:status>HTTP/1.1 200 OK</D:status>
       </D:propstat>
     </D:response>
   </D:multistatus>
EOT;

    $this->http_status('207 Multi-Status');
    header('Content-Type: text/xml; charset="utf-8"');

    }


    function REPORT()
    {
        
    }
}
