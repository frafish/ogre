<?php
include ("CONFIG.php");
include ("lib/dbconnection.php");

$casse = find_by('casse');

$prodotti = find_by('prodotti');
$categorie = find_by('categorie');

$reparti = find_by('reparti');

$settimana = array("Domenica","Luned&igrave;","Marted&igrave;","Mercoled&igrave;","Gioved&igrave;","Venerd&igrave;","Sabato");

$sessioni = find_by("accessi", "act = 'stop'", "time");
array_unshift($sessioni, array('id' => 0, 'time' => ZERO_DATE));
$sessioni[count($sessioni)] = array('id' => count($sessioni), 'time' => date('Y-m-d H:i:s'));
//echo '<hr><pre>'; var_dump($sessioni); echo '</pre>';


if(isset($_REQUEST['azione'])) {
	switch($_REQUEST['azione']) {
		case 'export':
			$csv_separator = ';';
			$csv = array();
			$matrix = array();
			$tavoli = array('Tavoli');
			$asporto = array('Asporto');
			$omaggi = array('Omaggi');
			$ordini = array('Ordini');
			$totali = array('Incassi');
			foreach ($sessioni as $skey => $sessione) {
				if($skey) {					
					// mi pesco il primo ordine in assoluto che fa cominciare la sessione
					$time_first_testata = get_real_start_time($time_start_sessione);
						
					$csv[0][] = $time_first_testata;
					$matrix[$skey] = get_vendita_prodotti(null, $time_start_sessione, $sessione['time']);
					
					$tavoli[] = db_sum('testate', 'coperti', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."' AND asporto = 0");
					$asporto[] = db_sum('testate', 'coperti', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."' AND asporto = 1");
					$omaggi[] = db_sum('testate', 'omaggi', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."'");
					$ordini[] = count(find_by('testate', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."'"));
					$totali[] = ceil(db_sum('testate', 'totale', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."'")*100)/100;
				} else {
					$csv[0][] = '';
				}
				$time_start_sessione = $sessione['time'];
			}
			//var_dump($matrix);
			foreach($prodotti as $pid => $aprodotto) {
				$row = array($aprodotto['nome']);
				foreach ($sessioni as $skey => $sessione) {
					if($skey) {					
						$qnt = (isset($matrix[$skey][$pid])) ? $matrix[$skey][$pid] : 0;
						$row[] = $qnt;
					}
				}
				$csv[] = $row;			
			}
			$csv[] = $tavoli;
			$csv[] = $asporto;
			$csv[] = $omaggi;
			$csv[] = $ordini;
			$csv[] = $totali;
			//var_dump($csv);
			get_csv(null, $csv, '', true, time().'_statistiche_sagra.csv');
			break;
	}
}

?>
<html>
<head>
<title>STATISTICHE</title>

	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/base.css">

	<style type="text/css">
	/*
	#main {
		width: 800px;
		padding: 50px;	
	}
	*/

	h3, h4 {
		margin: 0px;
		padding: 0px;	
	}
	.list-group {
	    margin: 10px 20px;
	}
	
	#main > div {
		padding: 10px;	
	}	
		
	#incassi {
		background-color: yellow;	
	}
	
	#prodovenduti {
		background-color: lightgreen;	
	}
	
	#reparto {
		background-color: lightblue;	
	}
	
	.well > h1 {
		background-color: #ccc;
		opacity: 0.8;
		padding: 5px 30px;
		margin: 0 -10px;	
		font-weight: bold;
		color: black;
		text-shadow: 0 0 3px white;
	}

	</style>



</head>


<body>

<div id="main" class="container-fluid">

<a href="/" class="btn btn-default btn-lg btn-back pull-left absolute"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">STATISTICHE</h1>
	
<?php print_alerts(); ?>
<div class="well well-white">
	<form action="" method="post">
	    <input type="hidden" value="export" name="azione">
		 <button class="btn btn-lg btn-block btn-primary" type="submit" name="submit" value="Carica"/><span class="glyphicon glyphicon-export" aria-hidden="true"></span> Scarica le statistiche</button>
	</form>
</div>	
	

