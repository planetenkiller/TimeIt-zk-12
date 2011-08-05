<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Needles
 */

function TimeIt_needleapi_timeit_info($args) {
    return array('module'  => 'TimeIt',
                 'info'    => 'TIMEIT{E-eventId(-YYYYMMDD)(-calendarId) | M-YYYYMM(-calendarId) | W-YYYYMMDD(-calendarId) | D-YYYYMMDD(-calendarId)}',
                 'inspect' => false);
}