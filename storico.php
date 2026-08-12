<?php
include ("CONFIG.php");
include ("lib/dbconnection.php");
$sstart = get_start_time();

if (isset($_REQUEST['azione']) && $_REQUEST['azione'] == 'rigenera') {
    $id_testata = $_REQUEST['id_testate'];
    $skip_print = true;
    include('stampa.php');
    set_alert("PDF dell'ordine $id_testata rigenerati con successo", "success");
    header("Location: storico.php" . (isset($_REQUEST['id_casse']) ? "?id_casse=".$_REQUEST['id_casse'] : ""));
    die();
}
?>
<html>
<head>
	<title>STORICO</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/base.css">
</head>


<body>
<div class="container-fluid">

	<a href="/" class="btn btn-default btn-lg btn-back pull-left"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">STORICO</h1>
	
<?php
if(is_user()) { // controllo se l'utente è autenticato 
?>	

	<?php print_alerts(); ?>
	
	<ul class="list-group">
	<?php
	$where = null;
	if(isset($_REQUEST['id_casse'])) {
		$id_cassa = $_REQUEST['id_casse'];
		$where = "id_casse = ".$id_cassa." AND time > '".$sstart."'";
	}
	
	
	$testate = find_by('testate', $where, 'time DESC');
	$casse = find_by('casse');
	$reparti = find_by('reparti');

	foreach($testate as $testata) { 
		$pdf_name = get_filename_by_id($testata['id']);	
		$filename = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.CARTELLA_RICEVUTA_COMPLETA.DIRECTORY_SEPARATOR.$pdf_name;		
		?>
		<li class="list-group-item">
			[ID: <strong><?php echo $testata['id']; ?></strong> - <?php echo $testata['time']; ?> - Cassa: <?php echo $casse[$testata['id_casse']]['nome']; ?> - Progressivo: <?php echo $testata['progressivo']; ?>] 
			<a class="btn btn-xs btn-danger" href="cassa.php?action=update&id=<?php echo $testata['id']; ?>"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>
			<a class="btn btn-xs btn-danger" href="annulla.php?id_testate=<?php echo $testata['id']; ?>"><span class="glyphicon glyphicon-erase" aria-hidden="true"></span></a>
			:
			
			<?php //echo $filename; ?>
	<?php	if (file_exists($filename)) { ?>
		<?php echo CARTELLA_RICEVUTA_COMPLETA; ?> 
		<a class="btn btn-xs btn-success" href="<?php echo $filename; ?>" target="_blank"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a>
		<a class="btn btn-xs btn-success" href="ricevuta.php?id_testate=<?php echo $testata['id']; ?>" target="_blank"><span class="glyphicon glyphicon-file" aria-hidden="true"></span></a> 
		<a class="btn btn-xs btn-warning" href="ristampa.php?id_testate=<?php echo $testata['id']; ?>&ricevuta=<?php echo CARTELLA_RICEVUTA_COMPLETA; ?>" target="_blank"><span class="glyphicon glyphicon-print" aria-hidden="true"></span></a>
	<?php } else { ?>
		<a class="btn btn-xs btn-info" href="storico.php?azione=rigenera&id_testate=<?php echo $testata['id']; ?><?php echo isset($_REQUEST['id_casse']) ? '&id_casse='.$_REQUEST['id_casse'] : ''; ?>"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> Rigenera PDF</a>
	<?php }
	
	$filename = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.CARTELLA_RICEVUTA_CLIENTE.DIRECTORY_SEPARATOR.$pdf_name;		
	if (file_exists($filename)) { ?>
		- <?php echo CARTELLA_RICEVUTA_CLIENTE; ?> 
		<a class="btn btn-xs btn-success" href="<?php echo $filename; ?>" target="_blank"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a> 
		<a class="btn btn-xs btn-warning" href="ristampa.php?id_testate=<?php echo $testata['id']; ?>&ricevuta=<?php echo CARTELLA_RICEVUTA_COMPLETA; ?>" target="_blank"><span class="glyphicon glyphicon-print" aria-hidden="true"></span></a>
	<?php }
	
		foreach ($reparti as $reparto) {
			$filename = CARTELLA_RICEVUTE.DIRECTORY_SEPARATOR.$reparto['nome'].DIRECTORY_SEPARATOR.$pdf_name;
			if (file_exists($filename)) { ?>
				 - <?php echo $reparto['nome']; ?>
				 <a class="btn btn-xs btn-success" href="<?php echo $filename; ?>" target="_blank"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a> 
				 <a class="btn btn-xs btn-warning" href="ristampa.php?id_testate=<?php echo $testata['id']; ?>&ricevuta=<?php echo $reparto['nome']; ?>" target="_blank"><span class="glyphicon glyphicon-print" aria-hidden="true"></span></a>
			<?php }		
		} ?>
		</li>
	<?php } ?>
	</ul>
	
<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']));
}
?>		
	
</div>
</body>
</html>