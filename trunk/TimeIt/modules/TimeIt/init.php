<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Core
 */

/**
 * Initialise the TimeIt module
 */
function TimeIt_init()
{
     // create the socialNetwork table
    try {
        DoctrineUtil::createTablesFromModels('TimeIt');
    } catch (Exception $e) {
        LogUtil::registerError($e->getMessage());
        return false;
    }

    return true;
}
 
  
/**
 * Upgrade the module from an old version
 */
function TimeIt_upgrade($oldversion)
{
    return true;
}

  
/**
 * Delete the TimeIt module
 */
function TimeIt_delete()
{
    // drop tables
    DoctrineUtil::dropTable('TimeIt_calendars');
    DoctrineUtil::dropTable('TimeIt_events');
    DoctrineUtil::dropTable('TimeIt_date_has_events');
    DoctrineUtil::dropTable('TimeIt_regs');

    // remove all module vars
    ModUtil::delVar('TimeIt');

    return true;
}
