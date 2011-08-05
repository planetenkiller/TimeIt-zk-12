<?php
/**
 * PostNuke Application Framework
 *
 * @copyright (c) 2001, PostNuke Development Team
 * @link http://www.postnuke.com
 * @version $Id: modifier.modified.php 18169 2006-03-16 02:17:22Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * @package PostNuke_Themes
 * @subpackage Atom
 */

/**
 * Smarty function to generate a valid atom ID for the feed
 *
 * Example
 *
 *   <updated><!--[updated]--></updated>
 *
 * @author       Mark West
 * @since        18 February 2007
 * @return       string the atom ID
 */
function smarty_function_updated($params, &$smarty)
{
    return strftime('%a, %d %b %Y %H:%M:%S %z', $GLOBALS['rss_feed_lastupdated']);
}
