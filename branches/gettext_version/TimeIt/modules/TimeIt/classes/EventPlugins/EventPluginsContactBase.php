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
 * Base class for all plugins about contacts.
 */
abstract class TimeItEventPluginsContactBase implements TimeItEventPluginsBase, ArrayAccess
{
    /**
     * Generates html code.
     * @return string HTML code
     */
    public function display()
    {
        $render = pnRender::getInstance('TimeIt');
        $render->assign('data', $this->getFormatedData());
        return $render->fetch('eventplugins/TimeIt_eventplugins_contactbase.htm');
    }

    public function editPostBack($values, &$dataForDB)
    {

    }
    
    /**
     * @return array possible keys: contactPerson => name of the contact person
     *                              email => e-mail address
     *                              phoneNr => phone number
     *                              website => URL
     *
     *                              address => address
     *                              zip => zip of the city
     *                              city => city
     *                              country => country
     *                              url_details = url which contaions more information about the contact
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