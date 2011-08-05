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
 * Form handler for the setttins page.
 */
class TimeIt_FormHandler_Edit_config
{
    function initialize(&$render)
    {
        if(!TimeItPermissionUtil::isAdmin()) {
             return LogUtil::registerPermissionError();
        }

        $domain = ZLanguage::getModuleDomain('TimeIt');
        $render->assign('workflowItems', array(array('text' => 'standard','value' => 'standard'),
                                               array('text' => 'moderate','value' => 'moderate')));
        $render->assign('defaultViewItems', array(array('text' => __('Month', $domain), 'value' => 'month'),
                                                  array('text' => __('Week', $domain),  'value' =>'week'),
                                                  array('text' => __('Day', $domain),   'value'=>'day')));
        $render->assign('defaultTemplateItems', array(array('text' => __('Table', $domain),'value' => 'table'),
                                                      array('text' => __('List', $domain),   'value' => 'list')));
        $render->assign('sortModeItems', array(array('text' => __('By name', $domain),     'value' => 'byname'),
                                               array('text' => __('By category sort value', $domain),'value' => 'bysortvalue')));

        $calendarsList = TimeItDomainFactory::getInstance('calendar')->getObjectList();
        $calendars = array();
        foreach($calendarsList AS $calendar) {
            $calendars[] = array('value' => $calendar['id'], 'text' => $calendar['name']);
        }
        $render->assign('defaultCalendarItems', $calendars);

        // find calendars with privateCalendar==1
        $calendarsPrivate =array(array('text'=>'-','value'=>0));
        foreach($calendarsList AS $calObj) {
            if($calObj['privateCalendar']) {
                $calendarsPrivate[] = array('value' => $calObj['id'], 'text' => $calObj['name']);
            }
        }
        $render->assign('defaultPrivateCalendarItems', $calendarsPrivate);

        $mapViewType = array(array('text'=>'Google Maps(TimeIt)','value'=>'googleMaps'));
        if (pnModAvailable('MyMap'))
        {
            $mapViewType[] = array('text'=>'MyMap','value'=>'mymap');
            $render->assign('MyMapModuleOk', true);
        } else
        {
            $render->assign('MyMapModuleOk', false);
        }
        $render->assign('mapViewTypeItems', $mapViewType);


        $firstWeekDayItems = array(array('text' => __('Sunday', $domain),    'value' => 0),
                                   array('text' => __('Monday', $domain),    'value' => 1),
                                   array('text' => __('Tuesday', $domain),   'value' => 2),
                                   array('text' => __('Wednesday', $domain), 'value' => 3),
                                   array('text' => __('Thursday', $domain),  'value' => 4),
                                   array('text' => __('Friday', $domain),    'value' => 5),
                                   array('text' => __('Saturday', $domain),  'value' => 6));
        $render->assign('firstWeekDayItems', $firstWeekDayItems);

        // userdeletion support
        $render->assign('userdeletionModeItems', array(array('text' => __('Anonymize events', $domain), 'value' => 'anonymize'),
                                                       array('text' => __('Delete events', $domain), 'value' => 'delete')));

        // load the categories system
        if (!($class = Loader::loadClass('CategoryRegistryUtil')))
            pn_exit ('Unable to load class [CategoryRegistryUtil] ...');
        if (!($class = Loader::loadClass('CategoryUtil')))
            pn_exit ('Unable to load class [CategoryUtil] ...');
        $categories  = CategoryRegistryUtil::getRegisteredModuleCategories ('TimeIt', 'TimeIt_events');
        $cats = array();
        foreach ($categories AS $property => $cid)
        {
            $cat = CategoryUtil::getCategoryByID($cid);
            $cats[] = array('value'=>$property,'text'=>(isset($cat['display_name'][ZLanguage::getLanguageCode()]))?$cat['display_name'][ZLanguage::getLanguageCode()]:$cat['name']);
        }

        $render->assign('colorCatsPropItems', $cats);

        // scribite! integration
        if (pnModAvailable('scribite'))
        {
            $editors = pnModAPIFunc('scribite','user','getEditors',array('editorname' => 'list'));// get editors
            $editorsConverted = array();
            // convert to pnform compitable format
            foreach ($editors AS $key=>$value)
            {
                $editorsConverted[] = array('text' => $key , 'value' => $value);
            }
            $render->assign('scribiteEditorItems', $editorsConverted);
        } else
        {
            $render->assign('scribiteEditorItems', false);
        }

        // ContactList integration
        if (pnModAvailable('ContactList'))
        {
            $render->assign('ContactListModuleOK', true);
        } else
        {
            $render->assign('ContactListModuleOK', false);
        }

        // locations integration
        if (pnModAvailable('locations'))
        {
            $render->assign('locationsModuleOK', true);
        } else
        {
            $render->assign('locationsModuleOK', false);
        }

        // formicula integration
        if (pnModAvailable('formicula'))
        {
            $render->assign('formiculaModuleOk', true);
            $render->assign('formiculaModuleOkReadOnly', false);
        } else
        {
            $render->assign('formiculaModuleOk', false);
            $render->assign('formiculaModuleOkReadOnly', true);
        }

        $render->assign(pnModGetVar('TimeIt'));
    }

    function handleCommand(&$render, &$args)
    {

        if ($args['commandName'] == 'update')
        {
            if (!$render->pnFormIsValid())
            {
                return false;
            } else
            {
                $data = $render->pnFormGetValues();

                pnModSetVars('TimeIt',$data);
                $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));

            }
        } else
        {
            $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
        }
    }
}
