<?php

/**
 * This behavior takes care for adding the standard fields if desired.
 *
 * @property string $obj_status object status (default = 'A' for Active)
 * @property timestamp $cr_date creation date timestamp
 * @property integer $cr_uid creator userid
 * @property timestamp $lu_date update date timestamp
 * @property integer $lu_uid update userid
 * @see TimeIt_Doctrine_StandardFieldsListener
 */
class TimeIt_Doctrine_StandardFields extends Doctrine_Template
{
    /**
     * Array of options
     *
     * @var array
     */
    protected $_options = array('oldColumnPrefix' => '');

    /**
     * __construct
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
    }

    public function setTableDefinition()
    {
        // historical prefix
        $oldPrefix = $this->_options['oldColumnPrefix'];

        $this->hasColumn($oldPrefix . 'obj_status as obj_status', 'string', 1, array('type' => 'string', 'length' => 1, 'notnull' => true, 'default' => 'A'));
        $this->hasColumn($oldPrefix . 'cr_date as cr_date', 'timestamp', null, array('type' => 'timestamp', 'notnull' => true, 'default' => '1970-01-01 00:00:00'));
        $this->hasColumn($oldPrefix . 'cr_uid as cr_uid', 'integer', 4, array('type' => 'integer', 'notnull' => true, 'default' => '0'));
        $this->hasColumn($oldPrefix . 'lu_date as lu_date', 'timestamp', null, array('type' => 'timestamp', 'notnull' => true, 'default' => '1970-01-01 00:00:00'));
        $this->hasColumn($oldPrefix . 'lu_uid as lu_uid', 'integer', 4, array('type' => 'integer', 'notnull' => true, 'default' => '0'));
    }

    public function setUp()
    {
        // take care for setting these values automatically
        $this->addListener(new TimeIt_Doctrine_StandardFieldsListener());
    }
}

