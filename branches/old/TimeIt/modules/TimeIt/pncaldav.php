<?php


function TimeIt_caldav_main()
{
    require_once 'modules/TimeIt/pnincludes/HTTP_WebDAV_Server-1.0.0RC4/myserver.php';
    $cls = new MyServer();
    $cls->ServeRequest();
    return true;
}

function TimeIt_caldav_test()
{
    $xml = <<<EOT
<C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:D="DAV:">
  <D:prop>
    <D:getetag/>
  </D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VEVENT"/>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>
EOT;
    $sxml = simplexml_load_string($xml);
    var_dump($sxml->children('DAV:'));
    return true;
}

