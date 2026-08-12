<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
include_once("lib/print.php");

if (!is_session_started() && !get_option('sessione-automatica')) {
	set_alert("La sessione &egrave; chiusa. <br><br><a href='sessioni.php' class='btn btn-warning'>AVVIA UNA NUOVA SESSIONE ORA</a>", 'danger');
	header('Location: index.php');
	die();
}

$sstart = get_start_time();

//print_r($_SESSION);
// espulsione utente
if (isset($_SESSION['id_cassa'])) {
	if (!is_logged_in()) {
		termina_sessione();
	}
} else {
	// utente non autorizzato per star qui
	//termina_sessione();
	//echo $_SERVER['PHP_SELF']; die();
	$page = basename($_SERVER['PHP_SELF']);
	header('Location: login.php?go='.$page);
}


$testata = false;
$dettagli = array();
if (isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'update':
			if(isset($_GET['id']) && is_numeric($_GET['id'])) {
				$testata = find('testate', $_GET['id']);
				if ($testata) {
					$dettagli = find_by('dettagli', 'id_testate = '.$testata['id']);
				}
			}
			break;
	}
}

/*
require_once './lib/Mobile_Detect.php';
$detect = new Mobile_Detect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
*/
//<!DOCTYPE html>
?>
<html>
	<head>
		<title>CASSA</title>

		<!--<link href="css/jquery-ui.min.css" rel="stylesheet">-->
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="css/base.css" />
		<link rel="stylesheet" type="text/css" href="css/cassa.css" />

		<script src="js/jquery-1.11.1.min.js"></script>
		<!--<script src="js/jquery-ui.min.js"></script>-->
		<script src="js/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/autosize.min.js"></script>
		<script type="text/javascript" src="js/jquery.ba-resize.min.js"></script>
		<script type="text/javascript" src="js/cassa.js"></script>

		<?php
		$bodyClass = array();

		if (is_user()) {

			$cassa = get_cassa();

			if (!$cassa) {
				termina_sessione();
			}

			if (isset($_SESSION['compact-view']) && $_SESSION['compact-view']) {
				$bodyClass[] = 'compact-view';
			}

			if ($cassa['id_categorie']) {
				// gestisco la schermata iniziale (categoria, coperti e tipo) in base alla cassa
				$categoria_preferita = find("categorie", $cassa['id_categorie']);
				if ($categoria_preferita) { ?>
				<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery("#mostra-<?php echo $categoria_preferita['nome']; ?>").click(); // mostra la tab preimpostata
					});
				</script>
				<?php }
			}

			if($cassa['asporto'] && get_option('asporto-automatico')) { ?>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery("#quantita-coperti").val("0");
					jQuery("#asporto").prop("checked", true); $("#label_asporto").addClass("btn-warning");
					//-jQuery("#tipoordine").val("3");
				});
			</script>
			<?php } ?>

		<?php } ?>


		<?php
		/*
		// apro in automatico il pdf completo per poterlo visionare e mandare in stampa se locale
		if(isset($_SESSION['print'])) {
			$pdfdir = $_SESSION['print'];
			if (file_exists($pdfdir)) {
				$serverpdfdir = "http://".$_SERVER['HTTP_HOST']."/".$pdfdir;
		?>
		<link rel="alternate" media="print" href="<?php echo $pdfdir; ?>">
		<script type="text/javascript">
		jQuery(window).load(function () {
				// FUNZIONA SOLO SU CHROME
				window.frames["printf"].focus();
				window.frames["printf"].print();
				//alert("stampo?");
				var pdfpage = window.open('<?php echo $serverpdfdir; ?>');
				pdfpage.print();//.delay(5000).close();
				//jQuery(document).focus();
				//pdfpage.close();
			});
		</script>
		<?php
			}
		}
		*/
		?>

		<!-- provo a renderlo full screen su mobile -->
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<script>
			jQuery(document).ready(function(){
				//if(navigator.userAgent.match(/Android/i)){
				    window.scrollTo(0,1);
				//}
				//set_button_height();
			});
		</script>

		<?php if (get_option('categorie-pieno-schermo')) { ?>
		<script type="text/javascript">
			jQuery(window).load(function(){
				set_button_height();
			});
			jQuery(window).resize(function(){
				set_button_height();
			});
		</script>
		<style type="text/css">
			#tabelle .tabs {
				height: 100%;
				overflow-y: auto;
			}
		</style>
		<?php } ?>

	</head>

	<body class="<?php echo implode(' ', $bodyClass); ?>">
		<div class="container-fluid">

