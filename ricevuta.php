<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
include_once("lib/print.php");

ini_set("display_errors", 0);

if (isset($_GET['id_testata'])) {
	$id_testata = $_GET['id_testata'];
}
if (isset($_GET['id_testate'])) {
	$id_testata = $_GET['id_testate'];
}
if (isset($_GET['id'])) {
	$id_testata = $_GET['id'];
}

$force_print = false;
if (isset($_GET['print'])) {
	$force_print = true;
}

//$ricevuta = $_GET['ricevuta'];

$formato_carta = (isset($_GET['formato_carta'])) ? $_GET['formato_carta'] : null;

$id_reparto = (isset($_GET['id_reparti'])) ? $_GET['id_reparti'] : null;

crea_pdf($id_testata, null, $formato_carta, $id_reparto, null, $force_print);

