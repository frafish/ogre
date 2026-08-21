<?php
include("CONFIG.php");
include ("lib/dbconnection.php");

$alert = null;
$tables = array('prodotti', 'ricette', 'ingredienti', 'categorie');


/*?><pre><?php var_dump($_REQUEST); ?></pre><?php*/

//; How many GET/POST/COOKIE input variables may be accepted
//max_input_vars = 1000 ==> 9999


if(isset($_REQUEST['azione'])) {
	switch($_REQUEST['azione']) {
		case 'aggiorna': 
			/*?><pre><?php var_dump($_REQUEST); ?></pre><?php*/
			foreach($tables as $table) {
				if(isset($_REQUEST[$table])) {
					$old = find_by($table);
					$new = $_REQUEST[$table];
					foreach($new as $id => $elemento) {
						if (is_int($id) && isset($old[$id])) {
							// aggiorno esistente
							$elemento['id'] = $id;
							db_save($table, $elemento, $old[$id]);
						} else {
							// inserisco nuovo
							$elemento['id'] = null;
							db_save($table, $elemento);
						}
					}
				}
			}
			set_alert("Aggiornamento avvenuto correttamente", 'success');
			break;
		case 'import': 
			$alert = importa_prodotti(); 
			break;
		case 'export':
			$prodotti = find_by('prodotti');
			$categorie = find_by('categorie');
			$reparti = find_by('reparti');
			$csv = array();
			foreach($prodotti as $pkey => $prodotto) {
				$ingredienti = get_ingredienti_by_prodotto($pkey, ', ');
				$csv[$pkey] = array(
					'NOME' => $prodotto['nome'],
					'CORTO' => $prodotto['corto'],
					'PREZZO' => number_format(floatval($prodotto['prezzo']), 2, ',', ''),
					'CATEGORIA' => $categorie[$prodotto['id_categorie']]['nome'],
					'REPARTO' => $reparti[$prodotto['id_reparti']]['nome'],
					'INGREDIENTI' => $ingredienti
				);			
			}
			get_csv(null, $csv, null, true, time().'_prodotti_sagra.csv');
			break;
		case 'aggiorna_qnt':
			aggiorna_quantita_prodotti();
			set_alert('Quantità dei prodotti aggiornate in base alle scorte di magazzino e vendite.', 'success');
			break;
		case 'erase':
			$cancella = true;
			if (file_exists(DB_FILE)) {
				$bk_dir = dirname(DB_BACKUP);
				if (!is_dir($bk_dir)) {
					mkdir($bk_dir);
				}
				$bk_name = 'ogre_'.slugify($giorni_settimana[date("w")]).'_'.date('Y-m-d_h-i-s').'_db.sqlite';
				$bk_rename = $bk_dir.DIRECTORY_SEPARATOR.$bk_name;
				dev_log('Sposto il file di backup prima di cancellare tutto, lo salvo in: '.$bk_rename);
				$cancella = copy(DB_FILE, $bk_rename);
				if($cancella) {
					set_alert('Per sicurezza ho generato il backup prima di cancellare tutto. Lo trovi qui <a href="/backup/'.$bk_name.'">'.$bk_name.'</a>', 'success');	
				}
			}
			if ($cancella) {
				$erase_tables = array('ricette', 'ingredienti', 'magazzino', 'prodotti', 'dettagli', 'testate', 'accessi', 'categorie', 'reparti');
				foreach($erase_tables as $table) {
					db_query("DROP TABLE ".$table);
				}
			}
			// ore ricreo le tabelle mancanti
			db_install();
			break;
	}
}

