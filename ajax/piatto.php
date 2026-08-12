<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");

$id = $_REQUEST['id'];
$time = date('Y-m-d H:i:s');//'0000-00-00 00:00:00'

$stato = $_GET['stato'];

if ($stato == 'back') {
	$testata = find('testate', $id);
	if ($testata['preparazione'] != ZERO_DATE) {
		$stato = 'preparazione';
	}
	if ($testata['consegnato'] != ZERO_DATE) {
		$stato = 'consegnato';
	}
	if ($testata['ritirato'] != ZERO_DATE) {
		$stato = 'ritirato';
	}
	$time = ZERO_DATE;
}

if($stato == 'asporto') {
	$testata = find('testate', $id);
	if(!$testata['asporto']) {
		echo $testata['asporto'];
	} else {
		echo $id;	
	} 
	die();
}

$query = "UPDATE testate SET ".$stato." = '".$time."' WHERE id = ".$id;
//echo $query_ritirato;
$result = db_query($query);

echo $id;
//echo $stato;