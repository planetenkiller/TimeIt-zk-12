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
class Timeit_FormHandler_config
{
    function initialize(&$render)
    {
        $render->assign('workflowItems', array(array('text'=>'standard','value'=>'standard'),
                                               array('text'=>'moderate','value'=>'moderate')));
        $render->assign('defaultViewItems', array(array('text'=>_MONTH,'value'=>'month'),
                                                  array('text'=>_WEEK,'value'=>'week'),
                                                  array('text'=>_DAY,'value'=>'day')));
        $render->assign('defaultTemplateItems', array(array('text'=>'default','value'=>'default'),
                                                      array('text'=>'list','value'=>'list')));
        $render->assign('sortModeItems', array(array('text'=>_TIMEIT_SORTMODE_NAME,'value'=>'byname'),
                                               array('text'=>_TIMEIT_SORTMODE_SORTVALUE,'value'=>'bysortvalue')));
        $calendars = pnModAPIFunc('TimeIt','calendar','getAllForDropdown');
        $render->assign('defaultCalendarItems', $calendars);
        // find calendars with privateCalendar==1
        $calendarsPrivate =array(array('text'=>'-','value'=>0));
        foreach($calendars AS $cal) {
            $calObj = pnModAPIFunc('TimeIt','calendar','get',$cal['value']);
            if($calObj['privateCalendar']) {
                $calendarsPrivate[] = $cal;
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


        $TDays = explode(" ", _DAY_OF_WEEK_LONG);
        $firstWeekDayItems = array(array('text' => $TDays[0] , 'value' => 0),
                                   array('text' => $TDays[1] , 'value' => 1),
                                   array('text' => $TDays[2] , 'value' => 2),
                                   array('text' => $TDays[3] , 'value' => 3),
                                   array('text' => $TDays[4] , 'value' => 4),
                                   array('text' => $TDays[5] , 'value' => 5),
                                   array('text' => $TDays[6] , 'value' => 6));
        $render->assign('firstWeekDayItems', $firstWeekDayItems);

        // userdeletion support
        $render->assign('userdeletionModeItems', array(array('text'=>_TIMEIT_USERDELITON_ITEM_ANONYMIZE,'value'=>'anonymize'),
                                                       array('text'=>_TIMEIT_USERDELITON_ITEM_DELETE,'value'=>'delete')));

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
            $cats[] = array('value'=>$property,'text'=>(isset($cat['display_name'][pnUserGetLang()]))?$cat['display_name'][pnUserGetLang()]:$cat['name']);
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

                /*if($data['enableMapView'] && !$data['useLocations'] && empty($data['googleMapsApiKey']))
                {
                    $form = &$render->pnFormGetPluginById('googleMapsApiKey');
                    $form->setError(_PNFORM_MANDATORYERROR);
                    return false;
                }*/

                pnModSetVars('TimeIt',$data);
                $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));

            }
        } else
        {
            $render->pnFormRedirect(pnModURL('TimeIt', 'admin', 'main'));
        }
    }
}
