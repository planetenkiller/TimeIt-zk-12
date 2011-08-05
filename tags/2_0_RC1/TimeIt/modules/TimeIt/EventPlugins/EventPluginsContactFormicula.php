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
    
class TimeItEventPluginsContactFormicula extends TimeItEventPluginsContactBase
{
    protected $formicula;
    protected $formid;
    protected $cid;
    protected $contact;
    protected $eid;
    protected $dheid;
    protected $startdate;
    
    public function __construct()
    {
        $this->formicula = pnModAvailable('formicula');
        $this->formid = -1;
        $this->contact = array();
        $this->cid = 0;
    }
    
    public function getName()
    {
        return 'ContactFormicula';
    }
    
    public function edit($mode, &$render)
    {
        if($this->formicula && $this->formid)
        {
            $formiculaContacts = pnModAPIFunc('formicula','user','readValidContacts',array('form'=>$this->formid));
            $formiculaContactItems = array();
            foreach($formiculaContacts AS $row)
            {
                $formiculaContactItems[] = array('text'=>$row['name'],'value'=>$row['cid']);
            }

            // add data
            $render->append('data', array('ContactFormicula_cid'=>$this->cid,'ContactFormicula_cidItems'=>$formiculaContactItems,'ContactFormicula_contact'=>'choose'), true);
            
            return true;
        } else
        {
            return false;
        }
    }

    public function editPostBack($values,&$dataForDB) {
        $dataForDB['data']['plugindata'][$this->getName()] = array();
        $dataForDB['data']['plugindata'][$this->getName()]['cid'] = $values['data']['ContactFormicula_cid'];

        unset($dataForDB['data']['ContactFormicula_cid'],
              $dataForDB['data']['ContactFormicula_contact']);
    }
    
    protected function getFormatedData()
    {
        return array('contactPerson' => $this->contact['name'],
                     'email'         => $this->contact['email']);
    }

    public function loadData(&$obj)
    {
        if($obj['cid']) {
            $calendar = pnModAPIFunc('TimeIt','calendar','get',$obj['cid']);
            $this->formid = (int)$calendar['formiculaFormId'];
        }

        $this->cid = (int)$obj['data']['plugindata'][$this->getName()]['cid'];
        if($this->cid) {
            $this->contact = pnModAPIFunc('formicula','user','getContact',array('cid'=>$this->cid,'form'=>$this->formid));
        }
        $this->eid = $obj['id'];
        $this->dheid = $obj['dhe_id'];
        $this->startdate = $obj['startDate'];

        if($obj['cid']) {
            $calendar = pnModAPIFunc('TimeIt','calendar','get',$obj['cid']);
            $this->formid = (int)$calendar['formiculaFormId'];
        }
        
    }
    
    public function displayAfterDesc(&$args)
    {
        if($this->formicula && $this->formid && $this->cid) {
            $pnRender = pnRender::getInstance('TimeIt');
            $pnRender->assign('formicula_addinfo', array('eid'=>$this->eid,'dheid'=>$this->dheid));
            $pnRender->assign('formicula_formid', $this->formid);
            $pnRender->assign('formicula', pnModAPIFunc('formicula','user','getContact',array('cid'=>$this->cid,'form'=>$this->formid)));
            $pnRender->assign('formicula_cid', $this->cid);
            $pnRender->assign('displayS', $args['displayS']);
            return $pnRender->fetch('eventplugins/TimeIt_eventplugins_contactformicula.htm');
        } else {
            return '';
        }
    }
}