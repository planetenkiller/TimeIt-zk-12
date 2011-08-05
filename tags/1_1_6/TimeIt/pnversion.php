<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

$modversion['name'] = _TIMEIT_NAME;
$modversion['displayname'] = _TIMEIT_DISPLAYNAME;
$modversion['description'] = _TIMEIT_DESCRIPTION;
$modversion['version'] = '1.1.6';
$modversion['credits'] = ' ';
$modversion['help'] = ' ';
$modversion['changelog'] = ' ';
$modversion['license'] = 'COPYING.txt';
$modversion['author'] = 'planetenkiller';
$modversion['contact'] = ' ';
$modversion['securityschema'] = array('TimeIt::' 		=> '::',
									  'TimeIt::Event' 	=> 'EventId::',
									  'TimeIt:Group:' 	=> 'Group Name::',
									  'TimeIt:subscribe:' => '::',
									  'TimeIt:Category:'=>'Category id::',
									  'TimeIt:Category:Add','Category id::');

$modversion['dependencies'] = array(array('modname'    => 'ContactList',
                                          'minversion' => '1.0',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED),
								    array('modname'    => 'scribite',
                                          'minversion' => '2.2',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED),
								    array('modname'    => 'MyMap',
                                          'minversion' => '1.0',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED),
								    array('modname'    => 'locations',
                                          'minversion' => '1.0',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED));
