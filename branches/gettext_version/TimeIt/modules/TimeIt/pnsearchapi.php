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
    $gtdomain = ZLanguage::getModuleDomain('TimeIt');
    pnModDBInfoLoad('Search');
    $pntable = pnDBGetTables();
    $ticolumn = $pntable['TimeIt_events_column'];
    $searchTable = $pntable['search_result'];
    $searchColumn = $pntable['search_result_column'];
    $sessionId = session_id();
    // generate where
    $where = search_construct_where($args,
                                    array('title', 
                                          'text'), 
                                    null);


    $filter = new TimeItFilter('event');
    $filter->setUseORToLinkExpressionsInAGroup(true);

    $where = trim($where);

    // remove first '(' and last ')'
    $where = substr($where, 1);
    $where = substr($where, 0, strlen($where)-1);

    $where = trim($where);

    $linkGrupsWithAND = false;

    // parse where and an fill filter object
    while(!empty($where)) {
        if($where[0] != '(') {
            return LogUtil::registerError(__('Timeit events: Internal error, can not create filter object', $gtdomain));
        }

        // remove beginning (
        $where = substr($where, 1);
        $where = trim($where);

        $filter->addGroup();

        $inGroup = true;
        while($inGroup) {
            $column = substr($where, 0, strpos($where, ' '));
            $where = substr($where, strpos($where, ' '));
            $where = trim($where);

            $operator = substr($where, 0, strpos($where, ' '));
            $where = substr($where, strpos($where, ' '));
            $where = trim($where);

            // remove beginning '
            $where = substr($where, 1);
            $where = trim($where);
            $value = substr($where, 0, strpos($where, "'"));
            // remove beginning '
            $where = substr($where, strpos($where, "'")+1);
            $where = trim($where);

            $filter->addExp($column.':'.strtolower($operator).':'.$value);

            if($where[0] == ')') {
                $inGroup = false;
            } else {
                $where = substr($where, strpos($where, ' '));
                $where = trim($where);
            }
        }

        // remove beginning )
        $where = substr($where, 1);
        $where = trim($where);

        if(strlen($where) > 3) {
            if(substr($where, 0, 3) == 'AND') {
                $linkGrupsWithAND = true;
            }

            $where = substr($where, strpos($where, ' '));
            $where = trim($where);
        }
    }

    if($linkGrupsWithAND) {
        $filter->setUseANDToLinkGroups(true);
    }

    // get events
    $array = TimeItDomainFactory::getInstance('event')->getEvents(-1, $filter);
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