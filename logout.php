<?php
include ("CONFIG.php");
include ("lib/dbconnection.php");

$sstart = get_start_time();
$send = date('Y-m-d H:i:s');

if(isset($_REQUEST['id_utente'])) {
	$id_utente = $_REQUEST['id_utente'];
} else {
	$id_utente = $_SESSION['id_utente'];
}

user_logout($id_utente);

if(!isset($_REQUEST['id_utente'])) {
	termina_sessione();
} else {
	header("Location: /sessioni.php");
	//header("Location: ".$_SERVER['HTTP_REFERER']);
}

?>