#!/usr/bin/env php
<?php

require_once dirname(dirname($_SERVER['SCRIPT_FILENAME'])).'/ZkUnit/zkunit.php';

ZkUnit_phpunitCommandLineStartup('TimeIt', dirname(__FILE__)); 


