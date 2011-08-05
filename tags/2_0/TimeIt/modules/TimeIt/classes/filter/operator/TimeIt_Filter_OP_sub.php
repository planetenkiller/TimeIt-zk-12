<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Filter
 */

Loader::requireOnce('modules/TimeIt/classes/filter/operator/TimeIt_Filter_OperatorIf.php');

/**
 * sub operator
 *
 * @author planetenkiller
 */
class TimeIt_Filter_OP_sub extends TimeIt_Filter_OP_in
{
    protected function getItems(&$group) {
        $items = parent::getItems($group);

        foreach($items AS $item) {
            $cats = CategoryUtil::getSubCategories($item);
            foreach ($cats as $item) {
                if(!in_array($item['id'], $items)) {
                    $items[] = $item['id'];
                }
            }
        }

        return $items;
    }
}