function importa_prodotti($divisore = ';') {

	if (isset($_FILES['file']) && $_FILES["file"]["tmp_name"]) {
		$allowedExts = array("csv", "CSV");
		$pezzi = explode(".", $_FILES["file"]["name"]);
		$extension = end($pezzi);
		if (in_array($extension, $allowedExts)) {
		  	if ($_FILES["file"]["error"] == 0) {
		  		
		  		if (!is_dir(__DIR__.DIRECTORY_SEPARATOR.CARTELLA_IMPORTATI)) {
					mkdir(__DIR__.DIRECTORY_SEPARATOR.CARTELLA_IMPORTATI);			
				}
				$imported_file = __DIR__.DIRECTORY_SEPARATOR.CARTELLA_IMPORTATI.DIRECTORY_SEPARATOR.$_FILES["file"]["name"];
				if (rename($_FILES['file']['tmp_name'], $imported_file)) {
				   $csv = file($imported_file);
				   //db_query("TRUNCATE TABLE `prodotti`");
					//db_query("TRUNCATE TABLE `ingredienti`");
					//db_query("TRUNCATE TABLE `ricette`");
					
					$cat_cache = array();
					$categories = find_by('categorie');
					if ($categories) { foreach ($categories as $c) $cat_cache[$c['nome']] = $c['id']; }
					
					$rep_cache = array();
					$reparti = find_by('reparti');
					if ($reparti) { foreach ($reparti as $r) $rep_cache[$r['nome']] = $r['id']; }
					
					$ing_cache = array();
					$ingredienti_db = find_by('ingredienti');
					if ($ingredienti_db) { foreach ($ingredienti_db as $i) $ing_cache[$i['nome']] = $i['id']; }
					
					foreach($csv as $rkey => $row) {
						if(!$rkey) {
							$fields = str_getcsv($row, $divisore, '"', "\\");
							continue;
						}
						$pezzi = str_getcsv($row, $divisore, '"', "\\");
						if (count($pezzi) != 6) {
							set_alert('ATTENZIONE la riga <strong>'.$row.'</strong> non rispetta la sintassi prevista dal csv. Esempio: <strong>"PATATE FRITTE *";"PATATINE";"2,50";"contorni";"cucina";"1 patatine fritte";</strong>. Verificala e rieffettua il caricamento.', 'danger');					
						} else {
							list($nome, $corto, $prezzo, $categoria, $reparto, $ingredienti) = $pezzi;
							
							$nome_cat = trim(str_replace('"', '', $categoria));
							if (!isset($cat_cache[$nome_cat])) {
								$id_categoria = db_query('INSERT INTO categorie (nome) VALUES ("'.$nome_cat.'")', false, false);
								$cat_cache[$nome_cat] = $id_categoria;
							} else { $id_categoria = $cat_cache[$nome_cat]; }
							
							$nome_rep = trim(str_replace('"', '', $reparto));
							if (!isset($rep_cache[$nome_rep])) {
								$id_reparto = db_query('INSERT INTO reparti (nome) VALUES ("'.$nome_rep.'")', false, false);
								$rep_cache[$nome_rep] = $id_reparto;
							} else { $id_reparto = $rep_cache[$nome_rep]; }

							$prezzo = str_replace(',', '.', $prezzo); //number_format(floatval($prezzo), 2, '.', '');
							$nome = normalize_text($nome);
							$corto = normalize_text($corto);
							$id_prodotto = find_one_by('prodotti', 'nome = "'.$nome.'" AND id_categorie = '.$id_categoria, null, 'id');
							if($id_prodotto) {
								$query = 'UPDATE prodotti SET corto = "'.$corto.'", prezzo = '.$prezzo.', id_categorie = '.$id_categoria.', id_reparti = '.$id_reparto.', ordine = '.$rkey.' WHERE id = '.$id_prodotto;
								db_query($query, false, false);	
							} else {				
								$query = 'INSERT INTO prodotti (nome, corto, prezzo, id_categorie, id_reparti, ordine) VALUES ("'.$nome.'", "'.$corto.'", '.$prezzo.', '.$id_categoria.', '.$id_reparto.', '.$rkey.')';
								$id_prodotto = db_query($query, false, false);
							}
							// ingredienti
							db_query('DELETE FROM ricette WHERE id_prodotti = '.$id_prodotto, false, false);
							$ingredienti = trim($ingredienti);
							if ($ingredienti) {
								$ingrdientis = explode(", ", $ingredienti);
								foreach($ingrdientis as $aing) {
									$ing = trim($aing);
									$sing = explode(" ", $ing, 2);
									// prodotti di un unico ingrediente
									if (count($sing) == 2) {
										$quantita = $sing[0];
										$nome_ing = $sing[1];
									} else {
										$quantita = 1;
										$nome_ing = $sing[0];
									}							
									
									$nome_ing = normalize_text($nome_ing);
									if (!isset($ing_cache[$nome_ing])) {
										$id_ingrediente = db_query('INSERT INTO ingredienti (nome) VALUES ("'.$nome_ing.'")', false, false);
										$ing_cache[$nome_ing] = $id_ingrediente;
									} else { $id_ingrediente = $ing_cache[$nome_ing]; }
									
									// devo creare la ricetta										
									db_query('INSERT INTO ricette (id_ingredienti, id_prodotti, quantita) VALUES ('.$id_ingrediente.', '.$id_prodotto.', '.$quantita.')', false, false);
								}
							}
						}
					}
					
					db_sync('categorie');
					db_sync('reparti');
					db_sync('prodotti');
					db_sync('ingredienti');
					db_sync('ricette');
					
					aggiorna_quantita_prodotti();
				}
			} else {
				set_alert("Return Code: " . $_FILES["file"]["error"], 'danger');
				return false;
		 	}
		 } else {
		 	set_alert("Tipo di file non valido, si richiede un CSV, scarica il file di esempio (puoi aprirlo con Excel)", 'danger');
			return false;
		 }
		 set_alert("COMPLIMENTI! Tutto sembra essersi caricato senza intoppi", 'success');
		 return true;
	}
	set_alert("Scegli un file prima di premere i pulsanti a caso", 'warning');
	return false;
}
?>
<html>
	<head>
		<title>Prodotti</title>
		
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
		<link href="css/bootstrap-switch.min.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="css/base.css" />
		<script src="js/jquery-1.11.1.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/bootstrap-switch.min.js"></script>
		<script src="js/RowSorter.js"></script>

		<script type="text/javascript">
			
			jQuery(document).ready(function () {
				
				if (jQuery('.nav-tabs').length > 0) { // if .nav-tabs exists
			        var hashtag = window.location.hash;
			        if (hashtag != '') {
			            jQuery('.nav-tabs > li').removeClass('active');
			            jQuery('.nav-tabs > li > a[href="'+hashtag+'"]').parent('li').addClass('active');
			            jQuery('.tab-content > div').removeClass('active');
			            jQuery(hashtag).addClass('active');
			        }
			   }							
				
				var inputs = jQuery('.table input:enabled, .table select');
				inputs.addClass('input-fake-disabled');
				inputs.prop( "disabled", true );
				
				inputs.each(function () {
					var init = jQuery(this).closest("td").html();
					jQuery(this).closest("td").html(
						'<div>'+init+'<a onclick="riattivaInput(this);" class="reactivate-input"></a></div>'
					);
				});
				/*				
				jQuery('table').on('click', "input:disabled, select", function () {
					jQuery(this).prop("disabled", false).focus();
				});
				*/
				/*
				inputs.closest("td").find('reactivate-input').click(function() {
					alert('click');
			    	jQuery(this).closest("td").find("input:disabled, select").prop("disabled", false).focus();
			    	return false;
			  	});
				*/				
				/*
				inputs.click(function () {
					alert('enabled');
					jQuery(this).prop( "disabled", false );
				});
				*/
			});
			
			function riattivaInput(elem) {
				//alert('click');
		    	jQuery(elem).closest("td").find("input:disabled, select").prop("disabled", false).focus();
		    	jQuery(elem).remove();
		    	return false;
			}
		</script>
		
		<style type="text/css">
			td div {
				position: relative;			
			}
			.reactivate-input {
				position: absolute;
				width: 100%;
				height: 100%;
				/*background-color: red;*/
				display: block;
				top: 0;
				left: 0;
				z-index: 100;
			}
			
			.sort-handler {
			    background-color: #f80;
			    cursor: move;
			}

			table td.sorter {
			    background-color: #EEEEEE;
			    background-image: url('/img/sort.png');
			    background-repeat: no-repeat;
			    background-position: center center;
			    background-size: 60% 40%;
			    cursor: move;
			    width: 10px;
			    padding: 0 10px;
			}
			table tr.sorting-row td {
				background-color: #8b8;
			}
			table.sorting-table tbody tr:not(.sorting-row) td {
				opacity: 0.4;
			}
			
			.field-id, .field-ordine {
				padding: 0;
				text-align: center;
			   width: 30px !important;
			}
		</style>
	</head>

