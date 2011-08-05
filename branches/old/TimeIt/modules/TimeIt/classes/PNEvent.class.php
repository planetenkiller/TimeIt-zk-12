<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Model
 */

Loader::requireOnce('modules/TimeIt/common.php');

class PNEvent extends PNObject
{
    var $preserve = false;
    var $dheobj = null;

    function PNEvent($init=null, $where='')
    {
        // Call base-class constructor
        $this->PNObject();
         
        // set the tablename this object maps to
        $this->_objType  = 'TimeIt_events';
        
        // set the ID field for this object
        $this->_objField = 'id';
        
        // set the access path under which the object's
        // input data can be retrieved upon input
        $this->_objPath  = 'event';
        
        // Call initialization routing
        $this->_init($init, $where);
    }

    function setPreserve($b)
    {
        $this->preserve = $b;
    }
    
    function getEvent($id, $translate=false, $dheobj=null)
    {
        $this->dheobj = $dheobj;
        $ret = $this->get($id);
        
        if(!empty($ret)) {
            if($translate) {
                $user_lang = pnUserGetLang();
                if(isset($ret['title_translate'][$user_lang]) && !empty($ret['title_translate'][$user_lang]))
                {
                    $ret['title'] = $ret['title_translate'][$user_lang];
                }

                if(isset($ret['text_translate'][$user_lang]) && !empty($ret['text_translate'][$user_lang]))
                {
                    $ret['text'] = $ret['text_translate'][$user_lang];
                }
            }

            return $ret;
        }

        return false;
    }

    function insert()
    {
        $this->insertPreProcess ();                                                      // new --\
        $res = DBUtil::insertObject ($this->_objData, $this->_objType, $this->_objField, $this->_objInsertPreserve);
        if ($res)
        {
            $this->insertPostProcess ();
            return $this->_objData;
        }

        return false;
    }


    function selectPostProcess ($obj=null)
    {
        // do the things only when there's a row
        if($this->_objData) {
            $this->_objData['data'] = unserialize($this->_objData['data']);
            $this->_objData['title_translate'] = unserialize($this->_objData['title_translate']);
            $this->_objData['text_translate'] = unserialize($this->_objData['text_translate']);

            if($this->dheobj) {
                $this->_objData['dhe_id'] = $this->dheobj['id'];
                $this->_objData['dhe_eid'] = $this->dheobj['eid'];
                $this->_objData['dhe_localeid'] = $this->dheobj['localeid'];
                $this->_objData['cid'] = $this->dheobj['cid'];
                $this->_objData['dhe_date'] = $this->dheobj['date'];
            }

            if(TimeIt_decorateWitEventPlugins($this->_objData)) {
                $backup = $this->_objData['__CATEGORIES__'];
                foreach($this->_objData['__CATEGORIES__'] AS $prop => $cat) {
                    if(is_array($cat)) {
                        $this->_objData['__CATEGORIES__'][$prop] = $cat['id'];
                    }
                }
                $this->save();
                $this->_objData['__CATEGORIES__'] = $backup;
            }
        }
    }
    
    function insertPreProcess ($data=null)
    {
        if($this->_objData['data'])
            $this->_objData['data'] = serialize($this->_objData['data']);
        
        if($this->_objData['title_translate'])
            $this->_objData['title_translate'] = serialize($this->_objData['title_translate']);
            
        if($this->_objData['text_translate'])
            $this->_objData['text_translate'] = serialize($this->_objData['text_translate']);
    }
    
    function updatePreProcess ($data=null)
    {
        if($this->_objData['data'])
            $this->_objData['data'] = serialize($this->_objData['data']);
            
        if($this->_objData['title_translate'])
            $this->_objData['title_translate'] = serialize($this->_objData['title_translate']);
            
        if($this->_objData['text_translate'])
            $this->_objData['text_translate'] = serialize($this->_objData['text_translate']);
    }
    
    function insertPostProcess ($data=null)
    {
        if(!empty($obj)) {
            $this->_objData['data'] = unserialize($this->_objData['data']);
            $this->_objData['title_translate'] = unserialize($this->_objData['title_translate']);
            $this->_objData['text_translate'] = unserialize($this->_objData['text_translate']);
        }
    }
    
    function updatePostProcess ($data=null)
    {
        if(!empty($obj)) {
            $this->_objData['data'] = unserialize($this->_objData['data']);
            $this->_objData['title_translate'] = unserialize($this->_objData['title_translate']);
            $this->_objData['text_translate'] = unserialize($this->_objData['text_translate']);
        }
    }
}

    
    