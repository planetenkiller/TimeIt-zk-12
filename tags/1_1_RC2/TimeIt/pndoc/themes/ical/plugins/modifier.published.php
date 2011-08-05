<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Smarty modifier format an issue date for DTEND
 *
 * Example
 *
 *   <!--[$MyVar|published]-->
 *
 * @param        array    $string     the contents to transform
 * @return       string   the modified output
 */
function smarty_modifier_published($string)
{
    return strftime('%Y%m%dT%H%M%SZ', strtotime('+1 day', strtotime($string)));
}