<?php if(is_user()) { ?>
	<?php
	$categorie = find_by('categorie', 'status = 1', 'ordine ASC');

	// se la cassa è limitata alla visione del solo reparto
	if ($cassa['id_reparti'] && !isset($_GET['action'])) {
		$categorie = db_query('SELECT DISTINCT categorie.* FROM categorie LEFT JOIN prodotti ON categorie.id = prodotti.id_categorie LEFT JOIN reparti ON reparti.id = prodotti.id_reparti WHERE (reparti.id = '.$cassa['id_reparti'].' OR categorie.universale = 1) AND categorie.status = 1 ORDER BY categorie.ordine ASC');
	}
	$n_categorie = count($categorie);

	$prodotti = array();
	$where_prodotti = "status = 1";
	if ($cassa['id_reparti'] > 0) {
		$conds = array();
		$conds[] = "id_reparti = ".$cassa['id_reparti'];
		
		$cat_uni = find_by('categorie', 'universale = 1');
		if (!empty($cat_uni)) {
			$uni_ids = array();
			foreach ($cat_uni as $cu) {
				$uni_ids[] = $cu['id'];
			}
			$conds[] = "id_categorie IN (".implode(",", $uni_ids).")";
		}
		
		if (!empty($conds)) {
			$where_prodotti .= " AND (".implode(" OR ", $conds).")";
		}
	}
	$all_prodotti = find_by("prodotti", $where_prodotti, "id_categorie ASC, ordine ASC");
	if (!empty($all_prodotti)) {
		foreach ($all_prodotti as $aprodotto) {
			$prodotti[$aprodotto['id_categorie']][$aprodotto['id']] = $aprodotto;
		}
	}

	$prodottiRicette = array();
	// TOTEST
	/*
	$ricette = find_by("ricette");
	foreach($ricette as $aricetta) {
		$prodottiRicette[$aricetta['id_prodotti']][$aricetta['id_ingredienti']] = $aricetta['quantita'];
	}
	*/
	?>

	<form id="form" method="POST" action="salva.php">

		<div class="row">
			<div id="tabelle" class="col-md-7 col-sm-7 col-xs-12">
				<div id="tabelle_contenitore" class="contenitore well mb-0 no-border<?php if ($n_categorie <= get_option('max-colonne-cassa-rapida')) { ?> quick-view<?php } ?>">

				<!-- Tab panes -->
  				<div class="tab-content row">
				<?php
				$prodotti_che_generano_coperti = 0;
				$reparti = find_by('reparti');
				$c = 0;
				foreach ($prodotti as $ckey => $categoria) { ?>
					<?php //print_categorie($categorie, $ckey); ?>
					<div role="tabpanel" class="tabs tab-pane active<?php if ($n_categorie <= get_option('max-colonne-cassa-rapida')) { ?> cassa-rapida col-md-<?php echo floor(12/$n_categorie); } ?>" id="tab-<?php echo $ckey; ?>">
						<?php print_categorie($categorie, $ckey, true, 'nav nav-tabs nav-justified etichette'); ?>
						<ol id="table-<?php echo $ckey; ?>" class="reparto">
							<?php
							$i = 0;
							foreach ($categoria as $pkey => $prodotto) { ?>
									<li>
										<?php
											// verifico se il prodotto genera coperti
											if($reparti[$prodotto['id_reparti']]['coperti']) {
												$prodotti_che_generano_coperti++;
											}

											// trovo la disponibilta del prodotto
											$disponibilita = isset($prodotto['quantita']) ? intval($prodotto['quantita']) : -1;
											
											$alertScorte = "";
											// segnalo che è finito
											if ($disponibilita == 0) {
												$alertScorte = "btn-danger";
											} elseif ($disponibilita > 0 && $disponibilita <= 10) {
												$alertScorte = "btn-warning";
											}
										?>
										<div class="td_add">
											<div class="row no-gutter btn-add-tavoli">
											  <div class="div_add col-md-10 col-sm-8 col-xs-8">
												<button class="btn btn-block btn-default-inverted text-bold btn-lg full-height-asd articolo aumenta <?php echo $alertScorte; ?>" data-target="quantita-<?php echo $prodotto['id']; ?>" type="button" id="aumenta-<?php echo $prodotto['id']; ?>">
													<span class="visible-md visible-lg"><?php echo $prodotto['nome']; ?></span>
													<span class="nome-corto visible-xs visible-sm"><?php echo (trim($prodotto['corto'])) ? $prodotto['corto'] : $prodotto['nome']; ?></span>
												</button>
												<?php if ($disponibilita >= 0) { ?>
													<span class="td_scorta info-prodotto"><?php echo $disponibilita; ?> <small>disponibili</small></span>
												<?php } ?>
												<span class="td_prezzo info-prodotto"><small>Prezzo:</small> <i class="prezzo"><?php echo number_format($prodotto['prezzo'], 2); ?></i> &euro;</span>
												<span class="td_id info-prodotto<?php if (!is_superman()) { ?> hidden<?php } ?>"><small>ID: <span class="product-id"><?php echo $prodotto['id']; ?></span></small></span>

												<textarea id="prodotto-<?php echo $prodotto['id']; ?>-nota" class="form-control none prodotto-nota absolute left top full-height " name="prodotto[<?php echo $prodotto['id']; ?>][nota]" placeholder="Scrivi una nota sul prodotto <?php echo $prodotto['nome']; ?>..."></textarea>
												<a class="td_nota none a-toggle btn btn-primary btn-xs mt-5 mr-5 absolute top right" href="#prodotto-<?php echo $prodotto['id']; ?>-nota"><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span></a>
											  </div>
											  <?php
											  		$quantita = 0;
											  		if(!empty($dettagli)) {
											  			foreach($dettagli as $dettaglio) {
															if($dettaglio['id_prodotti'] == $prodotto['id']) {
																$quantita = $dettaglio['quantita'];
															}
											  			}
											  		}
											  ?>
											  <div class="col-md-1 col-sm-2 col-xs-2">
												<input type="number" class="btn btn-block btn-default btn-lg full-height-asd quantita<?php if ($reparti[$prodotto['id_reparti']]['coperti']) { ?> ha-coperto<?php } ?>" name="prodotto[<?php echo $prodotto['id']; ?>][quantita]" value="<?php echo $quantita; ?>" id="quantita-<?php echo $prodotto['id']; ?>" onClick="this.select();">
											  </div>
											  <div class="col-md-1 col-sm-2 col-xs-2">
												<button class="btn btn-block btn-warning btn-lg full-height-asd diminuisci" data-target="quantita-<?php echo $prodotto['id']; ?>" type="button" id="diminuisci-<?php echo $prodotto['id']; ?>">-</button>
											  </div>
											</div>
										</td>
										<?php if ($prodotto['prezzo'] >= 0) { ?>
										<td class="td_omaggio omaggio-visible">
										   <div class="wello bg-primary">

												<div class="row no-gutter btn-add-omaggi"> <!--btn-group btn-group-justified full-height-asd" role="group">-->
												  <?php
												  		$quantita_omaggio = 0;
												  		if(!empty($dettagli)) {
												  			foreach($dettagli as $dettaglio) {
																if($dettaglio['id_prodotti'] == $prodotto['id']) {
																	$quantita_omaggio = $dettaglio['omaggio'];
																}
												  			}
												  		}
												  ?>

												  <div class="col-xs-4 btn-group-ads" role="group-asd">
													<button class="btn articolo full-height-asd btn-lg aumenta" data-target="quantita-omaggio-<?php echo $prodotto['id']; ?>" type="button" id="aumenta-omaggio-<?php echo $prodotto['id']; ?>">+</button>
												  </div>
												  <div class="col-xs-4 btn-group-asd" role="group-asd">
													<input type="number" class="btn btn-default btn-lg full-height-asd quantita-omaggio" name="prodotto[<?php echo $prodotto['id']; ?>][omaggio]" value="<?php echo $quantita_omaggio; ?>" id="quantita-omaggio-<?php echo $prodotto['id']; ?>" onClick="this.select();">
												  </div>
												  <div class="col-xs-4 btn-group-asd" role="group-asd">
													<button class="btn articolo full-height-asd btn-lg diminuisci" data-target="quantita-omaggio-<?php echo $prodotto['id']; ?>" type="button" id="diminuisci-omaggio-<?php echo $prodotto['id']; ?>">-</button>
												  </div>

												</div>
											</div>
										</div>
										<?php } ?>
									</li>
							<?php } ?>
						</ol>
					</div>
				<?php
				$c++;
				} ?>
				</div>
				</div>
			</div>

		<!--<div id="logo"><h1>Gestionale Sagra</h1></div>-->
			<div id="info" class="col-md-5 col-sm-5 col-xs-12">
				<div class="row">
					<div id="money" class="col-md-6 col-sm-6 col-xs-12 col-sx col-full-height">
						<div class="auto-height">

						<?php if ($prodotti_che_generano_coperti) { ?>
							<div id="coperti_container" class="text-center well well-white pb-4">

								<?php
									$coperti = 0;
									if($testata) {
										$coperti = $testata['coperti'];
									}
								?>
								<h4 class="widget-title"><span class="glyphicon glyphicon-cutlery" aria-hidden="true"></span> COPERTI:</h4>
								<div class="btn-group btn-group-justified" role="group">
								  <div class="btn-group" role="group">
									<button class="piucoperti aumenta btn btn-default btn-lg" id="aumenta-coperti" type="button" data-target="quantita-coperti">+</button>
								  </div>
								  <div class="btn-group" role="group">
									<input type="number" class="btn btn-danger quantita" name="quantita-coperti" id="quantita-coperti" value="<?php echo $coperti; ?>">
								  </div>
								  <div class="btn-group" role="group">
									<button class="menocoperti diminuisci btn btn-default btn-lg" type="button" data-target="quantita-coperti" id="diminuisci-coperti">-</button>
								  </div>
								</div>

								<?php
									$coperti_omaggio = 0;
									if($testata) {
										$coperti_omaggio = $testata['omaggi'];
									}
								?>
								<div id="omaggio-coperti" class="wello bg-primary">
									<h4><span class="glyphicon glyphicon-gift" aria-hidden="true"></span> OMAGGI:</h4>
									<div class="btn-group btn-group-justified" role="group">
									  <div class="btn-group" role="group">
										<button class="aumenta btn btn-lg" id="aumenta-coperti-omaggio" type="button" data-target="quantita-coperti-omaggio">+</button>
									  </div>
									  <div class="btn-group" role="group">
										<input type="number" class="btn quantita btn-lg" name="quantita-coperti-omaggio" id="quantita-coperti-omaggio" value="<?php echo $coperti_omaggio; ?>">
									  </div>
									  <div class="btn-group" role="group">
										<button class="diminuisci btn  btn-lg" type="button" data-target="quantita-coperti-omaggio" id="diminuisci-coperti-omaggio">-</button>
									  </div>
									</div>
								</div>

								<a class="btn btn-xs btn-mini btn-default block color-inherit absolute top left<?php if (get_option('tavolo')) { ?> hidden<?php } ?>" title="Aggiungi tavolo" href="#tavolo" onclick="jQuery(jQuery(this).attr('href')).toggle(); return false;">
									<span class="pull-right glyphicon glyphicon-eye-close hidden" aria-hidden="true"></span> <small>Tavolo</small>
								</a>
								<a class="btn btn-xs btn-mini btn-default block color-inherit absolute top right<?php if (get_option('cliente')) { ?> hidden<?php } ?>" title="Aggiungi cliente" href="#cliente" onclick="jQuery(jQuery(this).attr('href')).toggle(); return false;">
									<span class="pull-right glyphicon glyphicon-eye-close hidden" aria-hidden="true"></span> <small>Cliente</small>
								</a>

								<div id="tavolo"<?php if (!get_option('tavolo') && (!$testata || !$testata['tavolo'])) { ?> class="nascosto"<?php } ?>>
									<label for="tavolo">Tavolo:</label>
									<input class="form-control" type="text" id="tavolo" name="tavolo" value="<?php if($testata && $testata['tavolo']) { echo htmlspecialchars($testata['tavolo'], ENT_QUOTES, 'UTF-8'); } ?>" placeholder="Numero tavolo"<?php if (get_option('tavolo')) { ?> required<?php } ?>>
								</div>

								<div id="cliente"<?php if (!get_option('cliente') && (!$testata || !$testata['cliente'])) { ?> class="nascosto"<?php } ?>>
									<label for="tavolo">Cliente:</label>
									<input class="form-control" type="text" id="cliente" name="cliente" value="<?php if($testata && $testata['cliente']) { echo htmlspecialchars($testata['cliente'], ENT_QUOTES, 'UTF-8'); } ?>" placeholder="Nome cliente"<?php if (get_option('cliente')) { ?> required<?php } ?>>
								</div>

							</div>
						<?php } else { ?>
							<input type="hidden" name="quantita-coperti" id="quantita-coperti" value="0" class="no-coperti">
						<?php } ?>

							<div id="conto" class="well well-white pb-4">
							   <div id="conto_contenitore" class="contenitore text-center">
								  <h4 id="calcolatrice-title" class="widget-title text-center"><span class="glyphicon glyphicon-piggy-bank" aria-hidden="true"></span> CALCOLATRICE:</h4>
								  <div id="calcolatrice-pulsanti">
									  <button class="soldi moneta btn" value="0.10" type="button" id="calcolatrice-010">0,10&euro;</button>
									  <button class="soldi moneta btn" value="0.20" type="button" id="calcolatrice-020">0,20&euro;</button>
									  <button class="soldi moneta btn" value="0.50" type="button" id="calcolatrice-050">0,50&euro;</button><br/>
									  <button class="soldi moneta btn" value="1.00" type="button" id="calcolatrice-1">1&euro;</button>
									  <button class="soldi moneta btn" value="2.00" type="button" id="calcolatrice-2">2&euro;</button>
									  <button class="soldi moneta btn" value="5.00" type="button" id="calcolatrice-5">5&euro;</button><br/>
									  <button class="soldi moneta btn" value="10" type="button" id="calcolatrice-10">10&euro;</button>
									  <button class="soldi moneta btn" value="20" type="button" id="calcolatrice-20">20&euro;</button>
									  <button class="soldi moneta btn" value="50" type="button" id="calcolatrice-50">50&euro;</button><br/>
									  <button class="soldi btn btn-success" id="calcolatrice-ok" type="button">Esatti</button>
									  <button class="soldi btn btn-warning" id="calcolatrice-reset" type="button">Reset</button>
									  <span class="box-check" id="calcolatrice-pos"><input type="checkbox" name="pos" id="pos"<?php if($testata && $testata['pos']) { ?> checked="checked"<?php } ?>><label for="pos" id="label_pos" class="btn btn-block btn-default<?php if($testata && $testata['pos']) { ?> btn-warning<?php } ?>">POS</label></span>
								  </div>
								</div>
							 </div>
							 <div id="totali" class="well well-white pb-4">
							  <div class="row">
							  	  <div class="col-md-6 text-left">
										<h4 class="widget-title"><span class="glyphicon glyphicon-euro" aria-hidden="true"></span> TOTALE:</h4>
										<input type="text" id="totale" name="totale" value="0.00" class="disabled text-center">
							  	  </div>
								  <div class="col-md-6 text-right">
								  	<?php
								  	$pagato = '0.00';
								  	if($testata) {
								  		$pagato = $testata['pagato'];
								  	}
								  	?>
									 Versato: <input type="text" id="versato" name="versato" value="<?php echo $pagato; ?>">
									 <br/>
									 Resto: <div class="pull-right text-danger" id="rimanente">0.00</div>
								  </div>
							  </div>
							</div>

							<div id="concludi" class="contenitore well-asd well-white-asd">
								<input class="nuovo btn btn-danger btn-submit height-filler" id="nuovo-ordine" type="submit" value="<?php if($testata) { ?>SALVA E MODIFICA<?php } else { ?>CONCLUDI E STAMPA<?php } ?>" />
							</div>

						</div>
					</div>

					<div id="optional" class="col-md-6 col-sm-6 col-xs-12 col-dx col-full-height">
						<div class="auto-height">
							<div id="user" class="contenitore well well-white pb-4 pt-4">
								<h1 id="cassainfo" class="text-center">Cassa <strong class="cassa"><?php echo $cassa['nome']; ?></strong></h1>
								<input type="hidden" id="cassa" name="cassa" value="<?php echo $cassa['id']; ?>" />
								<?php
								// trovo le info dell'utente corrente
								$utente = find_one_by('utenti', 'id = '.$_SESSION['id_utente']);
								?>
								<div class="input-group mb-5">
									<span class="input-group-addon"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
									<input class="disabled form-control" value="<?php echo $utente['nome']; ?>" disabled="">
									<a class="input-group-addon btn btn-xs btn-danger" href="logout.php">LOGOUT</a>
								</div>
								<input type="hidden" id="utente" name="utente" value="<?php echo $_SESSION['id_utente']; ?>" />

								<?php
								// prelevo le info delle stampanti da db
								$stampanti = get_printers();
								?>
								<div class="input-group">
									<span class="input-group-addon"><span class="glyphicon glyphicon-print" aria-hidden="true"></span></span>
									<select id="selstampante" class="pull-rightasd form-control" name="stampante">
									<?php
									// trovo la stampante predefinita per la cassa corrente
									$defaultPrinter = get_printer();
									foreach($stampanti as $astampante) {
										$sel = ($defaultPrinter == $astampante['id']) ? ' selected="selected"' : "";
										?>
										   <option value="<?php echo $astampante['id']; ?>"<?php echo $sel; ?>><?php echo $astampante['nome']; ?> (<?php echo $astampante['ip']; ?>)</option>
									<?php } ?>
									</select>
								</div>
							</div>

							<div id="tipo_container" class="clearfix well well-white pb-4">
								<h4 class="widget-title text-center pull-left-asd mr-10-ads"><span class="glyphicon glyphicon-check" aria-hidden="true"></span> TIPO:</h4>
								<div class="row">
									<div class="col-md-6">
										<span class="box-check"><input type="checkbox" name="asporto" id="asporto"<?php if($testata && $testata['asporto']) { ?> checked="checked"<?php } ?>><label for="asporto" id="label_asporto" class="btn btn-block btn-default<?php if($testata && $testata['asporto']) { ?> btn-warning<?php } ?>"><span class="glyphicon glyphicon-road" aria-hidden="true"></span> Asporto </label></span>
									</div>
									<div class="col-md-6">
										<span class="box-check"><input type="checkbox" name="omaggio" id="omaggio"<?php if($testata && $testata['omaggi']) { ?> checked="checked"<?php } ?>><label for="omaggio" id="label_omaggio" class="btn btn-block btn-default<?php if($testata && $testata['omaggi']) { ?> btn-warning<?php } ?>"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span> Omaggio </label></span>
									</div>
								</div>
								<?php
								/*<select name="tipoordine" id="tipoordine" multiple="multiple">
									$result = mysql_query("SELECT * FROM tipi");
									while($row = mysql_fetch_array($result)) {
									  echo "<option value=\"".$row['id']."\">".$row['nome']."</option>";
									}
								</select>*/
								?>
							</div>

							<div id="resoconto" class="well well-white">
								<div id="resoconto_contenitore" class="contenitore height-filler">
									<h4 class="widget-title text-center"><span class="glyphicon glyphicon-list" aria-hidden="true"></span> DETTAGLIO:</h4>
									<ul id="lista"></ul>
								</div>
								<button type="button" id="reset" class="btn btn-danger btn-xs">RESET</button>
							</div>

							<?php if($testata) { ?>
								<div id="precedente" class="contenitore clearfix well well-white pb-4 relative">
									<h4 class="widget-title text-center"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> MODIFICA:</h4>
									<div class="row">
										<div class="col-md-6">
											ID: <span id="prog-precedente"><?php echo $testata['id']; ?></span><br/>
											Progressivo: <span id="prog-precedente"><?php echo $testata['progressivo']; ?></span><br/>
										</div>
										<div class="col-md-6">
											<a href="ristampa.php?stampante=<?php echo $defaultPrinter; ?>&ricevuta=all&id_testate=<?php echo $testata['id']; ?>" class="btn btn-primary" target="_blank">RISTAMPA</a>
											<a class="btn btn-success" href="storico.php?id_casse=<?php echo $cassa['id'];?>" target="_blank">Storico</a>
										</div>
									</div>
									<input type="hidden" name="id-testata" value="<?php echo $testata['id']; ?>">
									<!--<button class="stats" id="carica-statistiche" type="button">Statistiche</button><button class="storico" id="carica-storico" type="button">Storico ordini</button>-->
								</div>

							<?php	} else { ?>
								<div id="precedente" class="contenitore clearfix well well-white pb-4 relative">
									<h4 class="widget-title text-center"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> PRECEDENTE:</h4>
									<?php
									$precedente = find_one_by("testate", "id_casse = ".$_SESSION['id_cassa'], "id DESC");
									//print_r($precedente);
									if ($precedente) {
										$presto =  round($precedente['pagato'] - $precedente['totale'],2);
										?>
										<div class="row">
											<div class="col-md-6">
												Progr.: <span id="prog-precedente"><?php echo $precedente['progressivo']; ?></span><br/>
												Tipo: <span id="tipo-precedente"><?php echo get_tipo($precedente['asporto'], $precedente['omaggi']); ?></span><br/>
												<a href="ristampa.php?stampante=<?php echo $defaultPrinter; ?>&ricevuta=all&id_testate=<?php echo $precedente['id']; ?>" class="btn btn-primary btn-sm" target="_blank"><span class="glyphicon glyphicon-print" aria-hidden="true"></span> RISTAMPA</a>
											</div>
											<div class="col-md-6">
												<div>Dovuto: <span id="totale-precedente"><?php echo $precedente['totale']; ?></span></div>
												<div>Pagato: <span id="pagato-precedente"><?php echo $precedente['pagato']; ?></span></div>
												<div>Resto: <span id="resto-precedente"><?php echo $presto; ?></span></div>
											</div>
										</div>
										<a class="btn btn-xs btn-success absolute top right" href="storico.php?id_casse=<?php echo $cassa['id'];?>" target="_blank">Storico</a>
										<?php if (isset($_SESSION['print'])) {
											$printf = $_SESSION['print'];
											$_SESSION['print'] = '';
											unset($_SESSION['print']);
											$printf = 'ricevuta.php?print=1&id_testate='.$precedente['id'];
											?>
											<div role="alert" class="alert alert-warning relative">
												<iframe width="100%" style="width: 100%;" id="printf" name="printf" src="<?php echo $printf; ?>"></iframe>
												<button aria-label="Close" data-dismiss="alert" class="absolute top right btn btn-xs btn-danger" type="button"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
												<button aria-label="Expand" onClick="jQuery(this).parent().toggleClass('fixed-fullscreen');" class="absolute top left btn btn-xs btn-primary" type="button"><span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span></button>
											</div>
										<?php } ?>
									<?php } else { ?>
										<h5>Nessun precedente</h5>
									<?php } ?>
									<!--<button class="stats" id="carica-statistiche" type="button">Statistiche</button><button class="storico" id="carica-storico" type="button">Storico ordini</button>-->
								</div>
							<?php } ?>

							<div id="note" class="well well-white pb-4 mb-0">
								<div id="note_contenitore" class="contenitore">
									<a class="block color-inherit" title="Aggiungi nota" href="#" onclick="jQuery('#note-cucina').toggle(); return false;">
										<span class="pull-right glyphicon glyphicon-plus mt-5 color-white" aria-hidden="true"></span>
										<h4 class="widget-title black"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> NOTE:</h4>
									</a>
									<textarea class="autosize form-control" name="note" id="note-cucina" placeholder="Scrivi delle note sulla comanda..."><?php if($testata) { echo htmlspecialchars($testata['note'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div id="impostazioni" class="hidden-xs">
			<label for="compact-view" class="mt-0">
				<a class="btn btn-lg btn-primary" href="#compact-view">
					<span class="glyphicon glyphicon-sunglasses" aria-hidden="true"></span>
				</a>
			</label>
			<?php //var_dump($_SESSION['quick-view']); ?>
			<input class="nascosto" type="checkbox" name="compact-view" id="compact-view"<?php if (isset($_SESSION['compact-view']) && $_SESSION['compact-view']) { ?> checked="checked"<?php } ?>>
		</div>

	</form>

<?php } ?>

		 </div>
	</body>
</html>