<body>

<div class="container-fluid">

<a href="/" class="btn btn-default btn-lg btn-back pull-left"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
<h1 class="title">Gestione prodotti</h1>

<?php
if(is_user()) { // controllo se l'utente è autenticato 
?>
<?php print_alerts(); ?>
<div class="well well-white">
	<form action="" method="post" enctype="multipart/form-data" class="mb-0">
		<h2><?php if (db_count('prodotti')) { ?>Aggiornamento<?php } else { ?>Importazione<?php } ?> catalogo prodotti</h2>
		<div class="panel panel-default">
		  <div class="panel-heading">
		    <label for="file">Seleziona il csv da importare:</label>
		  </div>
		  <div class="panel-body">
		    <input name="file" id="file" type="file" class="form-control">
		    <?php if (db_count('prodotti')) { ?>
                <a class="btn btn-success mt-10 pull-right" title="Esporta l'elenco attuale dei prodotti già inseriti" target="_blank" href="?azione=export"><span class="glyphicon glyphicon-export" aria-hidden="true"></span> Esporta</a>
            <?php } ?>
		    <input type="hidden" value="import" name="azione">
			 <input class="btn btn-lg btn-warning mt-10" type="submit" name="submit" value="Carica" title="Aggiorna i prodotti già presenti e aggiunge quelli nuovi" />
		  </div>
		</div>
		<div class="alert alert-info mt-10 mb-0"><strong>HELP:</strong> non hai idea di come partire? <a class="btn btn-xs btn-primary" href="listini/dummy_prodotti.csv"><span class="glyphicon glyphicon-blackboard" aria-hidden="true"></span> scarica il csv di esempio</a></div>
	</form>
</div>


<?php if (db_count('prodotti')) { ?>
<hr>

<ul class="nav nav-tabs" role="tablist">
	<?php 
	// Mappatura delle descrizioni dei campi
	$desc_campi = array(
		'id' => 'Identificativo univoco interno (ID)',
		'nome' => 'Nome dell\'elemento',
		'corto' => 'Nome abbreviato per i bottoni in cassa rapida',
		'prezzo' => 'Prezzo di vendita al pubblico',
		'quantita' => 'Scorta disponibile in magazzino (o dose nella ricetta)',
		'id_categorie' => 'La categoria (tab) in cui verrà raggruppato',
		'id_reparti' => 'Il reparto dove verrà inviata la stampa',
		'ordine' => 'Ordine di visualizzazione (numeri più bassi vengono prima)',
		'status' => 'Abilita o disabilita (nasconde) l\'elemento',
		'id_prodotti' => 'Il prodotto in cui è contenuto l\'ingrediente',
		'id_ingredienti' => 'L\'ingrediente associato al prodotto',
		'griglie' => 'Se questo ingrediente richiede tempi/preparazione in griglia',
		'universale' => 'Rende la categoria visibile in tutte le configurazioni'
	);
	foreach ($tables as $tkey => $table) { ?>
		<li role="presentation" class="text-upper<?php if (!$tkey) { ?> active<?php } ?>"><a href="#tab-<?php echo $table; ?>" aria-controls="tab-<?php echo $table; ?>" role="tab" data-toggle="tab"><?php echo $table; ?></a></li>
	<?php } ?>
</ul>

<form action="" method="post">

  <!-- Tab panes -->
  <div class="tab-content">

	 <?php $table = 'prodotti'; ?>
    <div role="tabpanel" class="tab-pane well well-white active" id="tab-<?php echo $table; ?>">
<form action="" method="post">
		<?php
    	$rows = find_by($table, null, 'ordine ASC');
    	$fields = db_get_fields($table);
    	if (isset($fields['time'])) { unset($fields['time']); }
    	if (isset($fields['colore'])) { unset($fields['colore']); }
    	?>
    	<table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
	    	<thead>
		    	<tr>
		 			<?php foreach ($fields as $field => $afield) { ?>
		 				<th<?php if ($field == 'ordine') { ?> colspan="2"<?php } ?>>
							<?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
							<?php echo $field; ?> 
							<?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
		 				</th>
		 			<?php } ?>
		 		</tr>
		 	</thead>
		 	<tbody>
	    	<?php foreach ($rows as $rid => $row) { ?>
	    		<tr id="<?php echo $table.'-'.$rid; ?>">
	    			<?php foreach ($fields as $field => $afield) { ?>
	    				<td>
	    					<?php 
							if (is_foreign_key($field)) { 
								$ftable = db_get_table_by_fk($field);							
								$values = find_by($ftable);
	    					?>
	    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
									<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label['nome']; ?></option><?php } ?>	    						
	    						</select>  
							<?php } else {	    					
		    					switch($field) {
		    						case 'griglie':
		    						case 'status':
			    						$values = array('0' => 'No', '1' => 'Si');
			    						?>
			    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
											<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
			    						</select>  
			    						<?php break;
			    					case 'descrizione': ?>
			    						<?php echo $row[$field]; ?> 
			    						<?php break;
			    					case 'ordine': ?>
										<div class="input-group">										
				    						<input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
				    						<!--<strong class="input-group-addon sort-handler-asd btn btn-default"><span class="glyphicon glyphicon-move" aria-hidden="true"></span></strong>-->
			    						</div>
			    						</td><td class="sorter">
			    						<?php break;
			    					default: ?>
										<input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
								<?php }
							} ?>    				
	    				</td>
	    			<?php } ?>
	    		</tr>
	    	<?php } ?>
    		</tbody>
    	</table>
<input type="hidden" value="aggiorna" name="azione">
<a class="btn btn-lg btn-info pull-right mt-20 mb-20" style="margin-left: 10px;" title="Forza il ricalcolo delle quantità dei prodotti in base alle scorte e alle vendite" href="?azione=aggiorna_qnt"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> AGGIORNA QUANTITA'</a>
<input type="submit" class="btn btn-lg btn-warning pull-right mt-20 mb-20" value="SALVA">
<div class="clearfix"></div>
</form>
    </div>


    <?php $table = 'ricette'; ?>
    <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
<form action="" method="post">
    	<?php
    	$rows = find_by($table);
    	//$fields = array_keys(reset($rows));
    	$fields = db_get_fields($table);
		if (isset($fields['id'])) { unset($fields['id']); }
		if (isset($fields['time'])) { unset($fields['time']); }
    	?>
    	<table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
	    	<thead>
		    	<tr>
		 			<?php foreach ($fields as $field => $afield) { ?>
		 				<th>
							<?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
							<?php echo $field; ?>    				
							<?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
		 				</th>
		 			<?php } ?>
		 		</tr>
		 	</thead>
		 	<tbody>
	    	<?php foreach ($rows as $rid => $row) { ?>
	    		<tr id="<?php echo $table.'-'.$rid; ?>">
	    			<?php foreach ($fields as $field => $afield) { ?>
	    				<td>
		    				<?php 
							if (is_foreign_key($field)) { 
								$ftable = db_get_table_by_fk($field);							
								$values = find_by($ftable);
	    					?>
	    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
									<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label['nome']; ?></option><?php } ?>	    						
	    						</select>  
							<?php } else {	 
		    					switch($field) {
			    					case 'status':
			    					case 'admin':
		    							$values = array('0' => 'No', '1' => 'Si');
		    					?>
		    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
										<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
		    						</select>  
		    					<?php break;
		    						default: ?>
										<input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
								<?php }
							} ?>    				
	    				</td>
	    			<?php } ?>
	    		</tr>
	    	<?php } ?>
    		</tbody>
    	</table>
