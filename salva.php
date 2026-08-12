<?php
// verifico che la sessione non sia scaduta
/*if (!isset($_SESSION['id_cassa'])) {
	// rispedisco indietro a fare nuovamente il login
	header("Location: ".$_SERVER['HTTP_REFERER']);
}*/

include_once ("lib/dbconnection.php");

if (!is_session_started() && !get_option('sessione-automatica')) {
	set_alert("Operazione negata: sessione chiusa.", 'danger');
	header('Location: index.php');
	die();
}

//echo '<pre>'; var_dump($_REQUEST); print_r($_SESSION); echo '</pre>'; die();


// creazione nuova testata

//$tipo = $_REQUEST['tipo'];
$versato = $_REQUEST['versato'];
$totale = (floatval($_REQUEST['totale']) < 0) ? 0 : $_REQUEST['totale'];
$resto = number_format($versato - $totale, 2);
$asporto = (isset($_REQUEST['asporto'])) ? '1' : '0';
$note = normalize_text($_REQUEST['note']);
$coperti = (isset($_REQUEST['quantita-coperti'])) ? $_REQUEST['quantita-coperti'] : 0;
$omaggi = (isset($_REQUEST['quantita-coperti-omaggio'])) ? $_REQUEST['quantita-coperti-omaggio'] : 0;	
$cliente = $_REQUEST['cliente'];	
$tavolo = $_REQUEST['tavolo'];	
$time = time();
$timestamp = date("Y-m-d H:i:s", $time);
$pos = (isset($_REQUEST['pos'])) ? '1' : '0';
// nome della cassa	
$cassa = get_cassa($_REQUEST['cassa']);
$id_utente = $_REQUEST['utente'];
//$cassa_nome = $row["nome"];

//var_dump($_REQUEST['quick-view']); die();
$_SESSION['compact-view'] = (isset($_REQUEST['compact-view'])) ? true : false;

// trovo il progressivo della cassa
/*$query_progressivo = "SELECT COUNT(*) FROM testate"; // WHERE id_cassa = ".$_REQUEST['ocassa'];
$result = mysql_query($query_progressivo, $con);
if (!$result) {echo mysql_error($con); }
$row = mysql_fetch_array($result);
$last = $row[0];*/
//$query_progressivo = "SELECT * FROM testate ORDER BY id DESC LIMIT 1"; // WHERE id_cassa = ".$_REQUEST['ocassa'];

// se modifico una gia esistente
if(isset($_REQUEST['id-testata'])) {
	$id_testata = $_REQUEST['id-testata'];
	$testata = find('testate', $id_testata);
	$progressivo = $testata['progressivo'];
	//$timestamp = $testata['time'];
	$warning_text = 'ATTENZIONE: ORDINE MODIFICATO (Annulla e sostituisce precedente)';
	if (strpos($note, $warning_text) === false) {
		$note = $warning_text . "\n" . $note;
	}
	$query = 'UPDATE testate SET coperti = ?, omaggi = ?, pagato = ?, pos = ?, asporto = ?, totale = ?, note = ?, tavolo = ?, cliente = ?, time = ? WHERE id = ?';
	//echo "<h2>$query</h2>";
	db_query($query, false, true, [$coperti, $omaggi, $versato, $pos, $asporto, $totale, $note, $tavolo, $cliente, $timestamp, $testata['id']]);
} else {
	$last_id = find_one_by('testate', null, 'id DESC', 'id');
	$progressivo = get_next_progressivo(); //($last_id + 1) % get_option('max-progressivo');//1000 // numero massimo consentito è il 999, poi ricomincia da 0
	//echo $progressivo;
	// creo la testata
	$query = 'INSERT INTO testate (progressivo, coperti, omaggi, pagato, asporto, id_casse, id_utenti, totale, note, tavolo, cliente, time, pos) '.
	         'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	//echo "<h2>$query</h2>";
	$id_testata = db_query($query, false, true, [$progressivo, $coperti, $omaggi, $versato, $asporto, $cassa['id'], $id_utente, $totale, $note, $tavolo, $cliente, $timestamp, $pos]);
	// non mi fido di questo, se ho più client in contemporanea rischio di sormontare la richiesta con conseguente id doppio (INSERT > INSERT > ID)
	$id_testata = find_one_by('testate', "time = '".$timestamp."' AND id_casse = ".$cassa['id']." AND totale = ".$totale, null, 'id');
}

