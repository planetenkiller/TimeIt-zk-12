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

Loader::loadFile('EventPluginsLocationBase.php','modules/TimeIt/EventPlugins');


class TimeItEventPluginsLocationTimeIt extends TimeItEventPluginsLocationBase
{
    protected $data;

    public function __construct()
    {
        $this->data = array();
    }

    public function getName()
    {
        return "LocationTimeIt";
    }

    public function edit($mode, &$render)
    {
        $render->append('data', array('LocationTimeIt_name'=>$this->data['name'],
                                      'LocationTimeIt_street'=>$this->data['street'],
                                      'LocationTimeIt_houseNumber'=>$this->data['houseNumber'],
                                      'LocationTimeIt_zip'=>$this->data['zip'],
                                      'LocationTimeIt_city'=>$this->data['city'],
                                      'LocationTimeIt_country'=>$this->data['country'],
                                      'LocationTimeIt_lat'=>$this->data['lat'],
                                      'LocationTimeIt_lng'=>$this->data['lng'],
                                      'LocationTimeIt_displayMap'=>$this->data['displayMap']), true);

        return true;
    }

    public function editPostBack($values, &$dataForDB)
    {
        $dataForDB['data']['plugindata'][$this->getName()] = array();
        $dataForDB['data']['plugindata'][$this->getName()]['name']        = $values['data']['LocationTimeIt_name'];
        $dataForDB['data']['plugindata'][$this->getName()]['street']      = $values['data']['LocationTimeIt_street'];
        $dataForDB['data']['plugindata'][$this->getName()]['houseNumber'] = $values['data']['LocationTimeIt_houseNumber'];
        $dataForDB['data']['plugindata'][$this->getName()]['zip']         = $values['data']['LocationTimeIt_zip'];
        $dataForDB['data']['plugindata'][$this->getName()]['city']        = $values['data']['LocationTimeIt_city'];
        $dataForDB['data']['plugindata'][$this->getName()]['country']     = $values['data']['LocationTimeIt_country'];
        $dataForDB['data']['plugindata'][$this->getName()]['lat']         = $values['data']['LocationTimeIt_lat'];
        $dataForDB['data']['plugindata'][$this->getName()]['lng']         = $values['data']['LocationTimeIt_lng'];
        $dataForDB['data']['plugindata'][$this->getName()]['displayMap']  = $values['data']['LocationTimeIt_displayMap'];

        unset(  $dataForDB['data']['LocationTimeIt_name'],
                $dataForDB['data']['LocationTimeIt_street'],
                $dataForDB['data']['LocationTimeIt_houseNumber'],
                $dataForDB['data']['LocationTimeIt_zip'],
                $dataForDB['data']['LocationTimeIt_city'],
                $dataForDB['data']['LocationTimeIt_country'],
                $dataForDB['data']['LocationTimeIt_lat'],
                $dataForDB['data']['LocationTimeIt_lng'],
                $dataForDB['data']['LocationTimeIt_displayMap']);
    }

    public function loadData(&$obj)
    {
        if($obj['data']['plugindata'][$this->getName()]['name']) {
            $this->data['name'] = $obj['data']['plugindata'][$this->getName()]['name'];
        }
        if($obj['data']['plugindata'][$this->getName()]['street']) {
            $this->data['street'] = $obj['data']['plugindata'][$this->getName()]['street'];
        }
        if($obj['data']['plugindata'][$this->getName()]['houseNumber']) {
            $this->data['houseNumber'] = $obj['data']['plugindata'][$this->getName()]['houseNumber'];
        }
        if($obj['data']['plugindata'][$this->getName()]['zip']) {
            $this->data['zip'] = $obj['data']['plugindata'][$this->getName()]['zip'];
        }
        if($obj['data']['plugindata'][$this->getName()]['city']) {
            $this->data['city'] = $obj['data']['plugindata'][$this->getName()]['city'];
        }
        if($obj['data']['plugindata'][$this->getName()]['country']) {
            $this->data['country'] = $obj['data']['plugindata'][$this->getName()]['country'];
        }
        if($obj['data']['plugindata'][$this->getName()]['lat']) {
            $this->data['lat'] = $obj['data']['plugindata'][$this->getName()]['lat'];
        }
        if($obj['data']['plugindata'][$this->getName()]['lng']) {
            $this->data['lng'] = $obj['data']['plugindata'][$this->getName()]['lng'];
        }
        if($obj['data']['plugindata'][$this->getName()]['displayMap']) {
            $this->data['displayMap'] = $obj['data']['plugindata'][$this->getName()]['displayMap'];
        }
    }
    
    protected function getFormatedData()
    {
        return $this->data;
    }
}