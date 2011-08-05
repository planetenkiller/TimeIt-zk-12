<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage FormHandler
 */

/**
 * Form handler for calendar creation and modification.
 */
class TimeIt_FormHandler_Edit_calendar
{
    var $mode;
    var $cid;

    function initialize(&$render)
    {
        $domain = ZLanguage::getModuleDomain('TimeIt');
        $id = FormUtil::getPassedValue('id', false, 'GET');
        if(!empty($id)) {
            $this->mode = 'edit';
            $this->cid = $id;

            $obj = TimeItDomainFactory::getInstance('calendar')->getObject($id);
            if(empty($obj)) {
              return LogUtil::registerError(__f('Item with id %s not found.', $id, ZLanguage::getModuleDomain('TimeIt')), 404);
            }

            if(!TimeItPermissionUtil::canCreateCalendar()) {
                 return LogUtil::registerPermissionError();
            }

            $render->assign($obj);
        } else {
            $this->mode = 'add';
        }

        $render->assign('mode', $this->mode);

        // ContactList integration
        if (pnModAvailable('ContactList')) {
            $render->assign('ContactListModuleOK', true);
        } else {
            $render->assign('ContactListModuleOK', false);
        }


        $config = array();
        $config['workflowItems'] = array(array('text' => 'standard','value'=>'standard'),
                                         array('text' => 'moderate','value'=>'moderate'));
        $config['defaultViewItems'] = array(array('text' => __('Month', $domain),'value'=>'month'),
                                            array('text' => __('Week', $domain),'value'=>'week'),
                                            array('text' => __('Day', $domain),'value'=>'day'));
        $config['defaultTemplateItems'] = array(array('text'=> __('Table', $domain),'value'=>'table'),
                                                array('text'=> __('List', $domain),'value'=>'list'));


        // check all event plugins
        $eventplugins = TimeItEventPluginsUtil::getEventPlugins(true);
        $availablePlugins = array();
        $availablePluginsIndex = array();
        foreach($eventplugins AS $type => $plugins) {
            foreach($plugins AS $plugin) {
                $classname = TimeItEventPluginsUtil::getEventPluginClassname($plugin);
                TimeItEventPluginsUtil::getEventPluginInstance($plugin); // includes the plugin
                $dependencyCheckOk = false;
                
                $callback = array($classname, 'dependencyCheck');
                if(is_callable($callback)) {
                    if(call_user_func($callback)) {
                       $dependencyCheckOk = true;
                    }
                } else {
                    $dependencyCheckOk = true;
                }

                if($dependencyCheckOk) {
                    if(!isset($availablePlugins[$type])) {
                        $availablePlugins[$type] = array();
                    }

                    $availablePluginsIndex[] = $plugin;
                    $availablePlugins[$type][] = array('value' => TimeItEventPluginsUtil::getEventPluginNameWithoutType($plugin),
                                                       'text'  => TimeItEventPluginsUtil::getEventPluginInstance($plugin)->getDisplayname());
                }
            }
        }

        // assign variables
        $locationEventPlugins = array(array('text' => 'TimeIt', 'value' => 'TimeIt'));
        if (in_array('LocationLocations', $availablePluginsIndex)) {
            $render->assign('locationsModuleOK', true);
        } else {
            $render->assign('locationsModuleOK', false);
        }

        if (in_array('ContactFormicula', $availablePluginsIndex)){
            $render->assign('formiculaModuleOk', true);
            $render->assign('formiculaModuleOkReadOnly', false);
        } else{
            $render->assign('formiculaModuleOk', false);
            $render->assign('formiculaModuleOkReadOnly', true);
        }

        if (in_array('ContactAddressbook', $availablePluginsIndex)){
            $render->assign('AddressbookModuleOK', true);
        } else{
            $render->assign('AddressbookModuleOK', false);
        }

        $config['eventPluginsLocationItems'] = $availablePlugins['location'];
        $config['eventPluginsContactItems'] = $availablePlugins['contact'];

        $render->append('config', $config, true);
    }

    function handleCommand(&$render, &$args)
    {
        if ($args['commandName'] == 'create') {
            if (!$render->pnFormIsValid()) {
                return false;
            } else {
                $data = $render->pnFormGetValues();
                if($data['privateCalendar'] == false && $data['globalCalendar'] == false && $data['friendCalendar'] == false) {
                    return LogUtil::registerError(__('It isnt allowed to disable global events, event for friends only and private calendar!', ZLanguage::getModuleDomain('TimeIt')));
                }

                if($data['config']['subscribeMode'] == 'formicula' && empty($data['config']['formiculaFormId'])) {
                    $form = &$render->pnFormGetPluginById('formiculaFormId');
                    $form->setError(__('An entry in this field is mandatory.', ZLanguage::getModuleDomain('TimeIt')));
                    return false;
                }


                if($this->mode == 'edit') {
                    $data['id'] = $this->cid;
                    $ret = TimeItDomainFactory::getInstance('calendar')->updateCalendar($data);
                    if($ret) {
                        LogUtil::registerStatus (__('Done! Calendar updated.', ZLanguage::getModuleDomain('TimeIt')));
                    } else
                    {
                        LogUtil::registerError(__('Error! Update attempt failed.', ZLanguage::getModuleDomain('TimeIt')));
                    }
                } else {
                    $ret = TimeItDomainFactory::getInstance('calendar')->createCalendar($data);
                    if($ret) {
                        LogUtil::registerStatus (__('Done! Calendar created.', ZLanguage::getModuleDomain('TimeIt')));
                    } else {
                        LogUtil::registerError(__('Error! Creation attempt failed.', ZLanguage::getModuleDomain('TimeIt')));
                    }
                }
            }
        }
        
        $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'calendars'));
    }
}
