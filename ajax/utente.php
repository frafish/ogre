<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");

if (is_logged_in()) {
	echo '1';
} else {
	echo '0';
} 
?>