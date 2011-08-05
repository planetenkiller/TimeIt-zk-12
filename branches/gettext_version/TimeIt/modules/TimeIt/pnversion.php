<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Core
 */


$modversion['name'] = 'TimeIt';
$domain = ZLanguage::getModuleDomain($modversion['name']);
$modversion['displayname'] =  __("TimeIt", $domain);
$modversion['description'] =  __("TimeIt is a calendar module with multiple calendars support (and much more).", $domain);
$modversion['url'] =  __("TimeIt", $domain);
$modversion['version'] = '3.0.2';
$modversion['credits'] = ' ';
$modversion['help'] = ' ';
$modversion['changelog'] = ' ';
$modversion['license'] = 'license.txt';
$modversion['author'] = 'planetenkiller';
$modversion['contact'] = ' ';
$modversion['securityschema'] = array('TimeIt::'           => '::',
                                      'TimeIt::Event'      => 'EventId::',
                                      'TimeIt:Group:'      => 'Group Name::',
                                      'TimeIt:subscribe:'  => '::',
                                      'TimeIt:Category:'   => 'Category id::',
                                      'TimeIt:Category:Add'=> 'Category id::',
                                      'TimeIt:Calendar:'   => 'Calendar id::',
                                      'TimeIt:Translate:'  => '::');

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
                                          'minversion' => '2.0.1',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED),
                                    array('modname'    => 'AddressBook',
                                          'minversion' => '1.3.1',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED),
                                    array('modname'    => 'formicula',
                                          'minversion' => '2.2',
                                          'maxversion' => '',
                                          'status'     => PNMODULE_DEPENDENCY_RECOMMENDED));
