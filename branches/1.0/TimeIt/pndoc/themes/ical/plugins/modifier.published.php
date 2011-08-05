<?php
/**
 * PostNuke Application Framework
 *
 * @copyright (c) 2001, PostNuke Development Team
 * @link http://www.postnuke.com
 * @version $Id: modifier.published.php 22138 2007-06-01 10:19:14Z markwest $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * @package PostNuke_Themes
 * @subpackage Atom
 */

/**
 * Smarty modifier format an issue date for an atom news feed
 *
 * Example
 *
 *   <!--[$MyVar|published]-->
 *
 * @author       Mark West
 * @author		 Franz Skaaning
 * @since        02 March 2004
 * @param        array    $string     the contents to transform
 * @return       string   the modified output
 */
function smarty_modifier_published($string)
{
    return strftime('%a, %d %b %Y %H:%M:%S %z', strtotime($string));
}
