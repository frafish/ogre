<?php
include ("php/CONFIG.php");
include ("php/dbconnection.php");

//print_r($_SESSION);

$uquery = "UPDATE testate SET consegnato = time, preparazione = time, ritirato = time WHERE consegnato = '0000-00-00 00:00:00'";
$uresult = mysql_query($uquery, $link_identifier);
if (!$uresult) {
 echo "Could not successfully run query ($uquery) from DB: " . mysql_error($con);
 exit;
}
$uquery = "UPDATE testate SET preparazione = consegnato, ritirato = consegnato WHERE preparazione = '0000-00-00 00:00:00'";
$uresult = mysql_query($uquery, $link_identifier);
if (!$uresult) {
 echo "Could not successfully run query ($uquery) from DB: " . mysql_error($con);
 exit;
}
$uquery = "UPDATE testate SET ritirato = preparazione WHERE ritirato = '0000-00-00 00:00:00'";
$uresult = mysql_query($uquery, $link_identifier);
if (!$uresult) {
 echo "Could not successfully run query ($uquery) from DB: " . mysql_error($con);
 exit;
}

?>
<html>
<head>
<title>ADMIN</title>
</head>


<body>

<h1>Le comande sospese sono state chiuse.</h1>


<?php


/*
// l'ultima sessione	
$query = "SELECT time FROM accessi WHERE nutenti = 0 ORDER BY time ASC LIMIT 1";
$result = mysql_query($query, $link_identifier);
if (!$result) {
	    echo "Could not successfully run query ($query) from DB: " . mysql_error($con);
	    exit;
	}
if(mysql_num_rows($result)) {
	$row = mysql_fetch_array($result);
	$sstart = $row[0];
} else {
	$sstart = '0000-00-00 00:00:00';
}

$aquery = "SELECT * FROM accessi WHERE time > '".$sstart."' AND act LIKE 'in' ORDER BY time";
$aresult = mysql_query($aquery, $link_identifier);
if (!$aresult) {
 echo "Could not successfully run query ($aquery) from DB: " . mysql_error($con);
 exit;
}

if(mysql_num_rows($aresult)) {

	echo "<h2>Sessione terminata</h2>";
	
	$nuser = 0;
	while ($arow = mysql_fetch_array($aresult)) {
		
		$bquery = "SELECT id_utente FROM accessi WHERE id_utente = ".$arow['id_utente']." AND time > '".$arow['time']."' AND act LIKE 'out'";
		$bresult = mysql_query($bquery, $link_identifier);
		if (!$bresult) {
	    echo "Could not successfully run query ($bquery) from DB: " . mysql_error($con);
	    exit;
		}
		if(!mysql_num_rows($bresult)) {
				//ad ogni logout si decrementa il numero attuale di utenti
				$query_last = "SELECT * FROM accessi ORDER BY time DESC LIMIT 1";
				$result_last = mysql_query($query_last, $link_identifier);
				if (!$result_last) {
				    echo "Could not successfully run query ($query_last) from DB: " . mysql_error($link_identifier);
				    exit;
				}
				$row = mysql_fetch_assoc($result_last);
				$nutenti = $row['nutenti'];
				$nutenti--;
				// inserisco la nuova riga aggiornando il contatore
				mysql_query("INSERT INTO accessi (nutenti, id_utente, act) VALUES (".$nutenti.", ".$arow['id_utente'].", 'out')", $link_identifier);
		}	

	}
}	
*/
?>

</body>

</html>