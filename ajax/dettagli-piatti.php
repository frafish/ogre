<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");
include_once ("../lib/view_helpers.php");

$id_testata = $_REQUEST['id'];
$id_reparto = $_REQUEST['reparto'];

echo get_html_dettagli_piatti($id_testata, $id_reparto);
?>