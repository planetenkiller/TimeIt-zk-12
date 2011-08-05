<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage EventPlugins
 */

Loader::loadFile('EventPluginsBase.php','modules/TimeIt/classes/EventPlugins');
    
/**
 * Base class for all plugins about locations.
 */
abstract class TimeItEventPluginsLocationBase implements TimeItEventPluginsBase, ArrayAccess
{
    /**
     * Generates html code.
     * @return string HTML code
     */
    public function display()
    {
        $render = pnRender::getInstance('TimeIt');
        $render->assign('data', $this->getFormatedData());
        return $render->fetch('eventplugins/TimeIt_eventplugins_locationbase.htm');
    }

    public function editPostBack($values, &$dataForDB)
    {

    }
    
    /**
     * @return array possible keys: name => name of the location
     *                              street => street
     *                              houseNumber => house number
     *                              zip => zip of the city
     *                              city => city
     *                              phone => phone of the location
     *                              fax => fax of the location
     *                              url => website of the locaton
     *                              email => email of the location
     *                              country => country
     *                              lat => lat
     *                              lng => lng
     *                              displayMap = show map?
     *                              zoomFactor = zoom factor of the map
     */
    protected abstract function getFormatedData();

    /**
     * Unsupported
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Unsupported
     */
    public function offsetUnset($offset) 
    {
    }

    public function offsetExists($offset)
    {
        $data = $this->getFormatedData();
        return isset($data[$offset]);
    }

    public function offsetGet($offset)
    {
        $data = $this->getFormatedData();
        return isset($data[$offset]) ? $data[$offset] : null;
    }

    public function displayAfterDesc(&$args)
    {
        return '';
    }
}