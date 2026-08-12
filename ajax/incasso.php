<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");

$somma = 0;	
if (isset($_SESSION['id_cassa'])) {
	//echo "Cassa ".$_SESSION['id_cassa'];
	$sstart = get_start_time();
		
	$query = "SELECT sum(totale) as somma FROM testate WHERE id_casse = ".$_SESSION['id_cassa']." AND time > '".$sstart."'"; 
	$uresult = db_query($query);
	
	if(!empty($uresult)) {
		$somma = $uresult[0]['somma'];
	}
}

echo $somma;