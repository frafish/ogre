<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");

$sstart = get_start_time();

$ingredienti = get_vendita_ingredienti();

echo json_encode($ingredienti);