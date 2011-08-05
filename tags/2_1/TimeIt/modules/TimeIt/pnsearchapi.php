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

function TimeIt_searchapi_info()
{
    return array('title' => 'TimeIt',
                 'functions' => array('TimeIt' => 'search'));
}

function TimeIt_searchapi_options($args)
{
    if (SecurityUtil::checkPermission( 'TimeIt::', '::', ACCESS_READ)) {
        $pnRender = new pnRender('TimeIt');
        return $pnRender->fetch('TimeIt_search_options.htm');
    }

    return '';
}

function TimeIt_searchapi_search($args)
{
    pnModDBInfoLoad('Search');
    $pntable = pnDBGetTables();
    $ticolumn = $pntable['TimeIt_events_column'];
    $searchTable = $pntable['search_result'];
    $searchColumn = $pntable['search_result_column'];
    $sessionId = session_id();
    // generate where
    $where = search_construct_where($args,
                                    array($ticolumn['title'], 
                                          $ticolumn['text']), 
                                    null);
    // create object
    if (!($class = Loader::loadClassFromModule ('TimeIt', 'Event', true))) {
            pn_exit ("Unable to load class [Event] ...");
    }
    $class = new $class();
    $class->_objPermissionFilter = array ('realm'            =>  0,
                              'component_left'   =>  'TimeIt',
                              'component_middle' =>  '',
                              'component_right'  =>  'Event',
                              'instance_left'    =>  'id',
                              'instance_middle'  =>  '',
                              'instance_right'   =>  '',
                              'level'            =>  ACCESS_READ);
    // get events
    $array = $class->get($where);
    if ($array === false)
    {
        return LogUtil::registerError (_GETFAILED);
    }
    
    // insert sql
    $insertSql =
"INSERT INTO $searchTable
  ($searchColumn[title],
   $searchColumn[text],
   $searchColumn[extra],
   $searchColumn[created],
   $searchColumn[module],
   $searchColumn[session])
VALUES ";

    // Process the result set and insert into search result table
    foreach($array AS $obj) 
    {
            $sql = $insertSql . '('
                   . '\'' . DataUtil::formatForStore($obj['title']) . '\', '
                   . '\'' . DataUtil::formatForStore($obj['text']) . '\', '
                   . '\'' . DataUtil::formatForStore($obj['id']) . '\', '
                   . '\'' . DataUtil::formatForStore($obj['cr_date']) . '\', '
                   . '\'' . 'TimeIt' . '\', '
                   . '\'' . DataUtil::formatForStore($sessionId) . '\')';
            $insertResult = DBUtil::executeSQL($sql);
            if (!$insertResult) {
                return LogUtil::registerError (_GETFAILED);
            }
    }

    return true;
}

// The ampersands are *very* important here! We need to modify the $args variable
function TimeIt_searchapi_search_check(&$args)
{
    // Fetch database row and the "extra" value in this row
    $datarow = &$args['datarow'];
    $id = $datarow['extra'];
   
    // Write URL into the data
    $datarow['url'] = pnModUrl('TimeIt', 'user', 'event', array('id' => $id));

    // User has access to this item - so return true
    return true;
}