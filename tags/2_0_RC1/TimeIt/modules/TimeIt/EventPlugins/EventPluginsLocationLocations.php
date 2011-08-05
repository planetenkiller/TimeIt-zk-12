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


class TimeItEventPluginsLocationLocations extends TimeItEventPluginsLocationBase
{
    protected $locationId;
    protected $location;
    protected $displayMap;

    public function __construct()
    {
        $this->locationId = 0;
        $this->location = array();
    }

    public function getName()
    {
        return "LocationLocations";
    }

    public function edit($mode, &$render)
    {
        if(pnModAvailable('locations')) {
            $locationsItems = array(-1 => array('value'=>'','text'=>'---'));
            $locationsItems2 = pnModAPIFunc('locations','user','getLocationsForDropdown');

            $render->append('data', array('LocationLocations_id'=>$this->locationId,
                                          'LocationLocations_idItems'=>array_merge($locationsItems, $locationsItems2),
                                          'LocationLocations_displayMap'=>$this->displayMap), true);

            return true;
        } else {
            return false;
        }
    }

    public function editPostBack($values,&$dataForDB) {
         $dataForDB['data']['plugindata'][$this->getName()] = array();
         $dataForDB['data']['plugindata'][$this->getName()]['id'] = $values['data']['LocationLocations_id'];
         $dataForDB['data']['plugindata'][$this->getName()]['displayMap'] = $values['data']['LocationLocations_displayMap'];

         unset($dataForDB['data']['LocationLocations_id'],
               $dataForDB['data']['LocationLocations_displayMap']);
    }

    public function loadData(&$obj)
    {
        $this->locationId = (int)$obj['data']['plugindata'][$this->getName()]['id'];
        $this->displayMap = $obj['data']['plugindata'][$this->getName()]['displayMap'];
        if($this->locationId && pnModAvailable('locations')) {
            $this->location = pnModAPIFunc('locations','user','getLocationByID',array('locationid'=>$this->locationId));
            $this->location['displayMap'] = $this->displayMap;

            $latlng = explode(',', $this->location['latlng']);
            $lat = $latlng[0];
            $lng = $latlng[1];
            $this->location['lat'] = $lat;
            $this->location['lng'] = $lng;
        }
    }
    
    protected function getFormatedData()
    {
        return $this->location;
    }
}