if(!$id_testata) {
	echo "<h2>ERRORE: testata non inserita con la seguente query: ".$query."</h2>";
}

/*
// verifico il progressivo
if ($progressivo != $id_testata % 1000) {	
	dev_log('Ricevute con progressivo doppio in db!');
	$progressivo = $id_testata % 1000;
	
	// correggo il progressivo
	$query = "UPDATE testate SET progressivo = ".$progressivo." WHERE ID = ".$id_testata;
	//echo "<h2>$query</h2>";
	$result = db_query($query);
	
	// dovrei verificare che le altre siano a posto?!
	$query = "SELECT * FROM testate WHERE id % 1000 != progressivo";
	$result = mysql_query($query, $con);
	if (!$result) { echo mysql_error($con); }
}
*/

// controllo quali fogli devo stampare
// se ci sono bar stampo il foglio cliente e bar
// se ci sono cibi stampo foglio cliente e cucina
$reparti = find_by('reparti');
$reparti_attivi = array();
foreach($reparti as $rkey => $rvalue) {
		$reparti_attivi[$rvalue['id']] = 0;
}

// se modifico la comanda elimino le vecchie righe di dettagli prima di ricrearle aggiornate
if(isset($_REQUEST['id-testata'])) {
	db_query('DELETE FROM dettagli WHERE id_testate = '.$id_testata);	
}

$prodotti = find_by('prodotti'); // array con le info di tutti i prodotti
$ritotale = 0;
foreach ($_REQUEST['prodotto'] as $pid => $adettaglio) {
	$qnt_omaggio = (isset($adettaglio['omaggio'])) ? $adettaglio['omaggio'] : 0;
	$qnt = $adettaglio['quantita'] + $qnt_omaggio;
	if ($qnt) {
		$nota = (trim($adettaglio['nota'])) ? normalize_text($adettaglio['nota']) : '';
		//dev_log('Nota: '.var_dump(nl2br(htmlentities($adettaglio['nota'])), true));
		// creo le voci del dettaglio
		$query = 'INSERT INTO dettagli (id_testate, id_prodotti, quantita, omaggio, nota) VALUES (?, ?, ?, ?, ?)';
		//dev_log('Query salvataggio dettaglio: '.$query);
		$dettaglio = db_query($query, false, true, [$id_testata, $pid, $adettaglio['quantita'], $qnt_omaggio, $nota]);
		$reparti_attivi[$prodotti[$pid]['id_reparti']]++;		
		$ritotale += $adettaglio['quantita'] * $prodotti[$pid]['prezzo'];
	}
}	
	
if ($ritotale != floatval($totale) && ($ritotale > 0 && $totale > 0)) {
	dev_log('Totali ['.$id_testata.'] non uguali: '.$totale.' -- '.$ritotale);
}


// verifico se per i prodotti della comanda c'è bisogno di lavorazione
$da_preparare = false;
foreach($reparti_attivi as $rkey => $reparto) {
	if ($reparto && $reparti[$rkey]['fila']) {
		$da_preparare = true;	
	}
}
// se non sono destinate alla cucina le considero già come concluse
if (!$da_preparare) {
	$sql = 'UPDATE testate SET consegnato = ?, preparazione = ?, ritirato = ? WHERE id = ?';	
	$result = db_query($sql, false, true, [$timestamp, $timestamp, $timestamp, $id_testata]);
}

/*	
$dettaglioplus['nome'] = $row['nome'];
$dettaglioplus['prezzo'] = $row['prezzo'];
$dettaglioplus['id_categoria'] = $row['id_categoria'];
$dettaglioplus['id_reparto'] = $row['id_reparto'];
*/

if (!$id_testata) {
	die();
}

aggiorna_quantita_prodotti();

include("stampa.php");

if(isset($_REQUEST['id-testata'])) {
	header("Location: cassa.php?action=update&id=".$_REQUEST['id-testata']);
	die();
}

header("Location: cassa.php");

?>
