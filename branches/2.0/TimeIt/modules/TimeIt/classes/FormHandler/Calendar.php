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
class TimeIt_FormHandler_calendar
{
    var $mode;
    var $cid;

    function initialize(&$render)
    {
        $func = FormUtil::getPassedValue('func', null, 'GET');
        if($func == 'calendarsModify')
        {
            $id = FormUtil::getPassedValue('id', false, 'GET');
            if(!$id)
            {
                return LogUtil::registerError(_TIMEIT_NOIDPATAM, 404);
            }

            $this->mode = 'edit';
            $this->cid = $id;

            $obj = pnModAPIFunc('TimeIt','calendar','get', $id);
            if(empty($obj))
            {
              return LogUtil::registerError(_TIMEIT_IDNOTEXIST, 404);
            }
            $render->assign($obj);
        } else
        {
            $this->mode = 'add';
        }

        $render->assign('mode', $this->mode);

        // ContactList integration
        if (pnModAvailable('ContactList'))
        {
            $render->assign('ContactListModuleOK', true);
        } else
        {
            $render->assign('ContactListModuleOK', false);
        }


        $config = array();
        $config['workflowItems'] = array(array('text'=>'standard','value'=>'standard'),
                                               array('text'=>'moderate','value'=>'moderate'));
        $config['defaultViewItems'] = array(array('text'=>_MONTH,'value'=>'month'),
                                                  array('text'=>_WEEK,'value'=>'week'),
                                                  array('text'=>_DAY,'value'=>'day'));
        $config['defaultTemplateItems'] = array(array('text'=>'default','value'=>'default'),
                                                      array('text'=>'list','value'=>'list'));


        // ContactList integration
        if (pnModAvailable('ContactList'))
        {
            $render->assign('ContactListModuleOK', true);
        } else
        {
            $render->assign('ContactListModuleOK', false);
        }

        // check all event plugins
        $eventplugins = TimeIt::getEventPlugins(true);
        $availablePlugins = array();
        $availablePluginsIndex = array();
        foreach($eventplugins AS $type => $plugins) {
            foreach($plugins AS $plugin) {
                $classname = TimeIt::getEventPluginClassname($plugin);
                $dependencyCheckOk = false;
                if(is_callable(array($classname, 'dependencyCheck'))) {
                    if(call_user_func(array($classname, 'dependencyCheck'))) {
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
                    $availablePlugins[$type][] = array('value'=>TimeIt::getEventPluginNameWithoutType($plugin),
                                                       'text'=>TimeIt::getEventPluginInstance($plugin)->getDisplayname());
                }
            }
        }

        // assign variables
        $locationEventPlugins = array(array('text'=>'TimeIt','value'=>'TimeIt'));
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
        if ($args['commandName'] == 'create')
        {
            if (!$render->pnFormIsValid())
            {
                return false;
            } else
            {
                $data = $render->pnFormGetValues();
                if($data['privateCalendar'] == false && $data['globalCalendar'] == false && $data['friendCalendar'] == false)
                {
                    return LogUtil::registerError(_TIMEIT_ERROR_2);
                }

                if($data['config']['subscribeMode'] == 'formicula' && empty($data['config']['formiculaFormId']))
                {
                    $form = &$render->pnFormGetPluginById('formiculaFormId');
                    $form->setError(_PNFORM_MANDATORYERROR);
                    return false;
                }


                if($this->mode == 'edit')
                {
                    $data['id'] = $this->cid;
                    $ret = pnModAPIFunc('TimeIt','calendar','update',$data);
                    if($ret)
                    {
                        LogUtil::registerStatus (_UPDATESUCCEDED);
                    } else
                    {
                        LogUtil::registerError(_UPDATEFAILED);
                    }
                } else
                {
                    $ret = pnModAPIFunc('TimeIt','calendar','create',$data);
                    if($ret)
                    {
                        LogUtil::registerStatus (_CREATESUCCEDED);
                    } else
                    {
                        LogUtil::registerError(_CREATEFAILED);
                    }
                }
            }
        }
        $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'calendars'));
    }
}
