<?php

Loader::requireOnce('modules/TimeIt/common.php');

function TimeIt_syncml_sync()
{
    require_once 'modules/TimeIt/pnincludes/SyncML.php';
    require_once 'modules/TimeIt/pnincludes/SyncML/Backend.php';

    // Get input
    if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        $request = $GLOBALS['HTTP_RAW_POST_DATA'];
    } else {
        $request = implode("\r\n", file('php://input'));
    }

    $GLOBALS['backend'] = SyncML_Backend::factory('TimeIt', array());
    // Handle request.
    $h = new SyncML_ContentHandler();
    $response = $h->process($request, 'application/vnd.syncml+xml', 'http://'.$_SERVER["HTTP_HOST"].'/roland/zk_timeit_3-0/TimeIt/syncml');

    // Close the backend.
    $GLOBALS['backend']->close();

    // Send response
    header('Content-Type: application/vnd.syncml+xml');
    echo $response;
    return true;
}