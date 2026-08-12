<?php
include ("../php/CONFIG.php");
include ("../php/dbconnection.php");
$link_identifier = $con;

// ultima sessione
$query_sessione = "SELECT time FROM accessi WHERE nutenti = 0 ORDER BY time DESC LIMIT 1";
$result_sessione = mysql_query($query_sessione, $link_identifier);
if (!$result_sessione) {
    echo "Could not successfully run query ($query_ritirato) from DB: " . mysql_error($link_identifier);
    exit;
}
if (!mysql_num_rows($result_sessione)) {
	// prima sessione in assoluto
	$sstart = ZERO_DATE;
} else {
	$row = mysql_fetch_assoc($result_sessione);
	$sstart = $row['time'];
}

// ottengo la lista dei prodotti venduti nell'ultima sessione
$sql = "SELECT *
        FROM dettagli
        WHERE  time > '".$sstart."'";
//echo $sql;
$result = mysql_query($sql);
if (!$result) {
    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
    exit;
}
if (mysql_num_rows($result) == 0) { // non è stato venduto niente?
    //echo "No rows found, nothing to print so am exiting";
    exit;
}

$ingredienti = array(); // conterrà gli ingredienti
while ($row = mysql_fetch_assoc($result)) {
	// ottengo gli ingredienti di ogni prodotto
    $sql = "SELECT * FROM ricette WHERE id_prodotto = ".$row["id_prodotto"];
    //echo $sql;
    $r = mysql_query($sql, $link_identifier);
    while ($s = mysql_fetch_assoc($r)) {
    	$ingredienti[$s['id_ingrediente']] += $row['quantita'] * $s['quantita']; // aggiungo alla lista degli ingredienti
    }
}

echo json_encode($ingredienti);

mysql_free_result($result);

?>