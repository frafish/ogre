<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
//include_once("lib/print.php");


if(is_user()) { // controllo se l'utente è autenticato

	// ottengo la lista degli ingredienti
	$ingredienti = find_by("ingredienti");
	$sstart = get_start_time();
	
	if (empty($ingredienti)) {
	    echo "Nessun ingrediente?!";
	    exit;
	}
	
	// aggiorno le quantità
	// aggiorno le quantità con il valore inserito
	if(isset($_REQUEST['aggiorna'])) {
		foreach($_REQUEST['prodotto'] as $skey => $svalue) {
			$quantita = $svalue['quantita'];
			$soglia = $svalue['soglia'];
			$scorta = find_one_by("magazzino", "(time > '".$sstart."' OR durevole = 1) AND id_ingredienti = ".$skey); // solo gli ingredienti
			// se non ho ancora mai specificato la soglia del prodotto allora la inserisco
			if ($scorta === false) {
				if ($quantita || $soglia) {
				   $sql = "INSERT INTO magazzino (id_ingredienti, quantita, soglia) VALUES (".$skey.", ".$quantita.", ".$soglia.")";
					if (db_query($sql)) {
						$message = 'Aggiunti '.$quantita.' pezzi di '.$ingredienti[$skey]['nome'];
						if ($soglia) { $message .= ' con soglia '.$soglia; } 
						set_alert($message, 'success');
					}				
				}
			} else {
				// aggiorno la quantita solo se è stata variata
				if ($quantita != $scorta['quantita']) {
					$sql = "UPDATE magazzino SET quantita = ".$quantita." WHERE id = ".$scorta['id'];
					if (db_query($sql)) {
						set_alert('Aggiornati '.$quantita.' pezzi di '.$ingredienti[$skey]['nome'], 'success');
					}
				}
				// aggiorno la soglia solo se è stata variata
				if ($soglia != $scorta['soglia']) {
					$sql = "UPDATE magazzino SET soglia = ".$soglia." WHERE id = ".$scorta['id'];
					if (db_query($sql)) {
						set_alert('Aggiornata a '.$soglia.' la soglia di '.$ingredienti[$skey]['nome'], 'success');
					}	
				}
			}
		}
	}
	
	// ottengo la scorta e lo storico degli ingredienti
	$scorte = get_scorte(); //var_dump($scorte);
	foreach($scorte as $scorta) {
		//$ingredienti[$scorta['id_ingredienti']]['quantita'] = $scorta['quantita'];
		$ingredienti[$scorta['id_ingredienti']]['quantita'] = (isset($ingredienti[$scorta['id_ingredienti']]['quantita'])) ? $ingredienti[$scorta['id_ingredienti']]['quantita'] + $scorta['quantita'] : $scorta['quantita'];
		$ingredienti[$scorta['id_ingredienti']]['soglia'] = (isset($scorta['soglia'])) ? $scorta['soglia'] : 0;
	}
	
	//print_r($ingredienti);
	//print_r($ingredienti);
}
?>
<html>

	<head>
	
	<title>MAGAZZINO</title>
	
	<link rel="stylesheet" type="text/css" href="css/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="css/base.css">
	
	<script src="js/jquery.js"></script>
	
	<?php
	if(is_user()) { // controllo se l'utente è autenticato 
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
					var tot = parseInt($("#totali-"+id).val());
					$("#residui-"+id).html(tot-qnt);
	    		});
			});
			
			t = setTimeout("updateScreen()",1000*60*5); // ogni 5 minuti
		}
	
	</script>
	<?php } ?>

</head>

<body>
	<div class="container-fluid">
	
	<a href="/" class="btn btn-default btn-lg btn-back pull-left"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">MAGAZZINO</h1>
	
<?php
if(is_user()) { // controllo se l'utente è autenticato 
?>

<?php print_alerts(); ?>
	
	<form method="post" action="">
	<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr id="intestazione">
			<th>ID</th>
			<th>Nome</th>
			<th>Totali</th>
			<th>Vendute</th>
			<th>Da vendere</th>
			<th>Soglia</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ($ingredienti as $key => $aing) {
		$quantita = (isset($aing['quantita'])) ? $aing['quantita'] : "0";
		$soglia = (isset($aing['soglia'])) ? $aing['soglia'] : "0";
	?>
	<tr>
		<td><?php echo $key; ?></td>
		<td><?php echo $aing['nome']; ?></td>
		<td>		
			<input class="form-control<?php if ($quantita) { ?> input-focused<?php } ?>" id="totali-<?php echo $aing['id']; ?>" name="prodotto[<?php echo $aing['id']; ?>][quantita]" value="<?php echo $quantita; ?>"> 
		</td>
		<td id="venduti-<?php echo $aing['id']; ?>">0</td>
		<td id="residui-<?php echo $aing['id']; ?>">0</td>
		<td>
			<input class="form-control<?php if ($soglia) { ?> input-focused<?php } ?>" id="soglia-<?php echo $aing['id']; ?>" name="prodotto[<?php echo $aing['id']; ?>][soglia]" value="<?php echo $soglia; ?>"> 	
		</td>
	</tr>
	<?php
	}
	?>
	</tbody>
	</table>
	
	<input type="hidden" name="aggiorna" value="true">
	<input type="submit" value="AGGIORNA" class="btn btn-warning btn-lg">
	</form>

	<?php
	} else { // accesso negato, deve autenticarsi
		echo get_login_form(basename($_SERVER['PHP_SELF']));
	}
	?>	


	</div>
	

</body>

</html>
