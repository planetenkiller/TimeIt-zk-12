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
 
Loader::loadFile('EventPluginsContactFormicula.php','modules/TimeIt/EventPlugins');

class TimeItEventPluginsContactTiFormiCombi extends TimeItEventPluginsContactFormicula
{
    function __construct()
    {
        parent::__construct();
    }

    public static function dependencyCheck()
    {
        return pnModAvailable('formicula');
    }

    public function getName()
    {
        return 'ContactTiFormiCombi';
    }

    public function getDisplayname()
    {
        return 'TimeIt/Formicula';
    }

    public function displayAfterDesc(&$args)
    {
        if(pnUserLoggedIn()) {
            $pnRender = pnRender::getInstance('TimeIt');
            $pnRender->assign('displayS', $args['displayS']);
            $pnRender->assign('displayUS', $args['displayUS']);
            $pnRender->assign('eid', $args['event']['id']);
            $pnRender->assign('viewDate', $args['viewDate']);
            $pnRender->assign('dhobj', $args['dhobj']);
            return $pnRender->fetch('eventplugins/TimeIt_eventplugins_contacttimeit.htm');
        } else {
            return parent::displayAfterDesc($args);
        }
    }
}