<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Template Plugins
 */

/**
 *
 * @param string $ot object type: calendar, event, reg
 * @param string $perm permission: create, edit, delete, translate(event only)
 * @param array $obj object to check
 * @return <type>
 */
function smarty_function_tipermissions($params, &$smarty)
{
    if (empty($params['ot'])) {
        $smarty->trigger_error("tipermissions: missing 'ot' parameter");
        return;
    }
    
    if (empty($params['perm'])) {
        $smarty->trigger_error("tipermissions: missing 'perm' parameter");
        return;
    }

    $hasPermission = false;
    switch ($params['ot']) {
        case 'event':
            switch ($params['perm']) {
                case 'create':
                    $hasPermission = TimeItPermissionUtil::canCreateEvent();
                    break;
                case 'edit':
                    $hasPermission = TimeItPermissionUtil::canEditEvent($params['obj']);
                    break;
                case 'delete':
                    $hasPermission = TimeItPermissionUtil::canDeleteEvent($params['obj']);
                    break;
                case 'translate':
                    $hasPermission = TimeItPermissionUtil::canTranslateEvent($params['obj']);
                    break;
                default:
                    $smarty->trigger_error("tipermissions: 'perm' parameter: value '".$params['perm']."' isn't valid");
                    return;
            }
            break;
        case 'reg':
            switch ($params['perm']) {
                case 'create':
                    $hasPermission = TimeItPermissionUtil::canCreateReg($params['obj']);
                    break;
                default:
                    $smarty->trigger_error("tipermissions: 'perm' parameter: value '".$params['perm']."' isn't valid");
                    return;
            }
            break;
        default:
            $smarty->trigger_error("tipermissions: 'ot' parameter: value '".$params['perm']."' isn't valid");
            return;
    }
    
    if(!empty($params['assign'])) {
        $smarty->assign($params['assign'], $hasPermission);
    } else {
        return $hasPermission;
    }
}
