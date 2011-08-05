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
 * Smarty modifier to format datestamps via TimeIt::getDatetime().
 *
 * @author   planetenkiller
 * @param    string   $string         input date string
 * @param    string   format          strftime format for output
 * @return   string   the modified output
 */
function smarty_modifier_tidate_format($string, $format='datebrief')
{
    if (empty($format)) {
        $format = 'datebrief';
    }
    
    if ($string != '') {
        return TimeIt::getDatetime(DateUtil::makeTimestamp($string), $format);
    } 

    return;
}
