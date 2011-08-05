<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage EventPlugins
 */
    
Loader::loadFile('EventPluginsContactBase.php','modules/TimeIt/EventPlugins');
    
class TimeItEventPluginsContactTimeIt extends TimeItEventPluginsContactBase
{
    protected $contactPerson;
    protected $email;
    protected $phoneNr;
    protected $website;
    
    public function __construct()
    {
        // set standard values.
        $this->contactPerson = '';
        $this->email = '';
        $this->phoneNr = '';
        $this->website = '';
    }
    
    public function getName()
    {
        return 'ContactTimeIt';
    }

    public function getDisplayname()
    {
        return 'TimeIt';
    }
    
    public function edit($mode, &$render)
    {
        // add data
        $render->append('data', array('ContactTimeIt_contactPerson'=>$this->contactPerson,
                                      'ContactTimeIt_email'        =>$this->email,
                                      'ContactTimeIt_phoneNr'      =>$this->phoneNr,
                                      'ContactTimeIt_website'      =>$this->website), true);
        
        return true;
    }

    public function editPostBack($values, &$dataForDB)
    {
        $dataForDB['data']['plugindata'][$this->getName()] = array();
        $dataForDB['data']['plugindata'][$this->getName()]['contactPerson'] = $values['data']['ContactTimeIt_contactPerson'];
        $dataForDB['data']['plugindata'][$this->getName()]['email'] = $values['data']['ContactTimeIt_email'];
        $dataForDB['data']['plugindata'][$this->getName()]['phoneNr'] = $values['data']['ContactTimeIt_phoneNr'];
        $dataForDB['data']['plugindata'][$this->getName()]['website'] = $values['data']['ContactTimeIt_website'];

        unset( $dataForDB['data']['ContactTimeIt_contactPerson'],
               $dataForDB['data']['ContactTimeIt_email'],
               $dataForDB['data']['ContactTimeIt_phoneNr'],
               $dataForDB['data']['ContactTimeIt_website']);
    }

    protected function getFormatedData()
    {
        return array('contactPerson' => $this->contactPerson,
                     'email'         => $this->email,
                     'phoneNr'       => $this->phoneNr,
                     'website'       => $this->website);
    }

    public function loadData(&$obj)
    {
        $this->contactPerson = $obj['data']['plugindata'][$this->getName()]['contactPerson'];
        $this->email = $obj['data']['plugindata'][$this->getName()]['email'];
        $this->phoneNr = $obj['data']['plugindata'][$this->getName()]['phoneNr'];
        $this->website = $obj['data']['plugindata'][$this->getName()]['website'];
    }

    public function displayAfterDesc(&$args)
    {
        $pnRender = pnRender::getInstance('TimeIt');
        $pnRender->assign('displayS', $args['displayS']);
        $pnRender->assign('displayUS', $args['displayUS']);
        $pnRender->assign('eid', $args['event']['id']);
        $pnRender->assign('viewDate', $args['viewDate']);
        $pnRender->assign('dhobj', $args['dhobj']);
        return $pnRender->fetch('eventplugins/TimeIt_eventplugins_contacttimeit.htm');
    }
}