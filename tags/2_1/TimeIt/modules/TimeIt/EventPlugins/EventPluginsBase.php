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

    
/**
 * This is the base class for event plugins
 */
interface TimeItEventPluginsBase
{
    /**
     * @return the Plugin name
     */
    public function getName();

    /**
     * @return the Plugin displayname
     */
    public function getDisplayname();
    
    /**
     * TimeIt calls this function at every event create/edit
     * @param string $mode 'create' or 'edit'
     * @param object $render pnFormRender
     */
    public function edit($mode, &$render);

    /**
     * TimeIt calls this function after every event create/edit form submit.
     * @param arry $values values from pnForm
     * @param array $dataForDB current (new) TimeIt event for the DB
     */
    public function editPostBack($values, &$dataForDB);
    
    /**
     * Generates html code. The HTML code is shown on the right side on
     * the event detail view.
     * @return string HTML code
     */
    public function display();
    
    /**
     * Loads all data which this plugin will need from the event.
     * @param array $obj event form the database
     */
    public function loadData(&$obj);

    /**
     * Generates html code. The HTML Code from this function is placed
     * after the description in the event detail view.
     * @param array $args All smarty template variables
     * @return string HTML code
     */
    public function displayAfterDesc(&$args);
}