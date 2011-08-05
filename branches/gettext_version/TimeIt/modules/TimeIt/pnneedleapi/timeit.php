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

function TimeIt_needleapi_timeit($args) {
// Get arguments from argument array
    $nid = $args['nid'];
    unset($args);

    // cache the results
    static $cache;
    if(!isset($cache)) {
        $cache = array();
    }


    $dom = ZLanguage::getModuleDomain('TimeIt');

    if(!empty($nid)) {
        if(!isset($cache[$nid])) {
            // not in cache array
            if(pnModAvailable('TimeIt')) {
                // nid is like E-##.., M-##.., W-##.. or D-##...
                $vars = explode('-', $nid);
                $type = '';
                if(is_array($vars)) {
                    $type = $vars[0];
                }

                pnModDBInfoLoad('TimeIt');
                switch($type) {
                    // E = Event
                    case 'E':
                        if(count($vars) >= 2) {
                            $eventId = $vars[1];
                            $cid = pnModGetVar('TimeIt', 'defaultCalendar', 1);
                            $dheid = null;
                            // additional params?
                            if(count($vars) > 2) {
                                $param_3 = $vars[2];
                                // an date?
                                if(strlen($param_3) == 8) {
                                    $year = substr($param_3, 0, 4);
                                    $month = substr($param_3, 4, 2);
                                    $day = substr($param_3, 6, 2);
                                    // Is there a calender id?
                                    if(count($vars) > 3) {
                                        $cid = (int)$vars[3];
                                    }

                                    $dheObj = pnModAPIFunc('TimeIt','user','getDHEByDate',array('cid'  => $cid,
                                                                                                'eid'  => $eventId,
                                                                                                'date' => $year.'-'.$month.'-'.$day));
                                    if($dheObj) {
                                        $dheid = $dheObj['id'];
                                    }
                                } else {
                                    // an calendar id
                                    $cid = (int)$param_3;
                                }
                            } else {

                                $dheObj = pnModAPIFunc('TimeIt','user','getDHE',array('obj' => array('id' => $eventId)));
                                if($dheObj) {
                                    $dheid = $dheObj['id'];
                                }
                            }

                            $event = TimeItDomainFactory::getInstance('event')->getObject((int)$eventId, $dheid);
                            if(TimeItPermissionUtil::canViewEvent($event)) {
                                // format columns in the event
                                $event = pnModAPIFunc('TimeIt', 'user', 'getEventPreformat', array('obj' => $event,
                                                                                                   'noHooks' => true));
                                // create url
                                $url = pnModURL('TimeIt', 'user', 'display', !empty($dheid)? array('ot'    => 'event',
                                                                                                   'cid'   => $cid,
                                                                                                   'id'    => $eventId
                                                                                                   )
                                                                                             :
                                                                                             array('ot'    => 'event',
                                                                                                   'cid'   => $cid,
                                                                                                   'dheid' => $dheid,
                                                                                                   'id'    => $eventId));
                                // build html
                                $title = '<a href="'.$url.'">'.$event['title'].'</a>';

                                if(isset($event['plugins']['location']['city']) && $event['allDay'] == 0) {
                                    $cache[$nid] = __f(/*!%1$s is an link with the event title,%2$s is an city name,%3$s is an date, %4$s is an time*/'%1$s in %2$s on the %3$s at %4$s', array('<a href="'.$url.'">'.$event['title'].'</a>',
                                                                                                                                                                                                $event['plugins']['location']['city'],
                                                                                                                                                                                                $event['dhe_date'],
                                                                                                                                                                                                $event['allDayStartLocalFormated']), $dom);
                                } else if(isset($event['plugins']['location']['city']) && $event['allDay'] != 0) {
                                    $cache[$nid] = __f(/*!%1$s is an link with the event title,%2$s is an city name,%3$s is an date*/'%1$s in %2$s on the %3$s', array('<a href="'.$url.'">'.$event['title'].'</a>',
                                                                                                                                                                       $event['plugins']['location']['city'],
                                                                                                                                                                       $event['dhe_date']), $dom);
                                } else if(!isset($event['plugins']['location']['city']) && $event['allDay'] == 0) {
                                    $cache[$nid] = __f(/*!%1$s is an link with the event title,%2$s is an date, %3$s is an time*/'%1$s on the %2$s at %3$s', array('<a href="'.$url.'">'.$event['title'].'</a>',
                                                                                                                                                                   $event['dhe_date'],
                                                                                                                                                                   $event['allDayStartLocalFormated']), $dom);
                                } else {
                                    $cache[$nid] = __f(/*!%1$s is an link with the event title,%2$s is an city name,%3$s is an date, %4$s is an time*/'%1$s on the %2$s', array('<a href="'.$url.'">'.$event['title'].'</a>',
                                                                                                                                                                                $event['dhe_date']), $dom);
                                }

                            } else {
                                $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('No auth for event', $dom) . ' (' . $eventId . ')') .'</em>';
                            }
                        } else {
                            $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('to few parameter', $dom)) . '</em>';
                        }
                        break;

                    // M = month
                    case 'M':
                        if(count($vars) >= 2) {
                            $cid = pnModGetVar('TimeIt', 'defaultCalendar', 1);
                            $dheid = null;
                            $param_1 = $vars[1];
                            // an date?
                            if(strlen($param_1) == 6) {
                                $year = substr($param_1, 0, 4);
                                $month = substr($param_1, 4, 2);
                                // Is there a calender id?
                                if(count($vars) > 3) {
                                    $cid = (int)$vars[3];
                                }
                            } else {
                                $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('invalid parameter at pos. 1 (date with format YYYYMM)', $dom)) . '</em>';
                                break;
                            }
                            
                            // create url
                            $url = pnModURL('TimeIt', 'user', 'view', array('ot'       => 'event',
                                                                            'viewType' => 'month',
                                                                            'month'    => $month,
                                                                            'year'     => $year,
                                                                            'cid'      => $cid));

                            $cache[$nid] = '<a href="'.$url.'">';
                            $cache[$nid] .= __f(/*!%s is an date, e.g.: July, 2009*/'Events in %s', array(DateUtil::strftime('%B, %Y', mktime(0, 0, 0, $month, 1, $year))), $dom);
                            $cache[$nid] .= '</a>';
                        } else {
                            $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('to few parameters', $dom)) . '</em>';
                        }
                        break;

                     // W = week
                     case 'W':
                        if(count($vars) >= 2) {
                            $cid = pnModGetVar('TimeIt', 'defaultCalendar', 1);
                            $dheid = null;
                            $param_1 = $vars[1];
                            // an date?
                            if(strlen($param_1) == 8) {
                                $year = substr($param_1, 0, 4);
                                $month = substr($param_1, 4, 2);
                                $day = substr($param_1, 6, 2);
                                // Is there a calender id?
                                if(count($vars) > 3) {
                                    $cid = (int)$vars[3];
                                }
                            } else {
                                $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('invalid parameter at pos. 1 (date with format YYYYMMDD)', $dom)) . '</em>';
                                break;
                            }

                            // create url
                            $url = pnModURL('TimeIt', 'user', 'view', array('ot'       => 'event',
                                                                            'viewType' => 'week',
                                                                            'month'    => $month,
                                                                            'year'     => $year,
                                                                            'day'      => $day,
                                                                            'cid'      => $cid));

                            $cache[$nid] = '<a href="'.$url.'">';
                            $cache[$nid] .= __f(/*!%s is an date, e.g.: 47, 2009*/'Events in week %s', array(DateUtil::strftime('%V, %Y', mktime(0, 0, 0, $month, $day, $year))), $dom);
                            $cache[$nid] .= '</a>';
                        } else {
                            $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('to few parameters', $dom)) . '</em>';
                        }
                        break;

                    // D = day
                    case 'D':
                        if(count($vars) >= 2) {
                            $cid = pnModGetVar('TimeIt', 'defaultCalendar', 1);
                            $dheid = null;
                            $param_1 = $vars[1];
                            // an date?
                            if(strlen($param_1) == 8) {
                                $year = substr($param_1, 0, 4);
                                $month = substr($param_1, 4, 2);
                                $day = substr($param_1, 6, 2);
                                // Is there a calender id?
                                if(count($vars) > 3) {
                                    $cid = (int)$vars[3];
                                }
                            } else {
                                $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('invalid parameter at pos. 1 (date with format YYYYMMDD)', $dom)) . '</em>';
                                break;
                            }

                            // create url
                            $url = pnModURL('TimeIt', 'user', 'view', array('ot'       => 'event',
                                                                            'viewType' => 'day',
                                                                            'month'    => $month,
                                                                            'year'     => $year,
                                                                            'day'      => $day,
                                                                            'cid'      => $cid));

                            $cache[$nid] = '<a href="'.$url.'">';
                            $cache[$nid] .= __f(/*!%s is an date, e.g.: 47, 2009*/'Events on the %s', array(DateUtil::getDatetime(mktime(0, 0, 0, $month, $day, $year), 'datebrief')), $dom);
                            $cache[$nid] .= '</a>';
                        } else {
                            $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('to few parameters', $dom)) . '</em>';
                        }
                        break;

                    default:
                        $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('unknown parameter at pos.1 (E, M, W or D)', $dom)) . '</em>';
                }
            } else {
                $cache[$nid] = '<em>' . DataUtil::formatForDisplay(__('TimeIt not available', $dom)) . '</em>';
            }
        }
        $result = $cache[$nid];
    } else {
        $result = '<em>' . DataUtil::formatForDisplay(__('no needle id', $dom)) . '</em>';
    }
    return $result;
}