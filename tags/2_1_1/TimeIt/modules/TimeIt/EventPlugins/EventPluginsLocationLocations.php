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
    protected $zoom;

    protected static $locationsCache = null;
    private static $preFillLocationsCache = false;
    protected static $locationsModuleAvaible = null;
    protected static $locationNotFoundErrorDisplayd = array();

    public function __construct()
    {
        $this->locationId = 0;
        $this->zoom = 13;
        $this->location = array();

        if(self::$locationsModuleAvaible == null) {
            self::$locationsModuleAvaible = pnModAvailable('locations');
        }

        // setup cache
        if(self::$locationsCache == null && self::$preFillLocationsCache && self::$locationsModuleAvaible) {
            self::$locationsCache = array();
            // load the object array class corresponding to $objectType
            pnModDBInfoLoad('locations');
            if (!($class = Loader::loadArrayClassFromModule('locations', 'location', true))) {
                pn_exit('Unable to load array class [' . DataUtil::formatForDisplay('location') . '] ...');
            }
            $objectArray = new $class();
            $GLOBALS['pntables']['locations_location_db_extra_enable_attribution'] = false;
            $GLOBALS['pntables']['locations_location_db_extra_enable_meta'] = false;
            $objectData = $objectArray->get();
            $GLOBALS['pntables']['locations_location_db_extra_enable_attribution'] = true;
            $GLOBALS['pntables']['locations_location_db_extra_enable_meta'] = true;
            foreach($objectData AS $obj) {
                self::$locationsCache[$obj['locationid']] =& $obj;
            }

        } else if(self::$locationsCache == null && !self::$preFillLocationsCache) {
            self::$locationsCache = array();
        }
    }

    public static function setPrefilLocationCache($bool) 
    {
        self::$preFillLocationsCache = (bool)$bool;
    }

    public static function dependencyCheck()
    {
        return pnModAvailable('locations');
    }

    public function getName()
    {
        return "LocationLocations";
    }

    public function getDisplayname()
    {
        return 'Locations';
    }

    public function edit($mode, &$render)
    {
        if(pnModAvailable('locations')) {
            $locationsItems = array(-1 => array('value'=>'','text'=>'---'));
            $locationsItems2 = pnModAPIFunc('locations','user','getLocationsForDropdown');

            $render->append('data', array('LocationLocations_id'=>$this->locationId,
                                          'LocationLocations_idItems'=>array_merge($locationsItems, $locationsItems2),
                                          'LocationLocations_displayMap'=>$this->displayMap,
                                          'LocationLocations_zoomFactor'=>$this->zoom), true);

            $LocationTimeIt_zoomFactorItms = array();
            foreach(range(1, 19) AS $zoom) {
                $LocationTimeIt_zoomFactorItms[] = array('value'=>$zoom,'text'=>$zoom);
            }
            $render->assign('LocationLocations_zoomFactorItems' , $LocationTimeIt_zoomFactorItms);

            return true;
        } else {
            return false;
        }
    }

    public function editPostBack($values,&$dataForDB)
    {
         $dataForDB['data']['plugindata'][$this->getName()] = array();
         $dataForDB['data']['plugindata'][$this->getName()]['id'] = $values['data']['LocationLocations_id'];
         $dataForDB['data']['plugindata'][$this->getName()]['displayMap'] = $values['data']['LocationLocations_displayMap'];
         $dataForDB['data']['plugindata'][$this->getName()]['zoomFactor'] = $values['data']['LocationLocations_zoomFactor'];

         unset($dataForDB['data']['LocationLocations_id'],
               $dataForDB['data']['LocationLocations_displayMap'],
               $dataForDB['data']['LocationLocations_zoomFactor']);
    }

    public function loadData(&$obj)
    {
        $this->locationId = (int)$obj['data']['plugindata'][$this->getName()]['id'];
        $this->displayMap = $obj['data']['plugindata'][$this->getName()]['displayMap'];
        $this->zoom = $obj['data']['plugindata'][$this->getName()]['zoomFactor'];
        if($this->locationId && self::$locationsModuleAvaible) {
            if(!isset(self::$locationsCache[$this->locationId])) {
                 self::$locationsCache[$this->locationId] = pnModAPIFunc('locations','user','getLocationByID',array('locationid'=>$this->locationId));
            }

            $this->location = self::$locationsCache[$this->locationId];
            if($this->location === false) {
                // location not found!
                if(!isset(self::$locationNotFoundErrorDisplayd[$this->locationId])) {
                    self::$locationNotFoundErrorDisplayd[$this->locationId] = true;
                    LogUtil::registerError(pnml('_TIMEIT_LOCATIONNOTFOUND', array('s'=>$this->locationId,'e'=>$obj['title'])));
                }
            }
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
        $this->location['zoomFactor'] = $this->zoom;
        return $this->location;
    }
}