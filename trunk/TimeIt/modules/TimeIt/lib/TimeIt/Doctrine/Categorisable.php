<?php

/**
 * This template manages all record concerns related to it's dynamic categories.
 *
 * @see CategorisableListener
 */
class TimeIt_Doctrine_Categorisable extends Doctrine_Template
{
    public function setUp()
    {
        // Turn on object categorisation.
        // This listener gets a name to be able to disable and reenable it dynamically during runtime.
        $this->addListener(new TimeIt_Doctrine_CategorisableListener(), 'CategoryListener');
    }

    public function setCategories($cats) {
        $rec = $this->getInvoker();

        $rec->mapValue('__CATEGORIES__', $cats);
        $rec->state(Doctrine_Record::STATE_TDIRTY);
    }

    public function getCategories() {
        $rec = $this->getInvoker();

        if(isset($rec['__CATEGORIES__'])) {
            return $rec['__CATEGORIES__'];
        } else {
            return array();
        }
    }
}

