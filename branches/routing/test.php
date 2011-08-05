<?php

include 'tiRoutingIf.class.php';
include 'tiDefaultRouting.class.php';
include 'tiRoute.class.php';

$routing = new tiDefaultRouting();
$routing->set('a', new tiRoute('TimeIt/:viewtype/:day-:month-:year', array('module'=>'TimeIt','calendar'=>1), array('day'=>'\d+','month'=>'\d+','year'=>'\d+')));
$routing->set('b', new tiRoute('TimeIt/:calendar/:viewtype/:day-:month-:year', array('module'=>'TimeIt'), array('day'=>'\d+','month'=>'\d+','year'=>'\d+','calendar'=>'\d+')));
$routing->set('c', new tiRoute('TimeIt/event/:eid/:dhe_id', array('module'=>'TimeIt'), array('eid'=>'\d+','dhe_id'=>'\d+')));

echo 'test:<br/>';
var_dump($routing->parse('TimeIt/month/8-6-2009/key1/val2/key2/val2'));
var_dump($routing->parse('TimeIt/2/month/8-6-2009/key1/val2/key2/val2'));
var_dump($routing->parse('TimeIt/event/111/22/key1/val1/key2/val2'));
echo '<br/><br/>';

echo $routing->generate(null, array('viewtype'=>'month','day'=>1,'month'=>1,'year'=>2000,'key1'=>'val1','key2'=>'val2'));
echo '<br/>';
echo $routing->generate(null, array('calendar'=>'2','viewtype'=>'month','day'=>1,'month'=>1,'year'=>2000,'key1'=>'val1','key2'=>'val2'));
echo '<br/>';
echo $routing->generate(null, array('eid'=>111,'dhe_id'=>22,'key1'=>'val1','key2'=>'val2'));


echo '<br/><br/>';
$time_start = microtime(true);
for($i=0;$i<100;$i++) {
    $a = $routing->generate(null, array('eid'=>111,'dhe_id'=>22,'key1'=>'val1','key2'=>'val2'));
}
$time_end = microtime(true);
echo '<br/>Zeit(ms) generate 100x:'.(($time_end-$time_start)*1000);

echo '<br/><br/>';
$time_start = microtime(true);
for($i=0;$i<100;$i++) {
    $a = $routing->parse('TimeIt/event/111/22/key1/val1/key2/val2');
}
$time_end = microtime(true);
echo '<br/>Zeit(ms) parse 100x:'.(($time_end-$time_start)*1000);