<div id="incassi" class="well">

	<h1>INCASSI</h1>
	
	<ul class="days list-group clearfix">
	<?php
	$totale_sagra = 0;
	foreach ($sessioni as $skey => $sessione) {
		if($skey) {
			$totale_day = 0;
			
			// mi pesco il primo ordine in assoluto che fa cominciare la sessione
			$time_prima_testata = get_real_start_time($time_start_sessione);
			$time_begin = strtotime($time_prima_testata);	
			$testate = find_by('testate', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."'", 'id_casse, time');
			if (!empty($testate)) {
			?> 
			<li class="aday list-group-item clearfix">	
				<h3><?php echo $settimana[date("w",$time_begin)]; ?> <small><?php echo $time_prima_testata; ?> - <?php echo $sessione['time']; ?></small></h3>
				<ul class="casse list-group clearfix">
				<?php
					$totale_day = 0;
					foreach ($casse as $ckey => $acassa) {
						$totale_cassa = db_sum('testate', 'totale', "time >= '".$time_prima_testata."' AND time < '".$sessione['time']."' AND id_casse = ".$ckey);
						$totale_cassa_pos = db_sum('testate', 'totale', "time >= '".$time_prima_testata."' AND time < '".$sessione['time']."' AND id_casse = ".$ckey." AND POS = 1");
						if ($totale_cassa) {
							$totale_day += $totale_cassa;
							?>
							<li class="acassa list-group-item clearfix">
									<strong class="bg-default">Cassa <?php echo $acassa['nome']; ?>: <span class="badge badge-primary badge-lg"><?php echo round($totale_cassa*100)/100; ?> &euro;</span> <?php if ($totale_cassa_pos) { ?><span class="badge badge-secondary badge-lg">POS: <?php echo round($totale_cassa_pos*100)/100; ?> &euro;</span><?php } ?></strong>
							</li>
							<?php
						}	
					}
					$totale_sagra += $totale_day;
				?>
				</ul>
				<h4 class="pull-right">TOTALE sessione <span class="badge"><?php echo $totale_day; ?> &euro;</span></h4>
			</li>
			<?php
			}
		}
		$time_start_sessione = $sessione['time'];
	}
	?>
		<li class="aday list-group-item clearfix">	
			<?php echo "<h2>TOTALE: ".$totale_sagra." &euro;</h2>"; ?>
		</li>
	</ul>

</div>

<hr><hr>

<div id="prodovenduti" class="well">

<h1>PRODOTTI VENDUTI</h1>

<ul class="days list-group">
	<?php
	foreach ($sessioni as $skey => $sessione) {
		if($skey) {
			$totale_prodotto = 0;
			$prima_testata_sessione = find_one_by('testate', "time > '".$time_start_sessione."'", 'time');
			$time_begin = strtotime($prima_testata_sessione['time']);	
			$prodettagli = db_query("SELECT prodotti.id as id, prodotti.nome as nome, SUM(dettagli.quantita) as quantita, SUM(dettagli.omaggio) as omaggio FROM dettagli, prodotti, testate WHERE dettagli.id_prodotti = prodotti.id AND dettagli.id_testate = testate.id AND testate.time >= '".$time_start_sessione."' AND testate.time < '".$sessione['time']."' GROUP BY prodotti.nome");		
			if(!empty($prodettagli)) { ?>
			<li class="aday list-group-item clearfix">
				<h3><?php echo $settimana[date("w",$time_begin)]; ?> <small><?php echo $prima_testata_sessione['time']; ?> - <?php echo $sessione['time']; ?></small></h3>
					<ul class="prodotti list-group">
					<?php
					foreach($prodettagli as $row) { ?>
						<li class="list-group-item list-group-item-condensed clearfix">
							<strong><span class="badge"><?php echo $row['quantita'] + $row['omaggio']; ?></span> <?php echo $row['nome']; ?></strong>
						</li>
					<?php } ?>	
					</ul>
				<?php
				$tavoli = db_sum('testate', 'coperti', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."' AND asporto = 0");
				$asporto = db_sum('testate', 'coperti', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."' AND asporto = 1");
				$omaggi = db_sum('testate', 'omaggi', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."'");
				?>		
				<h4>Totale Coperti serata: <?php echo ($tavoli+$asporto+$omaggi); ?> <small>Tavoli: <?php echo $tavoli; ?> + Asporto: <?php echo $asporto; ?> + Omaggi: <?php echo $omaggi; ?></small></h4>		
				
			</li>
		<?php }
		}
		$time_start_sessione = $sessione['time'];
	}
	$all_prodettagli = db_query("SELECT prodotti.id as id, prodotti.nome as nome, SUM(dettagli.quantita) as quantita, SUM(dettagli.omaggio) as omaggio FROM dettagli, prodotti WHERE dettagli.id_prodotti = prodotti.id GROUP BY prodotti.nome");		
	if(!empty($all_prodettagli)) { ?>
	<li class="aday list-group-item clearfix">
		<h2>TOTALI:</h2>
			<ul class="prodotti list-group">
			<?php
			foreach($all_prodettagli as $row) { ?>
				<li class="list-group-item list-group-item-condensed clearfix">
					<strong><span class="badge"><?php echo $row['quantita'] + $row['omaggio']; ?></span> <?php echo $row['nome']; ?></strong>
				</li>
			<?php } ?>	
			</ul>
		<?php
		$tavoli = db_sum('testate', 'coperti', "asporto = 0");
		$asporto = db_sum('testate', 'coperti', "asporto = 1");
		$omaggi = db_sum('testate', 'omaggi');
		?>		
		<h3>Totale Coperti: <?php echo ($tavoli+$asporto+$omaggi); ?> <small>Tavoli: <?php echo $tavoli; ?> + Asporto: <?php echo $asporto; ?> + Omaggi: <?php echo $omaggi; ?></small></h3>
	</li>
	<?php } ?>