<input type="hidden" value="aggiorna" name="azione">
<input type="submit" class="btn btn-lg btn-warning pull-right mt-20 mb-20" value="SALVA">
<div class="clearfix"></div>
</form>
    </div>
    
	<?php $table = 'ingredienti'; ?>
    <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
<form action="" method="post">
    	<?php
    	$rows = find_by($table);
    	//$fields = array_keys(reset($rows));
    	$fields = db_get_fields($table);
		if (isset($fields['id'])) { unset($fields['id']); }
		if (isset($fields['time'])) { unset($fields['time']); }
    	?>
    	<table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
	    	<thead>
		    	<tr>
		 			<?php foreach ($fields as $field => $afield) { ?>
		 				<th>
							<?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
							<?php echo $field; ?>    				
							<?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
		 				</th>
		 			<?php } ?>
		 		</tr>
		 	</thead>
		 	<tbody>
	    	<?php foreach ($rows as $rid => $row) { ?>
	    		<tr id="<?php echo $table.'-'.$rid; ?>">
	    			<?php foreach ($fields as $field => $afield) { ?>
	    				<td>
		    				<?php 
							if (is_foreign_key($field)) { 
								$ftable = get_table_by_fk($field);							
								$values = find_by($ftable);
	    					?>
	    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
									<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label['nome']; ?></option><?php } ?>	    						
	    						</select>  
							<?php } else {	 
		    					switch($field) {
			    					case 'status':
			    					case 'griglie':
		    							$values = array('0' => 'No', '1' => 'Si');
		    					?>
		    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
										<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
		    						</select>  
		    					<?php break;
			    					case 'quantita':
			    							$values = array('0' => 'No', '1' => 'Si');
			    					?>
			    						<input class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]" disabled="disabled" value="<?php echo get_scorte(null, $row['id'], 'quantita'); ?>">
			    					<?php break;
		    						default: ?>
										<input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
								<?php }
							} ?>    				
	    				</td>
	    			<?php } ?>
	    		</tr>
	    	<?php } ?>
    		</tbody>
    	</table>
