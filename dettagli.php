<?php
include("CONFIG.php");
include ("lib/dbconnection.php");

$ids = array();
if(isset($_GET['id'])) {
	$ids[] = $_GET['id'];
} else {
	$sql = "SELECT id FROM testate";
	if (isset($_GET['t'])) {
	   list($data,$ora) = explode(" ", $trow['time']);
   	list($aa,$mm,$gg) = explode("-", $data);
   	list($hh,$mi,$ss) = explode(":", $ora);
   	$time = date("Y-m-d H:i:s", $_GET['t']); //2012-08-23 19:08:21
	   $sql .= " WHERE time = '".$time."'";
	}
	$result = mysql_query($sql);
	if (!$result) {
	    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
	    exit;
	}
	while ($row = mysql_fetch_array($result)) {
		$ids[] = $row[0];
	}
}

?>


<ul>
<?php

foreach ($ids as $id) {
	
	$sql = "SELECT * FROM testate WHERE testate.id = ".$id;
	$result = mysql_query($sql);
	if (!$result) {
	    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
	    exit;
	}
	$trow = mysql_fetch_assoc($result);
	
	echo "<li><hr>";
	
	// conversione in time
	list($data,$ora) = explode(" ", $trow['time']);
	list($aa,$mm,$gg) = explode("-", $data);
	list($hh,$mi,$ss) = explode(":", $ora);
	$time = mktime($hh,$mi,$ss, $mm, $gg, $aa);
	
	echo '<a name="t'.$time.'"><h1>'.$trow['id'].' (Progressivo '.$trow['progressivo'].')</h1></a>';
	
	$sql = "SELECT * FROM testate, dettagli, prodotti WHERE testate.id = dettagli.id_testata AND dettagli.id_prodotto = prodotti.id AND testate.id = ".$id;
	$result = mysql_query($sql);
	if (!$result) {
	    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
	    exit;
	}
	
	$totale = 0;
	if (mysql_num_rows($result)) {
		echo "<ul>";
		while ($row = mysql_fetch_assoc($result)) {
			//print_r($row);
			echo '<li>'.$row['quantita'].' x '.$row['nome'].'</li>';
			$totale = $row['totale'];
		}
		echo "</ul>";
	}
	
	echo '<h2>TOTALE: '.$trow['totale'].'&euro;</h2>';
	echo '<h2>VERSATO: '.$trow['pagato'].'&euro;</h2>';
	echo '<h2>RESTO: '.($trow['pagato'] - $trow['totale']).'&euro;</h2>';
	echo "<hr></li>";
}

?>
</ul>