</ul>

</div>

<hr><hr>

<div id="reparto" class="well">
	
	<h1>PRODOTTI e INCASSO per REPARTO</h1>
	
	<ul class="days list-group clearfix">
	<?php
	foreach ($sessioni as $skey => $sessione) {
		if($skey) {
			// mi pesco il primo ordine in assoluto che fa cominciare la sessione
			$time_prima_testata = get_real_start_time($time_start_sessione);
			$time_begin = strtotime($time_prima_testata);	
			$testate = find_by('testate', "time >= '".$time_start_sessione."' AND time < '".$sessione['time']."'", 'id_casse, time');
			if (!empty($testate)) {
			?> 
			<li class="aday list-group-item clearfix">	
				<h3><?php echo $settimana[date("w",$time_begin)]; ?> <small><?php echo $time_prima_testata; ?> - <?php echo $sessione['time']; ?></small></h3>
				<ul class="casse list-group clearfix">
				<?php
					$totale_day = 0;
					$query_reparti = "SELECT reparti.nome as reparto, SUM(dettagli.quantita + dettagli.omaggio) as quantita_totale, SUM(dettagli.quantita * prodotti.prezzo) as incasso_totale FROM dettagli JOIN testate ON dettagli.id_testate = testate.id JOIN prodotti ON dettagli.id_prodotti = prodotti.id JOIN reparti ON prodotti.id_reparti = reparti.id WHERE testate.time >= '".$time_start_sessione."' AND testate.time < '".$sessione['time']."' GROUP BY reparti.id";
					$stats_reparti = db_query($query_reparti);
					if($stats_reparti) {
						foreach ($stats_reparti as $stat) {
							$totale_day += $stat['incasso_totale'];
							?>
							<li class="acassa list-group-item clearfix">
									<strong class="bg-default">Reparto <?php echo $stat['reparto']; ?>: 
										<span class="badge badge-secondary badge-lg"><?php echo $stat['quantita_totale']; ?> pezzi</span>
										<span class="badge badge-primary badge-lg"><?php echo round($stat['incasso_totale']*100)/100; ?> &euro;</span>
									</strong>
							</li>
							<?php
						}
					}
				?>
				</ul>
				<h4 class="pull-right">TOTALE Incasso Prodotti: <span class="badge"><?php echo $totale_day; ?> &euro;</span></h4>
			</li>
			<?php
			}
		}
		$time_start_sessione = $sessione['time'];
	}
	?>
	</ul>
<?php
	$query_totale_reparti = "SELECT reparti.nome as reparto, SUM(dettagli.quantita + dettagli.omaggio) as quantita_totale, SUM(dettagli.quantita * prodotti.prezzo) as incasso_totale FROM dettagli JOIN prodotti ON dettagli.id_prodotti = prodotti.id JOIN reparti ON prodotti.id_reparti = reparti.id GROUP BY reparti.id";
	$stats_totale_reparti = db_query($query_totale_reparti);
	if($stats_totale_reparti) {
		echo '<li class="aday list-group-item clearfix">';
		echo "<h2>TOTALI SAGRA per REPARTO</h2>";
		echo '<ul class="responsabili list-group clearfix">';
		$gran_totale_incasso = 0;
		foreach($stats_totale_reparti as $stat) {
			$gran_totale_incasso += $stat['incasso_totale'];
			echo '<li class="arespo list-group-item clearfix">';	
			echo '<strong class="bg-default">Reparto '.$stat['reparto'].': <span class="badge badge-secondary badge-lg">'.$stat['quantita_totale'].' pezzi</span> <span class="badge badge-primary badge-lg">'.(round($stat['incasso_totale']*100)/100).' &euro;</span></strong>';
			echo "</li>";		
		}
		echo '</ul>';
		echo '<h3 class="pull-right">GRAN TOTALE Incasso Prodotti: <span class="badge">'.$gran_totale_incasso.' &euro;</span></h3>';
		echo "</li>";
	}
?>
</ul>

</div>

</div>

</body>

</html>