<input type="hidden" value="aggiorna" name="azione">
<input type="submit" class="btn btn-lg btn-warning pull-right mt-20 mb-20" value="SALVA">
<div class="clearfix"></div>
</form>
    </div>    
    
	<?php $table = 'categorie'; ?>
    <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
<form action="" method="post">
    	<?php
    	$rows = find_by($table, null, 'ordine ASC');
    	//$fields = array_keys(reset($rows));
    	$fields = db_get_fields($table);
		//var_dump($fields);
		//if(($key = array_search('time', $fields)) !== false) { unset($fields[$key]); } // rimuovo la colonna time
		if (isset($fields['time'])) { unset($fields['time']); }
    	?>
    	<table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
	    	<thead>
		    	<tr>
		 			<?php foreach ($fields as $field => $afield) { ?>
		 				<th>
							<?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
							<?php echo $field; ?>    				
							<?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
		 				</th>
		 			<?php } ?>
		 		</tr>
		 	</thead>
		 	<tbody>
	    	<?php foreach ($rows as $rid => $row) { ?>
	    		<tr id="<?php echo $table.'-'.$rid; ?>">
	    			<?php foreach ($fields as $field => $afield) { ?>
	    				<td>
	    					<?php switch($field) {
	    					case 'status':
	    					case 'universale':
	    						$values = array('0' => 'No', '1' => 'Si');
	    					?>
	    						<select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
									<?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
	    						</select>  
	    					<?php break;
	    					default: 
	    						?>
								<input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
							<?php } ?>    				
	    				</td>
	    			<?php } ?>
	    		</tr>
	    	<?php } ?>
    		</tbody>
    	</table>
<input type="hidden" value="aggiorna" name="azione">
<input type="submit" class="btn btn-lg btn-warning pull-right mt-20 mb-20" value="SALVA">
<div class="clearfix"></div>
</form>
    </div>

</div>


<form action="" method="post">
	<input type="hidden" value="erase" name="azione">
	<input type="submit" class="btn btn-lg btn-danger mt-20" value="ELIMINA TUTTO">
</form>

<?php } ?>

<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']));
}
?>	

</div>


	
	
	
<script type="text/javascript">
	/*new RowSorter('#prodotti', {
 			//handler: 'span.sort-handler'
 			handler: 'td.sorter'
 		});*/
	jQuery(document).ready(function(){
		// Set table as sortable
		jQuery('#prodotti').rowSorter({
 			//handler: 'span.sort-handler'
 			handler: 'td.sorter',
 			onDrop: function(tbody, row, current_index) {
		        //alert('Dragging the ' + current_index + '. row canceled.');
		        updateOrdine();
		   }
 		});
  	});
  	
  	function updateOrdine() {
  		jQuery('#prodotti .field-ordine').each(function (pos) {
  			jQuery(this).val(pos + 1);
  		});
  	}
</script>	
	
	
</body>
</html> 
