<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");

$id_testata = $_REQUEST['id'];

$dettagli = find_by('dettagli', "id_testata = ".$id_testata);
foreach ($dettagli as $did => $dettaglio) {
	$prodotto = find('prodotti'. $dettaglio['id_prodotto']);
	echo '<li>' . $dettaglio['quantita'] . ' - ' . $prodotto['nome'].'</li>';
}
?>		