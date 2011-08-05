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
    
Loader::loadFile('EventPluginsContactBase.php','modules/TimeIt/classes/EventPlugins');

/**
 * Event plugin for Addressbook integration.
 */
class TimeItEventPluginsContactAddressbook extends TimeItEventPluginsContactBase
{
    protected $addressbook;
    protected $abid;
    protected $abobj;
    
    public function __construct()
    {
        $this->addressbook = pnModAvailable('AddressBook');
        $this->abid = 0;
        $this->abobj = array();
    }

    public static function dependencyCheck()
    {
        return pnModAvailable('AddressBook');
    }
    
    public function getName()
    {
        return 'ContactAddressbook';
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
            $render->append('data', array('ContactAddressbook_abid'=>$this->abid,'ContactAddressbook_abobj'=>$this->abobj), true);
            
            return true;
        } else
        {
            return false;
        }
    }

    public function editPostBack($values,&$dataForDB)
    {
        $dataForDB['data']['plugindata'][$this->getName()] = array();
        $dataForDB['data']['plugindata'][$this->getName()]['abid'] = $values['data']['ContactAddressbook_abid'];

        unset($dataForDB['data']['ContactAddressbook_abid'],
              $dataForDB['data']['ContactAddressbook_search']);
    }

    /**
     * @param int $type see table zk_addressbook_labels
     */
    protected function searchContent($type)
    {
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
        return array('contactPerson' => $this->abobj['fname'].' '.$this->abobj['lname'],
                     'email'         => $this->searchContent(5),
                     'phoneNr'       => $this->searchContent(1),
                     'website'       => $this->searchContent(6),
                     'address'       => $this->abobj['address1'].' | '.$this->abobj['address2'],
                     'zip'           => $this->abobj['zip'],
                     'city'          => $this->abobj['city'],
                     'country'       => $this->abobj['country'],
                     'url_details'   => pnModURL('AddressBook','user','display',array('id'=>$this->abobj['id'], 'ot' => 'address')));
    }

    public function loadData(&$obj)
    {
        $this->abid = (int)$obj['data']['plugindata'][$this->getName()]['abid'];
        if($this->abid) {
            // load class
            pnModDBInfoLoad('AddressBook');
            if (!($class = Loader::loadClassFromModule('AddressBook', 'Address'))) {
                pn_exit ("Unable to load class [$ot] ...");
            }
            // load address
            $object = new $class();
            $data = $object->get($this->abid);
            $this->abobj = $data;
        }        
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