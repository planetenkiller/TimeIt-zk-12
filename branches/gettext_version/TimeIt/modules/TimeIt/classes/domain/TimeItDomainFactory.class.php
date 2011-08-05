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
 * Factory for Managers
 *
 * @author planetenkiller
 */
abstract class TimeItDomainFactory
{
    private static $objectTypeClasses = array('event'    => 'TimeItEventManager',
                                              'calendar' => 'TimeItCalendarManager',
                                              'reg'      => 'TimeItRegManager');

    /**
     * @param string $objectType the object type
     * @return object an shared instance
     */
    public static function getInstance($objectType)
    {
        if(array_key_exists($objectType, self::$objectTypeClasses)) {

            if(is_string(self::$objectTypeClasses[$objectType])) {
                Loader::loadClass(self::$objectTypeClasses[$objectType], 'modules/TimeIt/classes/domain');
                // replace classname with object
                self::$objectTypeClasses[$objectType] = new self::$objectTypeClasses[$objectType]();
            }

            return self::$objectTypeClasses[$objectType];
        } else {
            throw new InvalidArgumentException('Unkown object type "'.$objectType.'".');
        }
    }

    /**
     * Sets the manager for an object type. For testing only!!
     */
    public static function setInstance($objectType, $obj) {
        self::$objectTypeClasses[$objectType] = $obj;
    }
}

