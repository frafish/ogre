<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
include_once("lib/print.php");

//print_r($_REQUEST);
//print_r($_SESSION);
if (!isset($_GET['id_testate'])) {
	set_alert('Nessun id progressivo, scegli quale ricevuta vuoi ristampare!', 'warning');		
	header('Location: storico.php');
	die();
}
$id_testata = $_GET['id_testate'];
$testata = find('testate', $id_testata);
$nome_pdf = get_filename_by_id($id_testata);
//$ricevuta = $_GET['ricevuta'];

if(isset($_REQUEST['btn-print'])) {
	
	// trovo la stampante predefinita per la cassa corrente
	$id_printer = (isset($_REQUEST['stampante'])) ? $_REQUEST['stampante'] : null;
	if ($id_printer == null) {
		$id_printer = get_printer();
	} else {	
		$_SESSION['id_stampante'] = $id_printer;
	}	
	$printer = get_printer($id_printer);
	
	$tmp = explode('-', $_REQUEST['btn-print']);
	$ricevuta = trim(end($tmp));
	
	$filename = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$ricevuta.DIRECTORY_SEPARATOR.$nome_pdf;
	//echo '<h1>'.$filename.'</h1>';
	// creo o aggiorno il file pdf
	crea_pdf($id_testata, $filename);
	
	if($id_printer >= 0) {	
		if (file_exists($filename)) {
			// stampa di rete
			cmd_print($filename, $id_printer);
			$alert = 'Stampato il file <strong>'.$filename.'</strong> sulla stampante <strong>'.$printer['nome'].'</strong>';
			set_alert($alert, 'success');
		} else {
			$alert = 'File <strong>'.$filename.'</strong> non trovato!';
			set_alert($alert, 'error');		
		}
	} else {
		$alert = 'Se intendi stampare il file <strong>'.$filename.'</strong> da locale lo trovi a disposizione qui sotto!';
		set_alert($alert, 'warning');	
	}
	dev_log($alert);
}
?>	
<html>
<head>
	<title>RISTAMPA</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="css/base.css" />
	
	<script src="js/jquery-1.11.1.min.js"></script>
</head>

<body>

<div class="container-fluid">

<a href="/" class="btn btn-default btn-lg btn-back pull-left"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
<h1 class="title">RISTAMPA:</h1>

<?php
if(is_user()) { // controllo se l'utente è autenticato 
?>

<?php print_alerts(); ?>

<h2>ID: <?php echo $id_testata; ?> - PROGRESSIVO: <?php echo $testata['progressivo']; ?></h2>

<form action="" method="POST">

	<label for="stampate">Stampante:</label>
	<div class="input-group">
		<span class="input-group-addon"><span aria-hidden="true" class="glyphicon glyphicon-print"></span></span>
		<select name="stampante" class="pull-rightasd form-control" id="stampante">
			<?php
			$stampanti = get_printers();
			$id_printer = get_printer();
			foreach($stampanti as $stampante) {
			   echo '<option value="'.$stampante['id'].'"';
		  		if ($id_printer == $stampante['id']) {
		  			echo ' selected="selected"';
		  		}
			   echo ">".$stampante['nome']."</option>";
			}
		  	//echo $printer;
			?>
		</select>
	</div>

	<?php
		/*
		$scandir = scandir(CARTELLA_RICEVUTE);
		$ricevute = array();
		//$responsabili = find_by("reparti");
		foreach($scandir as $ascan) {
			if (is_dir(CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$ascan) && $ascan != '.' && $ascan != '..') {
				$ricevute[] = $ascan;
			}
		}
		*/
		$ricevute = get_reparti($id_testata, true);
		ksort($ricevute);
	?>
	<?php /* <select id="ricevuta" name="ricevuta">
	<?php
		foreach ($ricevute as $ric) {
			echo '<option value="'.$ric.'"';
			if ($ric == $ricevuta) { echo ' selected="selected"'; }
			echo '>'.$ric.'</option>';
				
		}
	?>
	</select>
	*/ ?>			
	<input type="hidden" name="file" value="<?php echo $nome_pdf; ?>">
	<input type="hidden" name="id_testate" value="<?php echo $id_testata; ?>">
	<input type="hidden" name="conferma" value="1">
	
	<div class="row mt-20">	
	<?php foreach ($ricevute as $id_reparto => $ric) { ?>
		<div class="col-md-4">
			<div class="well">
				<embed class="block full-width" style="height: 400px;" type="application/pdf" src="ricevuta.php?id_testata=<?php echo $id_testata; ?>&id_reparti=<?php echo $id_reparto; ?><?php if(isset($_REQUEST['btn-print']) && $id_printer < 0 && $ricevuta == $ric['nome']) { ?>&print=1<?php } ?>">
				<input class="btn btn-primary btn-block" type="submit" name="btn-print" value="STAMPA - <?php echo $ric['nome']; ?>">
				<button aria-label="Expand" onClick="jQuery(this).parent().toggleClass('fixed-fullscreen');" class="absolute top left btn btn-primary mt-5 ml-20" type="button"><span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span></button>
			</div>
		</div>
	<?php } ?>
	</div>
</form>

<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']));
}
?>

</div>

</body>
</html>
