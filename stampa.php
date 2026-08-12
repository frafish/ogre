<?php

include_once('lib'.DIRECTORY_SEPARATOR.'print.php');

/**************************************************************************************************/
//			OTTENGO LE INFO NECESSARIE DA DB
/**************************************************************************************************/

$testata = find_one_by('testate', $id_testata);
$id_cassa = $testata['id_casse'];

// trovo la stampante predefinita per la cassa corrente
$cassa = get_cassa($id_cassa);	
$defaultPrinter = $cassa['id_stampanti'];
// se la stampanta usata non è quella predefinita allora la metto in default di sessione e aggiorno il DB
if (isset($_REQUEST['stampante'])) {
	$id_printer = $_REQUEST['stampante'];
	if ($id_printer != $defaultPrinter) {
		db_query("UPDATE casse SET id_stampanti = " . (int)$id_printer . " WHERE id = " . (int)$id_cassa);
	}
} else {
	$id_printer = $defaultPrinter;
}
$_SESSION['id_stampante'] = $id_printer;

// cerco info sulla stampante in locale
$printers = get_printers(true);
$printer = $printers[$id_printer];

/**************************************************************************************************/
//			CREO TUTTI I POSSIBILI PDF
/**************************************************************************************************/

if (!is_dir(CARTELLA_RICEVUTE)) {
	mkdir(CARTELLA_RICEVUTE, 0777, true);
	@chmod(CARTELLA_RICEVUTE, 0777);
}

$nome_pdf = get_filename_by_id($id_testata);
$output_pdfs = array();
$fogli = array();
$reparti_attivi_cassa = array();

// creo il pdf con la ricevuta cliente
$tipo_ricevuta = $dir = CARTELLA_RICEVUTA_CLIENTE;
$fogli[] = $tipo_ricevuta;
$reparti_attivi_cassa[0] = $tipo_ricevuta;
$output_pdfs[0] = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$tipo_ricevuta.DIRECTORY_SEPARATOR.$nome_pdf;
crea_pdf($id_testata, reset($output_pdfs), $printers[$id_printer]['formato']);

/*
$dettagli = get_prodotti_plus($id_testata);
$reparti = find_by('reparti');
$reparti_attivi = array();
foreach($dettagli as $dkey => $dvalue) {
	$reparto = $reparti[$dvalue['id_reparti']];
	$reparti_attivi[$dvalue['id_reparti']] = $reparto;
}
*/
$reparti_attivi = get_reparti($id_testata);
//var_dump($reparti_attivi);
foreach($reparti_attivi as $reparto) {
	$tipo_ricevuta = $dir = slugify($reparto['nome']);
	// creo il pdf con la ricevuta per il reparto
	if($reparto['ricevuta']) {
		$fogli[] = $tipo_ricevuta;
		$formato_ricevuta = null;
		if ($reparto['id_stampanti'] > 0) { 
			$formato_ricevuta = $printers[$reparto['id_stampanti']]['formato'];
		} else {
			if ($id_stampante) {
				$formato_ricevuta = $printers[$id_stampante]['formato'];
			}
		}
		$output_pdfs[$reparto['id']] = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$tipo_ricevuta.DIRECTORY_SEPARATOR.$nome_pdf;
		crea_pdf($id_testata, $output_pdfs[$reparto['id']], $formato_ricevuta, $reparto['id']);
		if (!$reparto['id_stampanti']) { 
			$reparti_attivi_cassa[$reparto['id_reparti']] = $tipo_ricevuta;
		}
	}
}


// creo il documento unificato per la cassa
$tipo_ricevuta = $dir = CARTELLA_RICEVUTA_CASSA;
$output_cassa = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$tipo_ricevuta.DIRECTORY_SEPARATOR.$nome_pdf;
crea_pdf($id_testata, $output_cassa, $printers[$id_printer]['formato'], null, $reparti_attivi_cassa);

// creo il documento unico
$tipo_ricevuta = $dir = CARTELLA_RICEVUTA_COMPLETA;
$output_pdfs[] = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$tipo_ricevuta.DIRECTORY_SEPARATOR.$nome_pdf;
crea_pdf($id_testata, end($output_pdfs), $printers[$id_printer]['formato'], null, $fogli);

/**************************************************************************************************/
//			STAMPO I PDF SECONDO LE ESIGENZE DELLA CASSA
/**************************************************************************************************/

if (empty($skip_print)) {
// stampo la copia della ricevuta per il cliente
if($id_printer < 0) {

	// stampa locale (devo unire i 4 pdf in uno unico)
	$_SESSION['print'] = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.CARTELLA_RICEVUTA_COMPLETA.DIRECTORY_SEPARATOR.$nome_pdf; // stampa da locale con conferma
	
} else {
	
	$usa_ssh = false;

	// stampo la ricevuta del cliente
	// se la cassa è da asporto stampo tutti i fogli dalla stampante della cassa
	if ($testata['asporto'] && get_option('asporto-stampa')) {
		$dir = CARTELLA_RICEVUTA_COMPLETA;
	} else {
		$dir = CARTELLA_RICEVUTA_CASSA;
	}	
   cmd_print(CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$nome_pdf, $id_printer, ($usa_ssh) ? $ssh : null);


	// se la cassa stampa il foglio per la cucina con la stampante remota in cucina
	foreach($reparti_attivi as $arep) {
		if ($reparti[$arep['id']]['ricevuta'] && $reparti[$arep['id']]['id_stampanti']) {
			$dir = slugify($reparti[$arep['id']]['nome']);
			cmd_print(CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$nome_pdf, $reparti[$arep['id']]['id_stampanti'], ($usa_ssh) ? $ssh : null);
		}
	}

}
}
