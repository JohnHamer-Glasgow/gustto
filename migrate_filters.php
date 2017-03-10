<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/corelib/dataaccess.php');

$tts = array();

$query = "SELECT * from teachingtip";
$result = dataConnection::runQuery($query);
if (sizeof($result) != 0) {
	$tts = array();
	foreach($result as $r){
		$tt = new teachingtip($r);
		array_push($tts, $tt);
	}	
} 

foreach ($tts as $tt) {
	if (!empty($tt->class_size)) {
    echo '<pre>'.print_r($tt, true).'<pre>';
		$f = new ttfilter();
		$f->teachingtip_id = $tt->id;
		$f->category = "class_size";
		$f->opt = $tt->class_size;
		$f->insert();
	}
	if (!empty($tt->environment)) {
		$f = new ttfilter();
		$f->teachingtip_id = $tt->id;
		$f->category = "environment";
		$f->opt = $tt->environment;
		$f->insert() ;
	}
	if (!empty($tt->suitable_online_learning)) {
		$f = new ttfilter();
		$f->teachingtip_id = $tt->id;
		$f->category = "suitable_ol";
		$f->opt = $tt->suitable_online_learning;
		$f->insert() ;
	}
	if (!empty($tt->it_competency)) {
		$f = new ttfilter();
		$f->teachingtip_id = $tt->id;
		$f->category = "it_competency";
		$f->opt = $tt->it_competency;
		$f->insert() ;
	}
}




