<?php

function get_html_dettagli_piatti($id_testata, $id_reparto) {
	$out = '';
	$testata = find('testate', $id_testata);
	$reparto = find('reparti', $id_reparto);

	if ($testata['note'] != "") {
		$nota_html = nl2br($testata['note']);
		$alert_class = 'note';
		// Se è un ordine modificato, evidenzialo in rosso
		if (strpos($testata['note'], 'ATTENZIONE: ORDINE MODIFICATO') !== false) {
			$alert_class = 'alert alert-danger';
			$nota_html = '<strong><span class="glyphicon glyphicon-warning-sign"></span> ' . $nota_html . '</strong>';
		}
		$out .= '<div id="nota-'.$id_testata.'" class="'.$alert_class.'">
					<div class="col-xs-12">
					<p>'.$nota_html.'</p>
					</div>
				</div>';
	}

	$i = 1;
	$dettagli = find_by('dettagli', 'id_testate = '.$id_testata);
	foreach ($dettagli as $did => $dettaglio) {
		$prodotti = get_prodotti_plus($id_testata, 'dettagli.id ='.$dettaglio['id']);
		foreach ($prodotti as $pid => $prodotto) {
			if($prodotto['id_reparti'] == $id_reparto) {
					$nome = ($prodotto['corto']) ? htmlentities($prodotto['corto']) : htmlentities($prodotto['nome']);
					$qnt = $dettaglio['quantita'] + $dettaglio['omaggio'];					
					$out .= '<div id="piatto-'.$id_testata.'-'.$pid.'" class="piatti">			
								<button class="impiattato">Pronto</button>
								<h3><span id="pquantita'.$dettaglio['id'].'-'.$prodotto['id'].'" class="pqnt">'.$qnt.'</span> - <span class="pnome-'.$prodotto['id'].'">'.$nome.'</span></h3>
								<ul>';
					
					// raccolgo info sulle ricette
					$query_ingredienti = "SELECT ingredienti.nome AS nome, ricette.quantita AS quantita FROM ingredienti, ricette WHERE ricette.id_ingredienti = ingredienti.id AND ricette.id_prodotti = ".$pid;
					$ingredienti = db_query($query_ingredienti);
					
					if (empty($ingredienti)) {
						$out .= "<li>tanto olio di gomito</li>";
					} else {
						foreach ($ingredienti as $ingrediente) {
							$totale = $ingrediente['quantita'] * $qnt;
							$out .= "<li><span>".$totale."</span> - ".htmlentities($ingrediente['nome'])."</li>";
						}
					}
					
					$out .= '</ul>';
					if ($dettaglio['nota']) {
						$out .= '<div class="well well-sm well-warning bg-warning btn-danger mb-0">'.$dettaglio['nota'].'</div>';
					}
					$out .= '</div>';
				}
				
				$i++;
			}
		}
		
		$stampante = get_printer_by_reparto($id_reparto);
		if ($stampante) {
			$out .= '<div style="clear: both;">
						<a class="btn btn-warning reprint" target="_blank" href="/ristampa.php?stampante='.$stampante.'&ricevuta='.slugify($reparto['nome']).'&id_testate='.$id_testata.'&file='.get_filename_by_id($id_testata).'"><span class="glyphicon glyphicon-print" aria-hidden="true"></span></a>
					</div>';
		}
		
		return $out;
}
?>
