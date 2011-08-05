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

/**
 * Event plugin for Addressbook integration.
 */
class TimeItEventPluginsLocationAddressbook extends TimeItEventPluginsLocationBase
{
    protected $addressbook;
    protected $abid;
    protected $abobj;
    protected $displayMap;
    
    public function __construct()
    {
        $this->addressbook = pnModAvailable('Addressbook');
        $this->abid = 0;
        $this->abobj = array();
        $this->displayMap = false;
    }
    
    public function getName()
    {
        return 'LocationAddressbook';
    }

    public function getDisplayname()
    {
        return 'Addressbook';
    }
    
    public function edit($mode, &$render)
    {
        if($this->addressbook)
        {
            // add data
            $render->append('data', array('LocationAddressbook_displayMap'=>$this->displayMap, 'LocationAddressbook_abid'=>$this->abid,'LocationAddressbook_abobj'=>$this->abobj), true);
            
            return true;
        } else
        {
            return false;
        }
    }

    public function editPostBack($values,&$dataForDB) {
        $dataForDB['data']['plugindata'][$this->getName()] = array();
        $dataForDB['data']['plugindata'][$this->getName()]['abid'] = $values['data']['LocationAddressbook_abid'];
        $dataForDB['data']['plugindata'][$this->getName()]['displayMap'] = $values['data']['LocationAddressbook_displayMap'];

        unset($dataForDB['data']['LocationAddressbook_abid'],
              $dataForDB['data']['LocationAddressbook_search']);
    }

    /**
     * @param int $type see table zk_addressbook_labels
     */
    protected function searchContent($type) {
        $value = '';
        // search email
        for($i=1; $i<=5; $i++) {
            if($this->abobj['c_label_'.$i] == $type) {
                $value = $this->abobj['contact_'.$i];
                break;
            }
        }
        
        return $value;
    }

    protected function getFormatedData()
    {
        $latlng = explode(',', $this->abobj['geodata']);
        return array('name' => $this->abobj['fname'].' '.$this->abobj['lname'],
                     'email'         => $this->searchContent(5),
                     'phone'         => $this->searchContent(1),
                     'website'       => $this->searchContent(6),
                     'street'        => $this->abobj['address1'].' | '.$this->abobj['address2'],
                     'zip'           => $this->abobj['zip'],
                     'city'          => $this->abobj['city'],
                     'country'       => $this->abobj['country'],
                     'lat'           => $latlng[0],
                     'lng'           => $latlng[1],
                     'displayMap'    => $this->displayMap);
    }

    public function loadData(&$obj)
    {
        $this->abid = (int)$obj['data']['plugindata'][$this->getName()]['abid'];
        $this->displayMap = (bool)$obj['data']['plugindata'][$this->getName()]['displayMap'];
        if($this->abid) {
            // load class
            pnModDBInfoLoad('Addressbook');
            if (!($class = Loader::loadClassFromModule('Addressbook', 'address'))) {
                pn_exit ("Unable to load class [$ot] ...");
            }
            // load address
            $object = new $class();
            $data = $object->get($this->abid);
            $this->abobj = $data;
        }        
    }
}