<?php
include ("CONFIG.php");
include ("lib/dbconnection.php");

if (!isset($_GET['id_testate'])) {
	set_alert('Nessun id progressivo, scegli quale ricevuta vuoi eliminare!', 'warning');		
	header('Location: storico.php');
	die();
}
$id_testata = isset($_GET['id_testate']) ? $_GET['id_testate'] : 0;
$testata = find('testate', $id_testata);
if (!$testata) {
	set_alert('Nessuna ricevuta con questo id progressivo!', 'warning');
}

if (isset($_POST['distruggi']) && $testata) {
	db_query('DELETE FROM testate WHERE id = '.$id_testata);
	db_query('DELETE FROM dettagli WHERE id_testate = '.$id_testata);
	aggiorna_quantita_prodotti();
	set_alert('Comanda eliminata con successo!', 'success');
	header('Location: storico.php');
	die();
}
?>
<html>
<head>
	<title>Elimina ricevuta</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/base.css">
</head>


<body>
<div class="container-fluid">

	<a href="/" class="btn btn-default btn-lg btn-back pull-left"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">ANNULLA RICEVUTA</h1>
	
<?php
if(is_user()) { // controllo se l'utente è autenticato 
?>	

	<?php print_alerts(); ?>
	
	<?php if($testata) { ?>
	<div class="row">
		<div class="col-md-6">
			<?php
			$pdf_name = get_filename_by_id($testata['id']);	
			$filename = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.CARTELLA_RICEVUTA_COMPLETA.DIRECTORY_SEPARATOR.$pdf_name;		
			?>
			<embed class="full-width" style="height: 600px;" src="ricevuta.php?id_testata=<?php echo $testata['id']; ?>" type="application/pdf">
		</div>	
		<div class="col-md-6">
			<div class="well well-white">
				<form method="POST" action="">
					<input type="submit" name="distruggi" value="ANNULLA COMANDA" class="btn btn-danger btn-lg btn-block">
				</form>
			</div>
			
		</div>
	</div>
	<?php } ?>	
	
<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']));
}
?>		
	
</div>
</body>
</html>