<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Controller
 */

/**
 * User Controller.
 */
class TimeIt_User extends Zikula_Controller
{
    /**
     * Module entry point.
     *
     * @return string HTML Code
     */
    public function main()
    {
        return ModUtil::func('TimeIt', 'user', 'view');
    }

    /**
     * Displays all available events.
     *
     * @return string HTML Code
     */
    public function view()
    {
        // check object type
        $objectType = FormUtil::getPassedValue('ot', 'event', 'GET');
        $this->throwNotFoundUnless(in_array($objectType, TimeIt_Util::getObjectTypes('view')),
                                   $this->__f('Unkown object type %s.', DataUtil::formatForDisplay($objectType)));


        // load filter
        $filter = TimeIt_Filter_Container::getFilterFormGETPOST($objectType);

        // create Renderer
        $render = Renderer::getInstance('TimeIt', false);
        $render->assign('modvars', ModUtil::getVar('TimeIt'));

        // vars
        $tpl = null;
        $theme = null;
        $domain = $this->serviceManager->getService('timeit.manager.'.$objectType);

        // load the data
        if ($objectType == 'event') {
            $calendarId = (int)FormUtil::getPassedValue('cid', ModUtil::getVar('TimeIt', 'defaultCalendar'), 'GETPOST');
            $calendar = $this->serviceManager->getService('timeit.manager.calendar')->getObject($calendarId);
            $this->throwNotFoundIf(!empty($calendar), $this->__f('Calendar [%s] not found.', $calendar_id));

            $year    = (int)FormUtil::getPassedValue('year', date("Y"), 'GETPOST');
            $month   = (int)FormUtil::getPassedValue('month', date("n"), 'GETPOST');
            $day     = (int)FormUtil::getPassedValue('day', date("j"), 'GETPOST');
            $tpl = FormUtil::getPassedValue('viewType', FormUtil::getPassedValue('viewtype', $calendar['defaultView'], 'GETPOST'), 'GETPOST');
            $firstDayOfWeek = (int)FormUtil::getPassedValue('firstDayOfWeek', -1, 'GETPOST');
            $theme = FormUtil::getPassedValue('template', $calendar['defaultTemplate'] , 'GETPOST');

            // backward compatibility
            if($theme == 'default')
                $theme = 'table';

            // check for a valid $tpl
            if ($tpl != 'year' && $tpl != 'month' && $tpl != 'week' && $tpl != 'day') {
                $tpl = $calendar['defaultView'];
            }

            $render->assign('template', $theme);
            $render->assign('viewed_day', $day);
            $render->assign('viewed_month', $month);
            $render->assign('viewed_year', $year);
            $render->assign('viewType', $tpl);
            $render->assign('calendar', $calendar);
            $render->assign('viewed_date', DateUtil::getDatetime(mktime(0, 0, 0, $month, $day, $year), DATEONLYFORMAT_FIXED));
            $render->assign('date_today', DateUtil::getDatetime(null, DATEONLYFORMAT_FIXED));
            $render->assign('month_startDate', DateUtil::getDatetime(mktime(0, 0, 0, $month, 1, $year), DATEONLYFORMAT_FIXED)  );
            $render->assign('month_endDate', DateUtil::getDatetime(mktime(0, 0, 0, $month, DateUtil::getDaysInMonth($month, $year), $year), DATEONLYFORMAT_FIXED) );
            $render->assign('filter_obj_url', $filter->toURL());
            $render->assign('firstDayOfWeek', $firstDayOfWeek);
            $render->assign('selectedCats', array());

            $categories = CategoryRegistryUtil::getRegisteredModuleCategories('TimeIt', 'TimeIt_events');
            foreach ($categories as $property => $cid) {
                $cat = CategoryUtil::getCategoryByID($cid);

                if (isset($cat['__ATTRIBUTES__']['calendarid']) && !empty($cat['__ATTRIBUTES__']['calendarid'])) {
                    if ($cat['__ATTRIBUTES__']['calendarid'] != $calendar['id']) {
                        unset($categories[$property]);
                    }
                }
            }
            $render->assign('categories', $categories);

            // load event data
            switch ($tpl) {
                case 'year':
                    $objectData = $domain->getYearEvents($year, $calendar['id'], $firstDayOfWeek);
                    break;
                case 'month':
                    $objectData = $domain->getMonthEvents($year, $month, $day, $calendar['id'], $firstDayOfWeek, $filter);
                    break;
                case 'week':
                    $objectData = $domain->getWeekEvents($year, $month, $day, $calendar['id'], $filter);
                    break;
                case 'day':
                    $objectData = $domain->getDayEvents($year, $month, $day, $calendar['id'], $filter);
                    break;
            }
        }

        // assign the data
        $render->assign('objectArray', $objectData);

        // render the html
        return $this->_renderTemplate($render, $objectType, 'user', 'view', $theme, $tpl, 'table');
    }

    /**
     * Render template.
     *
     * @param Renderer $render       Renderer.
     * @param string   $objectType   Object type.
     * @param string   $type         Controller.
     * @param string   $func         Function of Controller.
     * @param string   $theme        Theme to use (themes are subfolders in /templates).
     * @param string   $tpl          Sub template name.
     * @param string   $defaultTheme Default theme (Use if $theme is null).
     *
     * @return string Rendered Template (HTML)
     */
    private function _renderTemplate($render, $objectType, $type, $func, $theme=null, $tpl=null, $defaultTheme=null)
    {
        $template = $type . '_' . $func . '_' . $objectType;
        if ($tpl != null) {
            $template .= '_' . $tpl;
        }
        $template .= '.htm';

        if (!empty($theme) && $render->template_exists(DataUtil::formatForOS($theme).'/'.$template)) {
            $template = DataUtil::formatForOS($theme).'/'.$template;
        } else if (!empty($defaultTheme) && $render->template_exists(DataUtil::formatForOS($defaultTheme).'/'.$template)) {
            $template =  DataUtil::formatForOS($defaultTheme).'/'.$template;
        }

        return $render->fetch($template);
    }
}
