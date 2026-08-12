<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
include_once("lib/print.php");

if (!db_count('ingredienti', 'griglie = 1')) {
	set_alert('ATTENZIONE: prima di accedere alle Griglie devi specificare per quali INGREDIENTI vuoi abilitarne la gestione!', 'warning');
	header('Location: prodotti.php#tab-ingredienti');
	die();
}

$sstart = get_start_time();
//var_dump($sstart);

if(is_user()) { // controllo se l'utente è autenticato 

	// ottengo le informazioni sugli ingredienti che vogliono monitorare
	// ottengo la lista degli ingredienti
   $ingredienti = find_by("ingredienti", "griglie = 1"); // aggiungo alla lista degli ingredienti
   foreach ($ingredienti as $pin => $ingrediente) {
		$ingredienti[$pin]['qnt'] = 0;
   }
	
	
	// aggiorno le quantità con il valore inserito
	if(isset($_REQUEST['id_ingrediente'])) {
		//dev_log('------------------------', 'sql');
		$id_ingrediente = $_REQUEST['id_ingrediente'];
		//var_dump($_REQUEST);  echo 'id_ingredienti = '.$id_ingrediente.' AND time > '.$sstart;
		
		$scorta = find_one_by('magazzino', "id_ingredienti = ".$id_ingrediente." AND time > '".$sstart."'");
		$qnt = $_REQUEST['qnt'];
		if ($scorta) {
			$qnt += $scorta['quantita'];
			$sql = "UPDATE magazzino SET quantita = ".$qnt." WHERE id_ingredienti = ".$id_ingrediente;
		} else {
	   	$sql = "INSERT INTO magazzino (id_ingredienti, quantita, durevole, time) VALUES (".$id_ingrediente.", ".$qnt.", 0, '".date('Y-m-d H:i:s')."')";
	   }
	   
		if (db_query($sql)) {
			aggiorna_quantita_prodotti();
			set_alert('Aggiunti '.$_REQUEST['qnt'].' pezzi di '.$ingredienti[$id_ingrediente]['nome'], 'success');
		}
	}
	
	// ottengo la scorta e lo storico degli ingredienti
	$scorte = find_by("magazzino"); // solo gli ingredienti
	foreach($scorte as $scorta) {
		if (isset($ingredienti[$scorta['id_ingredienti']])) {
			//print_r($row);
			// storico dell'anno precedente
			if (intval(date("Y",strtotime($scorta['time']))) == intval(date("Y"))-1) {
				//$ingredienti[$scorta['id_ingredienti']]['sto'] = $scorta['quantita'];
			} else {
				if (date("Y",strtotime($scorta['time'])) == date("Y")) {
					if (date("w",strtotime($scorta['time'])) == date("w")) {
						// scorte di quest'anno di questo giorno della settimana
						$ingredienti[$scorta['id_ingredienti']]['qnt'] += $scorta['quantita'];
					}
				}
			}
		}
	}
	
}
?>
<html>

<head>

<title>GRIGLIA</title>

<script src="js/jquery-1.11.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/base.css">

<?php
if(isset($_SESSION['id_utente'])) { // controllo se l'utente è autenticato
?>
<script>
	
	var time = new Date();
	var t = 1;
	
	$(document).ready(function () {
		//alert("partenza!");
  		updateScreen();
		
	});

	
	function updateScreen() {
		//alert("ciao");
		
		// recupero la lista delle testate che devono ancora essere servite
		$.ajax({
		  url: "ajax/ingredienti.php",
		  cache: false
		}).done(function( html ) {
		  	//alert(html);
     		var vendite = jQuery.parseJSON(html);
			jQuery.each(vendite, function(id, qnt) {
				// aggiorno rimasti e venduti
				$("#venduti-"+id).html(qnt);
				var tot = parseInt($("#totali-"+id).html());
				$("#residui-"+id).html(tot-qnt);
    		});
		});
		
		t = setTimeout("updateScreen()",1000*60*5); // ogni 5 minuti
	}

</script>
<?php } ?>

<style type="text/css">
td, th {
	font-size: xx-large; 	
	padding: 10px;
}

</style>

</head>

<body>

<div class="container-fluid">
	
	
	<a href="/" class="btn btn-default btn-lg btn-back pull-left absolute"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">Griglie - <?php echo $giorni_settimana[date("w",strtotime($sstart))]; ?></h1>	
	
<?php
if(is_user()) { // controllo se l'utente è autenticato 
?>	

	<?php print_alerts(); ?>	
	
	
	<table class="table table-striped table-hover table-bordered">
	
	<tr class="warning" id="intestazione">
		<th>Nome</th>
		<!--<th>Storico</th>-->
		<th>Totali</th>
		<th>Vendute</th>
		<th>Residui pronti</th>
		<th>Aggiunti in preparazione</th>
	</tr>
	
	<?php
	foreach ($ingredienti as $aing) {
	?>
	<tr>
		<td><?php echo $aing['nome']; ?></td>
		<!--<td id="storico-<?php echo $aing['nome']; ?>"><?php if (isset($aing['sto'])) { echo $aing['sto']; } else { echo "?"; } ?></td>-->
		<td id="totali-<?php echo $aing['id']; ?>"><?php if (isset($aing['qnt'])) { echo $aing['qnt']; } else { echo "0"; } ?></td>
		<td id="venduti-<?php echo $aing['id']; ?>">0</td>
		<td id="residui-<?php echo $aing['id']; ?>">0</td>
		<td>
			<form method="post" action="">
				<div class="input-group">				  
				  <input type="text" class="form-control input-lg" id="aggiunti-<?php echo $aing['id']; ?>" name="qnt" value="">
				  <div class="input-group-btn">
				    <input class="btn btn-warning btn-lg" type="submit" value="Aggiungi">
				  </div>
				</div>
				<input type="hidden" name="id_ingrediente" value="<?php echo $aing['id']; ?>"> 
			</form>
		</td>
	</tr>
	<?php
	}
	?>
	</table>
	
<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']));
}
?>	
</div>

</body>

</html>
